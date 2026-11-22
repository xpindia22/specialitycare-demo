<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Department extends Model {
    protected $fillable = ['name','code','description','icon','color','room','token_prefix','show_online','sort_order','is_active'];
    protected $casts = ['is_active'=>'boolean','show_online'=>'boolean'];
    /** Doctors whose primary department this is */
    public function doctors() { return $this->hasMany(Doctor::class); }
    /** Doctors who also work in this department */
    public function extraDoctors() { return $this->belongsToMany(Doctor::class); }

    /** Every active doctor in this department (primary or additional), by name */
    public function allDoctors()
    {
        return Doctor::where('is_active', true)
            ->where(fn ($q) => $q->where('department_id', $this->id)
                ->orWhereHas('departments', fn ($d) => $d->where('departments.id', $this->id)))
            ->orderBy('first_name')->get();
    }

    public function scopeOrdered($q) { return $q->orderBy('sort_order')->orderBy('name'); }
}
