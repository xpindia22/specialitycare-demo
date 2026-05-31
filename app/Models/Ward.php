<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $fillable = ['name','type','floor','description','is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    const TYPES = [
        'general'      => 'General',
        'semi_private' => 'Semi-Private',
        'private'      => 'Private',
        'icu'          => 'ICU',
        'hdu'          => 'HDU',
        'emergency'    => 'Emergency',
    ];

    public function beds() { return $this->hasMany(Bed::class); }

    public function getAvailableBedsCountAttribute(): int {
        return $this->beds->where('status','available')->where('is_active',true)->count();
    }
    public function getOccupiedBedsCountAttribute(): int {
        return $this->beds->where('status','occupied')->count();
    }
    public function getTotalBedsCountAttribute(): int {
        return $this->beds->where('is_active',true)->count();
    }
    public function getTypeLabelAttribute(): string {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
