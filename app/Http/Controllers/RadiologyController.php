<?php
namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Admission;
use App\Models\Encounter;
use Illuminate\Http\Request;

class RadiologyController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyOrder::with(['patient', 'orderedBy'])
            ->orderByDesc('created_at');

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('modality')) $query->where('modality', $request->modality);
        if ($request->filled('date'))     $query->whereDate('created_at', $request->date);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('order_no', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%")
                        ->orWhere('jhc_id', 'like', "%$q%"));
            });
        }

        $orders = $query->paginate(25)->withQueryString();
        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();

        return view('radiology.index', compact('orders', 'doctors'));
    }

    public function create(Request $request)
    {
        $patient   = $request->patient_id  ? Patient::findOrFail($request->patient_id)  : null;
        $admission = $request->admission_id ? Admission::findOrFail($request->admission_id) : null;
        $doctors   = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $patients  = Patient::where('is_active', true)->orderBy('first_name')->get();

        return view('radiology.create', compact('patient', 'admission', 'doctors', 'patients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'          => 'required|exists:patients,id',
            'admission_id'        => 'nullable|exists:admissions,id',
            'encounter_id'        => 'nullable|exists:encounters,id',
            'ordered_by'          => 'required|exists:doctors,id',
            'modality'            => 'required|in:xray,ct,mri,usg,mammography,dexa,other',
            'body_part'           => 'required|string|max:200',
            'clinical_indication' => 'nullable|string',
            'urgency'             => 'required|in:routine,urgent,emergency',
            'scheduled_at'        => 'nullable|date',
        ]);

        $data['order_no']   = RadiologyOrder::generateOrderNo();
        $data['created_by'] = auth()->id();
        $data['status']     = 'ordered';

        $order = RadiologyOrder::create($data);

        return redirect()->route('radiology.show', $order)->with('success', "Radiology order {$order->order_no} created.");
    }

    public function show(RadiologyOrder $order)
    {
        $order->load(['patient', 'orderedBy', 'admission', 'encounter', 'createdBy']);
        return view('radiology.show', compact('order'));
    }

    public function update(Request $request, RadiologyOrder $order)
    {
        $data = $request->validate([
            'findings'    => 'nullable|string',
            'impression'  => 'nullable|string',
            'reported_by' => 'nullable|string|max:200',
            'status'      => 'required|in:ordered,scheduled,completed,reported',
            'scheduled_at'=> 'nullable|date',
        ]);

        if ($data['status'] === 'completed' && !$order->completed_at) {
            $data['completed_at'] = now();
        }

        $order->update($data);

        return redirect()->route('radiology.show', $order)->with('success', 'Order updated successfully.');
    }

    public function printReport(RadiologyOrder $order)
    {
        $order->load(['patient', 'orderedBy', 'admission']);
        return view('radiology.print', compact('order'));
    }
}
