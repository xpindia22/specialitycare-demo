<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPackage extends Model
{
    protected $fillable = [
        'name', 'category', 'description', 'package_price',
        'includes_room', 'includes_medicines', 'includes_investigations', 'includes_ot',
        'max_days', 'is_active', 'created_by',
    ];

    protected $casts = [
        'package_price'            => 'decimal:2',
        'includes_room'            => 'boolean',
        'includes_medicines'       => 'boolean',
        'includes_investigations'  => 'boolean',
        'includes_ot'              => 'boolean',
    ];

    const CATEGORIES = [
        'maternity'   => 'Maternity',
        'surgery'     => 'General Surgery',
        'cardiac'     => 'Cardiac',
        'ortho'       => 'Orthopaedic',
        'neuro'       => 'Neurology',
        'oncology'    => 'Oncology',
        'paediatric'  => 'Paediatric',
        'dialysis'    => 'Dialysis',
        'icu'         => 'ICU Care',
        'other'       => 'Other',
    ];

    public function items()       { return $this->hasMany(BillingPackageItem::class, 'package_id'); }
    public function admissions()  { return $this->hasMany(AdmissionPackage::class, 'package_id'); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }

    public function getIncludesLabelAttribute(): string
    {
        $inc = [];
        if ($this->includes_room)           $inc[] = 'Room';
        if ($this->includes_medicines)      $inc[] = 'Medicines';
        if ($this->includes_investigations) $inc[] = 'Investigations';
        if ($this->includes_ot)             $inc[] = 'OT';
        return $inc ? implode(', ', $inc) : 'None specified';
    }
}
