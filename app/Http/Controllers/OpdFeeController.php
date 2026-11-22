<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\OpdDiscount;
use App\Models\OpdFeeRule;
use App\Models\Patient;
use App\Services\OpdFee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Administration → OPD Fee Rules: fees by department / doctor, follow-up windows, discounts. */
class OpdFeeController extends Controller
{
    public function index()
    {
        $rules = OpdFeeRule::with(['department', 'doctor'])->get()
            ->sortBy(fn ($r) => [($r->department_id ? 1 : 0) + ($r->doctor_id ? 2 : 0), $r->department?->name, $r->doctor?->first_name])->values();
        $discounts = OpdDiscount::orderByDesc('is_active')->orderBy('name')->get();
        $departments = Department::ordered()->where('is_active', true)->get();
        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $canEdit = auth()->user()->isAdmin();
        return view('opd.fees', compact('rules', 'discounts', 'departments', 'doctors', 'canEdit'));
    }

    public function storeRule(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $data = $this->ruleData($request);
        $rule = OpdFeeRule::create($data);
        ActivityLog::log('created', 'OPD fee rule added: ' . $rule->load('department', 'doctor')->scope_label);
        return back()->with('success', 'Fee rule saved.');
    }

    public function updateRule(Request $request, OpdFeeRule $rule)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $rule->update($this->ruleData($request, $rule));
        ActivityLog::log('updated', 'OPD fee rule changed: ' . $rule->load('department', 'doctor')->scope_label);
        return back()->with('success', 'Fee rule saved.');
    }

    public function destroyRule(OpdFeeRule $rule)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        ActivityLog::log('deleted', 'OPD fee rule removed: ' . $rule->scope_label);
        $rule->delete();
        return back()->with('success', 'Fee rule removed.');
    }

    public function storeDiscount(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $d = OpdDiscount::create($this->discountData($request));
        ActivityLog::log('created', "OPD discount added: {$d->name} {$d->percent}%");
        return back()->with('success', 'Discount saved.');
    }

    public function updateDiscount(Request $request, OpdDiscount $discount)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $discount->update($this->discountData($request));
        ActivityLog::log('updated', "OPD discount changed: {$discount->name} {$discount->percent}%");
        return back()->with('success', 'Discount saved.');
    }

    public function destroyDiscount(OpdDiscount $discount)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        ActivityLog::log('deleted', "OPD discount removed: {$discount->name}");
        $discount->delete();
        return back()->with('success', 'Discount removed.');
    }

    /** JSON fee for a patient + doctor (+ department) today — used by the checker and reception. */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'patient_id'    => 'required|exists:patients,id',
            'doctor_id'     => 'nullable|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'date'          => 'nullable|date',
        ]);
        $doctor = !empty($data['doctor_id']) ? Doctor::find($data['doctor_id']) : null;
        $q = OpdFee::quote(Patient::findOrFail($data['patient_id']), $doctor,
            $data['department_id'] ?? $doctor?->department_id, !empty($data['date']) ? \Carbon\Carbon::parse($data['date']) : null);
        return response()->json($q);
    }

    private function ruleData(Request $request, ?OpdFeeRule $rule = null): array
    {
        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'doctor_id'     => 'nullable|exists:doctors,id',
            'new_fee'       => 'nullable|numeric|min:0|max:1000000',
            'followup_fee'  => 'nullable|numeric|min:0|max:1000000',
            'followup_days' => 'nullable|integer|min:0|max:3650',
            'free_days'     => 'nullable|integer|min:0|max:3650',
            'free_visits'   => 'nullable|integer|min:1|max:20',
            'notes'         => 'nullable|string|max:255',
            'is_active'     => 'nullable|boolean',
        ]);
        $data['free_visits'] = $data['free_visits'] ?? 1;
        $data['is_active'] = $request->boolean('is_active', true);
        $dup = OpdFeeRule::where('department_id', $data['department_id'] ?? null)->where('doctor_id', $data['doctor_id'] ?? null)
            ->when($rule, fn ($q) => $q->where('id', '!=', $rule->id))->exists();
        if ($dup) {
            throw \Illuminate\Validation\ValidationException::withMessages(['department_id' => 'There is already a rule for this department / doctor — edit that one.']);
        }
        return $data;
    }

    private function discountData(Request $request): array
    {
        $data = $request->validate([
            'name'      => 'required|string|max:80',
            'percent'   => 'required|numeric|min:0|max:100',
            'age_min'   => 'nullable|integer|min:0|max:130',
            'age_max'   => 'nullable|integer|min:0|max:130',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
