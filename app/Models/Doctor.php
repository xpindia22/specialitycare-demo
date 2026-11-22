<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'doctor_id', 'first_name', 'last_name', 'gender',
        'date_of_birth', 'phone', 'alt_phone', 'email', 'address',
        'city', 'state', 'pincode', 'specialization', 'department_id',
        'qualification', 'registration_no', 'experience_years', 'photo',
        'signature', 'consultation_fee', 'bio', 'available_days',
        'start_time', 'end_time', 'slot_duration', 'is_active', 'auto_present',
        'languages', 'show_online',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'available_days' => 'array',
        'is_active' => 'boolean',
        'auto_present' => 'boolean',
        'show_online' => 'boolean',
        'consultation_fee' => 'decimal:2',
    ];

    public function attendances() { return $this->hasMany(Attendance::class); }

    public function getFullNameAttribute(): string
    {
        return "Dr. {$this->first_name} {$this->last_name}";
    }

    public function user() { return $this->belongsTo(User::class); }
    public function department() { return $this->belongsTo(Department::class); }
    /** Additional departments (the primary one is department_id) */
    public function departments() { return $this->belongsToMany(Department::class); }
    public function schedules() { return $this->hasMany(DoctorSchedule::class)->orderBy('day_of_week')->orderBy('start_time'); }
    public function leaves() { return $this->hasMany(DoctorLeave::class)->orderByDesc('from_date'); }

    /** Primary + additional department ids */
    public function departmentIds(): array
    {
        return array_values(array_unique(array_filter(array_merge([$this->department_id], $this->departments->pluck('id')->all()))));
    }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function encounters() { return $this->hasMany(Encounter::class); }

    public static function generateDoctorId(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->doctor_id, -4) + 1 : 1;
        return 'SC/DOC/' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
