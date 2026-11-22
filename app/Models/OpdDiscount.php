<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdDiscount extends Model
{
    protected $fillable = ['name', 'percent', 'age_min', 'age_max', 'is_active'];
    protected $casts = ['percent' => 'decimal:2', 'is_active' => 'boolean'];

    public function getIsAutoAttribute(): bool { return $this->age_min !== null || $this->age_max !== null; }

    public function appliesToAge(?int $age): bool
    {
        if (!$this->is_auto || $age === null) return false;
        return ($this->age_min === null || $age >= $this->age_min) && ($this->age_max === null || $age <= $this->age_max);
    }
}
