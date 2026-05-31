<?php
// ============================================================
// FILE: app/Models/Patient.php
// ============================================================
namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model implements Authenticatable
{
    use HasFactory;
    use AuthenticatableTrait;

    protected $fillable = [
        'jhc_id', 'openemr_pid', 'first_name', 'last_name', 'middle_name',
        'gender', 'date_of_birth', 'blood_group', 'phone', 'alt_phone',
        'email', 'address', 'city', 'state', 'pincode', 'country',
        'aadhar_no', 'emergency_contact_name', 'emergency_contact_phone',
        'emergency_contact_relation', 'occupation', 'marital_status',
        'religion', 'nationality', 'referred_by', 'photo', 'is_active',
        'insurance_company', 'policy_no', 'tpa_name', 'insurance_valid_until',
        'referral_source_id', 'allergies', 'is_chronic',
        'portal_enabled', 'portal_pin', 'portal_pin_set_at',
    ];

    protected $hidden = ['portal_pin'];

    protected $casts = [
        'date_of_birth' => 'date', 'is_active' => 'boolean', 'insurance_valid_until' => 'date',
        'is_chronic' => 'boolean',
        'portal_enabled' => 'boolean', 'portal_pin_set_at' => 'datetime',
    ];

    // ── Patient portal auth (dedicated `patient` guard) ───────────────────
    /** The PIN hash is the auth password for the patient guard. */
    public function getAuthPasswordName(): string { return 'portal_pin'; }
    public function getAuthPassword() { return (string) $this->portal_pin; }
    /** Patients don't use "remember me" — there is no remember_token column. */
    public function getRememberTokenName() { return ''; }

    /** Stable synthetic email used as the phpBB SSO match key for patients. */
    public function forumSsoEmail(): string
    {
        return 'pt' . $this->id . '@patient.forum.local';
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getAgeAttribute(): string
    {
        if (!$this->date_of_birth) return 'N/A';
        $dob = \Carbon\Carbon::parse($this->date_of_birth);
        $now = now();
        $years = (int) $dob->diffInYears($now);
        if ($years < 1) {
            $months = $dob->diffInMonths($now);
            return "{$months} months";
        }
        return "{$years} yrs";
    }

    public function getAgeYearsAttribute(): int
    {
        if (!$this->date_of_birth) return 40;
        return (int) \Carbon\Carbon::parse($this->date_of_birth)->diffInYears(now());
    }

    public function patientAllergies() { return $this->hasMany(\App\Models\PatientAllergy::class)->orderBy('allergen'); }
    public function dietPrescriptions() { return $this->hasMany(\App\Models\DietPrescription::class)->latest(); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function encounters() { return $this->hasMany(Encounter::class); }
    public function communications() { return $this->hasMany(\App\Models\PatientCommunication::class)->latest('occurred_at'); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
    public function bills() { return $this->hasMany(Bill::class); }
    public function labOrders() { return $this->hasMany(LabOrder::class); }
    public function clinicalNotes() { return $this->hasMany(ClinicalNote::class); }
    public function ecgRecords() { return $this->hasMany(EcgRecord::class); }
    public function echoRecords() { return $this->hasMany(EchoRecord::class); }
    public function admissions()       { return $this->hasMany(Admission::class); }
    public function referralSource()   { return $this->belongsTo(ReferralSource::class); }
    public function radiologyOrders()  { return $this->hasMany(RadiologyOrder::class); }

    public static function generateJhcId(): string
    {
        $prefix = setting('jhc_id_prefix', 'JHC');
        $digits = (int) setting('jhc_id_digits', 5);
        $last = static::orderBy('id', 'desc')->first();
        if ($last && preg_match('/\d+$/', $last->jhc_id, $m)) {
            $nextNum = (int)$m[0] + 1;
        } else {
            $nextNum = 1;
        }
        return $prefix . str_pad($nextNum, $digits, '0', STR_PAD_LEFT);
    }
}
