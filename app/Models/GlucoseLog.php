<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A single diabetes-diary entry for a patient: a blood-sugar reading and/or
 * the medicine taken at that time. Entries are patient-scoped (NOT tied to an
 * encounter) so a patient can log several readings a day — FBS, pre-lunch,
 * pre-dinner, 2-hr PPBS, bedtime — without opening an encounter each time.
 */
class GlucoseLog extends Model
{
    protected $fillable = [
        'patient_id', 'logged_at', 'reading_type', 'value',
        'medications', 'insulin_units', 'symptoms', 'notes', 'meal_notes', 'recorded_by',
    ];

    protected $casts = [
        'logged_at'     => 'datetime',
        'value'         => 'decimal:1',
        'insulin_units' => 'decimal:1',
    ];

    /** Reading types shown in the diary, with display labels and units. */
    public const TYPES = [
        'fasting'       => ['label' => 'Fasting (FBS)',        'short' => 'FBS',        'unit' => 'mg/dL'],
        'pre_lunch'     => ['label' => 'Before lunch',         'short' => 'Pre-lunch',  'unit' => 'mg/dL'],
        'pre_dinner'    => ['label' => 'Before dinner',        'short' => 'Pre-dinner', 'unit' => 'mg/dL'],
        'post_prandial' => ['label' => 'After meal (2-hr PP)', 'short' => '2-hr PP',    'unit' => 'mg/dL'],
        'bedtime'       => ['label' => 'Bedtime',              'short' => 'Bedtime',    'unit' => 'mg/dL'],
        'random'        => ['label' => 'Random',               'short' => 'Random',     'unit' => 'mg/dL'],
        'hba1c'         => ['label' => 'HbA1c',                'short' => 'HbA1c',      'unit' => '%'],
    ];

    /** Glucose (mg/dL) reading types — i.e. everything except HbA1c. */
    public const MGDL_TYPES = ['fasting', 'pre_lunch', 'pre_dinner', 'post_prandial', 'bedtime', 'random'];

    /** Reading types judged against the pre-meal target band (80–130 mg/dL). */
    public const PREMEAL_TYPES = ['fasting', 'pre_lunch', 'pre_dinner', 'bedtime'];

    /** Column order for the day-by-day diary grid. */
    public const GRID_COLUMNS = ['fasting', 'pre_lunch', 'pre_dinner', 'post_prandial', 'bedtime', 'random'];

    /** Time-of-day slots (left→right) used for the diary food strip + Health Tracker. */
    public const FOOD_SLOTS = ['Morning', 'Late morning', 'Noon', 'Afternoon', 'Evening', 'Night'];

    /** Clock-time range for each food slot (shown in brackets on the strip + picker). */
    public const FOOD_SLOT_RANGES = [
        'Morning'      => '5–10 AM',
        'Late morning' => '10 AM–12 PM',
        'Noon'         => '12–3 PM',
        'Afternoon'    => '3–6 PM',
        'Evening'      => '6–9 PM',
        'Night'        => '9 PM–5 AM',
    ];

    /**
     * Merged diary columns: each pairs a blood-sugar reading type with the food
     * eaten in that part of the day. Order = left→right by time, then Random.
     */
    public const DIARY_COLUMNS = [
        ['label' => 'Morning (FBS)',       'bs' => 'fasting',       'range' => '5 AM–12 PM', 'color' => '#fffbeb'],
        ['label' => 'Lunch (Pre-lunch)',   'bs' => 'pre_lunch',     'range' => '12–3 PM',    'color' => '#f0fdf4'],
        ['label' => 'Afternoon',           'bs' => 'post_prandial', 'range' => '3–6 PM',     'color' => '#eff6ff'],
        ['label' => 'Dinner (Pre-dinner)', 'bs' => 'pre_dinner',    'range' => '6–9 PM',     'color' => '#fff7ed'],
        ['label' => 'Night (Bedtime)',     'bs' => 'bedtime',       'range' => '9 PM–5 AM',  'color' => '#f5f3ff'],
    ];

