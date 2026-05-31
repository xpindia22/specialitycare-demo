<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdCharge extends Model
{
    protected $fillable = [
        'admission_id','charge_date','category','description',
        'unit_price','quantity','amount','notes','created_by',
    ];
    protected $casts = [
        'charge_date' => 'date',
        'unit_price'  => 'decimal:2',
        'quantity'    => 'decimal:2',
        'amount'      => 'decimal:2',
    ];

    const CATEGORIES = [
        'room_rent'      => ['label' => 'Room Rent',        'icon' => 'fa-bed',            'color' => 'navy'],
        'nursing_charge' => ['label' => 'Nursing Charge',   'icon' => 'fa-notes-medical',  'color' => 'teal'],
        'doctor_visit'   => ['label' => 'Doctor Visit',     'icon' => 'fa-stethoscope',    'color' => 'teal'],
        'procedure'      => ['label' => 'Procedure',        'icon' => 'fa-syringe',        'color' => 'amber'],
        'investigation'  => ['label' => 'Investigation',    'icon' => 'fa-flask-vial',     'color' => 'purple'],
        'pharmacy'       => ['label' => 'Pharmacy',         'icon' => 'fa-pills',          'color' => 'green'],
        'oxygen'         => ['label' => 'Oxygen',           'icon' => 'fa-lungs',          'color' => 'teal'],
        'diet'           => ['label' => 'Diet',             'icon' => 'fa-utensils',       'color' => 'green'],
        'other'          => ['label' => 'Other',            'icon' => 'fa-ellipsis',       'color' => 'gray'],
    ];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function getCategoryLabelAttribute(): string { return self::CATEGORIES[$this->category]['label'] ?? ucfirst($this->category); }
    public function getCategoryIconAttribute(): string  { return self::CATEGORIES[$this->category]['icon']  ?? 'fa-circle'; }
    public function getCategoryColorAttribute(): string { return self::CATEGORIES[$this->category]['color'] ?? 'gray'; }
}
