<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An exercise/activity entry with duration and calories burned. Patient-scoped.
 */
class ExerciseLog extends Model
{
    protected $fillable = ['patient_id', 'logged_at', 'activity', 'minutes', 'calories_burned', 'notes', 'recorded_by'];

    protected $casts = [
        'logged_at'       => 'datetime',
        'minutes'         => 'integer',
        'calories_burned' => 'integer',
    ];

    /**
     * MET (metabolic equivalent) values for common activities; used to estimate
     * calories burned: kcal = MET × 3.5 × weightKg / 200 × minutes.
     * Buyer-added activities persist as RxOption(field=exercise_item).
     */
    public const ACTIVITIES = [
        'Walking (casual)'   => 3.0,
        'Brisk walking'      => 5.0,
        'Jogging'            => 7.0,
        'Running'            => 9.8,
        'Cycling (moderate)' => 6.8,
        'Swimming'           => 7.0,
        'Yoga'               => 2.5,
        'Strength training'  => 5.0,
        'Skipping'           => 11.0,
        'Dancing'            => 5.0,
        'Cricket'            => 5.0,
        'Household chores'   => 3.0,
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    /** kcal burned for an activity given minutes and body weight (kg). */
    public static function estimateCalories(string $activity, ?int $minutes, ?float $weightKg): ?int
    {
        $met = self::ACTIVITIES[$activity] ?? null;
        if (!$met || !$minutes || !$weightKg) return null;
        return (int) round($met * 3.5 * $weightKg / 200 * $minutes);
    }
}