    /** Reading-type dropdown labels (column-style, with time range). Random has no
     *  column — it's placed by time and shown with a "Random" tag next to the value. */
    public const READING_OPTIONS = [
        'fasting'       => 'Morning (FBS) · 5 AM–12 PM',
        'pre_lunch'     => 'Lunch (Pre-lunch) · 12–3 PM',
        'post_prandial' => 'Afternoon · 3–6 PM',
        'pre_dinner'    => 'Dinner (Pre-dinner) · 6–9 PM',
        'bedtime'       => 'Night (Bedtime) · 9 PM–5 AM',
        'random'        => 'Random',
    ];

    /** Soothing colour for the combined Notes-Symptoms column. */
    public const NOTES_COLUMN_COLOR = '#fdf2f8';

    /**
     * Which diary column a blood-sugar reading belongs in — by the TIME it was
     * taken (not its type), since e.g. a 2-hr PP can follow any meal. A 'random'
     * reading always goes to the Random column. Returns 0-5 → DIARY_COLUMNS.
     */
    public static function readingColumn(?string $readingType, $loggedAt): int
    {
        $min = (int) $loggedAt->format('G') * 60 + (int) $loggedAt->format('i');
        if ($min >= 1260 || $min < 300) return 4;   // 9 PM–5 AM  → Night
        if ($min < 720)  return 0;                   // 5 AM–12 PM → Morning
        if ($min < 900)  return 1;                   // 12–3 PM    → Lunch
        if ($min < 1080) return 2;                   // 3–6 PM     → Afternoon
        return 3;                                    // 6–9 PM     → Dinner
    }

    /** Food slots offered in the picker (label => clock range) → map to DIARY_COLUMNS. */
    public const FOOD_PICK_SLOTS = [
        'Morning'   => '5 AM – 12 PM',
        'Lunch'     => '12 PM – 3 PM',
        'Afternoon' => '3 PM – 6 PM',
        'Dinner'    => '6 PM – 9 PM',
        'Night'     => '9 PM – 5 AM',
        'Random'    => 'anytime',
    ];

    /**
     * Parse a meal_notes string into food items, each resolved to a diary COLUMN
     * (0-5 → DIARY_COLUMNS). Each chip is "food [× N] [@ <slot-label-or-clock-time>]".
     * Returns [['food'=>, 'time'=>(display), 'col'=>0-5, 'sort'=>ts], …].
     */
    public static function parseFoods(?string $mealNotes, string $date, int $fallbackTs): array
    {
        // slot/meal label → column index (handles old "Noon"/"Evening"/"Late morning")
        $colByLabel  = [
            'morning' => 0, 'late morning' => 0,
            'noon' => 1, 'lunch' => 1,
            'afternoon' => 2,
            'evening' => 3, 'dinner' => 3,
            'night' => 4,
        ];
        $colStartMin = [0 => 300, 1 => 720, 2 => 900, 3 => 1080, 4 => 1260];
        $out = [];
        foreach (array_filter(array_map('trim', explode(',', (string) $mealNotes))) as $chip) {
            $food = $chip;
            $time = null;
            if (preg_match('/^(.*\S)\s*@\s*(\S.*)$/', $chip, $m)) {
                $food = trim($m[1]);
                $time = trim($m[2]);
            }
            $tl = strtolower(trim((string) $time));
            if ($time !== null && isset($colByLabel[$tl])) {            // meal/slot label
                $col  = $colByLabel[$tl];
                $sort = strtotime($date) + $colStartMin[$col] * 60;
                $disp = $time;
            } else {                                                     // clock time (or none)
                $ts = $time ? strtotime($date . ' ' . $time) : false;
                if (!$ts) $ts = $fallbackTs;
                $min = (int) date('G', $ts) * 60 + (int) date('i', $ts);
                if ($min >= 1260 || $min < 300) $col = 4;   // 9 PM–5 AM → Night
                elseif ($min < 720)  $col = 0;              // 5 AM–12 PM → Morning
                elseif ($min < 900)  $col = 1;              // 12–3 PM → Lunch
                elseif ($min < 1080) $col = 2;              // 3–6 PM → Afternoon
                else                 $col = 3;              // 6–9 PM → Dinner
                $sort = $ts;
                $disp = $time ?: date('h:i A', $ts);
            }
            $out[] = ['food' => $food, 'time' => $disp, 'col' => $col, 'sort' => $sort];
        }
        return $out;
    }

