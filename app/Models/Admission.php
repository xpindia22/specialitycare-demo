<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Admission extends Model
{
    protected $fillable = [
        'ipd_no', 'patient_id', 'bed_id', 'ward_id', 'admitting_doctor_id',
        'admission_type', 'admission_date', 'provisional_diagnosis',
        'referred_by', 'referral_source_id', 'notes', 'status', 'discharge_date',
        'discharge_condition', 'discharge_notes', 'discharge_summary', 'created_by',
        'is_mlc', 'mlc_no', 'mlc_police_station', 'mlc_notes',
    ];

    protected $casts = [
        'admission_date'  => 'datetime',
        'discharge_date'  => 'datetime',
        'is_mlc'          => 'boolean',
    ];

    const ADMISSION_TYPES = [
        'emergency' => 'Emergency',
        'elective'  => 'Elective',
        'referral'  => 'Referral',
    ];

    const STATUSES = [
        'admitted'    => ['label' => 'Admitted',    'color' => 'teal'],
        'discharged'  => ['label' => 'Discharged',  'color' => 'green'],
        'transferred' => ['label' => 'Transferred', 'color' => 'amber'],
        'absconded'   => ['label' => 'Absconded',   'color' => 'red'],
    ];

    const DISCHARGE_CONDITIONS = [
        'improved'    => 'Improved',
        'recovered'   => 'Recovered',
        'lama'        => 'LAMA (Left Against Medical Advice)',
        'expired'     => 'Expired',
        'transferred' => 'Transferred',
    ];

    public function patient()         { return $this->belongsTo(Patient::class); }
    public function referralSource()  { return $this->belongsTo(ReferralSource::class); }
    public function bed()             { return $this->belongsTo(Bed::class); }
    public function ward()            { return $this->belongsTo(Ward::class); }
    public function admittingDoctor() { return $this->belongsTo(Doctor::class, 'admitting_doctor_id'); }
    public function createdBy()       { return $this->belongsTo(User::class, 'created_by'); }
    public function notes()           { return $this->hasMany(IpdNote::class)->orderByDesc('noted_at'); }
    public function vitals()          { return $this->hasMany(IpdVitals::class)->orderByDesc('recorded_at'); }
    public function charges()         { return $this->hasMany(IpdCharge::class)->orderBy('charge_date')->orderBy('id'); }
    public function prescriptions()   { return $this->hasMany(IpdPrescription::class)->orderBy('drug_name'); }
    public function ioEntries()       { return $this->hasMany(\App\Models\IoEntry::class)->orderByDesc('recorded_at'); }
    public function nursingShifts()   { return $this->hasMany(\App\Models\NursingShift::class)->orderByDesc('shift_date'); }
    public function nursingTasks()    { return $this->hasMany(\App\Models\NursingTask::class)->orderBy('due_at')->orderBy('id'); }
    public function dietCharts()      { return $this->hasMany(\App\Models\DietChart::class)->orderByDesc('date'); }
    public function consents()          { return $this->hasMany(\App\Models\ConsentForm::class); }
    public function insuranceClaims()   { return $this->hasMany(\App\Models\InsuranceClaim::class); }
    public function packages()          { return $this->hasMany(\App\Models\AdmissionPackage::class); }
    public function deposits()        { return $this->hasMany(IpdDeposit::class)->orderByDesc('created_at'); }
    public function radiologyOrders() { return $this->hasMany(RadiologyOrder::class)->orderByDesc('created_at'); }
    public function operations()      { return $this->hasMany(Operation::class)->orderByDesc('operation_date'); }
    public function handoverNotes()   { return $this->hasMany(\App\Models\HandoverNote::class)->latest(); }
    public function preauths()        { return $this->hasMany(\App\Models\InsurancePreauth::class)->latest(); }

    public function getTotalChargesAttribute(): float {
        return (float) $this->charges()->sum('amount');
    }

    public function getTotalDepositsAttribute(): float {
        return (float) $this->deposits()->sum('amount');
    }

    public function getStatusLabelAttribute(): string {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }
    public function getStatusColorAttribute(): string {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }
    public function getDaysAdmittedAttribute(): int {
        $end = $this->discharge_date ?? now();
        return (int) $this->admission_date->diffInDays($end) + 1;
    }

    public static function generateIpdNo(): string {
        $prefix = 'IPD/' . now()->format('Y/');
        $last = static::where('ipd_no', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last && preg_match('/(\d+)$/', $last->ipd_no, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
