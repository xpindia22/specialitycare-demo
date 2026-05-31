<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyRoster extends Model
{
    protected $fillable = ['staff_id', 'week_start', 'shift', 'start_time', 'end_time', 'day_of_week', 'ward_id', 'notes'];

    protected $casts = ['week_start' => 'date'];

    public function getTimeRangeAttribute(): ?string
    {
        if (!$this->start_time || !$this->end_time) return null;
        return \Illuminate\Support\Carbon::parse($this->start_time)->format('H:i')
             . '–' . \Illuminate\Support\Carbon::parse($this->end_time)->format('H:i');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }
}
