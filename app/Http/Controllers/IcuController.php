<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\IcuInfusion;
use App\Models\IoEntry;
use App\Models\IpdVitals;
use App\Services\EarlyWarning;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IcuController extends Controller
{
    const WINDOWS = [6 => '6 h', 12 => '12 h', 24 => '24 h', 48 => '48 h', 72 => '72 h', 168 => '7 days'];

    // ── ICU board (central monitor) ─────────────────────────────────────

    public function board(Request $request)
    {
        $admissions = $this->icuAdmissions();
        $since = now()->subDay();

        $rows = $admissions->map(function (Admission $a) use ($since) {
            $last2 = $a->vitals()->limit(2)->get();
            $latest = $last2->first();
            $io = IoEntry::where('admission_id', $a->id)->where('recorded_at', '>=', $since)
                ->selectRaw("SUM(CASE WHEN io_type='input' THEN amount_ml ELSE 0 END) i, SUM(CASE WHEN io_type='output' THEN amount_ml ELSE 0 END) o")->first();
            $running = $a->icuInfusions->whereNull('stopped_at');
            $infusedMl = $this->infusedMl($a->icuInfusions, $since, now());
            return [
                'a'        => $a,
                'v'        => $latest,
                'level'    => EarlyWarning::levelOf($latest),
                'alerts'   => $this->alerts($latest, $last2->get(1), $running),
                'running'  => $running,
                'in'       => (int) $io->i + $infusedMl,
                'out'      => (int) $io->o,
                'weight'   => $this->weightOf($a),
            ];
        })->sortByDesc(fn ($r) => [count($r['alerts']), $r['v']->news2 ?? -1])->values();

        if ($request->boolean('partial')) return view('icu._board-tiles', compact('rows'));
        return view('icu.board', compact('rows'));
    }

    // ── Patient ICU chart ───────────────────────────────────────────────

    public function chart(Request $request, Admission $admission)
    {
        $hours = (int) $request->input('hours', 24);
        if (!isset(self::WINDOWS[$hours])) $hours = 24;
        $to = $admission->discharge_date ?? now();
        $from = $to->copy()->subHours($hours);
        if ($from->lt($admission->admission_date)) $from = $admission->admission_date->copy();

        $admission->load(['patient', 'ward', 'bed', 'admittingDoctor', 'icuInfusions.logs.user', 'icuInfusions.startedBy']);

        $vitals = IpdVitals::with('recordedBy')->where('admission_id', $admission->id)
            ->whereBetween('recorded_at', [$from, $to])->orderBy('recorded_at')->get();
        $latest = $admission->vitals()->first();
        $previous = $admission->vitals()->skip(1)->first();

        $infusions = $admission->icuInfusions->filter(fn ($i) => $i->started_at->lte($to) && (!$i->stopped_at || $i->stopped_at->gte($from)));
        $running = $admission->icuInfusions->whereNull('stopped_at')->values();

        $io = IoEntry::where('admission_id', $admission->id)->whereBetween('recorded_at', [$from, $to])->orderBy('recorded_at')->get();
        $infusedMl = $this->infusedMl($infusions, $from, $to);
        $balance = [
            'in'       => (int) $io->where('io_type', 'input')->sum('amount_ml') + $infusedMl,
            'infused'  => $infusedMl,
            'out'      => (int) $io->where('io_type', 'output')->sum('amount_ml'),
            'urine'    => (int) $io->where('category', 'urine')->sum('amount_ml'),
        ];
        $weight = $this->weightOf($admission);
        $hoursSpan = max(1, $from->diffInMinutes($to) / 60);
        $balance['urine_ml_kg_h'] = $weight ? round($balance['urine'] / $weight / $hoursSpan, 2) : null;

        $series = $this->chartSeries($vitals, $infusions, $io, $from, $to);
        $news = $latest ? EarlyWarning::news2($latest) : null;

        return view('icu.chart', [
            'admission' => $admission, 'vitals' => $vitals, 'latest' => $latest, 'news' => $news,
            'alerts' => $this->alerts($latest, $previous, $running),
            'infusions' => $infusions, 'running' => $running, 'balance' => $balance, 'weight' => $weight,
            'series' => $series, 'hours' => $hours, 'from' => $from, 'to' => $to,
            'drugs' => config('icu_drugs.infusions'),
            'canWrite' => $admission->status === 'admitted' || auth()->user()->isAdmin(),
        ]);
    }

    public function storeObs(Request $request, Admission $admission)
    {
        $data = $this->validateObs($request);
        $data['admission_id'] = $admission->id;
        $data['recorded_by']  = auth()->id();
        $v = IpdVitals::create($data);

        ActivityLog::log('IPD Vitals Recorded', "ICU observations for {$admission->ipd_no} (NEWS2 {$v->news2})");
        return redirect()->route('icu.chart', [$admission, 'hours' => $request->input('hours', 24)])
            ->with('success', 'Observations saved. NEWS2 ' . $v->news2 . ($v->news2_partial ? ' (partial)' : '') . '.');
    }

    public static function obsRules(): array
    {
        return [
            'recorded_at'      => 'required|date|before_or_equal:' . now()->addMinutes(10)->toDateTimeString(),
            'temperature'      => 'nullable|numeric|between:80,115',
            'pulse'            => 'nullable|integer|between:0,300',
            'respiration_rate' => 'nullable|integer|between:0,80',
            'bp_systolic'      => 'nullable|integer|between:20,300',
            'bp_diastolic'     => 'nullable|integer|between:10,200',
            'map'              => 'nullable|integer|between:10,250',
            'cvp'              => 'nullable|integer|between:-10,40',
            'rhythm'           => 'nullable|string|max:40',
            'spo2'             => 'nullable|integer|between:30,100',
            'avpu'             => ['nullable', Rule::in(array_keys(IpdVitals::AVPU))],
            'gcs_e'            => 'nullable|integer|between:1,4',
            'gcs_v'            => 'nullable|integer|between:1,5',
            'gcs_m'            => 'nullable|integer|between:1,6',
            'intubated'        => 'nullable|boolean',
            'pupils'           => 'nullable|string|max:40',
            'rass'             => 'nullable|integer|between:-5,4',
            'pain_score'       => 'nullable|integer|between:0,10',
            'o2_device'        => ['nullable', Rule::in(array_keys(IpdVitals::O2_DEVICES))],
            'o2_flow'          => 'nullable|numeric|between:0,80',
            'fio2'             => 'nullable|integer|between:21,100',
            'spo2_scale2'      => 'nullable|boolean',
            'vent_mode'        => 'nullable|string|max:20',
            'tidal_volume'     => 'nullable|integer|between:50,1500',
            'vent_rate'        => 'nullable|integer|between:0,60',
            'peep'             => 'nullable|numeric|between:0,30',
            'pip'              => 'nullable|integer|between:0,80',
            'weight'           => 'nullable|numeric|between:1,300',
            'blood_glucose'    => 'nullable|numeric|between:10,1000',
            'urine_output'     => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
        ];
    }

    private function validateObs(Request $request): array
    {
        $data = $request->validate(self::obsRules());
        $data['intubated'] = $request->boolean('intubated');
        $data['spo2_scale2'] = $request->boolean('spo2_scale2');
        if ($data['intubated']) $data['gcs_v'] = null;
        return $data;
    }

    // ── Infusions ───────────────────────────────────────────────────────

    public function startInfusion(Request $request, Admission $admission)
    {
        abort_if($admission->status !== 'admitted', 422, 'Patient is not admitted.');
        $data = $request->validate([
            'drug_key'    => ['nullable', Rule::in(array_keys(config('icu_drugs.infusions')))],
            'drug_name'   => 'required_without:drug_key|nullable|string|max:120',
            'amount'      => 'required|numeric|gt:0|max:1000000',
            'amount_unit' => 'required|in:mg,mcg,U',
            'volume_ml'   => 'required|numeric|gt:0|max:1000',
            'diluent'     => 'nullable|string|max:20',
            'dose_unit'   => ['required', 'regex:#^(mg|mcg|U)(/kg)?/(min|h)$#'],
            'weight_kg'   => 'nullable|numeric|between:0.5,300',
            'dose'        => 'nullable|numeric|gte:0|required_without:rate_ml_h',
            'rate_ml_h'   => 'nullable|numeric|gte:0|max:1000|required_without:dose',
            'started_at'  => 'required|date|before_or_equal:' . now()->addMinutes(10)->toDateTimeString(),
            'notes'       => 'nullable|string|max:300',
        ]);

        if ($data['drug_key']) {
            $drug = config("icu_drugs.infusions.{$data['drug_key']}");
            $data['drug_name'] = $drug['name'];
            $data['dose_unit'] = $drug['unit'];
            $data['amount_unit'] = $drug['amount'];
        }
        if (str_contains($data['dose_unit'], '/kg/') && empty($data['weight_kg'])) {
            return back()->withErrors(['weight_kg' => 'Weight is needed for a per-kg dose.'])->withInput();
        }
        try {
            $conc = IcuInfusion::concentration($data['amount'], $data['amount_unit'], $data['volume_ml'], $data['dose_unit']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount_unit' => $e->getMessage()])->withInput();
        }
        $this->fillDoseRate($data, $conc);

        $inf = DB::transaction(function () use ($data, $admission) {
            $inf = $admission->icuInfusions()->create($data + ['started_by' => auth()->id()]);
            $inf->logs()->create(['logged_at' => $inf->started_at, 'action' => 'start', 'dose' => $inf->dose,
                'rate_ml_h' => $inf->rate_ml_h, 'note' => $data['notes'] ?? null, 'user_id' => auth()->id()]);
            return $inf;
        });

        ActivityLog::log('ICU Infusion Started', "{$admission->ipd_no}: {$inf->drug_name} {$inf->dose_label} = {$inf->rate_ml_h} ml/h");
        return $this->backToChart($request, $admission, "{$inf->drug_name} started at {$inf->dose_label} ({$inf->rate_ml_h} ml/h).");
    }

    public function changeInfusion(Request $request, Admission $admission, IcuInfusion $infusion)
    {
        abort_if($infusion->admission_id !== $admission->id, 404);
        if ($infusion->stopped_at) return back()->with('error', 'This infusion has already been stopped.');

        $data = $request->validate([
            'action'    => 'required|in:change,new_syringe',
            'dose'      => 'nullable|numeric|gte:0|required_without:rate_ml_h',
            'rate_ml_h' => 'nullable|numeric|gte:0|max:1000|required_without:dose',
            'logged_at' => 'required|date|after_or_equal:' . $infusion->started_at->toDateTimeString() . '|before_or_equal:' . now()->addMinutes(10)->toDateTimeString(),
            'note'      => 'nullable|string|max:300',
        ]);
        $data['weight_kg'] = $infusion->weight_kg;
        $this->fillDoseRate($data, $infusion->concentration, $infusion->dose_unit);

        DB::transaction(function () use ($infusion, $data) {
            $infusion->update(['dose' => $data['dose'], 'rate_ml_h' => $data['rate_ml_h']]);
            $infusion->logs()->create(['logged_at' => $data['logged_at'], 'action' => $data['action'], 'dose' => $data['dose'],
                'rate_ml_h' => $data['rate_ml_h'], 'note' => $data['note'] ?? null, 'user_id' => auth()->id()]);
        });

        $what = $data['action'] === 'new_syringe' ? 'New syringe' : 'Rate changed';
        ActivityLog::log('ICU Infusion Changed', "{$admission->ipd_no}: {$infusion->drug_name} {$what} → {$infusion->dose_label} = {$infusion->rate_ml_h} ml/h");
        return $this->backToChart($request, $admission, "{$infusion->drug_name}: {$what}, now {$infusion->dose_label} ({$infusion->rate_ml_h} ml/h).");
    }

    public function stopInfusion(Request $request, Admission $admission, IcuInfusion $infusion)
    {
        abort_if($infusion->admission_id !== $admission->id, 404);
        if ($infusion->stopped_at) return back()->with('error', 'This infusion has already been stopped.');
        $data = $request->validate([
            'stopped_at' => 'required|date|after_or_equal:' . $infusion->started_at->toDateTimeString() . '|before_or_equal:' . now()->addMinutes(10)->toDateTimeString(),
            'note'       => 'nullable|string|max:300',
        ]);

        DB::transaction(function () use ($infusion, $data) {
            $infusion->update(['stopped_at' => $data['stopped_at'], 'stopped_by' => auth()->id()]);
            $infusion->logs()->create(['logged_at' => $data['stopped_at'], 'action' => 'stop', 'dose' => 0, 'rate_ml_h' => 0,
                'note' => $data['note'] ?? null, 'user_id' => auth()->id()]);
        });

        ActivityLog::log('ICU Infusion Stopped', "{$admission->ipd_no}: {$infusion->drug_name} stopped");
        return $this->backToChart($request, $admission, "{$infusion->drug_name} stopped.");
    }

    /** Admin only: remove an infusion entered by mistake. */
    public function destroyInfusion(Request $request, Admission $admission, IcuInfusion $infusion)
    {
        abort_if($infusion->admission_id !== $admission->id, 404);
        abort_unless(auth()->user()->isAdmin() || $infusion->started_by === auth()->id(), 403);
        $name = $infusion->drug_name;
        $infusion->delete();
        ActivityLog::log('ICU Infusion Deleted', "{$admission->ipd_no}: {$name} entry deleted");
        return $this->backToChart($request, $admission, "{$name} entry deleted.");
    }

    // ── Drug calculator / emergency sheet ───────────────────────────────

    public function calculator(Request $request)
    {
        $admission = $request->filled('admission') ? Admission::with('patient', 'bed', 'ward')->find($request->input('admission')) : null;
        return view('icu.calculator', [
            'drugs'      => config('icu_drugs.infusions'),
            'bolus'      => config('icu_drugs.bolus'),
            'electrical' => config('icu_drugs.electrical'),
            'admission'  => $admission,
            'weight'     => $admission ? $this->weightOf($admission) : $request->input('weight'),
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** Admitted patients in ICU-type wards, plus anyone elsewhere on a running infusion. */
    public function icuAdmissions(): Collection
    {
        return Admission::with(['patient', 'ward', 'bed', 'admittingDoctor', 'icuInfusions'])
            ->where('status', 'admitted')
            ->where(fn ($q) => $q->whereHas('ward', fn ($w) => $w->where('type', 'icu'))
                ->orWhereHas('icuInfusions', fn ($i) => $i->whereNull('stopped_at')))
            ->get()
            ->sortBy(fn ($a) => [$a->ward->name ?? '', $a->bed->bed_number ?? ''])
            ->values();
    }

    public function weightOf(Admission $a): ?float
    {
        $w = IpdVitals::where('admission_id', $a->id)->whereNotNull('weight')->orderByDesc('recorded_at')->value('weight')
            ?? IcuInfusion::where('admission_id', $a->id)->whereNotNull('weight_kg')->latest('started_at')->value('weight_kg');
        return $w ? (float) $w : null;
    }

    /** Fill whichever of dose / rate_ml_h is missing (dose wins when both are given). */
    private function fillDoseRate(array &$data, float $conc, ?string $doseUnit = null): void
    {
        $unit = $doseUnit ?? $data['dose_unit'];
        $w = $data['weight_kg'] ?? null;
        if (isset($data['dose']) && $data['dose'] !== '') {
            $data['rate_ml_h'] = round(IcuInfusion::rateFor((float) $data['dose'], $conc, $unit, $w), 2);
        } else {
            $data['dose'] = round(IcuInfusion::doseFor((float) $data['rate_ml_h'], $conc, $unit, $w), 4);
        }
    }

    private function backToChart(Request $request, Admission $admission, string $msg)
    {
        $to = $request->input('return') === 'board' ? route('icu.board') : route('icu.chart', $admission) . '#infusions';
        return redirect($to)->with('success', $msg);
    }

    /** ml infused between $from and $to, from each infusion's rate log. */
    public function infusedMl(Collection $infusions, CarbonInterface $from, CarbonInterface $to): int
    {
        $ml = 0;
        foreach ($infusions as $inf) {
            $logs = $inf->relationLoaded('logs') ? $inf->logs : $inf->logs()->get();
            foreach ($logs->values() as $i => $log) {
                $segStart = $log->logged_at;
                $segEnd = $logs->get($i + 1)?->logged_at ?? $inf->stopped_at ?? now();
                $a = $segStart->max($from);
                $b = $segEnd->min($to);
                if ($b->gt($a)) $ml += $log->rate_ml_h * $a->diffInMinutes($b) / 60;
            }
        }
        return (int) round($ml);
    }

    /** Bedside alerts from the latest observation and running drugs. */
    public function alerts(?IpdVitals $v, ?IpdVitals $prev, Collection $running): array
    {
        $out = [];
        if (!$v) return [['danger', 'No observations recorded']];
        $age = $v->recorded_at->diffInMinutes(now());
        if ($age > 120) $out[] = ['warn', 'Last observation ' . $v->recorded_at->diffForHumans()];
        if ($v->mean_ap !== null && $v->mean_ap < 65) $out[] = ['danger', "MAP {$v->mean_ap}"];
        if ($v->spo2 !== null && $v->spo2 < 90) $out[] = ['danger', "SpO₂ {$v->spo2}%"];
        if ($v->pulse !== null && ($v->pulse > 130 || $v->pulse < 40)) $out[] = ['danger', "HR {$v->pulse}"];
        if ($v->respiration_rate !== null && ($v->respiration_rate > 30 || $v->respiration_rate < 8)) $out[] = ['danger', "RR {$v->respiration_rate}"];
        if ($v->bp_systolic !== null && $v->bp_systolic >= 200) $out[] = ['danger', "SBP {$v->bp_systolic}"];
        if ($v->blood_glucose !== null && ($v->blood_glucose < 70 || $v->blood_glucose > 300)) $out[] = ['danger', "Glucose {$v->blood_glucose}"];
        if ($v->temperature !== null && $v->temperature >= 102.2) $out[] = ['warn', "Temp {$v->temperature}°F"];
        if ($v->news2 !== null && $v->news2 >= 7) $out[] = ['danger', "NEWS2 {$v->news2}"];
        elseif ($v->news2 !== null && $v->news2 >= 5) $out[] = ['warn', "NEWS2 {$v->news2}"];
        if ($v->gcs !== null && $prev?->gcs !== null && !$v->intubated && !$prev->intubated && $prev->gcs - $v->gcs >= 2) {
            $out[] = ['danger', "GCS fell {$prev->gcs} → {$v->gcs}"];
        }
        foreach ($running as $inf) {
            $max = $inf->drug['max'] ?? null;
            if ($max !== null && $inf->dose > $max) $out[] = ['warn', "{$inf->drug_name} {$inf->dose_label} (above usual max {$max})"];
        }
        return $out;
    }

    private function chartSeries(Collection $vitals, Collection $infusions, Collection $io, CarbonInterface $from, CarbonInterface $to): array
    {
        $ms = fn (CarbonInterface $t) => $t->getTimestamp() * 1000;
        $pt = fn ($field) => $vitals->filter(fn ($v) => $v->{$field} !== null)->map(fn ($v) => ['x' => $ms($v->recorded_at), 'y' => (float) $v->{$field}])->values();

        $drugs = $infusions->map(function (IcuInfusion $inf) use ($ms, $from, $to) {
            $points = [];
            foreach ($inf->logs->filter(fn ($l) => $l->logged_at->lte($to)) as $log) {
                $x = $ms($log->logged_at->max($from));
                if ($log->action === 'stop') {
                    // hold the last dose up to the stop time, then break the line
                    $points[] = ['x' => $x, 'y' => end($points)['y'] ?? null];
                    $points[] = ['x' => $x, 'y' => null];
                } else {
                    $points[] = ['x' => $x, 'y' => $log->dose];
                }
            }
            if (!$inf->stopped_at) $points[] = ['x' => $ms(now()->min($to)), 'y' => $inf->dose];
            return ['label' => $inf->drug_name . ' (' . $inf->dose_unit . ')', 'vasoactive' => $inf->is_vasoactive, 'data' => $points];
        })->values();

        // Hourly cumulative fluid balance
        $cum = 0;
        $balance = [];
        foreach ($io as $e) {
            $cum += $e->io_type === 'input' ? $e->amount_ml : -$e->amount_ml;
            $balance[] = ['x' => $ms($e->recorded_at), 'y' => $cum];
        }

        return [
            'from' => $ms($from), 'to' => $ms($to),
            'hr' => $pt('pulse'), 'sbp' => $pt('bp_systolic'), 'dbp' => $pt('bp_diastolic'),
            'map' => $vitals->filter(fn ($v) => $v->mean_ap !== null)->map(fn ($v) => ['x' => $ms($v->recorded_at), 'y' => $v->mean_ap])->values(),
            'cvp' => $pt('cvp'), 'rr' => $pt('respiration_rate'), 'spo2' => $pt('spo2'), 'fio2' => $pt('fio2'), 'peep' => $pt('peep'),
            'temp' => $pt('temperature'), 'glucose' => $pt('blood_glucose'), 'news2' => $pt('news2'),
            'gcs' => $vitals->filter(fn ($v) => $v->gcs !== null)->map(fn ($v) => ['x' => $ms($v->recorded_at), 'y' => $v->gcs])->values(),
            'rass' => $pt('rass'),
            'drugs' => $drugs, 'balance' => $balance,
        ];
    }
}
