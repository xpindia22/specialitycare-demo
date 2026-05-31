<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Doctor;
use App\Models\Payee;
use App\Models\Staff;
use Illuminate\Http\Request;

class PayeesController extends Controller
{
    public function index()
    {
        $payees = Payee::with('ledger', 'payFromLedger', 'hrStaff')->orderByDesc('is_active')->orderBy('name')->get();
        return view('accounts.payees.index', compact('payees'));
    }

    public function create()
    {
        return view('accounts.payees.form', [
            'payee'    => new Payee(['is_active' => true]),
            'payFrom'  => $this->payFromLedgers(),
            'ledgers'  => $this->reusableLedgers(),
            'hrStaff'  => $this->hrStaffOptions(),
            'hrDoctors'=> $this->hrDoctorOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $payee = Payee::create($data);
        return redirect()->route('accounts.payees.index')
            ->with('success', "Payee '{$payee->name}' added. Ledger '{$payee->ledger->name}' auto-created.");
    }

    public function edit(Payee $payee)
    {
        return view('accounts.payees.form', [
            'payee'    => $payee,
            'payFrom'  => $this->payFromLedgers(),
            'ledgers'  => $this->reusableLedgers(),
            'hrStaff'  => $this->hrStaffOptions(),
            'hrDoctors'=> $this->hrDoctorOptions(),
        ]);
    }

    public function update(Request $request, Payee $payee)
    {
        $data = $this->validated($request);
        $payee->update($data);
        return redirect()->route('accounts.payees.index')->with('success', "Payee '{$payee->name}' updated.");
    }

    public function destroy(Payee $payee)
    {
        if ($payee->ledger?->entries()->exists()) {
            $payee->update(['is_active' => false]);
            return back()->with('success', "Payee '{$payee->name}' has posted entries — marked inactive instead of deleted.");
        }
        $name = $payee->name;
        $payee->delete();
        return back()->with('success', "Payee '{$name}' removed.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'role'               => ['nullable', 'string', 'max:64'],
            'monthly_amount'     => ['required', 'numeric', 'min:0'],
            'pay_from_ledger_id' => ['required', 'integer', 'exists:account_ledgers,id'],
            'ledger_id'          => ['nullable', 'integer', 'exists:account_ledgers,id'],
            'hr_staff_id'        => ['nullable', 'integer', 'exists:staff,id'],
            'hr_doctor_id'       => ['nullable', 'integer', 'exists:doctors,id'],
            'contact_phone'      => ['nullable', 'string', 'max:32'],
            'is_active'          => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        // Empty ledger_id triggers auto-creation in Payee::creating hook
        if (empty($data['ledger_id'])) $data['ledger_id'] = null;
        return $data;
    }

    private function payFromLedgers()
    {
        return AccountLedger::where('is_active', true)
            ->where(fn($q) => $q->where('party_type', 'Cash')->orWhere('party_type', 'Bank'))
            ->orderBy('name')->get();
    }

    private function reusableLedgers()
    {
        // Existing indirect-expense ledgers user might want to reuse instead
        // of auto-creating (e.g., point all maid payments at EXP-MAID).
        return AccountLedger::with('group')
            ->whereHas('group', fn($g) => $g->where('nature', 'Expenses'))
            ->where('is_active', true)
            ->orderBy('name')->get();
    }

    private function hrStaffOptions()
    {
        try {
            return Staff::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'designation', 'salary']);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function hrDoctorOptions()
    {
        try {
            return Doctor::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'specialization', 'salary']);
        } catch (\Throwable) {
            return collect();
        }
    }
}
