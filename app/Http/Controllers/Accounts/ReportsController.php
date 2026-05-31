<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Financial reports: Day Book, Trial Balance, P&L, Balance Sheet.
 * GST-specific reports (GSTR-1, GSTR-3B, HSN summary) live in GstReportsController.
 */
class ReportsController extends Controller
{
    public function dayBook(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        $vouchers = Voucher::with('type', 'party', 'entries.ledger')
            ->where('status', 'Posted')
            ->whereDate('voucher_date', $date)
            ->orderBy('voucher_type_id')->orderBy('id')
            ->get();

        $totals = ['dr' => 0, 'cr' => 0, 'count' => $vouchers->count()];
        foreach ($vouchers as $v) {
            foreach ($v->entries as $e) {
                $totals['dr'] += (float) $e->dr_amount;
                $totals['cr'] += (float) $e->cr_amount;
            }
        }

        return view('accounts.reports.day-book', compact('vouchers', 'date', 'totals'));
    }

    public function trialBalance(Request $request, VoucherService $svc)
    {
        $upto = $request->query('upto') ? Carbon::parse($request->query('upto')) : Carbon::today();

        $rows = DB::table('account_ledgers as l')
            ->join('account_groups as g', 'g.id', '=', 'l.group_id')
            ->leftJoin('voucher_entries as ve', 've.ledger_id', '=', 'l.id')
            ->leftJoin('vouchers as v', function ($j) use ($upto) {
                $j->on('v.id', '=', 've.voucher_id')->where('v.status', 'Posted')->where('v.voucher_date', '<=', $upto);
            })
            ->select(
                'l.id', 'l.code', 'l.name',
                'l.opening_balance', 'l.opening_balance_dr_cr',
                'g.id as group_id', 'g.name as group_name', 'g.code as group_code',
                'g.nature', 'g.parent_id as group_parent_id', 'g.sort_order',
                DB::raw('COALESCE(SUM(ve.dr_amount), 0) as posted_dr'),
                DB::raw('COALESCE(SUM(ve.cr_amount), 0) as posted_cr')
            )
            ->groupBy('l.id', 'l.code', 'l.name', 'l.opening_balance', 'l.opening_balance_dr_cr',
                     'g.id', 'g.name', 'g.code', 'g.nature', 'g.parent_id', 'g.sort_order')
            ->orderBy('g.sort_order')->orderBy('l.name')
            ->get();

        $totals = ['dr' => 0, 'cr' => 0];
        foreach ($rows as $r) {
            $openingSigned = (float) $r->opening_balance * ($r->opening_balance_dr_cr === 'Dr' ? 1 : -1);
            $net = $openingSigned + (float) $r->posted_dr - (float) $r->posted_cr;
            $r->dr_balance = $net > 0 ? round($net, 2) : 0;
            $r->cr_balance = $net < 0 ? round(-$net, 2) : 0;
            $totals['dr'] += $r->dr_balance;
            $totals['cr'] += $r->cr_balance;
        }

        $grouped = $rows->groupBy('group_name');

        return view('accounts.reports.trial-balance', compact('grouped', 'totals', 'upto'));
    }

