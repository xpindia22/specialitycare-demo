<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DischargeClearance extends Model
{
    protected $fillable = ['admission_id', 'item', 'note', 'cleared_by', 'cleared_at'];
    protected $casts = ['cleared_at' => 'datetime'];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function user()      { return $this->belongsTo(User::class, 'cleared_by'); }
}
