<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Models\AccountLedger;
use App\Models\RecurringExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dedicated supplier management — a friendly facade over AccountLedger rows
 * with party_type = 'Supplier'. Hides the accounting jargon (group / Dr-Cr /
 * party_type) so non-accountant users don't trip on it. The full ledger
 * form remains available at /accounts/ledgers for power users.
 */
class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $q          = trim((string) $request->query('q', ''));
        $showAll    = (bool) $request->query('show_inactive');

        $suppliers = AccountLedger::query()
            ->where('party_type', 'Supplier')
            ->when($q, fn($x) => $x->where(fn($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->orWhere('gstin', 'like', "%{$q}%")))
            ->when(!$showAll, fn($x) => $x->where('is_active', true))
            ->orderBy('name')
            ->get();

        // Attach outstanding per supplier (Cr - Dr on Posted vouchers)
        $balances = DB::table('voucher_entries as ve')
            ->join('vouchers as v', 'v.id', '=', 've.voucher_id')
            ->where('v.status', 'Posted')
            ->whereIn('ve.ledger_id', $suppliers->pluck('id'))
            ->selectRaw('ve.ledger_id, SUM(ve.cr_amount) - SUM(ve.dr_amount) as outstanding')
            ->groupBy('ve.ledger_id')
            ->pluck('outstanding', 'ledger_id');

        $suppliers->each(fn($s) => $s->outstanding = round((float) ($balances[$s->id] ?? 0), 2));

        return view('accounts.suppliers.index', compact('suppliers', 'q', 'showAll'));
    }

    public function create()
    {
        return view('accounts.suppliers.form', [
            'supplier' => new AccountLedger(['is_active' => true, 'opening_balance_dr_cr' => 'Cr', 'party_type' => 'Supplier']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['party_type'] = 'Supplier';
        $data['group_id']   = $this->sundryCreditorsGroupId();
        $data['code']       = $data['code'] ?: $this->autoCode($data['name']);

        $supplier = AccountLedger::create($data);
        return redirect()->route('accounts.suppliers.index')
            ->with('success', "Supplier '{$supplier->name}' added.");
    }

    public function edit(AccountLedger $supplier)
    {
        $this->ensureIsSupplier($supplier);
        return view('accounts.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, AccountLedger $supplier)
    {
        $this->ensureIsSupplier($supplier);
        $data = $this->validated($request, $supplier->id);
        // Don't allow flipping party_type away from Supplier through this form
        $data['party_type'] = 'Supplier';
        $supplier->update($data);
        return redirect()->route('accounts.suppliers.index')->with('success', "Supplier '{$supplier->name}' updated.");
    }

    public function destroy(AccountLedger $supplier)
    {
        $this->ensureIsSupplier($supplier);
        if ($supplier->entries()->exists()) {
            $supplier->update(['is_active' => false]);
            return back()->with('success', "'{$supplier->name}' has posted entries — marked Inactive instead of deleted (audit trail preserved).");
        }
        $usedByRecurring = RecurringExpense::where('counter_ledger_id', $supplier->id)
            ->orWhere('expense_ledger_id', $supplier->id)
            ->pluck('name');
        if ($usedByRecurring->isNotEmpty()) {
            return back()->withErrors(['supplier' => 'Still referenced by recurring expense(s): ' . $usedByRecurring->implode(', ')]);
        }
        $name = $supplier->name;
        $supplier->delete();
        return back()->with('success', "Supplier '{$name}' deleted.");
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function ensureIsSupplier(AccountLedger $l): void
    {
        abort_if($l->party_type !== 'Supplier', 404, 'Not a supplier ledger.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'code'                  => ['nullable', 'string', 'max:64'],
            'opening_balance'       => ['nullable', 'numeric', 'min:0'],
            'opening_balance_dr_cr' => ['required', 'in:Dr,Cr'],
            'gstin'                 => ['nullable', 'string', 'max:20'],
            'state_code'            => ['nullable', 'string', 'max:4'],
            'contact_phone'         => ['nullable', 'string', 'max:32'],
            'contact_email'         => ['nullable', 'email', 'max:191'],
            'address'               => ['nullable', 'string'],
            'is_active'             => ['nullable', 'boolean'],
            'notes'                 => ['nullable', 'string'],
        ]);
    }

    private function sundryCreditorsGroupId(): int
    {
        return AccountGroup::where('name', 'Sundry Creditors')->value('id')
            ?? abort(500, 'Sundry Creditors group missing — re-seed account_groups.');
    }

    private function autoCode(string $name): string
    {
        $slug = trim(strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $name)), '-');
        $base = 'SCR-' . substr($slug ?: 'SUPPLIER', 0, 50);
        $i = 0;
        do { $code = $i === 0 ? $base : "{$base}-{$i}"; $i++; }
        while (AccountLedger::where('code', $code)->exists());
        return $code;
    }
}
