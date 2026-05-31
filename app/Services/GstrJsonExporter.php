<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Emits GSTN-portal-accepted JSON for GSTR-1 and GSTR-3B returns.
 *
 * Scope (clinic profile — walk-in patients, no GSTIN-holding customers):
 *   - GSTR-1: b2cs (small B2C summarised by state × rate) + hsn summary
 *   - GSTR-3B: outward (sup_details.osup_det + osup_nil_exmp) + ITC (itc_elg)
 *
 * **Schema caveat:** GSTN updates the JSON schema periodically (typically
 * 1-2× per year). Validate against the free GST Offline Tool before relying
 * on the output for an actual filing.
 */
class GstrJsonExporter
{
    private const SCHEMA_VERSION = 'GST3.2.4';

    public function gstr1(Carbon $from, Carbon $to): array
    {
        $clinicGstin = (string) setting('clinic_gstin');
        $clinicState = (string) (setting('clinic_state_code') ?: '');

        // Per-voucher sales rows (mirrors GstReportsController::salesRows but in here for self-containment)
        $rows = DB::table('voucher_entries as ve')
            ->join('vouchers as v',            'v.id',  '=', 've.voucher_id')
            ->join('voucher_types as vt',      'vt.id', '=', 'v.voucher_type_id')
            ->leftJoin('account_ledgers as p', 'p.id',  '=', 'v.party_ledger_id')
            ->where('v.status', 'Posted')
            ->where('vt.code', 'SAL')
            ->whereBetween('v.voucher_date', [$from, $to])
            ->select(
                'p.gstin as party_gstin',
                'p.state_code as party_state',
                've.gst_rate', 've.taxable_amount', 've.cgst_amount',
                've.sgst_amount', 've.igst_amount', 've.cess_amount'
            )
            ->get();

        // ── b2cs: small B2C summarised by (place-of-supply, rate, supply type)
        $b2csBuckets = [];
        $grossTurnoverInclTax = 0.0;

        foreach ($rows as $r) {
            if (!empty($r->party_gstin)) continue; // skip B2B (out of scope for this clinic profile)

            $rate    = round((float) $r->gst_rate, 2);
            $pos     = $r->party_state ?: $clinicState; // POS defaults to clinic state for walk-ins
            $isIntra = !$pos || trim($pos) === trim($clinicState);
            $sply_ty = $isIntra ? 'INTRA' : 'INTER';
            $key     = "{$sply_ty}|{$pos}|{$rate}";

            if (!isset($b2csBuckets[$key])) {
                $b2csBuckets[$key] = [
                    'sply_ty' => $sply_ty, 'pos' => str_pad((string) $pos, 2, '0', STR_PAD_LEFT),
                    'typ' => 'OE', 'rt' => $rate,
                    'txval' => 0.0, 'iamt' => 0.0, 'camt' => 0.0, 'samt' => 0.0, 'csamt' => 0.0,
                ];
            }
            $b2csBuckets[$key]['txval'] += (float) $r->taxable_amount;
            $b2csBuckets[$key]['iamt']  += (float) $r->igst_amount;
            $b2csBuckets[$key]['camt']  += (float) $r->cgst_amount;
            $b2csBuckets[$key]['samt']  += (float) $r->sgst_amount;
            $b2csBuckets[$key]['csamt'] += (float) $r->cess_amount;

            $grossTurnoverInclTax += (float) $r->taxable_amount
                + (float) $r->cgst_amount + (float) $r->sgst_amount
                + (float) $r->igst_amount + (float) $r->cess_amount;
        }
        $b2cs = array_values(array_map(fn($b) => array_map(fn($v) => is_float($v) ? round($v, 2) : $v, $b), $b2csBuckets));

        // ── hsn: HSN-wise summary, split B2B/B2C per schema 3.2.2+ (HSN bifurcation)
        $hsnRowsRaw = DB::table('voucher_entries as ve')
            ->join('vouchers as v',            'v.id',  '=', 've.voucher_id')
            ->join('voucher_types as vt',      'vt.id', '=', 'v.voucher_type_id')
            ->leftJoin('account_ledgers as p', 'p.id',  '=', 'v.party_ledger_id')
            ->where('v.status', 'Posted')
            ->where('vt.code', 'SAL')
            ->whereBetween('v.voucher_date', [$from, $to])
            ->whereNotNull('ve.hsn_sac')
            ->select(
                've.hsn_sac', 've.gst_rate',
                DB::raw('IF(p.gstin IS NOT NULL AND p.gstin <> "", "B2B", "B2C") as channel'),
                DB::raw('SUM(ve.taxable_amount) as txval'),
                DB::raw('SUM(ve.cgst_amount)    as camt'),
                DB::raw('SUM(ve.sgst_amount)    as samt'),
                DB::raw('SUM(ve.igst_amount)    as iamt'),
                DB::raw('SUM(ve.cess_amount)    as csamt'),
                DB::raw('COUNT(*) as line_count')
            )
            ->groupBy('ve.hsn_sac', 've.gst_rate', 'channel')
            ->get();

        $hsnB2b = [];
        $hsnB2c = [];
        $iB2b = $iB2c = 1;
        foreach ($hsnRowsRaw as $h) {
            $row = [
                'num'    => 0, // re-numbered below
                'hsn_sc' => (string) $h->hsn_sac,
                'desc'   => '', // optional; portal accepts empty
                'uqc'    => 'NOS',
                'qty'    => (int) $h->line_count, // placeholder (AATO < ₹5 cr → exact qty not required)
                'rt'     => round((float) $h->gst_rate, 2),
                'txval'  => round((float) $h->txval, 2),
                'iamt'   => round((float) $h->iamt, 2),
                'camt'   => round((float) $h->camt, 2),
                'samt'   => round((float) $h->samt, 2),
                'csamt'  => round((float) $h->csamt, 2),
            ];
            if ($h->channel === 'B2B') {
                $row['num'] = $iB2b++;
                $hsnB2b[] = $row;
            } else {
                $row['num'] = $iB2c++;
                $hsnB2c[] = $row;
            }
        }

        return [
            'gstin'   => $clinicGstin,
            'fp'      => $from->format('mY'),
            'version' => self::SCHEMA_VERSION,
            'hash'    => 'hash',
            'gt'      => round($grossTurnoverInclTax, 2),
            'cur_gt'  => round($grossTurnoverInclTax, 2),
            'b2cs'    => $b2cs,
            'hsn'     => [
                'hsn_b2b' => ['data' => $hsnB2b],
                'hsn_b2c' => ['data' => $hsnB2c],
            ],
        ];
    }

