<?php

namespace App\Services;

/**
 * Place-of-supply GST splitter.
 *
 * Indian GST rule:
 *   - Intra-state (supplier and recipient in same state): tax splits equally
 *     into CGST + SGST.
 *   - Inter-state (different states): the full tax goes to IGST.
 *
 * Pass the clinic's state code, the party's state code (nullable for
 * unregistered cash patients — we treat them as intra-state by default),
 * and the taxable amount + rate. Get back the per-bucket amounts.
 *
 *   ['taxable'=>1000, 'rate'=>18, 'cgst'=>90, 'sgst'=>90, 'igst'=>0, 'total_tax'=>180]
 */
class GstSplitService
{
    public function split(float $taxable, float $ratePct, ?string $clinicState, ?string $partyState, float $cessPct = 0.0): array
    {
        $taxable = max(0, round($taxable, 2));
        $ratePct = max(0, $ratePct);

        $totalTax = round($taxable * $ratePct / 100, 2);
        $cess     = round($taxable * $cessPct / 100, 2);

        // No party state recorded → assume intra-state (cash patient walking in).
        $intraState = !$partyState || ($clinicState && trim($partyState) === trim($clinicState));

        if ($intraState) {
            $half = round($totalTax / 2, 2);
            // Distribute rounding remainder so cgst+sgst exactly equals totalTax
            $cgst = $half;
            $sgst = round($totalTax - $cgst, 2);
            $igst = 0.0;
        } else {
            $cgst = 0.0;
            $sgst = 0.0;
            $igst = $totalTax;
        }

        return [
            'taxable'   => $taxable,
            'rate'      => $ratePct,
            'cgst'      => $cgst,
            'sgst'      => $sgst,
            'igst'      => $igst,
            'cess'      => $cess,
            'total_tax' => round($totalTax + $cess, 2),
            'intrastate' => $intraState,
        ];
    }

    public function clinicState(): ?string
    {
        return setting('clinic_state_code') ?: null;
    }
}
