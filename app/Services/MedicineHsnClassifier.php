<?php

namespace App\Services;

use App\Models\GstRate;
use App\Models\Medicine;

/**
 * Heuristic classifier that maps a medicine to an Indian HSN code + GST rate.
 *
 * Rules driven by (a) medicine_category name, (b) keyword matches on the
 * medicine name, (c) name+strength patterns. Returns the catch-all
 * `30049099 @ 12%` for anything unrecognised — 12% is the standard rate for
 * retail pharma under chapter 3004, NOT 0% (the system default).
 *
 * Reference: CBIC GST schedule III (12%) covers most medicaments; schedule II
 * (5%) covers insulin, life-saving drugs, vaccines, ORS, etc.
 */
class MedicineHsnClassifier
{
    /** Returns ['hsn_code' => string, 'gst_rate_id' => int]. */
    public function classify(Medicine $medicine): array
    {
        $name = strtolower($medicine->name);
        $cat  = strtolower($medicine->category?->name ?? '');

        // 1. Insulin → 5%, HSN 30043100
        if (str_contains($name, 'insulin') || str_contains($name, 'humalog') || str_contains($name, 'lantus') || str_contains($name, 'novomix')) {
            return $this->result('30043100', 5);
        }

        // 2. Vaccines / immunisations → 5%, HSN 30022000
        if (str_contains($name, 'vaccine') || str_contains($name, 'vacc.') || str_contains($name, 'immunoglobulin')) {
            return $this->result('30022000', 5);
        }

        // 3. ORS / rehydration salts → 5%, HSN 30049011
        if (str_contains($name, 'ors ') || preg_match('/\bors\b/', $name) || str_contains($name, 'electral') || str_contains($name, 'rehydra')) {
            return $this->result('30049011', 5);
        }

        // 4. Antibiotics — penicillin family → 30041000 @ 12%
        if ($cat === 'antibiotics' || str_contains($name, 'cillin') || str_contains($name, 'augment')) {
            $penicillin = preg_match('/(?:penicil|amoxi|ampi|amox|cloxa|piperacil|augment)/', $name);
            return $penicillin ? $this->result('30041000', 12) : $this->result('30042000', 12);
        }

        // 5. Vitamins & supplements → 30045000 @ 12%
        if (str_contains($cat, 'vitamin') || str_contains($cat, 'supplement')
            || preg_match('/\b(?:vit[ -]?[a-z]\d?|b12|b6|d3|folic|calcium|iron|zinc|magnesium)\b/', $name)
            || str_contains($name, 'multivit')) {
            return $this->result('30045000', 12);
        }

        // 6. Hormones (corticosteroids, thyroid) → 30043200 @ 12%
        if ($cat === 'hormones' || str_contains($name, 'thyroid') || str_contains($name, 'thyronorm')
            || str_contains($name, 'cortis') || str_contains($name, 'prednis') || str_contains($name, 'hydrocort')) {
            return $this->result('30043200', 12);
        }

        // 7. Antidiabetics (non-insulin) → 30049099 @ 12%
        if ($cat === 'antidiabetics' || str_contains($name, 'metformin') || str_contains($name, 'gliclaz') || str_contains($name, 'glimep')) {
            return $this->result('30049099', 12);
        }

        // 8. Topicals / dermatological creams, ointments, dusting powders → 30049099 @ 12%
        if ($cat === 'topicals' || $cat === 'dermatological'
            || str_contains($medicine->dosage_form ?? '', 'Cream')
            || str_contains($medicine->dosage_form ?? '', 'Ointment')
            || str_contains($name, 'cream') || str_contains($name, 'oint')
            || str_contains($name, 'dusting powder') || str_contains($name, 'lotion') || str_contains($name, 'gel')) {
            return $this->result('30049099', 12);
        }

        // 9. Ayurvedic / Homoeopathic → 30049011 @ 12%
        if (preg_match('/\b(?:ayur|himalaya|tulsi|ashwa|brahmi|himcocid|liv\s*52|homoe?o)\b/i', $name)) {
            return $this->result('30049011', 12);
        }

        // 10. Catch-all: most retail pharma → 30049099 @ 12%
        return $this->result('30049099', 12);
    }

    /** @return array{hsn_code:string, gst_rate_id:int} */
    private function result(string $hsn, float $rate): array
    {
        $gstId = GstRate::where('rate', $rate)->value('id');
        if (!$gstId) {
            // If the exact rate isn't seeded, fall back to 12% (always seeded)
            $gstId = GstRate::where('rate', 12)->value('id') ?? 1;
        }
        return ['hsn_code' => $hsn, 'gst_rate_id' => $gstId];
    }
}
