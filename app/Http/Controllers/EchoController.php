<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\EchoRecord;
use App\Models\EchoTemplate;
use App\Models\EncounterReport;
use App\Models\Patient;
use App\Models\Doctor;
use App\Services\PatientNotifier;

class EchoController extends Controller
{
    /**
     * Send the Echo report to patient + admin per notification_preferences.
     * Trigger: user clicks "Send Report" on the Echo show page.
     */
    public function sendReport(EchoRecord $echo, PatientNotifier $notifier)
    {
        $result = $notifier->sendEchoReport($echo);
        if ($result['ok'] ?? false) {
            $msg = 'Echo report queued for delivery: ' . implode(', ', $result['channels'] ?? []) . '.';
            ActivityLog::log('updated', "Sent echo report {$echo->record_no} via " . implode(',', $result['channels'] ?? []));
            return redirect()->route('echo.show', $echo)->with('success', $msg);
        }
        $reason = $result['reason'] ?? 'unknown';
        $hint = $reason === 'all_channels_off_in_prefs'
            ? 'All channels are OFF for echo_report_ready. Enable at least one channel at /admin/notifications/preferences.'
            : "Send failed: {$reason}";
        return redirect()->route('echo.show', $echo)->with('error', $hint);
    }

    private function saveFile($file, $patient, $recordNo): string
    {
        $dir = public_path('storage/echo_files');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (!is_writable($dir)) chmod($dir, 0777);

        $ext  = strtolower($file->getClientOriginalExtension());
        $name = preg_replace('/[^A-Za-z0-9\-]/', '-', $patient->full_name);
        $date = now()->format('Y-m-d_Hi');
        $no   = str_replace(['/','-',' '], '-', $recordNo);
        $filename = "{$name}-{$date}-{$no}.{$ext}";

        $file->move($dir, $filename);
        chmod($dir . '/' . $filename, 0644);
        return $filename;
    }

    private function deleteFile(?string $filename): void
    {
        if ($filename) {
            $paths = [
                public_path("storage/echo_files/{$filename}"),
                public_path("storage/{$filename}"),
            ];
            foreach ($paths as $p) {
                if (file_exists($p)) { unlink($p); break; }
            }
        }
    }

