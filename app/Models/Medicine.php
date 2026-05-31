<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Medicine extends Model {
    protected $fillable = ['name','generic_name','medicine_category_id','dosage_form','strength','unit','unit_price','reorder_level','indications','side_effects','contraindications','drug_interactions','dosage_instructions','manufacturer','hsn_code','gst_rate_id','is_active'];
    protected $casts = ['is_active'=>'boolean','unit_price'=>'decimal:2'];
    public function category() { return $this->belongsTo(MedicineCategory::class,'medicine_category_id'); }
    public function gstRate() { return $this->belongsTo(GstRate::class); }
    public function stocks() { return $this->hasMany(PharmacyStock::class); }
    public function prescriptionItems() { return $this->hasMany(PrescriptionItem::class); }
    public function getFormulationAttribute() { return $this->dosage_form; }

    // Resolves generic name: uses stored generic_name, then brand map, then bracket extraction.
    public function getResolvedGenericAttribute(): ?string {
        if ($this->generic_name) return $this->generic_name;

        // Brand map lookup first (most reliable)
        $n = strtolower($this->name);
        foreach (DrugInteraction::BRAND_MAP as $keyword => $generic) {
            if (str_contains($n, $keyword)) return $generic;
        }

        // Fallback: extract from brackets/parens "DRUG [GENERIC TAB]" → "generic"
        if (preg_match('/[\[\(]([^\[\]\(\)]{3,50})[\]\)]/i', $this->name, $m)) {
            $raw = trim($m[1]);
            $clean = preg_replace('/\b\d+\s*(?:mg|mcg|ml|gm?|iu|%)\b/i', '', $raw);
            $clean = preg_replace('/\b\d+\b/', '', $clean);
            $clean = preg_replace('/\b(?:tab(?:lets?)?|cap(?:sules?)?|inj(?:ection)?|syp|syrup|gel|cream|oint(?:ment)?|forte|plus|retard|xr|mr|sr|od|er|ds|la|xl|rf)\b/i', '', $clean);
            $clean = trim(preg_replace('/\s{2,}/', ' ', strtolower($clean)));
            if (strlen($clean) >= 4) return $clean;
        }

        // Last resort: strip the medicine name of dosage and form info to get a likely generic
        $stripped = preg_replace('/\b\d+\s*(?:mg|mcg|ml|gm?|iu|%)\b/i', '', $this->name);
        $stripped = preg_replace('/\b(?:tab(?:lets?)?|cap(?:sules?)?|inj(?:ection)?|syp|syrup|gel|cream|oint(?:ment)?)\b/i', '', $stripped);
        $stripped = trim(preg_replace('/\s{2,}/', ' ', strtolower($stripped)));
        // Only return if it's a single clean word (likely the generic name = trade name)
        if (preg_match('/^[a-z]{4,20}$/', $stripped)) return $stripped;

        return null;
    }
    public function getTotalStockAttribute(): int {
        return $this->stocks()->where('is_active',true)->where(function($q){ $q->whereNull('expiry_date')->orWhere('expiry_date','>=',now()->toDateString()); })->sum('quantity');
    }
    public function isInStock(): bool { return $this->total_stock > 0; }
    public function getStockStatusAttribute(): string {
        $s = $this->total_stock;
        if ($s == 0) return 'out_of_stock';
        if ($s <= 5) return 'critical';
        if ($s <= $this->reorder_level) return 'low';
        return 'adequate';
    }
}
