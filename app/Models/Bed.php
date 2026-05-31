<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $fillable = ['ward_id','bed_number','type','status','notes','is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    const STATUSES = [
        'available'   => ['label' => 'Available',    'color' => 'green'],
        'occupied'    => ['label' => 'Occupied',     'color' => 'red'],
        'reserved'    => ['label' => 'Reserved',     'color' => 'amber'],
        'maintenance' => ['label' => 'Maintenance',  'color' => 'grey'],
    ];

    public function ward()       { return $this->belongsTo(Ward::class); }
    public function admission()  { return $this->hasOne(Admission::class)->where('status','admitted'); }

    public function getStatusLabelAttribute(): string {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }
    public function getStatusColorAttribute(): string {
        return self::STATUSES[$this->status]['color'] ?? 'grey';
    }
}
