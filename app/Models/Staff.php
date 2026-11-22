<?php
// ============================================================
// FILE: app/Models/Staff.php
// ============================================================
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Staff extends Model {
    protected $fillable = [
        'user_id','staff_id','first_name','last_name','gender','date_of_birth',
        'phone','alt_phone','email','address','city','state','pincode',
        'designation','department','joining_date','qualification','photo',
        'aadhar_no','pan_no','salary','is_active','auto_present'
    ];
    protected $casts = ['date_of_birth'=>'date','joining_date'=>'date','is_active'=>'boolean','auto_present'=>'boolean'];
    public function getFullNameAttribute() { return "{$this->first_name} {$this->last_name}"; }
    public function user() { return $this->belongsTo(User::class); }
    public function attendances()         { return $this->hasMany(Attendance::class); }
    public function payrollAdjustments()  { return $this->hasMany(PayrollAdjustment::class); }
    public function payrollRuns()         { return $this->hasMany(PayrollRun::class); }
    public static function generateStaffId(): string {
        $last = static::orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->staff_id,-4)+1 : 1;
        return 'SC/STF/'.str_pad($num,4,'0',STR_PAD_LEFT);
    }
}
