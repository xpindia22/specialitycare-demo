<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Diagnosis extends Model {
    protected $fillable = ['encounter_id','icd_code','diagnosis_name','type','status','notes'];
    public function encounter() { return $this->belongsTo(Encounter::class); }
}
