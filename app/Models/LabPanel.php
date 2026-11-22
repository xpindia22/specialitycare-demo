<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabPanel extends Model {
    protected $fillable = ['name','short_code','description','price','profit','turnaround_hours','is_active','is_pinned','gender','min_age','max_age'];
    protected $casts = ['is_active'=>'boolean','is_pinned'=>'boolean','price'=>'decimal:2','profit'=>'decimal:2','min_age'=>'integer','max_age'=>'integer'];
    public function tests() { return $this->belongsToMany(LabTest::class,'lab_panel_tests','lab_panel_id','lab_test_id'); }

    /** True if targeted at a specific sex/age (e.g. a yearly checkup). */
    public function isTargeted(): bool { return ($this->gender && $this->gender !== 'all') || $this->min_age !== null || $this->max_age !== null; }

    /** Does this panel suit the given patient (sex + age)? Untargeted panels suit everyone. */
    public function matchesPatient($patient): bool
    {
        if (!$patient) return true;
        $g = $this->gender ?: 'all';
        if ($g !== 'all' && strtolower((string) $patient->gender) !== $g) return false;
        $age = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null;
        if ($age !== null) {
            if ($this->min_age !== null && $age < $this->min_age) return false;
            if ($this->max_age !== null && $age > $this->max_age) return false;
        }
        return true;
    }

    /** Short badge like "♂ ≤40" / "♀ 40+" for targeted panels. */
    public function targetBadge(): string
    {
        if (!$this->isTargeted()) return '';
        $sex = $this->gender === 'male' ? '♂' : ($this->gender === 'female' ? '♀' : '');
        $age = ($this->min_age && $this->max_age) ? "{$this->min_age}–{$this->max_age}"
             : ($this->max_age ? "≤{$this->max_age}" : ($this->min_age ? "{$this->min_age}+" : ''));
        return trim($sex.' '.$age);
    }
}
