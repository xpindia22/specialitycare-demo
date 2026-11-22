<?php

namespace App\Http\Controllers;

use App\Models\BpLog;
use App\Models\DietLog;
use App\Models\GlucoseLog;
use App\Models\Patient;
use Illuminate\Http\Request;

class GlucoseLogController extends Controller
{
    public function index(Patient $patient)
    {
        $logs     = $this->logsFor($patient);
        $analysis = GlucoseLog::analyze($logs);
        $days     = $this->buildDays($logs);
        $points   = $this->chartPoints($logs);

        // Buyer-added picklist entries (curated lists live as model constants).
        $medExtras     = \App\Models\RxOption::where('field', 'diabetes_med')->orderBy('sort')->orderBy('id')->pluck('value')->all();
        $medPinned     = \App\Models\RxOption::pinned('diabetes_med');
        $symptomExtras = \App\Models\RxOption::where('field', 'glucose_symptom')->orderBy('sort')->orderBy('id')->pluck('value')->all();
        $foodExtras         = \App\Models\RxOption::where('field', 'food_item')->orderBy('sort')->orderBy('id')->pluck('value')->all();
        $foodPinned         = \App\Models\RxOption::pinned('food_item');
        $readingExtras      = \App\Models\RxOption::where('field', 'glucose_reading_type')->orderBy('sort')->orderBy('id')->pluck('value')->all();

        // Per-entry data the edit modal pre-fills from (keyed by id).
        $entries = $logs->mapWithKeys(fn ($l) => [$l->id => [
            'logged_at'     => $l->logged_at->format('Y-m-d\TH:i'),
            'reading_type'  => $l->reading_type,
            'value'         => $l->value !== null ? $this->fmt($l->value) : null,
            'insulin_units' => $l->insulin_units !== null ? $this->fmt($l->insulin_units) : null,
            'medications'   => $l->medications,
            'symptoms'      => $l->symptoms,
            'notes'         => $l->notes,
            'meal_notes'    => $l->meal_notes,
        ]]);

        // Diet logs grouped by date for the meal sub-row.
        $mealsByDay = DietLog::where('patient_id', $patient->id)
            ->orderBy('logged_at')
            ->get()
            ->groupBy(fn ($d) => $d->logged_at->toDateString());

        // BP logs grouped by date for the BP sub-row, and further bucketed into
        // the same time-of-day columns so each reading aligns under its column.
        $bpByDay = BpLog::where('patient_id', $patient->id)
            ->orderBy('logged_at')
            ->get()
            ->groupBy(fn ($b) => $b->logged_at->toDateString());

        $bpByDayCol = [];  // 'Y-m-d' => [ colIdx => Collection<BpLog> ]
        foreach ($bpByDay as $date => $dayBps) {
            $cols = [];
            for ($i = 0; $i < count(BpLog::DIARY_COLUMNS); $i++) $cols[$i] = collect();
            foreach ($dayBps as $b) {
                if ($b->systolic !== null && $b->diastolic !== null) {
                    $cols[BpLog::readingColumn($b->logged_at)]->push($b);
                }
            }
            $bpByDayCol[$date] = $cols;
        }

        // Foods entered in the diary's meal picker (glucose_log.meal_notes, each chip
        // optionally carrying "@ <time>") are placed on a time-of-day strip: 6 clock
        // slots left→right, each food dropped into the slot matching its eaten-time.
        $diaryColumns = GlucoseLog::DIARY_COLUMNS;
        $ncols        = count($diaryColumns);
        $foodByDayCol = [];   // 'Y-m-d' => [ colIdx => [ ['food','time','sort','logId'], … ] ]

        foreach ($logs->groupBy(fn ($l) => $l->logged_at->toDateString()) as $date => $dayLogs) {
            $cols = array_fill(0, $ncols, []);
            $any  = false;
            foreach ($dayLogs as $l) {
                foreach (GlucoseLog::parseFoods($l->meal_notes, $date, $l->logged_at->getTimestamp()) as $f) {
                    $f['logId'] = $l->id;
                    $cols[$f['col']][] = $f;
                    $any = true;
                }
            }
            if ($any) {
                foreach ($cols as &$c) {
                    usort($c, fn ($a, $b) => $a['sort'] <=> $b['sort']);
                }
                unset($c);
                $foodByDayCol[$date] = $cols;
            }
        }

        // Blood-sugar readings placed into columns by TIME (not type): a 2-hr PP at
        // 10pm goes to Night, etc. Random-type readings go to the Random column.
        $readingsByDayCol = [];   // 'Y-m-d' => [ colIdx => Collection<GlucoseLog> ]
        foreach ($logs->groupBy(fn ($l) => $l->logged_at->toDateString()) as $date => $dayLogs) {
            $rcols = [];
            for ($i = 0; $i < $ncols; $i++) $rcols[$i] = collect();   // distinct collections (NOT array_fill — would share one object)
            foreach ($dayLogs as $l) {
                if ($l->value !== null && in_array($l->reading_type, GlucoseLog::MGDL_TYPES, true)) {
                    $rcols[GlucoseLog::readingColumn($l->reading_type, $l->logged_at)]->push($l);
                }
            }
            foreach ($rcols as $k => $rc) {
                $rcols[$k] = $rc->sortBy('logged_at')->values();
            }
            $readingsByDayCol[$date] = $rcols;
        }

        return view('glucose.index', compact('patient', 'logs', 'analysis', 'days', 'points', 'medExtras', 'medPinned', 'symptomExtras', 'foodExtras', 'foodPinned', 'readingExtras', 'entries', 'mealsByDay', 'bpByDay', 'bpByDayCol', 'diaryColumns', 'foodByDayCol', 'readingsByDayCol'));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate($this->rules());
        if ($err = $this->normalize($data)) {
            return back()->withInput()->with('error', $err);
        }

        GlucoseLog::create($data + [
            'patient_id'  => $patient->id,
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Diary entry saved.');
    }

    public function update(Request $request, Patient $patient, GlucoseLog $glucoseLog)
    {
        abort_if($glucoseLog->patient_id !== $patient->id, 404);

        $data = $request->validate($this->rules());
        if ($err = $this->normalize($data)) {
            return back()->withInput()->with('error', $err);
        }

        $glucoseLog->update($data);

        return back()->with('success', 'Entry updated.');
    }

    /** Inline-edit just the consumed-at time of one food within a log's meal_notes. */
    public function updateFoodTime(Request $request, Patient $patient, GlucoseLog $glucoseLog)
    {
        abort_if($glucoseLog->patient_id !== $patient->id, 404);
        $data   = $request->validate([
            'food' => 'required|string|max:191',
            'time' => 'nullable|string|max:20',
        ]);
        $target = trim($data['food']);
        $time   = trim((string) ($data['time'] ?? ''));
        if ($time !== '' && preg_match('/\d/', $time) && ($ts = strtotime($time)) !== false) {
            $time = date('g:i A', $ts);   // normalise clock times to "1:40 PM"; leave slot labels ("Noon") as-is
        }

        $out  = [];
        $done = false;
        foreach (array_filter(array_map('trim', explode(',', (string) $glucoseLog->meal_notes))) as $chip) {
            $base = $chip;
            if (preg_match('/^(.*\S)\s*@\s*(\S.*)$/', $chip, $m)) {
                $base = trim($m[1]);
            }
            if (!$done && $base === $target) {
                $done  = true;
                $out[] = $time !== '' ? ($base . ' @ ' . $time) : $base;
            } else {
                $out[] = $chip;
            }
        }
        $glucoseLog->update(['meal_notes' => implode(', ', $out)]);
        return response()->json(['ok' => true]);
    }

    private function rules(): array
    {
        return [
            'logged_at'     => 'required|date',
            'reading_type'  => 'nullable|string|max:50',
            'value'         => 'nullable|numeric|min:0|max:1000',
            'medications'   => 'nullable|string|max:500',
            'insulin_units' => 'nullable|numeric|min:0|max:300',
            'symptoms'      => 'nullable|string|max:500',
            'notes'         => 'nullable|string|max:1000',
            'meal_notes'    => 'nullable|string|max:500',
        ];
    }

    /** Cross-field checks + normalisation shared by store/update. Returns an error string or null; mutates $data. */
    private function normalize(array &$data): ?string
    {
        $hasReading = !empty($data['reading_type']);
        $hasValue   = isset($data['value']) && $data['value'] !== null && $data['value'] !== '';

        if ($hasReading && !$hasValue) {
            return 'Enter the reading value for the selected type.';
        }
        if (!$hasReading && $hasValue) {
            return 'Choose which kind of reading this value is.';
        }
        if (!$hasReading && empty($data['medications']) && ($data['insulin_units'] ?? null) === null && empty($data['notes']) && empty($data['meal_notes'])) {
            return 'Nothing to save — add a reading, a medicine, or a note.';
        }
        if (($data['reading_type'] ?? null) === 'hba1c' && $hasValue && $data['value'] > 20) {
            return 'HbA1c is a percentage (e.g. 7.2). For a blood-sugar value pick a different reading type.';
        }

        if (!$hasReading) {
            $data['reading_type'] = null;
            $data['value'] = null;
        }
        return null;
    }

    public function destroy(Patient $patient, GlucoseLog $glucoseLog)
    {
        abort_if($glucoseLog->patient_id !== $patient->id, 404);
        $glucoseLog->delete();

        return back()->with('success', 'Entry removed.');
    }

    public function print(Patient $patient)
    {
        $logs     = $this->logsFor($patient);
        $analysis = GlucoseLog::analyze($logs);
        $days     = $this->buildDays($logs);

        return view('glucose.print', compact('patient', 'logs', 'analysis', 'days'));
    }

    private function logsFor(Patient $patient)
    {
        return GlucoseLog::with('recordedBy')
            ->where('patient_id', $patient->id)
            ->orderByDesc('logged_at')
            ->get();
    }

    /** Group entries by calendar day (latest day first) for the diary grid. */
    private function buildDays($logs)
    {
        return $logs->groupBy(fn ($l) => $l->logged_at->toDateString())
            ->map(function ($dayLogs, $date) {
                $cells = [];
                foreach (GlucoseLog::GRID_COLUMNS as $type) {
                    $cells[$type] = $dayLogs->where('reading_type', $type)->sortBy('logged_at')->values();
                }
                return [
                    'date'  => $date,
                    'cells' => $cells,
                    'hba1c' => $dayLogs->where('reading_type', 'hba1c')->sortBy('logged_at')->values(),
                    'meds'  => $dayLogs->filter(fn ($l) => $l->medications || $l->insulin_units !== null)
                                       ->sortBy('logged_at')->values(),
                    'notes' => $dayLogs->filter(fn ($l) => $l->notes || $l->symptoms ||
                                   ($l->reading_type && !in_array($l->reading_type, array_merge(GlucoseLog::GRID_COLUMNS, ['hba1c'])))
                               )->sortBy('logged_at')->values(),
                ];
            })->values();
    }

    /** Format a stored decimal without trailing zeros (110.0 -> "110", 7.20 -> "7.2"). */
    private function fmt($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    }

    /** Chronological mg/dL points for the trend chart (HbA1c excluded). */
    private function chartPoints($logs)
    {
        return $logs->filter(fn ($l) => $l->isGlucose() && $l->value !== null)
            ->sortBy('logged_at')->values()
            ->map(fn ($l) => [
                'label' => $l->logged_at->format('d M, H:i'),
                'y'     => (float) $l->value,
                'type'  => $l->typeShort(),
                'color' => $l->classification()['color'],
            ]);
    }
}
