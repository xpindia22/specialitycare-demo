<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['staff_id','doctor_id','date','status','check_in','check_out','hours','notes','marked_by'];
    protected $casts = ['date' => 'date', 'hours' => 'decimal:2'];

    const STATUSES = [
        'present'       => ['label' => 'Present',       'color' => '#16a34a', 'bg' => '#dcfce7', 'paid' => true,  'days' => 1.0],
        'half_day'      => ['label' => 'Half-Day',      'color' => '#0891b2', 'bg' => '#cffafe', 'paid' => true,  'days' => 0.5],
        'absent'        => ['label' => 'Absent',        'color' => '#dc2626', 'bg' => '#fee2e2', 'paid' => false, 'days' => 0.0],
        'paid_leave'    => ['label' => 'Paid Leave',    'color' => '#7c3aed', 'bg' => '#ede9fe', 'paid' => true,  'days' => 1.0],
        'unpaid_leave'  => ['label' => 'Unpaid Leave',  'color' => '#b45309', 'bg' => '#fef3c7', 'paid' => false, 'days' => 0.0],
        'week_off'      => ['label' => 'Week Off',      'color' => '#475569', 'bg' => '#e2e8f0', 'paid' => true,  'days' => 1.0],
        'holiday'       => ['label' => 'Holiday',       'color' => '#0f766e', 'bg' => '#ccfbf1', 'paid' => true,  'days' => 1.0],
    ];

    public function staff()    { return $this->belongsTo(Staff::class); }
    public function doctor()   { return $this->belongsTo(Doctor::class); }
    public function markedBy() { return $this->belongsTo(User::class, 'marked_by'); }

    public function subject()
    {
        return $this->doctor_id ? $this->doctor : $this->staff;
    }
}