    public function profitLoss(Request $request, VoucherService $svc)
    {
        $fyYear = (int) ($request->query('fy_year') ?: $svc->fyYear(Carbon::today()));
        [$fyStart, $fyEnd] = $svc->fyRange($fyYear);
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : $fyStart;
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))   : Carbon::today();

        // Sum each ledger's net (Cr − Dr for Income, Dr − Cr for Expenses) within period.
        $ledgerRows = DB::table('voucher_entries as ve')
            ->join('vouchers as v', 'v.id', '=', 've.voucher_id')
            ->join('account_ledgers as l', 'l.id', '=', 've.ledger_id')
            ->join('account_groups as g', 'g.id', '=', 'l.group_id')
            ->where('v.status', 'Posted')
            ->whereBetween('v.voucher_date', [$from, $to])
            ->whereIn('g.nature', ['Income', 'Expenses'])
            ->select(
                'l.id as ledger_id', 'l.name as ledger_name',
                'g.id as group_id', 'g.name as group_name', 'g.code as group_code',
                'g.nature', 'g.affects_gross_profit', 'g.sort_order',
                DB::raw('SUM(ve.dr_amount) as dr'), DB::raw('SUM(ve.cr_amount) as cr')
            )
            ->groupBy('l.id', 'l.name', 'g.id', 'g.name', 'g.code', 'g.nature', 'g.affects_gross_profit', 'g.sort_order')
            ->orderBy('g.sort_order')->orderBy('l.name')
            ->get();

        $income  = ['direct' => [], 'indirect' => [], 'total_direct' => 0, 'total_indirect' => 0];
        $expense = ['direct' => [], 'indirect' => [], 'total_direct' => 0, 'total_indirect' => 0];

        foreach ($ledgerRows as $r) {
            if ($r->nature === 'Income') {
                $amount = round((float) $r->cr - (float) $r->dr, 2);
                if ($amount == 0) continue;
                $bucket = $r->affects_gross_profit ? 'direct' : 'indirect';
                $income[$bucket][] = ['name' => $r->ledger_name, 'group' => $r->group_name, 'amount' => $amount];
                $income['total_' . $bucket] += $amount;
            } else { // Expenses
                $amount = round((float) $r->dr - (float) $r->cr, 2);
                if ($amount == 0) continue;
                $bucket = $r->affects_gross_profit ? 'direct' : 'indirect';
                $expense[$bucket][] = ['name' => $r->ledger_name, 'group' => $r->group_name, 'amount' => $amount];
                $expense['total_' . $bucket] += $amount;
            }
        }

        $grossProfit = round($income['total_direct'] - $expense['total_direct'], 2);
        $netProfit   = round($grossProfit + $income['total_indirect'] - $expense['total_indirect'], 2);
        $totalIncome  = round($income['total_direct'] + $income['total_indirect'], 2);
        $totalExpense = round($expense['total_direct'] + $expense['total_indirect'], 2);

        return view('accounts.reports.profit-loss', compact(
            'income', 'expense', 'grossProfit', 'netProfit', 'totalIncome', 'totalExpense',
            'from', 'to', 'fyYear'
        ));
    }

    public function balanceSheet(Request $request, VoucherService $svc)
    {
        $upto = $request->query('upto') ? Carbon::parse($request->query('upto')) : Carbon::today();
        $fyYear = $svc->fyYear($upto);
        [$fyStart, $fyEnd] = $svc->fyRange($fyYear);

        // For each ledger: opening (signed) + Σdr - Σcr up to `upto` => closing.
        $rows = DB::table('account_ledgers as l')
            ->join('account_groups as g', 'g.id', '=', 'l.group_id')
            ->leftJoin('voucher_entries as ve', 've.ledger_id', '=', 'l.id')
            ->leftJoin('vouchers as v', function ($j) use ($upto) {
                $j->on('v.id', '=', 've.voucher_id')->where('v.status', 'Posted')->where('v.voucher_date', '<=', $upto);
            })
            ->select(
                'l.id', 'l.name as ledger_name', 'l.opening_balance', 'l.opening_balance_dr_cr',
                'g.id as group_id', 'g.name as group_name', 'g.code as group_code', 'g.nature', 'g.sort_order',
                DB::raw('COALESCE(SUM(ve.dr_amount), 0) as dr'),
                DB::raw('COALESCE(SUM(ve.cr_amount), 0) as cr')
            )
            ->groupBy('l.id', 'l.name', 'l.opening_balance', 'l.opening_balance_dr_cr',
                     'g.id', 'g.name', 'g.code', 'g.nature', 'g.sort_order')
            ->orderBy('g.sort_order')->orderBy('l.name')
            ->get();

        $assets      = []; $liabilities = [];
        $assetsTotal = 0;  $liabTotal   = 0;

        foreach ($rows as $r) {
            if (!in_array($r->nature, ['Assets', 'Liabilities'], true)) continue;
            $openingSigned = (float) $r->opening_balance * ($r->opening_balance_dr_cr === 'Dr' ? 1 : -1);
            $net = $openingSigned + (float) $r->dr - (float) $r->cr;
            if (round($net, 2) == 0) continue;

            if ($r->nature === 'Assets') {
                $amount = round($net, 2);
                $assets[$r->group_name][] = ['name' => $r->ledger_name, 'amount' => $amount];
                $assetsTotal += $amount;
            } else { // Liabilities
                $amount = round(-$net, 2);
                $liabilities[$r->group_name][] = ['name' => $r->ledger_name, 'amount' => $amount];
                $liabTotal += $amount;
            }
        }

        // Net profit/loss to date — sum Income.cr − Income.dr − (Expense.dr − Expense.cr) within FY.
        $pl = DB::table('voucher_entries as ve')
            ->join('vouchers as v', 'v.id', '=', 've.voucher_id')
            ->join('account_ledgers as l', 'l.id', '=', 've.ledger_id')
            ->join('account_groups as g', 'g.id', '=', 'l.group_id')
            ->where('v.status', 'Posted')
            ->whereBetween('v.voucher_date', [$fyStart, $upto])
            ->whereIn('g.nature', ['Income', 'Expenses'])
            ->select('g.nature', DB::raw('SUM(ve.cr_amount - ve.dr_amount) as net'))
            ->groupBy('g.nature')
            ->pluck('net', 'nature');
        $netProfit = round((float) ($pl['Income'] ?? 0) + (float) ($pl['Expenses'] ?? 0), 2);

        // Park net profit/loss on the Liabilities side (under Capital Account)
        if (abs($netProfit) > 0.005) {
            $label = $netProfit >= 0 ? 'Net Profit (current period)' : 'Net Loss (current period)';
            $liabilities['Capital Account'][] = ['name' => $label, 'amount' => $netProfit];
            $liabTotal += $netProfit;
        }

        $difference = round($assetsTotal - $liabTotal, 2);

        return view('accounts.reports.balance-sheet', compact(
            'assets', 'liabilities', 'assetsTotal', 'liabTotal', 'difference', 'netProfit', 'upto', 'fyYear'
        ));
    }
}
