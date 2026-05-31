<?php
// FILE: app/Models/DietChart.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietChart extends Model
{
    protected $fillable = ['admission_id', 'meal_time', 'instructions', 'date', 'created_by'];

    protected $casts = [
        'date' => 'date',
    ];

    const MEAL_TIMES = ['morning', 'afternoon', 'evening', 'night'];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
