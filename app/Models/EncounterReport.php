<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncounterReport extends Model
{
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'title',
        'report_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_by',
        'source_system',
        'source_id',
        'source_url',
    ];

    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getFileSizeLabelAttribute(): string
    {
        if (!$this->file_size) return 'Unknown size';
        if ($this->file_size >= 1048576) return number_format($this->file_size / 1048576, 1) . ' MB';
        return number_format($this->file_size / 1024, 0) . ' KB';
    }
}
