<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IcuInfusionLog extends Model
{
    protected $fillable = ['icu_infusion_id', 'logged_at', 'action', 'dose', 'rate_ml_h', 'note', 'user_id'];
    protected $casts = ['logged_at' => 'datetime', 'dose' => 'float', 'rate_ml_h' => 'float'];

    const ACTIONS = ['start' => 'Started', 'change' => 'Rate changed', 'new_syringe' => 'New syringe', 'stop' => 'Stopped'];

    public function infusion() { return $this->belongsTo(IcuInfusion::class, 'icu_infusion_id'); }
    public function user()     { return $this->belongsTo(User::class); }
}
