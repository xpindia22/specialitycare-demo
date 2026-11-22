<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Diagnosis extends Model {
    protected $fillable = ['encounter_id','icd_code','diagnosis_name','type','status','notes'];
    public function encounter() { return $this->belongsTo(Encounter::class); }
    protected static function booted(): void {
        // Rows of a deleted (hidden) visit stay out of every list and report
        static::addGlobalScope('liveVisit', fn ($q) => $q->where(fn ($w) =>
            $w->whereNull('diagnoses.encounter_id')->orWhereHas('encounter')));
    }
}
