<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\RecurringExpenseRunner;
use App\Services\VoucherService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    public function index(VoucherService $svc, RecurringExpenseRunner $recurring)
    {
        // In-app scheduler: post any due recurring expenses after the response
        // is sent. Idempotent, so duplicate triggers are harmless. Means buyers
        // don't need cron — opening the Accounts dashboard is the heartbeat.
        dispatch(function () use ($recurring) { $recurring->postDue(); })->afterResponse();

        $today    = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd   = $today->copy()->endOfMonth();
        $fyYear   = $svc->fyYear($today);
        [$fyStart, $fyEnd] = $svc->fyRange($fyYear);

        // Month figures: Σ Cr on Income groups − Σ Dr on Expense groups
        $month = $this->incomeExpenseTotals($monthStart, $monthEnd);
        $fy    = $this->incomeExpenseTotals($fyStart, $fyEnd);

        $recentVouchers = Voucher::with('type', 'party')
            ->where('status', 'Posted')
            ->orderByDesc('voucher_date')->orderByDesc('id')
            ->limit(15)->get();

        $voucherCounts = DB::table('vouchers')
            ->join('voucher_types', 'voucher_types.id', '=', 'vouchers.voucher_type_id')
            ->where('vouchers.status', 'Posted')
            ->whereBetween('vouchers.voucher_date', [$monthStart, $monthEnd])
            ->select('voucher_types.code', 'voucher_types.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('voucher_types.id', 'voucher_types.code', 'voucher_types.name')
            ->orderBy('voucher_types.sort_order')
            ->get();

        return view('accounts.index', compact(
            'month', 'fy', 'recentVouchers', 'voucherCounts',
            'monthStart', 'monthEnd', 'fyStart', 'fyEnd', 'fyYear'
        ));
    }

    private function incomeExpenseTotals(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('voucher_entries as ve')
            ->join('vouchers as v',           'v.id',  '=', 've.voucher_id')
            ->join('account_ledgers as l',    'l.id',  '=', 've.ledger_id')
            ->join('account_groups as g',     'g.id',  '=', 'l.group_id')
            ->where('v.status', 'Posted')
            ->whereBetween('v.voucher_date', [$start, $end])
            ->whereIn('g.nature', ['Income', 'Expenses'])
            ->select('g.nature', DB::raw('SUM(ve.cr_amount - ve.dr_amount) as net'))
            ->groupBy('g.nature')
            ->pluck('net', 'nature');

        $income  = (float) ($rows['Income']   ?? 0);   // Cr positive => income earned
        $expense = -(float) ($rows['Expenses'] ?? 0);  // Dr positive => expense incurred (negate the Cr-Dr formula)
        return [
            'income'  => round($income, 2),
            'expense' => round($expense, 2),
            'net'     => round($income - $expense, 2),
        ];
    }
}
