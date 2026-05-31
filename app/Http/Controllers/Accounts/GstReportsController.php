<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Services\GstrJsonExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * GST returns: GSTR-1 (outward supplies), GSTR-3B (summary), HSN-wise summary.
 *
 * These are read-only views derived from voucher_entries.cgst/sgst/igst
 * columns. Sales (vt.code='SAL') feed GSTR-1 + the outward section of 3B;
 * Purchases (vt.code='PUR') feed the inward / ITC section of 3B.
 */
class GstReportsController extends Controller
{
    public function gstr1(Request $request, GstrJsonExporter $exporter)
    {
        [$from, $to, $monthYear] = $this->monthRange($request);

        if ($request->query('format') === 'json') {
            $payload = $exporter->gstr1($from, $to);
            $filename = sprintf('gstr1-%s.json', $from->format('mY'));
            return response()
                ->json($payload, 200, ['Content-Disposition' => "attachment; filename=\"{$filename}\""], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        $rows = $this->salesRows($from, $to);

        // Split into B2B (party has GSTIN) vs B2C
        $b2b = []; $b2c = []; $totals = ['taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0, 'invoice_value' => 0];

        $byVoucher = $rows->groupBy('voucher_id');
        foreach ($byVoucher as $voucherId => $entries) {
            $head = $entries->first();
            $taxable = (float) $entries->sum('taxable_amount');
            $cgst    = (float) $entries->sum('cgst_amount');
            $sgst    = (float) $entries->sum('sgst_amount');
            $igst    = (float) $entries->sum('igst_amount');
            $cess    = (float) $entries->sum('cess_amount');
            $invoice = round($taxable + $cgst + $sgst + $igst + $cess, 2);

            $row = [
                'voucher_id'    => $voucherId,
                'voucher_no'    => $head->voucher_no,
                'voucher_date'  => $head->voucher_date,
                'party_name'    => $head->party_name ?? '—',
                'party_gstin'   => $head->party_gstin,
                'party_state'   => $head->party_state,
                'taxable'       => round($taxable, 2),
                'cgst'          => round($cgst, 2),
                'sgst'          => round($sgst, 2),
                'igst'          => round($igst, 2),
                'cess'          => round($cess, 2),
                'invoice_value' => $invoice,
            ];
            if (!empty($head->party_gstin)) $b2b[] = $row; else $b2c[] = $row;

            $totals['taxable']       += $row['taxable'];
            $totals['cgst']          += $row['cgst'];
            $totals['sgst']          += $row['sgst'];
            $totals['igst']          += $row['igst'];
            $totals['cess']          += $row['cess'];
            $totals['invoice_value'] += $row['invoice_value'];
        }

        return view('accounts.reports.gstr1', compact('b2b', 'b2c', 'totals', 'from', 'to', 'monthYear'));
    }

    public function gstr3b(Request $request, GstrJsonExporter $exporter)
    {
        [$from, $to, $monthYear] = $this->monthRange($request);

        if ($request->query('format') === 'json') {
            $payload = $exporter->gstr3b($from, $to);
            $filename = sprintf('gstr3b-%s.json', $from->format('mY'));
            return response()
                ->json($payload, 200, ['Content-Disposition' => "attachment; filename=\"{$filename}\""], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        // Outward (sales)
        $outward = $this->vouchersSumByCode($from, $to, 'SAL');
        // Inward (purchases) — ITC available
        $inward  = $this->vouchersSumByCode($from, $to, 'PUR');

        $taxPayable = [
            'cgst' => max(0, $outward['cgst'] - $inward['cgst']),
            'sgst' => max(0, $outward['sgst'] - $inward['sgst']),
            'igst' => max(0, $outward['igst'] - $inward['igst']),
            'cess' => max(0, $outward['cess'] - $inward['cess']),
        ];

        return view('accounts.reports.gstr3b', compact('outward', 'inward', 'taxPayable', 'from', 'to', 'monthYear'));
    }

    public function hsnSummary(Request $request)
    {
        [$from, $to, $monthYear] = $this->monthRange($request);
        $direction = $request->query('direction', 'outward'); // outward | inward
        $code = $direction === 'inward' ? 'PUR' : 'SAL';

        $rows = DB::table('voucher_entries as ve')
            ->join('vouchers as v',       'v.id',  '=', 've.voucher_id')
            ->join('voucher_types as vt', 'vt.id', '=', 'v.voucher_type_id')
            ->where('v.status', 'Posted')
            ->whereBetween('v.voucher_date', [$from, $to])
            ->where('vt.code', $code)
            ->whereNotNull('ve.hsn_sac')
            ->where('ve.taxable_amount', '>', 0)
            ->select(
                've.hsn_sac', 've.gst_rate',
                DB::raw('SUM(ve.taxable_amount) as taxable'),
                DB::raw('SUM(ve.cgst_amount) as cgst'),
                DB::raw('SUM(ve.sgst_amount) as sgst'),
                DB::raw('SUM(ve.igst_amount) as igst'),
                DB::raw('SUM(ve.cess_amount) as cess'),
                DB::raw('COUNT(*) as line_count')
            )
            ->groupBy('ve.hsn_sac', 've.gst_rate')
            ->orderBy('ve.hsn_sac')
            ->get();

        $totals = ['taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0];
        foreach ($rows as $r) {
            foreach ($totals as $k => $_) $totals[$k] += (float) $r->$k;
        }

        return view('accounts.reports.hsn-summary', compact('rows', 'totals', 'from', 'to', 'monthYear', 'direction'));
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function monthRange(Request $request): array
    {
        $month = (int) ($request->query('month') ?: now()->month);
        $year  = (int) ($request->query('year')  ?: now()->year);
        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to   = $from->copy()->endOfMonth()->endOfDay();
        return [$from, $to, $from->format('M Y')];
    }

    private function salesRows(Carbon $from, Carbon $to)
    {
        return DB::table('voucher_entries as ve')
            ->join('vouchers as v',           'v.id',  '=', 've.voucher_id')
            ->join('voucher_types as vt',     'vt.id', '=', 'v.voucher_type_id')
            ->leftJoin('account_ledgers as p','p.id',  '=', 'v.party_ledger_id')
            ->where('v.status', 'Posted')
            ->where('vt.code', 'SAL')
            ->whereBetween('v.voucher_date', [$from, $to])
            ->where(function ($w) {
                $w->where('ve.taxable_amount', '>', 0)
                  ->orWhere('ve.cgst_amount', '>', 0)
                  ->orWhere('ve.sgst_amount', '>', 0)
                  ->orWhere('ve.igst_amount', '>', 0);
            })
            ->select(
                'v.id as voucher_id', 'v.voucher_no', 'v.voucher_date',
                'p.name as party_name', 'p.gstin as party_gstin', 'p.state_code as party_state',
                've.taxable_amount', 've.cgst_amount', 've.sgst_amount', 've.igst_amount', 've.cess_amount'
            )
            ->orderBy('v.voucher_date')->orderBy('v.id')
            ->get();
    }

    private function vouchersSumByCode(Carbon $from, Carbon $to, string $code): array
    {
        $row = DB::table('voucher_entries as ve')
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
            'taxable' => round((float) $row->taxable, 2),
            'cgst'    => round((float) $row->cgst,    2),
            'sgst'    => round((float) $row->sgst,    2),
            'igst'    => round((float) $row->igst,    2),
            'cess'    => round((float) $row->cess,    2),
        ];
    }
}
