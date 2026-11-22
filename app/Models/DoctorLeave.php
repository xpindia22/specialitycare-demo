<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DoctorLeave extends Model {
    protected $fillable = ['doctor_id','from_date','to_date','reason','created_by'];
    protected $casts = ['from_date'=>'date','to_date'=>'date'];
    public function doctor() { return $this->belongsTo(Doctor::class); }
}
