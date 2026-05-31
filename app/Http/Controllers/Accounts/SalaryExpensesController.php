<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Payee;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Dropdown-driven salary payment: pick a payee, amount auto-fills with
 * their monthly_amount (overridable for half-month / bonus / deduction),
 * posts a Payment voucher dr their per-payee ledger / cr Cash or Bank.
 */
class SalaryExpensesController extends Controller
{
    public function index()
    {
        $payees = Payee::with('ledger', 'payFromLedger')
            ->where('is_active', true)
            ->orderBy('name')->get();

        // Recent salary payments — anything posted to a ledger linked to a payee
        $payeeLedgerIds = $payees->pluck('ledger_id')->all();
        $recent = collect();
        if (!empty($payeeLedgerIds)) {
            $recent = Voucher::with('entries.ledger')
                ->where('status', 'Posted')
                ->whereHas('entries', fn($q) => $q->whereIn('ledger_id', $payeeLedgerIds)->where('dr_amount', '>', 0))
                ->orderByDesc('voucher_date')->orderByDesc('id')
                ->limit(15)->get();
        }

        return view('accounts.expenses.salary', compact('payees', 'recent'));
    }

    public function store(Request $request, VoucherService $svc)
    {
        $data = $request->validate([
            'payee_id'     => ['required', 'integer', 'exists:payees,id'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'date'         => ['required', 'date'],
            'pay_from'     => ['nullable', 'integer', 'exists:account_ledgers,id'],
            'narration'    => ['nullable', 'string', 'max:500'],
            'reference_no' => ['nullable', 'string', 'max:64'],
        ]);

        $payee = Payee::with('ledger', 'payFromLedger')->findOrFail($data['payee_id']);
        $payFromId = $data['pay_from'] ?: $payee->pay_from_ledger_id;
        $monthLabel = Carbon::parse($data['date'])->format('M Y');

        $voucher = $svc->create([
            'type'         => 'PAY',
            'date'         => $data['date'],
            'reference_no' => $data['reference_no'] ?? null,
            'narration'    => $data['narration'] ?: "Salary - {$payee->name} ({$monthLabel})",
            'entries' => [
                ['ledger' => $payee->ledger_id, 'dr' => (float) $data['amount']],
                ['ledger' => (int) $payFromId,  'cr' => (float) $data['amount']],
            ],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('accounts.expenses.salary')
            ->with('success', "Salary posted as {$voucher->voucher_no} — {$payee->name} ₹" . number_format((float) $data['amount'], 2) . " ({$monthLabel}).");
    }
}
