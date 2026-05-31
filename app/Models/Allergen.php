<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allergen extends Model
{
    protected $fillable = ['name', 'category', 'is_active', 'sort_order'];

    const CATEGORIES = [
        'drugs'         => ['label' => 'Drugs & Medications', 'icon' => 'fa-pills',        'color' => '#ef4444'],
        'foods'         => ['label' => 'Foods',               'icon' => 'fa-utensils',     'color' => '#f59e0b'],
        'environmental' => ['label' => 'Environmental',       'icon' => 'fa-leaf',         'color' => '#22c55e'],
        'other'         => ['label' => 'Other',               'icon' => 'fa-circle-dot',   'color' => '#8b5cf6'],
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }

    public static function flatList(): array {
        return static::active()->orderBy('category')->orderBy('sort_order')->orderBy('name')->pluck('name')->toArray();
    }
}
