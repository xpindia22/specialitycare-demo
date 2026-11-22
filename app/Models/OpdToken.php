<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdToken extends Model
{
    protected $fillable = [
        'token_no','queue','token_date','patient_id','doctor_id','department_id',
        'status','priority','purpose','notes',
        'called_at','consultation_started_at','completed_at','created_by',
    ];

    protected $casts = [
        'token_date'             => 'date',
        'called_at'              => 'datetime',
        'consultation_started_at'=> 'datetime',
        'completed_at'           => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function doctor()   { return $this->belongsTo(Doctor::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function createdBy(){ return $this->belongsTo(User::class, 'created_by'); }
    public function triage()     { return $this->hasOne(TriageRecord::class, 'opd_token_id'); }
    public function encounter()  { return $this->hasOne(Encounter::class, 'opd_token_id'); }
    public function referral()   { return $this->hasOne(OpdReferral::class, 'token_id'); }

    public function getDisplayAttribute(): string {
        return ($this->queue ?: 'T') . '-' . str_pad($this->token_no, 3, '0', STR_PAD_LEFT);
    }

    /** Next number in today's series: '' = clinic-wide T-series, else a department prefix */
    public static function nextTokenNo(string $queue = ''): int {
        $last = static::whereDate('token_date', today())->where('queue', $queue)->max('token_no');
        return ($last ?? 0) + 1;
    }

    /**
     * Department and numbering series for a new token: the chosen department, else the
     * doctor's primary one. A department with a token prefix gets its own series.
     * Returns ['department_id' => ?int, 'queue' => string].
     */
    public static function seriesFor(?int $departmentId, ?int $doctorId): array {
        $departmentId = $departmentId ?: ($doctorId ? Doctor::whereKey($doctorId)->value('department_id') : null);
        $prefix = $departmentId ? strtoupper(trim((string) Department::whereKey($departmentId)->value('token_prefix'))) : '';
        return ['department_id' => $departmentId, 'queue' => $prefix];
    }

    /** Create today's token with the next number in its series (retries if two desks clash) */
    public static function issue(array $attrs): self {
        $series = static::seriesFor($attrs['department_id'] ?? null, $attrs['doctor_id'] ?? null);
        for ($try = 0; ; $try++) {
            try {
                return static::create(array_merge($attrs, $series, [
                    'token_no'   => static::nextTokenNo($series['queue']),
                    'token_date' => today(),
                ]));
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($try >= 4) throw $e;
            }
        }
    }

    public static function todayStats(): array {
        $q = static::whereDate('token_date', today());
        return [
            'total'           => (clone $q)->count(),
            'waiting'         => (clone $q)->where('status','waiting')->count(),
            'in_consultation' => (clone $q)->where('status','in_consultation')->count(),
            'done'            => (clone $q)->where('status','done')->count(),
            'skipped'         => (clone $q)->where('status','skipped')->count(),
        ];
    }
}
