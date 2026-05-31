<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Quick-entry shortcut for the common case: enter rent / electricity /
 * petrol / maid salary etc. without thinking about debits and credits.
 *
 * Posts a Payment voucher: Dr <chosen expense ledger>, Cr Cash (or Bank).
 */
class ExpensesController extends Controller
{
    public function quick()
    {
        $expenseLedgers = AccountLedger::with('group')
            ->whereHas('group', fn($g) => $g->whereIn('nature', ['Expenses'])->orWhere('code', 'PUR'))
            ->where('is_active', true)
            ->orderBy('name')->get();

        $payFrom = AccountLedger::where('is_active', true)
            ->where(fn($q) => $q->where('party_type', 'Cash')->orWhere('party_type', 'Bank'))
            ->orderBy('name')->get();

        $recent = \App\Models\Voucher::with('type', 'entries.ledger')
            ->where('status', 'Posted')
            ->whereHas('type', fn($t) => $t->where('code', 'PAY'))
            ->orderByDesc('voucher_date')->orderByDesc('id')
            ->limit(15)->get();

        return view('accounts.expenses.quick', compact('expenseLedgers', 'payFrom', 'recent'));
    }

    public function storeQuick(Request $request, VoucherService $svc)
    {
        $data = $request->validate([
            'expense_ledger' => ['required', 'integer', 'exists:account_ledgers,id'],
            'pay_from'       => ['required', 'integer', 'exists:account_ledgers,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'date'           => ['required', 'date'],
            'narration'      => ['nullable', 'string', 'max:500'],
            'reference_no'   => ['nullable', 'string', 'max:64'],
        ]);

        $voucher = $svc->create([
            'type'         => 'PAY',
            'date'         => $data['date'],
            'narration'    => $data['narration'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'entries' => [
                ['ledger' => (int) $data['expense_ledger'], 'dr' => (float) $data['amount']],
                ['ledger' => (int) $data['pay_from'],       'cr' => (float) $data['amount']],
            ],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('accounts.expenses.quick')
            ->with('success', "Expense posted as {$voucher->voucher_no} (₹" . number_format((float) $data['amount'], 2) . ").");
    }
}
