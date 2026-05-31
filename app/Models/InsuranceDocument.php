<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceDocument extends Model
{
    protected $fillable = ['claim_id', 'document_type', 'file_path', 'original_name', 'file_size', 'uploaded_by'];

    const DOCUMENT_TYPES = [
        'discharge_summary'   => 'Discharge Summary',
        'investigation'       => 'Investigation Reports',
        'prescription'        => 'Prescriptions / Treatment Chart',
        'id_proof'            => 'ID Proof (Policy / Member Card)',
        'consent_form'        => 'Consent Forms',
        'operation_notes'     => 'Operation Notes',
        'other'               => 'Other',
    ];

    public function claim()      { return $this->belongsTo(InsuranceClaim::class, 'claim_id'); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getTypeLabelAttribute(): string {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }
    public function getFileSizeHumanAttribute(): string {
        $b = (int) $this->file_size;
        if ($b < 1024) return "{$b} B";
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        return round($b / 1048576, 1) . ' MB';
    }
}
