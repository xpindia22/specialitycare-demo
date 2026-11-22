<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A single blood-pressure diary entry for a patient: systolic/diastolic
 * (and optional pulse) plus the antihypertensive medicine taken and any
 * symptoms. Patient-scoped (no encounter required), like the glucose diary.
 */
class BpLog extends Model
{
    protected $fillable = [
        'patient_id', 'logged_at', 'systolic', 'diastolic', 'pulse',
        'medications', 'symptoms', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'systolic'  => 'integer',
        'diastolic' => 'integer',
        'pulse'     => 'integer',
    ];

    /** Curated antihypertensive picklist; buyer-added drugs persist as RxOption(field=bp_med). */
    public const MEDICINES = [
        'ACE inhibitor / ARB' => [
            'Telmisartan 40 mg', 'Telmisartan 80 mg', 'Losartan 50 mg', 'Ramipril 5 mg', 'Enalapril 5 mg',
        ],
        'Calcium channel blocker' => [
            'Amlodipine 5 mg', 'Amlodipine 10 mg', 'Cilnidipine 10 mg',
        ],
        'Diuretic' => [
            'Hydrochlorothiazide 12.5 mg', 'Chlorthalidone 12.5 mg', 'Indapamide 1.5 mg',
        ],
        'Beta blocker' => [
            'Metoprolol 25 mg', 'Metoprolol 50 mg', 'Atenolol 50 mg', 'Bisoprolol 5 mg',
        ],
        'Combination' => [
            'Telmisartan 40 + Amlodipine 5', 'Telmisartan 40 + HCTZ 12.5',
        ],
    ];

    /** Curated BP-related symptoms, grouped high- vs low-pressure. */
    public const SYMPTOMS = [
        'High BP (hypertension)' => [
            'Headache', 'Blurred vision', 'Chest pain', 'Shortness of breath',
            'Palpitations', 'Nosebleed', 'Dizziness',
        ],
        'Low BP (hypotension)' => [
            'Lightheadedness', 'Fainting', 'Fatigue', 'Nausea', 'Cold / clammy skin',
        ],
    ];

    /**
     * Time-of-day columns for the diary grid — a reading is plotted into the
     * column matching the clock time it was checked. Same buckets as the
     * glucose diary so the two diaries read consistently.
     */
    public const DIARY_COLUMNS = [
        ['label' => 'Morning',   'range' => '5 AM–12 PM', 'color' => '#fffbeb'],
        ['label' => 'Lunch',     'range' => '12–3 PM',    'color' => '#f0fdf4'],
        ['label' => 'Afternoon', 'range' => '3–6 PM',     'color' => '#eff6ff'],
        ['label' => 'Dinner',    'range' => '6–9 PM',     'color' => '#fff7ed'],
        ['label' => 'Night',     'range' => '9 PM–5 AM',  'color' => '#f5f3ff'],
    ];

    /** Background colour for the Notes-Symptoms column. */
    public const NOTES_COLUMN_COLOR = '#fdf2f8';

    /**
     * Which diary column (0-4 → DIARY_COLUMNS) a reading falls in, by the
     * clock time it was taken. Mirrors GlucoseLog::readingColumn() bucketing.
     */
    public static function readingColumn($loggedAt): int
    {
        $min = (int) $loggedAt->format('G') * 60 + (int) $loggedAt->format('i');
        if ($min >= 1260 || $min < 300) return 4;   // 9 PM–5 AM  → Night
        if ($min < 720)  return 0;                   // 5 AM–12 PM → Morning
        if ($min < 900)  return 1;                   // 12–3 PM    → Lunch
        if ($min < 1080) return 2;                   // 3–6 PM     → Afternoon
        return 3;                                    // 6–9 PM     → Dinner
    }

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function reading(): string
    {
        return $this->systolic . '/' . $this->diastolic;
    }

    public function classification(): array
    {
        return self::classify($this->systolic, $this->diastolic);
    }

