<?php
namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\PayrollAdjustment;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PayrollAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        [$y, $m] = array_pad(explode('-', $month), 2, null);
        $start = Carbon::create((int)$y, (int)$m, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $adjustments = PayrollAdjustment::with('staff', 'doctor')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')->orderBy('id', 'desc')
            ->get();

        // Unified people list for the picker — same key format as attendance.
        $people = collect();
        foreach (Staff::where('is_active', true)->orderBy('first_name')->get() as $s) {
            $people->push((object) [
                'key'  => 'staff_' . $s->id,
                'name' => trim($s->first_name . ' ' . $s->last_name),
                'kind' => 'staff',
                'designation' => $s->designation ?: 'Staff',
                'code' => $s->staff_id,
            ]);
        }
        foreach (Doctor::where('is_active', true)->orderBy('first_name')->get() as $d) {
            $people->push((object) [
                'key'  => 'doctor_' . $d->id,
                'name' => 'Dr. ' . trim($d->first_name . ' ' . $d->last_name),
                'kind' => 'doctor',
                'designation' => $d->specialization ?: 'Doctor',
                'code' => $d->doctor_id,
            ]);
        }
        $people = $people->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('hr.adjustments', [
            'month'       => $month,
            'monthLabel'  => $start->format('F Y'),
            'adjustments' => $adjustments,
            'people'      => $people,
            'types'       => PayrollAdjustment::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'person_key' => ['required', 'regex:/^(staff|doctor)_\d+$/'],
            'date'       => 'required|date',
            'type'       => ['required', Rule::in(array_keys(PayrollAdjustment::TYPES))],
            'amount'     => 'required|numeric|min:0.01|max:1000000',
            'notes'      => 'nullable|string|max:255',
        ]);

        [$kind, $id] = explode('_', $data['person_key']);
        $id = (int) $id;
        if ($kind === 'staff') {
            abort_unless(Staff::whereKey($id)->exists(), 422, 'Invalid staff');
            $fkData = ['staff_id' => $id, 'doctor_id' => null];
        } else {
            abort_unless(Doctor::whereKey($id)->exists(), 422, 'Invalid doctor');
            $fkData = ['doctor_id' => $id, 'staff_id' => null];
        }

        PayrollAdjustment::create(array_merge($fkData, [
            'date'       => $data['date'],
            'type'       => $data['type'],
            'amount'     => $data['amount'],
            'notes'      => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->route('hr.adjustments.index', ['month' => Carbon::parse($data['date'])->format('Y-m')])
            ->with('success', 'Adjustment recorded.');
    }

    public function destroy(PayrollAdjustment $adjustment)
    {
        $month = Carbon::parse($adjustment->date)->format('Y-m');
        $adjustment->delete();
        return redirect()->route('hr.adjustments.index', ['month' => $month])
            ->with('success', 'Adjustment deleted.');
    }
}
