<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IoEntry extends Model
{
    protected $fillable = [
        'admission_id', 'io_type', 'category', 'amount_ml', 'remarks', 'recorded_at', 'recorded_by',
    ];

    protected $casts = ['recorded_at' => 'datetime'];

    const INPUT_CATEGORIES = [
        'oral'      => 'Oral / PO',
        'iv_fluid'  => 'IV Fluid',
        'blood'     => 'Blood / Blood Product',
        'ng_tube'   => 'NG Tube / RT Feed',
        'other_in'  => 'Other Input',
    ];

    const OUTPUT_CATEGORIES = [
        'urine'      => 'Urine',
        'drain'      => 'Drain / Wound',
        'vomit'      => 'Vomit / Aspirate',
        'stool'      => 'Stool',
        'other_out'  => 'Other Output',
    ];

    public static function allCategories(): array
    {
        return self::INPUT_CATEGORIES + self::OUTPUT_CATEGORIES;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::allCategories()[$this->category] ?? ucfirst($this->category);
    }

    public function admission()  { return $this->belongsTo(Admission::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
}
