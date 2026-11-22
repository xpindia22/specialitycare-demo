<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\EcgRecord;
use App\Models\EncounterReport;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\ReferralSource;
use App\Models\ReferralPayment;

class EcgController extends Controller
{
    private function saveFile($file, $patient, $recordNo): string
    {
        $dir = public_path('storage/ecg_files');
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
        if ($filename && file_exists(public_path("storage/ecg_files/{$filename}"))) {
            unlink(public_path("storage/ecg_files/{$filename}"));
        }
    }

    public function index(Request $request) {
        $q     = trim($request->q ?? '');
        $query = EcgRecord::with('patient','doctor')->latest();
        if ($request->patient_id) $query->where('patient_id', $request->patient_id);
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('record_no', 'like', "%{$q}%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name','like',"%{$q}%")->orWhere('last_name','like',"%{$q}%"))
                    ->orWhereHas('doctor',  fn($d) => $d->where('first_name','like',"%{$q}%")->orWhere('last_name','like',"%{$q}%"))
                    ->orWhere('heart_rate','like',"%{$q}%")
                    ->orWhere('rhythm','like',"%{$q}%");
            });
        }
        $records = $query->paginate(20)->appends($request->only('patient_id','q'));
        $filterPatient = $request->patient_id ? Patient::find($request->patient_id) : null;
        $traceUploads = $request->patient_id
            ? EncounterReport::with('encounter')->whereHas('encounter')->where('patient_id', $request->patient_id)->where('report_type', 'ECG Trace')->latest()->get()
            : collect();
        return view('ecg.index', compact('records','filterPatient','traceUploads','q'));
    }

    public function create(Request $request) {
        $patients        = Patient::where('is_active',true)->orderBy('first_name')->get();
        $doctors         = Doctor::where('is_active',true)->orderBy('first_name')->get();
        $patient         = $request->patient_id ? Patient::find($request->patient_id) : null;
        $referralSources = ReferralSource::where('is_active',true)->orderBy('name')->get();
        $price = 250;
        return view('ecg.create', compact('patients','doctors','patient','price','referralSources'));
    }

    public function store(Request $request) {
        $request->validate([
            'patient_id'  => 'required|exists:patients,id',
            'doctor_id'   => 'required|exists:doctors,id',
            'record_date' => 'required|date',
            'ecg_file'    => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,bmp,tiff|max:20480',
        ]);
        $recordNo = EcgRecord::generateNo();
        $data = array_merge(
            $request->except(['_token','ecg_file']),
            ['record_no' => $recordNo, 'performed_by' => auth()->id()]
        );
        if ($request->hasFile('ecg_file')) {
            $patient = Patient::findOrFail($request->patient_id);
            $data['ecg_file'] = $this->saveFile($request->file('ecg_file'), $patient, $recordNo);
        }
        $ecg = EcgRecord::create($data);
        $ecg->load('patient');
        $this->maybeCreateReferralIncentive($ecg);
        ActivityLog::log('created', "Created ECG record {$ecg->record_no} for patient {$ecg->patient->full_name}");
        return redirect()->route('ecg.show', $ecg)->with('success','ECG record saved.');
    }

    /**
     * Flat referral incentive for every ECG referred in (rate configurable in
     * Settings, default ₹100) — every time, unlike the one-time consultation bonus.
     */
    protected function maybeCreateReferralIncentive(EcgRecord $ecg): void
    {
        if (!$ecg->referral_source_id) return;
        $rate = (float) setting('referral_rate_ecg', 100);
        if ($rate <= 0) return;
        ReferralPayment::create([
            'referral_source_id' => $ecg->referral_source_id,
            'patient_id'         => $ecg->patient_id,
            'calculated_amount'  => $rate,
            'paid_amount'        => 0,
            'status'             => 'pending',
            'notes'              => "Auto ECG referral incentive for record {$ecg->record_no}",
            'created_by'         => auth()->id(),
        ]);
    }

    /**
     * Auto-read an ECG file (newly chosen in the form, or the record's saved
     * file) and return the extracted parameters as JSON for the edit/create
     * form to pre-fill. Values are a DRAFT — the doctor reviews and saves.
     */
    public function read(Request $request) {
        $request->validate([
            'ecg_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,bmp,tiff|max:20480',
            'ecg_id'   => 'nullable|exists:ecg_records,id',
        ]);

        $path = null; $ext = '';
        if ($request->hasFile('ecg_file')) {
            $file = $request->file('ecg_file');
            $path = $file->getRealPath();
            $ext  = strtolower($file->getClientOriginalExtension());
        } elseif ($request->ecg_id) {
            $ecg = EcgRecord::find($request->ecg_id);
            if ($ecg && $ecg->ecg_file) {
                $abs = public_path('storage/ecg_files/' . $ecg->ecg_file);
                if (is_file($abs)) { $path = $abs; $ext = strtolower(pathinfo($ecg->ecg_file, PATHINFO_EXTENSION)); }
            }
        }

        if (!$path) {
            return response()->json(['ok' => false, 'error' => 'No ECG file to read — choose a file first.'], 422);
        }

        $result = \App\Services\EcgReader::read($path, $ext);
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function destroyFile(EcgRecord $ecg) {
        $this->deleteFile($ecg->ecg_file);
        $ecg->update(['ecg_file' => null]);
        $ecg->load('patient');
        ActivityLog::log('updated', "Deleted ECG file for record {$ecg->record_no} ({$ecg->patient->full_name})");
        return redirect()->route('ecg.show', $ecg)->with('success', 'ECG file deleted.');
    }

    public function show(EcgRecord $ecg) {
        $ecg->load('patient','doctor','performedBy');
        return view('ecg.show', compact('ecg'));
    }

    public function edit(EcgRecord $ecg) {
        $doctors         = Doctor::where('is_active',true)->orderBy('first_name')->get();
        $referralSources = ReferralSource::where('is_active',true)->orderBy('name')->get();
        $price = $ecg->price;
        return view('ecg.edit', compact('ecg','doctors','price','referralSources'));
    }

    public function update(Request $request, EcgRecord $ecg) {
        $request->validate([
            'doctor_id'   => 'required',
            'record_date' => 'required|date',
            'ecg_file'    => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf,bmp,tiff|max:20480',
        ]);
        $data = $request->except(['_token','_method','ecg_file']);
        if ($request->hasFile('ecg_file')) {
            $this->deleteFile($ecg->ecg_file);
            $ecg->load('patient');
            $data['ecg_file'] = $this->saveFile($request->file('ecg_file'), $ecg->patient, $ecg->record_no);
        }
        $ecg->update($data);
        $ecg->load('patient');
        ActivityLog::log('updated', "Updated ECG record {$ecg->record_no} for patient {$ecg->patient->full_name}");
        return redirect()->route('ecg.show', $ecg)->with('success','ECG record updated.');
    }

    public function destroy(EcgRecord $ecg) {
        $ecg->load('patient');
        $this->deleteFile($ecg->ecg_file);
        $patientId = $ecg->patient_id;
        $name = $ecg->patient->full_name;
        $no   = $ecg->record_no;
        $ecg->delete();
        ActivityLog::log('deleted', "Deleted ECG record {$no} for patient {$name}");
        return redirect()->route('ecg.index', ['patient_id' => $patientId])->with('success', "ECG record {$no} deleted.");
    }
}