    /**
     * Curated diabetes-medicine picklist for the diary's medicine dropdown.
     * Buyer-added drugs are persisted separately as RxOption(field=diabetes_med)
     * so they appear alongside these the next time.
     */
    public const MEDICINES = [
        'Oral medicines' => [
            'Glimepiride 1 mg', 'Glimepiride 2 mg', 'Glimepiride 3 mg', 'Glimepiride 4 mg',
            'Gliclazide 30 mg', 'Gliclazide 60 mg', 'Gliclazide 80 mg',
            'Linagliptin 5 mg',
            'Metformin 500 mg', 'Metformin 1000 mg',
        ],
        'Insulins' => [
            'Actrapid (insulin)', 'Mixtard 30/70', 'Lantus (glargine)',
        ],
    ];

    /**
     * Curated signs / symptoms picklist, grouped by low- vs high-sugar.
     * Buyer-added symptoms persist as RxOption(field=glucose_symptom).
     */
    public const SYMPTOMS = [
        'Low sugar (hypoglycaemia)' => [
            'Sweating', 'Tremor / shakiness', 'Palpitations', 'Hunger', 'Dizziness',
            'Confusion', 'Blurred vision', 'Anxiety / irritability', 'Weakness / fatigue',
            'Headache', 'Slurred speech', 'Seizure', 'Loss of consciousness',
        ],
        'High sugar (hyperglycaemia)' => [
            'Excessive thirst', 'Frequent urination', 'Excessive hunger', 'Fatigue',
            'Dry mouth', 'Nausea / vomiting', 'Unexplained weight loss',
            'Slow-healing wounds', 'Drowsiness', 'Fruity-smelling breath',
        ],
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function typeLabel(): string { return self::TYPES[$this->reading_type]['label'] ?? '—'; }
    public function typeShort(): string { return self::TYPES[$this->reading_type]['short'] ?? '—'; }
    public function unit(): string      { return self::TYPES[$this->reading_type]['unit'] ?? ''; }
    public function isGlucose(): bool   { return in_array($this->reading_type, self::MGDL_TYPES, true); }

    public function classification(): array
    {
        return self::classify($this->reading_type, $this->value);
    }

    /**
     * Classify one reading into a status bucket with a label and colour.
     * Thresholds are ADA-aligned (mg/dL for glucose; % for HbA1c).
     */
    public static function classify(?string $type, $value): array
    {
        $green = '#16a34a'; $amber = '#d97706'; $red = '#dc2626'; $dark = '#991b1b'; $grey = '#94a3b8';

        if ($value === null || $value === '') {
            return ['status' => 'none', 'label' => '—', 'color' => $grey];
        }
        $v = (float) $value;

        if ($type === 'hba1c') {
            if ($v < 5.7)  return ['status' => 'in_target', 'label' => 'Normal',          'color' => $green];
            if ($v < 6.5)  return ['status' => 'elevated',  'label' => 'Pre-diabetes',    'color' => $amber];
            if ($v < 7.0)  return ['status' => 'in_target', 'label' => 'At goal (<7%)',   'color' => $green];
            if ($v <= 8.0) return ['status' => 'elevated',  'label' => 'Above goal',      'color' => $amber];
            return            ['status' => 'high',      'label' => 'Poor control',     'color' => $dark];
        }

        // Glucose readings (mg/dL)
        if ($v < 70) {
            return ['status' => 'low', 'label' => ($v < 54 ? 'Very low' : 'Low'), 'color' => $red];
        }
        [$targetHi, $elevatedHi] = match ($type) {
            'post_prandial' => [179, 250],
            'random'        => [199, 300],
            default         => [130, 180],   // fasting, pre_lunch, pre_dinner, bedtime
        };
        if ($v <= $targetHi)   return ['status' => 'in_target', 'label' => 'In target',     'color' => $green];
        if ($v <= $elevatedHi) return ['status' => 'elevated',  'label' => 'Above target',  'color' => $amber];
        return                     ['status' => 'high',      'label' => 'High',          'color' => $dark];
    }

    /**
     * Rule-based summary + advice over a patient's diary. Looks at the last
     * 30 days of glucose readings (falls back to all-time if none recent).
     * Returns ['stats' => array|null, 'messages' => array of {type,text}].
     * Advice is informational only — see the disclaimer shown in the view.
     */
    public static function analyze(Collection $logs): array
    {
        $glucose = $logs->filter(fn ($l) => $l->isGlucose() && $l->value !== null);
        $now     = now();

        $recent = $glucose->filter(fn ($l) => $l->logged_at && $l->logged_at->gte($now->copy()->subDays(30)));
        $sample = $recent->isNotEmpty() ? $recent : $glucose;
        $windowDays = $recent->isNotEmpty() ? 30 : null;

        $messages = [];
        $stats = null;

        if ($sample->isNotEmpty()) {
            $count    = $sample->count();
            $avg      = (int) round($sample->avg(fn ($l) => (float) $l->value));
            $byStatus = $sample->groupBy(fn ($l) => $l->classification()['status']);
            $low      = ($byStatus['low']       ?? collect())->count();
            $inTarget = ($byStatus['in_target'] ?? collect())->count();
            $elevated = ($byStatus['elevated']  ?? collect())->count();
            $high     = ($byStatus['high']      ?? collect())->count();
            $inPct    = (int) round($inTarget / $count * 100);
            // Estimated HbA1c from mean glucose (ADAG: A1c = (mean + 46.7) / 28.7).
            $estA1c   = $count >= 7 ? round(($avg + 46.7) / 28.7, 1) : null;

            $stats = compact('count', 'avg', 'low', 'inTarget', 'elevated', 'high', 'inPct', 'estA1c', 'windowDays');

            if ($low > 0) {
                $messages[] = ['type' => 'warn', 'text' =>
                    "{$low} low reading" . ($low > 1 ? 's' : '') . " (below 70 mg/dL) recorded — risk of hypoglycaemia. "
                    . "Keep fast-acting sugar handy and ask the doctor whether a medicine dose or its timing should be reduced."];
            }

            $preTot  = $sample->filter(fn ($l) => in_array($l->reading_type, self::PREMEAL_TYPES, true))->count();
            $preHigh = $sample->filter(fn ($l) => in_array($l->reading_type, self::PREMEAL_TYPES, true)
                            && in_array($l->classification()['status'], ['elevated', 'high'], true))->count();
            if ($preTot >= 3 && $preHigh / $preTot >= 0.5) {
                $messages[] = ['type' => 'warn', 'text' =>
                    "Fasting / before-meal sugars are often above target — discuss the background (basal) medication and evening meal timing with the doctor."];
            }

            $ppTot  = $sample->where('reading_type', 'post_prandial')->count();
            $ppHigh = $sample->filter(fn ($l) => $l->reading_type === 'post_prandial'
                            && in_array($l->classification()['status'], ['elevated', 'high'], true))->count();
            if ($ppTot >= 3 && $ppHigh / $ppTot >= 0.5) {
                $messages[] = ['type' => 'warn', 'text' =>
                    "After-meal (2-hr) sugars are often high — review meal portions / carbohydrates and the medicine taken with meals."];
            }

            if ($inPct >= 70) {
                $messages[] = ['type' => 'good', 'text' => "Good control — {$inPct}% of recent readings are in target. Keep up the current routine."];
            } elseif ($inPct >= 50) {
                $messages[] = ['type' => 'info', 'text' => "Fair control — {$inPct}% of readings in target. Small changes to diet, activity or medication timing may help."];
            } else {
                $messages[] = ['type' => 'warn', 'text' => "Only {$inPct}% of recent readings are in target — please review the treatment plan with the doctor."];
            }
        }

        $hba1c = $logs->where('reading_type', 'hba1c')->sortByDesc('logged_at')->first();
        if ($hba1c) {
            $c = self::classify('hba1c', $hba1c->value);
            $messages[] = [
                'type' => $c['status'] === 'in_target' ? 'good' : ($c['status'] === 'high' ? 'warn' : 'info'),
                'text' => "Latest HbA1c {$hba1c->value}% — {$c['label']}. "
                        . "(HbA1c reflects the ~3-month average; the goal for most adults with diabetes is below 7%.)",
            ];
        }

        $last = $glucose->sortByDesc('logged_at')->first();
        if (!$last || $last->logged_at->lt($now->copy()->subDays(14))) {
            $messages[] = ['type' => 'info', 'text' =>
                "No blood-sugar readings logged in the last 2 weeks. Logging fasting and 2-hour-after-meal values regularly gives a clearer picture."];
        }

        return ['stats' => $stats, 'messages' => $messages];
    }
}
