<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\IpdCharge;
use App\Models\IpdDeposit;
use Illuminate\Http\Request;

class IpdBillingController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'charge_date' => 'required|date',
            'category'    => 'required|in:' . implode(',', array_keys(IpdCharge::CATEGORIES)),
            'description' => 'required|string|max:300',
            'unit_price'  => 'required|numeric|min:0',
            'quantity'    => 'required|numeric|min:0.01',
            'notes'       => 'nullable|string|max:300',
        ]);
        $data['amount']       = round($data['unit_price'] * $data['quantity'], 2);
        $data['admission_id'] = $admission->id;
        $data['created_by']   = auth()->id();

        IpdCharge::create($data);

        ActivityLog::log(
            'IPD Charge Added',
            "Added ₹{$data['amount']} ({$data['category']}) to admission {$admission->ipd_no}"
        );

        return back()->with('success', 'Charge added.')->withFragment('tab-billing');
    }

    public function destroy(Admission $admission, IpdCharge $charge)
    {
        abort_if($charge->admission_id !== $admission->id, 404);
        abort_if($charge->created_by !== auth()->id() && !auth()->user()->isAdmin(), 403);

        ActivityLog::log('IPD Charge Deleted', "Deleted charge ₹{$charge->amount} from {$admission->ipd_no}");
        $charge->delete();

        return back()->with('success', 'Charge removed.')->withFragment('tab-billing');
    }

    public function bill(Admission $admission)
    {
        $admission->load(['patient', 'ward', 'bed', 'admittingDoctor']);
        $charges    = $admission->charges()->with('createdBy')->orderBy('charge_date')->get();
        $byCategory = $charges->groupBy('category');
        $total      = $charges->sum('amount');
        $deposits   = $admission->deposits()->orderBy('created_at')->get();
        $totalPaid  = $deposits->sum('amount');
        $balance    = max(0, $total - $totalPaid);

        return view('admissions.bill', compact('admission', 'charges', 'byCategory', 'total', 'deposits', 'totalPaid', 'balance'));
    }

    public function storeDeposit(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'payment_method'  => 'required|in:cash,card,upi,cheque,other',
            'transaction_ref' => 'nullable|string|max:200',
            'notes'           => 'nullable|string|max:500',
        ]);

        $data['admission_id'] = $admission->id;
        $data['received_by']  = auth()->id();

        IpdDeposit::create($data);

        ActivityLog::log('IPD Deposit', "Deposit ₹{$data['amount']} received for admission {$admission->ipd_no}");

        return back()->with('success', 'Deposit recorded successfully.')->withFragment('tab-billing');
    }

    public function destroyDeposit(Admission $admission, IpdDeposit $deposit)
    {
        abort_if($deposit->admission_id !== $admission->id, 404);
        abort_if(!auth()->user()->isAdmin(), 403);

        ActivityLog::log('IPD Deposit Deleted', "Deleted deposit ₹{$deposit->amount} from {$admission->ipd_no}");
        $deposit->delete();

        return back()->with('success', 'Deposit deleted.')->withFragment('tab-billing');
    }
}
