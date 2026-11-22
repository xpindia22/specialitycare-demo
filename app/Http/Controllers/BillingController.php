<?php
// FILE: app/Http/Controllers/BillingController.php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Encounter;
use App\Models\ServiceCharge;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\EcgRecord;
use App\Models\EchoRecord;
use App\Models\ReferralPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $bills = Bill::with('patient', 'items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('bill_date', $request->date))
            ->when($request->search, function ($q) use ($request) {
                $q->where('bill_no', 'like', "%{$request->search}%")
                  ->orWhereHas('patient', fn($p) => $p->where('jhc_id', 'like', "%{$request->search}%")
                      ->orWhere('first_name', 'like', "%{$request->search}%")
                      ->orWhere('phone', 'like', "%{$request->search}%"));
            })
            ->latest('bill_date')->paginate(20)->withQueryString();

        $pendingBills = Bill::with('patient')
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->where('due_amount', '>', 0)
            ->latest('bill_date')
            ->get();

        $todayBills = Bill::whereDate('bill_date', today())->get();
        $todayCollection = Payment::whereDate('payment_date', today())->sum('amount');
        $pendingAmount   = $pendingBills->sum('due_amount');
        $todaySummary = [
            'billed'    => $todayBills->sum('total_amount'),
            'collected' => $todayCollection,
            'due'       => $todayBills->sum('due_amount'),
            'discount'  => $todayBills->sum('discount_amount'),
            'net'       => $todayCollection - $todayBills->sum('discount_amount'),
        ];

        return view('billing.index', compact('bills', 'pendingBills', 'todayCollection', 'pendingAmount', 'todaySummary'));
    }

    public function create(Request $request)
    {
        $patient  = $request->patient_id ? Patient::find($request->patient_id) : null;
        $encounter= $request->encounter_id
            ? Encounter::with(['diagnoses','labOrders.items.test','prescriptions'])->find($request->encounter_id)
            : null;
        $services = ServiceCharge::where('is_active', true)->get()->groupBy('category');

        // Pre-build bill items from encounter
        $encounterItems = [];
        if ($encounter) {
            // Consultation fee from the OPD fee rules (free / follow-up window, discounts)
            $encounter->loadMissing(['patient', 'doctor']);
            $feeQuote = \App\Services\OpdFee::quote($encounter->patient, $encounter->doctor, \App\Services\OpdFee::departmentOf($encounter), $encounter->encounter_date, $encounter);
            $encounterItems[] = \App\Services\OpdFee::billItem($feeQuote, $encounter->doctor);

            // Lab orders — skip any already settled by their own bill (e.g. uploaded
            // reports, auto-recorded as paid at sample collection). Billing them again
            // here would be a double charge.
            foreach ($encounter->labOrders as $lo) {
                if ($lo->bill_id) continue;
                foreach ($lo->items as $item) {
                    $name  = $item->test?->name ?? $item->panel?->name ?? 'Lab Test';
                    $price = $item->price ?? 0;
                    $encounterItems[] = ['name' => $name, 'type' => 'Investigation', 'price' => $price, 'qty' => 1];
                }
            }
        }

        $patients = \App\Models\Patient::where('is_active', true)
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id','first_name','last_name','jhc_id','phone','gender']);

        return view('billing.create', compact('patient', 'encounter', 'services', 'encounterItems', 'patients') + ['feeQuote' => $feeQuote ?? null]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'encounter_id'       => 'nullable|exists:encounters,id',
            'billing_package_id' => 'nullable|exists:billing_packages,id',
            'items'              => 'required|array|min:1',
            'items.*.name'       => 'required|string',
            'items.*.type'       => 'required|string',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
            'discount_percent'   => 'nullable|numeric|min:0|max:100',
            'payment_method'     => 'nullable|string',
            'paid_amount'        => 'nullable|numeric|min:0',
            'bill_date'          => 'nullable|date|before_or_equal:today',
            'payment_date'       => 'nullable|date|before_or_equal:today',
            'notes'              => 'nullable|string',
            'insurance_amount'   => 'nullable|numeric|min:0',
            'insurance_company'  => 'nullable|string|max:200',
            'tpa_ref'            => 'nullable|string|max:200',
        ]);

        $subtotal = 0;
        foreach ($request->items as $item) {
            $itemTotal = ($item['qty'] * $item['price']) - ($item['discount'] ?? 0);
            $subtotal += $itemTotal;
        }

        $discountPct    = (float)($request->discount_percent ?? 0);
        $discountAmount = round($subtotal * $discountPct / 100, 2);
        $afterDiscount  = $subtotal - $discountAmount;
        $taxAmount      = 0; // Add tax logic if needed
        $totalAmount    = $afterDiscount + $taxAmount;
        $paidAmount     = (float)($request->paid_amount ?? 0);
        $dueAmount      = max(0, $totalAmount - $paidAmount);

        $payStatus = 'Unpaid';
        if ($paidAmount >= $totalAmount) $payStatus = 'Paid';
        elseif ($paidAmount > 0)         $payStatus = 'Partial';

        $bill = Bill::create([
            'bill_no'            => Bill::generateNo(),
            'patient_id'         => $request->patient_id,
            'encounter_id'       => $request->encounter_id,
            'billing_package_id' => $request->billing_package_id,
            'bill_date'          => $request->bill_date ?: today(),
            'subtotal'          => $subtotal,
            'discount_percent'  => $discountPct,
            'discount_amount'   => $discountAmount,
            'tax_amount'        => $taxAmount,
            'total_amount'      => $totalAmount,
            'paid_amount'       => $paidAmount,
            'due_amount'        => $dueAmount,
            'status'            => $payStatus,
            'payment_method'    => $request->payment_method,
            'notes'             => $request->notes,
            'insurance_amount'  => $request->insurance_amount ?? 0,
            'insurance_company' => $request->insurance_company,
            'tpa_ref'           => $request->tpa_ref,
            'created_by'        => auth()->id(),
        ]);

        foreach ($request->items as $item) {
            $itemTotal = ($item['qty'] * $item['price']) - ($item['discount'] ?? 0);
            $bill->items()->create([
                'item_type'    => $item['type'],
                'item_name'    => $item['name'],
                'quantity'     => $item['qty'],
                'unit_price'   => $item['price'],
                'discount'     => $item['discount'] ?? 0,
                'tax'          => 0,
                'total'        => $itemTotal,
                'reference_id' => $item['ref_id'] ?? null,
            ]);
        }

        if ($paidAmount > 0) {
            Payment::create([
                'bill_id'        => $bill->id,
                'amount'         => $paidAmount,
                'payment_method' => $request->payment_method ?? 'Cash',
                'payment_date'   => $request->payment_date ?: today(),
                'received_by'    => auth()->id(),
            ]);
        }

        $bill->load('patient');
        ActivityLog::log('created', "Created bill {$bill->bill_no} for {$bill->patient->first_name} {$bill->patient->last_name} — ₹{$bill->total_amount} ({$bill->status})");
        return redirect()->route('billing.show', $bill)->with('success', "Bill {$bill->bill_no} created.");
    }

    public function show(Bill $bill)
    {
        $bill->load('patient', 'encounter', 'items', 'payments', 'createdBy');
        return view('billing.show', compact('bill'));
    }

    public function addPayment(Request $request, Bill $bill)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . $bill->due_amount,
            'payment_method' => 'required|string',
            'transaction_ref'=> 'nullable|string',
            'payment_date'   => 'nullable|date|before_or_equal:today',
        ]);

        Payment::create([
            'bill_id'         => $bill->id,
            'amount'          => $request->amount,
            'payment_method'  => $request->payment_method,
            'transaction_ref' => $request->transaction_ref,
            'payment_date'    => $request->payment_date ?: today(),
            'notes'           => $request->notes,
            'received_by'     => auth()->id(),
        ]);

        $newPaid = $bill->payments()->sum('amount');
        $newDue  = max(0, $bill->total_amount - $newPaid);
        $newStatus = $newDue == 0 ? 'Paid' : 'Partial';
        $bill->update([
            'paid_amount'    => $newPaid,
            'due_amount'     => $newDue,
            'status'         => $newStatus,
        ]);

        if ($newStatus === 'Paid') {
            $this->maybeCreateReferralPayment($bill);
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    /** Correct the bill date of an existing bill (staff often enter bills a day late). */
    public function updateBillDate(Request $request, Bill $bill)
    {
        $data = $request->validate(['bill_date' => 'required|date|before_or_equal:today']);
        $old  = $bill->bill_date->toDateString();
        if ($old === $data['bill_date']) return back();

        $bill->update(['bill_date' => $data['bill_date']]);
        $this->moveVoucherDate(Bill::class, $bill->id, $data['bill_date'], true);
        ActivityLog::log('updated', "Changed bill date of {$bill->bill_no} from {$old} to {$data['bill_date']}");
        return back()->with('success', 'Bill date updated.');
    }

    /** Correct the date of a payment already recorded against a bill. */
    public function updatePaymentDate(Request $request, Bill $bill, Payment $payment)
    {
        abort_unless($payment->bill_id === $bill->id, 404);
        $data = $request->validate(['payment_date' => 'required|date|before_or_equal:today']);
        $old  = $payment->payment_date?->toDateString();
        if ($old === $data['payment_date']) return back();

        $payment->update(['payment_date' => $data['payment_date']]);
        $this->moveVoucherDate(Payment::class, $payment->id, $data['payment_date']);
        ActivityLog::log('updated', "Changed date of ₹{$payment->amount} payment on bill {$bill->bill_no} from {$old} to {$data['payment_date']}");
        return back()->with('success', 'Payment date updated.');
    }

    /** Remove a payment entered by mistake (e.g. recorded twice). Owner only. */
    public function destroyPayment(Bill $bill, Payment $payment)
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Only the clinic owner can delete payments.');
        abort_unless($payment->bill_id === $bill->id, 404);

        DB::transaction(function () use ($bill, $payment) {
            $this->cancelVoucher(Payment::class, $payment->id, "payment deleted from bill {$bill->bill_no}");
            $payment->delete();

            $paid = (float) $bill->payments()->sum('amount');
            $bill->update([
                'paid_amount' => $paid,
                'due_amount'  => max(0, $bill->total_amount - $paid),
                'status'      => $paid <= 0 ? 'Unpaid' : ($paid >= $bill->total_amount ? 'Paid' : 'Partial'),
            ]);
        });

        ActivityLog::log('deleted', "Deleted ₹{$payment->amount} {$payment->payment_method} payment dated {$payment->payment_date?->toDateString()} from bill {$bill->bill_no}");
        return back()->with('success', 'Payment deleted.');
    }

    /** Delete a whole bill entered by mistake (e.g. duplicate). Owner only. */
    public function destroy(Bill $bill)
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Only the clinic owner can delete bills.');
        $bill->load('patient', 'payments');

        DB::transaction(function () use ($bill) {
            $this->cancelVoucher(Bill::class, $bill->id, "bill {$bill->bill_no} deleted");
            foreach ($bill->payments as $p) {
                $this->cancelVoucher(Payment::class, $p->id, "bill {$bill->bill_no} deleted");
            }
            // Unpaid referral commission for this bill no longer applies; paid ones are kept
            ReferralPayment::where('bill_id', $bill->id)->where('paid_amount', '<=', 0)->delete();
            $bill->payments()->delete();
            $bill->delete(); // bill_items cascade
        });

        ActivityLog::log('deleted', "Deleted bill {$bill->bill_no} for " . ($bill->patient?->full_name ?? "patient #{$bill->patient_id}")
            . " — total ₹{$bill->total_amount}, paid ₹{$bill->paid_amount}, dated {$bill->bill_date->toDateString()}");
        return redirect()->route('billing.index')->with('success', "Bill {$bill->bill_no} deleted.");
    }

    /** Cancel (not delete) the auto-posted accounting voucher, keeping the audit trail. */
    private function cancelVoucher(string $sourceType, int $sourceId, string $reason): void
    {
        $voucher = \App\Models\Voucher::where('source_type', $sourceType)->where('source_id', $sourceId)
            ->where('status', '!=', 'Cancelled')->first();
        if ($voucher) app(\App\Services\VoucherService::class)->cancel($voucher, $reason);
    }

    /**
     * Keep the auto-posted accounting voucher on the same date as its bill/payment.
     * Left alone if the new date falls in a different financial year, since the
     * voucher number belongs to its FY series.
     */
    private function moveVoucherDate(string $sourceType, int $sourceId, string $date, bool $withReference = false): void
    {
        $voucher = \App\Models\Voucher::where('source_type', $sourceType)->where('source_id', $sourceId)->first();
        if (!$voucher) return;
        $fy = app(\App\Services\VoucherService::class)->fyYear(\Illuminate\Support\Carbon::parse($date));
        if ((int) $voucher->fy_year !== $fy) return;
        $voucher->update(array_filter([
            'voucher_date'   => $date,
            'reference_date' => $withReference ? $date : null,
        ]));
    }

    public function updateStatus(Request $request, Bill $bill)
    {
        $data = $request->validate([
            'status' => 'required|in:Unpaid,Partial,Paid,Refunded',
            'payment_method' => 'nullable|string',
            'payment_date'   => 'nullable|date|before_or_equal:today',
        ]);

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'Paid') {
            if ($bill->due_amount > 0) {
                Payment::create([
                    'bill_id'        => $bill->id,
                    'amount'         => $bill->due_amount,
                    'payment_method' => $data['payment_method'] ?? 'Cash',
                    'payment_date'   => $data['payment_date'] ?? today(),
                    'notes'          => 'Marked paid from bill status update.',
                    'received_by'    => auth()->id(),
                ]);
            }
            $updates['paid_amount'] = $bill->total_amount;
            $updates['due_amount'] = 0;
        } elseif ($data['status'] === 'Unpaid') {
            $updates['paid_amount'] = 0;
            $updates['due_amount'] = $bill->total_amount;
        } elseif ($data['status'] === 'Partial') {
            $updates['due_amount'] = max(0, $bill->total_amount - $bill->paid_amount);
        } elseif ($data['status'] === 'Refunded') {
            $updates['due_amount'] = 0;
        }

        $bill->update($updates);
        if ($data['status'] === 'Paid') {
            $this->maybeCreateReferralPayment($bill);
        }
        $bill->load('patient');
        ActivityLog::log('updated', "Updated bill {$bill->bill_no} status to {$data['status']} for {$bill->patient->first_name} {$bill->patient->last_name}");
        return back()->with('success', 'Bill status updated.');
    }

    /**
     * Auto-create a referral payment record when a bill is fully paid,
     * if the patient has a referral source with a defined rate.
     */
    protected function maybeCreateReferralPayment(Bill $bill): void
    {
        $bill->load('patient.referralSource');
        $rs = $bill->patient?->referralSource;
        if (!$rs || !$rs->is_active || !$rs->rate_value) return;

        // Only create once per bill
        if (ReferralPayment::where('bill_id', $bill->id)->exists()) return;

        $calculated = 0;
        if ($rs->rate_type === 'percentage') {
            $calculated = round($bill->total_amount * $rs->rate_value / 100, 2);
        } elseif ($rs->rate_type === 'fixed') {
            $calculated = (float) $rs->rate_value;
        }

        if ($calculated <= 0) return;

        ReferralPayment::create([
            'referral_source_id' => $rs->id,
            'patient_id'         => $bill->patient_id,
            'admission_id'       => null,
            'bill_id'            => $bill->id,
            'calculated_amount'  => $calculated,
            'paid_amount'        => 0,
            'status'             => 'pending',
            'created_by'         => auth()->id(),
        ]);
    }

    public function print(Bill $bill)
    {
        $bill->load('patient', 'encounter.doctor', 'items', 'payments');
        return view('billing.print', compact('bill'));
    }

    public function pdf(Bill $bill)
    {
        $bill->load('patient', 'encounter.doctor', 'items', 'payments');
        $pdf = Pdf::loadView('billing.print', compact('bill'));
        // bill_no contains '/' (e.g. INV/2026/00123) which Symfony rejects in
        // the Content-Disposition filename — replace slashes with hyphens.
        $safe = str_replace(['/', '\\'], '-', (string) $bill->bill_no);
        return $pdf->download("bill-{$safe}.pdf");
    }

    public function daily(Request $request)
    {
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();
        $bills = Bill::with('patient', 'items')
            ->whereDate('bill_date', $date)
            ->get();
        $summary = [
            'total'   => $bills->sum('total_amount'),
            'paid'    => Payment::whereDate('payment_date', $date)->sum('amount'),
            'due'     => $bills->sum('due_amount'),
            'discount'=> $bills->sum('discount_amount'),
            'net'     => Payment::whereDate('payment_date', $date)->sum('amount') - $bills->sum('discount_amount'),
            'count'   => $bills->count(),
            'cash'    => Payment::whereDate('payment_date', $date)->where('payment_method', 'Cash')->sum('amount'),
            'card'    => Payment::whereDate('payment_date', $date)->where('payment_method', 'Card')->sum('amount'),
            'upi'     => Payment::whereDate('payment_date', $date)->where('payment_method', 'UPI')->sum('amount'),
            'cheque'  => Payment::whereDate('payment_date', $date)->where('payment_method', 'Cheque')->sum('amount'),
        ];
        return view('billing.daily', compact('bills', 'summary', 'date'));
    }
}