    public function index(Request $request) {
        $q     = trim($request->q ?? '');
        $query = EchoRecord::with('patient','doctor')->latest();
        if ($request->patient_id) $query->where('patient_id', $request->patient_id);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('record_no', 'like', "%{$q}%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name','like',"%{$q}%")->orWhere('last_name','like',"%{$q}%"))
                    ->orWhereHas('doctor',  fn($d) => $d->where('first_name','like',"%{$q}%")->orWhere('last_name','like',"%{$q}%"))
                    ->orWhere('ef_percent','like',"%{$q}%")
                    ->orWhere('lv_edd','like',"%{$q}%");
            });
        }
        $records = $query->paginate(20)->appends($request->only('patient_id','q'));
        $filterPatient = $request->patient_id ? Patient::find($request->patient_id) : null;
        $traceUploads = $request->patient_id
            ? EncounterReport::with('encounter')->where('patient_id', $request->patient_id)->where('report_type', 'Echo File')->latest()->get()
            : collect();
        return view('echo.index', compact('records','filterPatient','traceUploads','q'));
    }

    public function create(Request $request) {
        $patients        = Patient::where('is_active',true)->orderBy('first_name')->get();
        $doctors         = Doctor::where('is_active',true)->orderBy('first_name')->get();
        $patient         = $request->patient_id ? Patient::find($request->patient_id) : null;
        $defaultDoctorId = Doctor::where('first_name','Robert')->where('last_name','James')->value('id') ?? 1;
        $templates       = EchoTemplate::orderBy('is_builtin','desc')->orderBy('category')->orderBy('name')->get();
        return view('echo.create', compact('patients','doctors','patient','defaultDoctorId','templates'));
    }

    /** Return all echo templates as JSON for live "Apply template" UI. */
    public function templatesJson()
    {
        return response()->json(
            EchoTemplate::orderBy('is_builtin','desc')->orderBy('category')->orderBy('name')->get()
        );
    }

    /** Save the posted echo field values as a new template. */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:120',
            'category'    => 'required|string|in:'.implode(',', array_keys(EchoTemplate::CATEGORIES)),
            'description' => 'nullable|string|max:255',
            'fields'      => 'required|array',
        ]);

        $fields = array_intersect_key($request->input('fields', []), array_flip(EchoTemplate::FIELD_KEYS));
        $fields = array_map(fn($v) => is_scalar($v) ? (string) $v : $v, $fields);

        $template = EchoTemplate::create([
            'name'        => trim($request->input('name')),
            'category'    => $request->input('category'),
            'description' => $request->input('description') ?: null,
            'fields'      => $fields,
            'is_builtin'  => false,
            'created_by'  => auth()->id(),
        ]);

        ActivityLog::log('created', "Saved Echo template '{$template->name}'");
        return response()->json($template, 201);
    }

    /** Delete a user-created template (built-ins are protected). */
    public function destroyTemplate(EchoTemplate $template)
    {
        if ($template->is_builtin) {
            return response()->json(['error' => 'Built-in templates cannot be deleted.'], 403);
        }
        $name = $template->name;
        $template->delete();
        ActivityLog::log('deleted', "Deleted Echo template '{$name}'");
        return response()->json(['ok' => true]);
    }

    public function store(Request $request) {
        $request->validate([
            'patient_id'  => 'required|exists:patients,id',
            'doctor_id'   => 'required|exists:doctors,id',
            'record_date' => 'required|date',
            'ef_percent'  => 'required',
            'echo_file'   => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,bmp,tiff,mp4,mov|max:51200',
        ]);
        $recordNo = EchoRecord::generateNo();
        $data = array_merge(
            $request->except(['_token','echo_file','_method']),
            ['record_no' => $recordNo, 'performed_by' => auth()->id()]
        );
        if ($request->hasFile('echo_file')) {
            $patient = Patient::findOrFail($request->patient_id);
            $data['echo_file'] = $this->saveFile($request->file('echo_file'), $patient, $recordNo);
        }
        $echo = EchoRecord::create($data);
        $echo->load('patient');
        ActivityLog::log('created', "Created Echo record {$echo->record_no} for patient {$echo->patient->full_name}");
        return redirect()->route('echo.show', $echo)->with('success','Echo record saved.');
    }

    public function destroyFile(EchoRecord $echo) {
        $this->deleteFile($echo->echo_file);
        $echo->update(['echo_file' => null]);
        $echo->load('patient');
        ActivityLog::log('updated', "Deleted Echo file for record {$echo->record_no} ({$echo->patient->full_name})");
        return redirect()->route('echo.show', $echo)->with('success', 'Echo file deleted.');
    }

    public function show(EchoRecord $echo) {
        $echo->load('patient','doctor');
        return view('echo.show', compact('echo'));
    }

    /** Server-side PDF print (DomPDF) — pinned header/footer on every page.
     *  Filename: "<PatientName>-Echo-<RecordNo>-<DD-MM-YYYY>.pdf" */
    public function print(EchoRecord $echo) {
        $echo->load('patient', 'doctor');
        $patient = $echo->patient;
        $name = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) ?: 'patient';
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', $name);
        $name = trim($name, '-') ?: 'patient';
        $no   = str_replace(['/', '\\'], '-', $echo->record_no);
        $date = optional($echo->record_date)->format('d-m-Y') ?: date('d-m-Y');
        $filename = "{$name}-Echo-{$no}-{$date}.pdf";
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('echo.print-pdf', ['echo' => $echo])
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    public function edit(EchoRecord $echo) {
        $echo->load('patient','doctor');
        $doctors   = Doctor::where('is_active',true)->orderBy('first_name')->get();
        $templates = EchoTemplate::orderBy('is_builtin','desc')->orderBy('category')->orderBy('name')->get();
        return view('echo.edit', compact('echo','doctors','templates'));
    }

    public function update(Request $request, EchoRecord $echo) {
        $request->validate([
            'doctor_id'   => 'required|exists:doctors,id',
            'record_date' => 'required|date',
            'ef_percent'  => 'required',
            'echo_file'   => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,bmp,tiff,mp4,mov|max:51200',
        ]);
        $data = $request->except(['_token','_method','echo_file']);
        if ($request->hasFile('echo_file')) {
            $this->deleteFile($echo->echo_file);
            $echo->load('patient');
            $data['echo_file'] = $this->saveFile($request->file('echo_file'), $echo->patient, $echo->record_no);
        }
        $echo->update($data);
        $echo->load('patient');
        ActivityLog::log('updated', "Updated Echo record {$echo->record_no} for patient {$echo->patient->full_name}");
        return redirect()->route('echo.show', $echo)->with('success','Echo record updated.');
    }

    public function destroy(EchoRecord $echo) {
        $echo->load('patient');
        $this->deleteFile($echo->echo_file);
        $patientId = $echo->patient_id;
        $name = $echo->patient->full_name;
        $no   = $echo->record_no;
        $echo->delete();
        ActivityLog::log('deleted', "Deleted Echo record {$no} for patient {$name}");
        return redirect()->route('echo.index', ['patient_id' => $patientId])->with('success', "Echo record {$no} deleted.");
    }
}