    public function gstr3b(Carbon $from, Carbon $to): array
    {
        $clinicGstin = (string) setting('clinic_gstin');
        $outward = $this->sumByVoucherCode($from, $to, 'SAL');
        $inward  = $this->sumByVoucherCode($from, $to, 'PUR');

        return [
            'gstin'      => $clinicGstin,
            'ret_period' => $from->format('mY'),
            'sup_details' => [
                'osup_det' => [
                    'txval' => $outward['taxable'],
                    'iamt'  => $outward['igst'],
                    'camt'  => $outward['cgst'],
                    'samt'  => $outward['sgst'],
                    'csamt' => $outward['cess'],
                ],
                'osup_zero'    => ['txval' => 0, 'iamt' => 0, 'csamt' => 0],
                'osup_nil_exmp'=> ['txval' => 0],
                'isup_rev'     => ['txval' => 0, 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                'osup_nongst'  => ['txval' => 0, 'iamt' => 0],
            ],
            'inter_sup' => [
                'unreg_details' => [],
                'comp_details'  => [],
                'uin_details'   => [],
            ],
            'itc_elg' => [
                'itc_avl' => [
                    ['ty' => 'IMPG', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                    ['ty' => 'IMPS', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                    ['ty' => 'ISRC', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                    ['ty' => 'ISD',  'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                    ['ty' => 'OTH',  'iamt' => $inward['igst'], 'camt' => $inward['cgst'], 'samt' => $inward['sgst'], 'csamt' => $inward['cess']],
                ],
                'itc_rev' => [
                    ['ty' => 'RUL', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                    ['ty' => 'OTH', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                ],
                'itc_net' => [
                    'iamt' => $inward['igst'],
                    'camt' => $inward['cgst'],
                    'samt' => $inward['sgst'],
                    'csamt'=> $inward['cess'],
                ],
                'itc_inelg' => [
                    ['ty' => 'RUL', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                    ['ty' => 'OTH', 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0],
                ],
            ],
            'inward_sup' => [
                'isup_details' => [
                    ['ty' => 'GST',    'inter' => 0, 'intra' => 0],
                    ['ty' => 'NONGST', 'inter' => 0, 'intra' => 0],
                ],
            ],
        ];
    }

    private function sumByVoucherCode(Carbon $from, Carbon $to, string $code): array
    {
        $r = DB::table('voucher_entries as ve')
            ->join('vouchers as v',       'v.id',  '=', 've.voucher_id')
            ->join('voucher_types as vt', 'vt.id', '=', 'v.voucher_type_id')
            ->where('v.status', 'Posted')
            ->where('vt.code', $code)
            ->whereBetween('v.voucher_date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(ve.taxable_amount),0) as taxable,
                COALESCE(SUM(ve.cgst_amount),0)    as cgst,
                COALESCE(SUM(ve.sgst_amount),0)    as sgst,
                COALESCE(SUM(ve.igst_amount),0)    as igst,
                COALESCE(SUM(ve.cess_amount),0)    as cess
            ')->first();
        return [
            'taxable' => round((float) $r->taxable, 2),
            'cgst'    => round((float) $r->cgst, 2),
            'sgst'    => round((float) $r->sgst, 2),
            'igst'    => round((float) $r->igst, 2),
            'cess'    => round((float) $r->cess, 2),
        ];
    }
}
