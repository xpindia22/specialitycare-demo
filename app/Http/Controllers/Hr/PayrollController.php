<?php
namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Payee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        [$y, $m] = array_pad(explode('-', $month), 2, null);
        $year  = (int) $y;
        $monthN= (int) $m;

        $allStaff   = Staff::where('is_active', true)->orderBy('first_name')->get();
        $allDoctors = Doctor::where('is_active', true)->orderBy('first_name')->get();

        // Recompute on every load so figures stay fresh as attendance / adjustments change
        // (paid runs are locked and skipped inside computeForMonth).
        $rows = $allStaff->map(fn (Staff $s) => PayrollRun::computeForMonth($s, $year, $monthN))
            ->concat($allDoctors->map(fn (Doctor $d) => PayrollRun::computeForMonth($d, $year, $monthN)))
            ->sortBy(fn ($r) => mb_strtolower($r->subject_name))
            ->values();

        $totals = [
            'gross'  => $rows->sum(fn($r) => $r->base_pay_prorated + $r->credits),
            'net'    => $rows->sum('net_payable'),
            'paid'   => $rows->where('status','paid')->sum('net_payable'),
            'pending'=> $rows->where('status','pending')->sum('net_payable'),
        ];

        // Surface recurring_expense rows that overlap with this month's payees
        // — those will double-post once HR markPaid is wired to Accounts.
        $payeeLedgerIds = Payee::whereNotNull('ledger_id')
            ->where(fn($q) => $q->whereNotNull('hr_staff_id')->orWhereNotNull('hr_doctor_id'))
            ->pluck('ledger_id')->all();
        $redundantRecurring = collect();
        if (!empty($payeeLedgerIds)) {
            $redundantRecurring = \App\Models\RecurringExpense::where('is_active', true)
                ->whereIn('expense_ledger_id', $payeeLedgerIds)
                ->orderBy('name')->get();
        }

        return view('hr.payroll', [
            'month'              => $month,
            'monthLabel'         => Carbon::create($year, $monthN, 1)->format('F Y'),
            'rows'               => $rows,
            'totals'             => $totals,
            'redundantRecurring' => $redundantRecurring,
        ]);
    }

    /**
     * Bulk-deactivate the recurring_expenses entries that would now double-
     * post against the per-payee ledgers HR also writes to.
     */
    public function deactivateRedundantRecurring(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'No recurring expense selected.');
        }
        $count = \App\Models\RecurringExpense::whereIn('id', $ids)->update(['is_active' => false]);
        return back()->with('success', "Deactivated {$count} recurring expense entr" . ($count === 1 ? 'y' : 'ies') . '. HR Payroll is now the single source of truth for those salaries.');
    }

    public function markPaid(Request $request, PayrollRun $run, VoucherService $voucher)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['cash','bank_transfer','upi','cheque'])],
            'paid_at'        => 'required|date',
            'notes'          => 'nullable|string|max:255',
        ]);
        $run->update([
            'status'         => 'paid',
            'payment_method' => $data['payment_method'],
            'paid_at'        => $data['paid_at'],
            'paid_by'        => auth()->id(),
            'notes'          => $data['notes'] ?? null,
        ]);

        // Auto-post a PAY voucher in Accounts if this person has a Payee
        // record linked (Dr per-payee salary ledger, Cr Cash or configured
        // pay-from ledger). source_type+source_id make it idempotent so
        // unpay→repay doesn't duplicate.
        $payee = $run->doctor_id
            ? Payee::with('payFromLedger')->where('hr_doctor_id', $run->doctor_id)->where('is_active', true)->first()
            : Payee::with('payFromLedger')->where('hr_staff_id',  $run->staff_id) ->where('is_active', true)->first();

        $month = sprintf('%04d-%02d', $run->year, $run->month);
        $monthLabel = Carbon::create($run->year, $run->month, 1)->format('M Y');
        $accountsMsg = '';

        if ($payee && $payee->ledger_id && (float) $run->net_payable > 0) {
            $existing = $voucher->findBySource(PayrollRun::class, $run->id);
            if ($existing && $existing->status !== 'Cancelled') {
                $accountsMsg = " Voucher already posted: {$existing->voucher_no}.";
            } else {
                $payFromId = $payee->pay_from_ledger_id
                    ?: \App\Models\AccountLedger::where('code', 'CASH')->value('id')
                    ?: \App\Models\AccountLedger::whereHas('group', fn($q) => $q->where('code', 'CASH'))->value('id');

                if ($payFromId) {
                    try {
                        $v = $voucher->create([
                            'type'         => 'PAY',
                            'date'         => $data['paid_at'],
                            'narration'    => "Salary - {$payee->name} ({$monthLabel})" . (!empty($data['notes']) ? " — {$data['notes']}" : ''),
                            'entries'      => [
                                ['ledger' => $payee->ledger_id, 'dr' => (float) $run->net_payable],
                                ['ledger' => (int) $payFromId,  'cr' => (float) $run->net_payable],
                            ],
                            'source_type'  => PayrollRun::class,
                            'source_id'    => $run->id,
                            'created_by'   => auth()->id(),
                        ]);
                        $accountsMsg = " Posted to Accounts as {$v->voucher_no}.";
                    } catch (\Throwable $e) {
                        \Log::warning("HR markPaid: voucher post failed for run #{$run->id}: " . $e->getMessage());
                        $accountsMsg = " (Accounts voucher post failed — check logs.)";
                    }
                } else {
                    $accountsMsg = " (No Cash/Bank ledger configured — voucher not posted.)";
                }
            }
        } elseif (!$payee) {
            $accountsMsg = " No Accounts Payee linked — voucher not posted.";
        }

        return redirect()->route('hr.payroll.index', ['month' => $month])
            ->with('success', "Marked paid: {$run->subject_name} — ₹" . number_format($run->net_payable, 2) . '.' . $accountsMsg);
    }

    public function unpay(PayrollRun $run, VoucherService $voucher)
    {
        $run->update([
            'status' => 'pending', 'payment_method' => null, 'paid_at' => null, 'paid_by' => null,
        ]);

        // Cancel the auto-posted voucher (if any) so accounts stay consistent.
        $accountsMsg = '';
        $existing = $voucher->findBySource(PayrollRun::class, $run->id);
        if ($existing && $existing->status !== 'Cancelled') {
            $voucher->cancel($existing, "Payroll #{$run->id} reverted to pending");
            $accountsMsg = " Cancelled accounts voucher {$existing->voucher_no}.";
        }

        $month = sprintf('%04d-%02d', $run->year, $run->month);
        return redirect()->route('hr.payroll.index', ['month' => $month])
            ->with('success', 'Payment reverted to pending.' . $accountsMsg);
    }

    public function slip(PayrollRun $run)
    {
        $run->load('staff', 'doctor', 'paidBy');
        $start = Carbon::create($run->year, $run->month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $fkColumn = $run->doctor_id ? 'doctor_id' : 'staff_id';
        $fkValue  = $run->doctor_id ?: $run->staff_id;
        $adjustments = PayrollAdjustment::where($fkColumn, $fkValue)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')->get();

        $clinic = [
            'name'    => setting('clinic_name', config('app.name')),
            'address' => setting('clinic_address'),
            'phone'   => setting('clinic_phone'),
        ];

        return view('hr.payslip', compact('run','adjustments','clinic'));
    }
}
