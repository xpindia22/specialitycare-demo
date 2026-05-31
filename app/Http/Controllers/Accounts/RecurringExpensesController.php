<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\RecurringExpense;
use App\Services\RecurringExpenseRunner;
use Illuminate\Http\Request;

class RecurringExpensesController extends Controller
{
    public function index()
    {
        $items = RecurringExpense::with('expenseLedger', 'counterLedger')->orderByDesc('is_active')->orderBy('day_of_month')->get();
        $expenseLedgers = AccountLedger::with('group')
            ->whereHas('group', fn($g) => $g->where('nature', 'Expenses'))
            ->where('is_active', true)->orderBy('name')->get();
        $payFrom = AccountLedger::where('is_active', true)
            ->where(fn($q) => $q->where('party_type', 'Cash')->orWhere('party_type', 'Bank'))
            ->orderBy('name')->get();

        return view('accounts.recurring.index', compact('items', 'expenseLedgers', 'payFrom'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'expense_ledger_id' => ['required', 'integer', 'exists:account_ledgers,id'],
            'counter_ledger_id' => ['required', 'integer', 'exists:account_ledgers,id'],
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'day_of_month'      => ['required', 'integer', 'min:1', 'max:28'],
            'narration'         => ['nullable', 'string', 'max:500'],
            'is_active'         => ['nullable', 'boolean'],
            'starts_on'         => ['nullable', 'date'],
            'ends_on'           => ['nullable', 'date', 'after:starts_on'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        RecurringExpense::create($data);
        return back()->with('success', "Recurring expense '{$data['name']}' added.");
    }

    public function destroy(RecurringExpense $recurring)
    {
        $name = $recurring->name;
        $recurring->delete();
        return back()->with('success', "Recurring expense '{$name}' removed.");
    }

    public function runDue(RecurringExpenseRunner $runner)
    {
        $r = $runner->postDue();
        $msg = "Posted {$r['posted']}, skipped {$r['skipped']} (already done this month).";
        if ($r['vouchers']) $msg .= ' Vouchers: ' . implode('; ', $r['vouchers']) . '.';
        if ($r['errors'])   $msg .= ' Errors: '   . implode('; ', $r['errors']);
        return back()->with('success', $msg);
    }
}
