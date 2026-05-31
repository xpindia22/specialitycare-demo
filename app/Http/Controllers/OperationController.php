<?php
namespace App\Http\Controllers;

use App\Models\Operation;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Admission;
use Illuminate\Http\Request;

class OperationController extends Controller
{
    public function index(Request $request)
    {
        $query = Operation::with(['patient', 'surgeon', 'admission'])
            ->orderByDesc('operation_date')
            ->orderByDesc('id');

        if ($request->filled('date'))     $query->where('operation_date', $request->date);
        if ($request->filled('surgeon'))  $query->where('surgeon_id', $request->surgeon);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('operation_no', 'like', "%$q%")
                    ->orWhere('procedure_name', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%"));
            });
        }

        $operations = $query->paginate(25)->withQueryString();
        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();

        $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $weekOps   = Operation::with(['patient', 'surgeon'])
            ->whereBetween('operation_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('operation_date')->orderBy('start_time')
            ->get()
            ->groupBy(fn($op) => $op->operation_date->format('Y-m-d'));

        return view('ot.index', compact('operations', 'doctors', 'weekStart', 'weekEnd', 'weekOps'));
    }

    public function create(Request $request)
    {
        $patient   = $request->patient_id   ? Patient::findOrFail($request->patient_id)   : null;
        $admission = $request->admission_id ? Admission::findOrFail($request->admission_id) : null;
        $doctors   = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $patients  = Patient::where('is_active', true)->orderBy('first_name')->get();

        return view('ot.create', compact('patient', 'admission', 'doctors', 'patients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'admission_id'     => 'nullable|exists:admissions,id',
            'surgeon_id'       => 'required|exists:doctors,id',
            'anesthetist_name' => 'nullable|string|max:200',
            'operation_date'   => 'required|date',
            'start_time'       => 'nullable|date_format:H:i',
            'end_time'         => 'nullable|date_format:H:i',
            'procedure_name'   => 'required|string|max:300',
            'icd_code'         => 'nullable|string|max:50',
            'diagnosis'        => 'required|string|max:300',
            'anesthesia_type'  => 'required|in:general,spinal,epidural,local,regional,none',
            'pre_op_diagnosis' => 'nullable|string',
            'post_op_diagnosis'=> 'nullable|string',
            'findings'         => 'nullable|string',
            'complications'    => 'nullable|string',
            'outcome'          => 'required|in:successful,complicated,abandoned,patient_expired',
            'scrub_nurse'      => 'nullable|string|max:200',
            'circulating_nurse'=> 'nullable|string|max:200',
            'blood_transfused' => 'boolean',
            'blood_units'      => 'nullable|integer|min:0',
            'status'           => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes'            => 'nullable|string',
        ]);

        $data['operation_no'] = Operation::generateOpNo();
        $data['created_by']   = auth()->id();
        $data['blood_transfused'] = $request->boolean('blood_transfused');

        $operation = Operation::create($data);

        return redirect()->route('ot.show', $operation)->with('success', "Operation {$operation->operation_no} recorded.");
    }

    public function show(Operation $operation)
    {
        $operation->load(['patient', 'surgeon', 'admission', 'createdBy']);
        return view('ot.show', compact('operation'));
    }

    public function update(Request $request, Operation $operation)
    {
        $data = $request->validate([
            'pre_op_diagnosis' => 'nullable|string',
            'post_op_diagnosis'=> 'nullable|string',
            'findings'         => 'nullable|string',
            'complications'    => 'nullable|string',
            'outcome'          => 'required|in:successful,complicated,abandoned,patient_expired',
            'status'           => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes'            => 'nullable|string',
            'start_time'       => 'nullable|date_format:H:i',
            'end_time'         => 'nullable|date_format:H:i',
            'blood_transfused' => 'boolean',
            'blood_units'      => 'nullable|integer|min:0',
        ]);

        $data['blood_transfused'] = $request->boolean('blood_transfused');
        $operation->update($data);

        return redirect()->route('ot.show', $operation)->with('success', 'Operation updated.');
    }

    public function printRegister(Request $request)
    {
        $from = $request->get('from', today()->toDateString());
        $to   = $request->get('to',   today()->toDateString());

        $operations = Operation::with(['patient', 'surgeon', 'admission'])
            ->whereBetween('operation_date', [$from, $to])
            ->orderBy('operation_date')
            ->orderBy('start_time')
            ->get();

        return view('ot.print', compact('operations', 'from', 'to'));
    }
}
