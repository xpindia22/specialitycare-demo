<?php
namespace App\Http\Controllers;

use App\Models\ReferralSource;
use App\Models\ReferralPayment;
use App\Models\Patient;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function index()
    {
        $sources = ReferralSource::withCount('patients')
            ->withSum('payments as total_earned', 'calculated_amount')
            ->withSum('payments as total_paid', 'paid_amount')
            ->orderBy('name')
            ->get();

        return view('referrals.index', compact('sources'));
    }

    public function dashboard()
    {
        $totalPending = ReferralPayment::whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(calculated_amount - paid_amount) as pending')
            ->value('pending') ?? 0;

        $topReferrers = ReferralSource::withCount('patients')
            ->withSum('payments as total_earned', 'calculated_amount')
            ->having('patients_count', '>', 0)
            ->orderByDesc('patients_count')
            ->limit(10)
            ->get();

        $thisMonthEarned = ReferralPayment::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('calculated_amount');

        $recentPayments = ReferralPayment::with(['referralSource', 'patient', 'bill'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('referrals.dashboard', compact(
            'totalPending', 'topReferrers', 'thisMonthEarned', 'recentPayments'
        ));
    }

    public function create()
    {
        return view('referrals.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'type'           => 'required|in:doctor,hospital,clinic,nurse,other',
            'type_other'     => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:200',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:200',
            'address'        => 'nullable|string',
            'rate_type'      => 'required|in:percentage,fixed',
            'rate_value'     => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
            'is_active'      => 'boolean',
        ]);

        $data['is_active']   = $request->boolean('is_active', true);
        $data['type_other']  = $data['type'] === 'other' ? ($data['type_other'] ?? null) : null;
        $source = ReferralSource::create($data);
        ActivityLog::log('Referral Source Created', "Added referral source: {$source->name} (type: {$source->type})");

        return redirect()->route('referrals.show', $source)->with('success', 'Referral source created successfully.');
    }

    /**
     * Lightweight inline create from the patient-registration "Referred By"
     * dropdown — just a name; sensible defaults for the rest so the receptionist
     * doesn't have to leave the patient form. Returns JSON {id, name} so the JS
     * can append the new option and select it. Full editing happens later from
     * /referrals/{id}/edit.
     */
    public function quickStore(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:200']);
        $source = ReferralSource::create([
            'name'       => $data['name'],
            'type'       => 'doctor',
            'rate_type'  => 'percentage',
            'rate_value' => 0,
            'is_active'  => true,
        ]);
        ActivityLog::log('Referral Source Created (quick-add)', "Added referral source from patient form: {$source->name}");
        return response()->json(['id' => $source->id, 'name' => $source->name]);
    }

    public function show(ReferralSource $source)
    {
        $source->load(['patients.referralSource']);
        $payments = ReferralPayment::with(['patient', 'bill', 'admission'])
            ->where('referral_source_id', $source->id)
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total_patients'  => $source->patients()->count(),
            'total_earned'    => $payments->sum('calculated_amount'),
            'total_paid'      => $payments->sum('paid_amount'),
            'pending_balance' => $payments->sum('calculated_amount') - $payments->sum('paid_amount'),
        ];

        return view('referrals.show', compact('source', 'payments', 'stats'));
    }

    public function edit(ReferralSource $source)
    {
        return view('referrals.edit', compact('source'));
    }

    public function update(Request $request, ReferralSource $source)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'type'           => 'required|in:doctor,hospital,clinic,nurse,other',
            'type_other'     => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:200',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:200',
            'address'        => 'nullable|string',
            'rate_type'      => 'required|in:percentage,fixed',
            'rate_value'     => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['type_other'] = $data['type'] === 'other' ? ($data['type_other'] ?? null) : null;
        $source->update($data);
        ActivityLog::log('Referral Source Updated', "Updated referral source: {$source->name} (type: {$source->type})");

        return redirect()->route('referrals.show', $source)->with('success', 'Referral source updated successfully.');
    }

    public function payments(ReferralSource $source)
    {
        $payments = ReferralPayment::with(['patient', 'bill', 'admission'])
            ->where('referral_source_id', $source->id)
            ->orderByDesc('created_at')
            ->get();

        return view('referrals.payments', compact('source', 'payments'));
    }

    public function destroy(ReferralSource $source)
    {
        $name = $source->name;
        $type = $source->type;
        $source->delete();
        ActivityLog::log('Referral Source Deleted', "Deleted referral source: {$name} (type: {$type})");
        return redirect()->route('referrals.index')->with('success', 'Referral source deleted.');
    }

    public function recordPayment(Request $request)
    {
        $data = $request->validate([
            'referral_payment_id' => 'required|exists:referral_payments,id',
            'paid_amount'         => 'required|numeric|min:0.01',
            'payment_method'      => 'required|in:cash,bank_transfer,cheque,upi',
            'transaction_ref'     => 'nullable|string|max:200',
            'payment_date'        => 'required|date',
            'notes'               => 'nullable|string',
        ]);

        $rp = ReferralPayment::findOrFail($data['referral_payment_id']);
        $newPaid = $rp->paid_amount + $data['paid_amount'];
        $status = $newPaid >= $rp->calculated_amount ? 'paid' : 'partial';

        $rp->update([
            'paid_amount'     => $newPaid,
            'payment_method'  => $data['payment_method'],
            'transaction_ref' => $data['transaction_ref'] ?? $rp->transaction_ref,
            'payment_date'    => $data['payment_date'],
            'notes'           => $data['notes'] ?? $rp->notes,
            'status'          => $status,
        ]);

        $sourceName = $rp->referralSource->name ?? ('ID ' . $rp->referral_source_id);
        ActivityLog::log('Referral Payment Recorded', "Recorded ₹{$data['paid_amount']} payment via {$data['payment_method']} for referral source: {$sourceName}");
        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}