    /**
     * ACC/AHA-aligned BP category from systolic/diastolic (mmHg). The higher
     * (worse) of the two values decides the category.
     */
    public static function classify(?int $sys, ?int $dia): array
    {
        $green = '#16a34a'; $amber = '#d97706'; $orange = '#ea580c'; $red = '#dc2626'; $dark = '#7f1d1d'; $blue = '#2563eb'; $grey = '#94a3b8';

        if (!$sys || !$dia) {
            return ['status' => 'none', 'label' => '—', 'color' => $grey];
        }
        if ($sys >= 180 || $dia >= 120) {
            return ['status' => 'crisis', 'label' => 'Hypertensive crisis', 'color' => $dark];
        }
        if ($sys < 90 || $dia < 60) {
            return ['status' => 'low', 'label' => 'Low (hypotension)', 'color' => $blue];
        }
        if ($sys >= 140 || $dia >= 90) {
            return ['status' => 'stage2', 'label' => 'Stage 2 high', 'color' => $red];
        }
        if ($sys >= 130 || $dia > 80) {
            return ['status' => 'stage1', 'label' => 'Stage 1 high', 'color' => $orange];
        }
        if ($sys > 120) {
            return ['status' => 'elevated', 'label' => 'Elevated', 'color' => $amber];
        }
        return ['status' => 'normal', 'label' => 'Normal', 'color' => $green];
    }

    /**
     * Rule-based summary + advice over the last 30 days of readings (falls
     * back to all-time). Advice is informational — see the view disclaimer.
     */
    public static function analyze(Collection $logs): array
    {
        $now    = now();
        $recent = $logs->filter(fn ($l) => $l->logged_at && $l->logged_at->gte($now->copy()->subDays(30)));
        $sample = $recent->isNotEmpty() ? $recent : $logs;
        $windowDays = $recent->isNotEmpty() ? 30 : null;

        $messages = [];
        $stats = null;

        if ($sample->isNotEmpty()) {
            $count   = $sample->count();
            $avgSys  = (int) round($sample->avg('systolic'));
            $avgDia  = (int) round($sample->avg('diastolic'));
            $byStatus = $sample->groupBy(fn ($l) => $l->classification()['status']);
            $crisis  = ($byStatus['crisis'] ?? collect())->count();
            $stage2  = ($byStatus['stage2'] ?? collect())->count();
            $low     = ($byStatus['low']    ?? collect())->count();
            $atGoal  = $sample->filter(fn ($l) => $l->systolic < 140 && $l->diastolic < 90)->count();
            $goalPct = (int) round($atGoal / $count * 100);

            $stats = compact('count', 'avgSys', 'avgDia', 'goalPct', 'stage2', 'crisis', 'low', 'windowDays');

            if ($crisis > 0) {
                $messages[] = ['type' => 'warn', 'text' =>
                    "{$crisis} reading" . ($crisis > 1 ? 's' : '') . " in the hypertensive-crisis range (≥180/120). If accompanied by chest pain, breathlessness, weakness or vision change this is an emergency — seek care immediately."];
            }
            if ($low > 0) {
                $messages[] = ['type' => 'info', 'text' =>
                    "{$low} low reading" . ($low > 1 ? 's' : '') . " (below 90/60) — watch for dizziness or falls, especially on standing; the doctor may review BP-medicine doses."];
            }
            if ($count >= 3 && $stage2 / $count >= 0.4) {
                $messages[] = ['type' => 'warn', 'text' =>
                    "BP is often in the Stage-2 range (≥140/90) — discuss intensifying or adjusting blood-pressure medication, salt intake and activity with the doctor."];
            }

            if ($goalPct >= 75) {
                $messages[] = ['type' => 'good', 'text' => "Good control — {$goalPct}% of recent readings are under 140/90. Keep up the current routine."];
            } elseif ($goalPct >= 50) {
                $messages[] = ['type' => 'info', 'text' => "Fair control — {$goalPct}% of readings under 140/90. Consistent timing of readings and medicines helps."];
            } else {
                $messages[] = ['type' => 'warn', 'text' => "Only {$goalPct}% of recent readings are under 140/90 — please review the treatment plan with the doctor."];
            }
        }

        $last = $logs->sortByDesc('logged_at')->first();
        if (!$last || $last->logged_at->lt($now->copy()->subDays(14))) {
            $messages[] = ['type' => 'info', 'text' =>
                "No BP readings logged in the last 2 weeks. Measuring at the same times each day (e.g. morning and evening) gives a clearer picture."];
        }

        return ['stats' => $stats, 'messages' => $messages];
    }
}
