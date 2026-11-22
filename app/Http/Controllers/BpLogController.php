<?php

namespace App\Http\Controllers;

use App\Models\BpLog;
use App\Models\Patient;
use App\Models\RxOption;
use Illuminate\Http\Request;

class BpLogController extends Controller
{
    public function index(Patient $patient)
    {
        $logs     = $this->logsFor($patient);
        $analysis = BpLog::analyze($logs);
        $points   = $this->chartPoints($logs);

        $medExtras     = RxOption::where('field', 'bp_med')->orderBy('sort')->orderBy('id')->pluck('value')->all();
        $medPinned     = RxOption::pinned('bp_med');
        $symptomExtras = RxOption::where('field', 'bp_symptom')->orderBy('sort')->orderBy('id')->pluck('value')->all();

        $entries = $logs->mapWithKeys(fn ($l) => [$l->id => [
            'logged_at'   => $l->logged_at->format('Y-m-d\TH:i'),
            'systolic'    => $l->systolic,
            'diastolic'   => $l->diastolic,
            'pulse'       => $l->pulse,
            'medications' => $l->medications,
            'symptoms'    => $l->symptoms,
            'notes'       => $l->notes,
        ]]);

        // Diary grid: each reading plotted into its time-of-day column.
        $diaryColumns = BpLog::DIARY_COLUMNS;
        $ncols = count($diaryColumns);
        $readingsByDayCol = [];  // 'Y-m-d' => [ colIdx => Collection<BpLog> ]
        $byDay = $logs->groupBy(fn ($l) => $l->logged_at->toDateString());

        foreach ($byDay as $date => $dayLogs) {
            $rcols = [];
            for ($i = 0; $i < $ncols; $i++) $rcols[$i] = collect();
            foreach ($dayLogs as $l) {
                if ($l->systolic !== null && $l->diastolic !== null) {
                    $rcols[BpLog::readingColumn($l->logged_at)]->push($l);
                }
            }
            foreach ($rcols as $k => $rc) {
                $rcols[$k] = $rc->sortBy('logged_at')->values();
            }
            $readingsByDayCol[$date] = $rcols;
        }

        // Day rows (most recent first). Notes column collects medicine-only,
        // note and symptom entries for the day.
        $days = $byDay->map(fn ($dayLogs, $date) => [
            'date'  => $date,
            'meds'  => $dayLogs->filter(fn ($l) => $l->medications && $l->systolic === null)
                               ->sortBy('logged_at')->values(),
            'notes' => $dayLogs->filter(fn ($l) => $l->notes || $l->symptoms)
                               ->sortBy('logged_at')->values(),
        ])->values();

        return view('bp.index', compact('patient', 'logs', 'analysis', 'points', 'medExtras', 'medPinned', 'symptomExtras', 'entries', 'diaryColumns', 'readingsByDayCol', 'days'));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate($this->rules());
        if ($err = $this->normalize($data)) {
            return back()->withInput()->with('error', $err);
        }

        BpLog::create($data + ['patient_id' => $patient->id, 'recorded_by' => auth()->id()]);

        return back()->with('success', 'BP entry saved.');
    }

    public function update(Request $request, Patient $patient, BpLog $bpLog)
    {
        abort_if($bpLog->patient_id !== $patient->id, 404);

        $data = $request->validate($this->rules());
        if ($err = $this->normalize($data)) {
            return back()->withInput()->with('error', $err);
        }

        $bpLog->update($data);

        return back()->with('success', 'Entry updated.');
    }

    public function destroy(Patient $patient, BpLog $bpLog)
    {
        abort_if($bpLog->patient_id !== $patient->id, 404);
        $bpLog->delete();

        return back()->with('success', 'Entry removed.');
    }

    public function print(Patient $patient)
    {
        $logs     = $this->logsFor($patient);
        $analysis = BpLog::analyze($logs);

        return view('bp.print', compact('patient', 'logs', 'analysis'));
    }

    private function rules(): array
    {
        return [
            'logged_at'   => 'required|date',
            'systolic'    => 'nullable|integer|min:50|max:300',
            'diastolic'   => 'nullable|integer|min:30|max:200',
            'pulse'       => 'nullable|integer|min:20|max:300',
            'medications' => 'nullable|string|max:500',
            'symptoms'    => 'nullable|string|max:500',
            'notes'       => 'nullable|string|max:1000',
        ];
    }

    /** Cross-field checks shared by store/update. Returns an error string or null. */
    private function normalize(array &$data): ?string
    {
        $hasSys = ($data['systolic'] ?? null) !== null && $data['systolic'] !== '';
        $hasDia = ($data['diastolic'] ?? null) !== null && $data['diastolic'] !== '';

        if ($hasSys xor $hasDia) {
            return 'Enter both systolic and diastolic for a BP reading.';
        }
        if ($hasSys && $hasDia && (int) $data['systolic'] <= (int) $data['diastolic']) {
            return 'Systolic should be higher than diastolic — please check the values.';
        }
        if (!$hasSys && empty($data['medications']) && ($data['pulse'] ?? null) === null && empty($data['notes'])) {
            return 'Nothing to save — add a BP reading, a medicine, a pulse, or a note.';
        }
        return null;
    }

    private function logsFor(Patient $patient)
    {
        return BpLog::with('recordedBy')
            ->where('patient_id', $patient->id)
            ->orderByDesc('logged_at')
            ->get();
    }

    /** Chronological points for the trend chart. */
    private function chartPoints($logs)
    {
        return $logs->filter(fn ($l) => $l->systolic && $l->diastolic)
            ->sortBy('logged_at')->values()
            ->map(fn ($l) => [
                'label' => $l->logged_at->format('d M, H:i'),
                'sys'   => $l->systolic,
                'dia'   => $l->diastolic,
                'color' => $l->classification()['color'],
            ]);
    }
}
