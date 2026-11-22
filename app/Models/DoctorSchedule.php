<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
/** One OPD session in a doctor's weekly timetable (a doctor may have several a day). */
class DoctorSchedule extends Model {
    protected $fillable = ['doctor_id','day_of_week','start_time','end_time','slot_minutes','max_patients','department_id','room'];
    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'];
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function department() { return $this->belongsTo(Department::class); }
}
