<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dropdown-driven supplier payment: pick a supplier, see their current
 * outstanding (Cr - Dr), amount defaults to full outstanding (overridable
 * for partial payments), posts a Payment voucher Dr supplier ledger /
 * Cr Cash or Bank.
 *
 * Mirrors SalaryExpensesController's pattern.
 */
class SupplierPaymentsController extends Controller
{
    public function index()
    {
        $suppliers = $this->suppliersWithOutstanding();
        $payFrom   = $this->payFromLedgers();
        $recent    = $this->recentSupplierPayments($suppliers->pluck('id')->all());
        $defaultSupplierId = AccountLedger::defaultSupplier()?->id;
        return view('accounts.expenses.pay-supplier', compact('suppliers', 'payFrom', 'recent', 'defaultSupplierId'));
    }

    public function store(Request $request, VoucherService $svc)
    {
        $data = $request->validate([
            'supplier_ledger_id' => ['required', 'integer', 'exists:account_ledgers,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'date'               => ['required', 'date'],
            'pay_from'           => ['required', 'integer', 'exists:account_ledgers,id'],
            'reference_no'       => ['nullable', 'string', 'max:64'],
            'narration'          => ['nullable', 'string', 'max:500'],
        ]);

        $supplier = AccountLedger::findOrFail($data['supplier_ledger_id']);
        $payFrom  = AccountLedger::findOrFail($data['pay_from']);

        $voucher = $svc->create([
            'type'         => 'PAY',
            'date'         => $data['date'],
            'party_ledger' => $supplier->id,
            'reference_no' => $data['reference_no'] ?? null,
            'narration'    => $data['narration'] ?: "Paid to {$supplier->name}",
            'entries' => [
                ['ledger' => $supplier->id, 'dr' => (float) $data['amount'], 'narration' => 'Settlement to supplier'],
                ['ledger' => $payFrom->id,  'cr' => (float) $data['amount'], 'narration' => "Paid by {$payFrom->name}"],
            ],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('accounts.expenses.pay-supplier')
            ->with('success', "Payment posted as {$voucher->voucher_no} — paid {$supplier->name} ₹" . number_format((float) $data['amount'], 2) . " from {$payFrom->name}.");
    }

    /** Suppliers with their current outstanding (Cr - Dr on Posted vouchers). */
    private function suppliersWithOutstanding()
    {
        $balances = DB::table('voucher_entries as ve')
            ->join('vouchers as v', 'v.id', '=', 've.voucher_id')
            ->where('v.status', 'Posted')
            ->selectRaw('ve.ledger_id, SUM(ve.cr_amount) - SUM(ve.dr_amount) as outstanding')
            ->groupBy('ve.ledger_id')
            ->pluck('outstanding', 'ledger_id');

        return AccountLedger::where('is_active', true)
            ->where('party_type', 'Supplier')
            ->orderBy('name')
            ->get()
            ->map(function ($l) use ($balances) {
                $l->outstanding = round((float) ($balances[$l->id] ?? 0), 2);
                return $l;
            });
    }

    private function payFromLedgers()
    {
        return AccountLedger::where('is_active', true)
            ->where(fn($q) => $q->where('party_type', 'Cash')->orWhere('party_type', 'Bank'))
            ->orderBy('name')->get();
    }

    /** Recent supplier-payment vouchers — any Posted PAY whose party is a supplier. */
    private function recentSupplierPayments(array $supplierIds)
    {
        if (empty($supplierIds)) return collect();
        return Voucher::with('party', 'entries.ledger')
            ->whereHas('type', fn($q) => $q->where('code', 'PAY'))
            ->where('status', 'Posted')
            ->whereIn('party_ledger_id', $supplierIds)
            ->orderByDesc('voucher_date')->orderByDesc('id')
            ->limit(15)->get();
    }
}
