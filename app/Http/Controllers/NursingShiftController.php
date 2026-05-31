<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\NursingShift;
use Illuminate\Http\Request;

class NursingShiftController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'shift_date'            => 'required|date',
            'shift'                 => 'required|in:morning,evening,night',
            'patient_condition'     => 'nullable|in:' . implode(',', array_keys(NursingShift::CONDITIONS)),
            'assessment'            => 'nullable|string|max:2000',
            'care_given'            => 'nullable|string|max:2000',
            'medications_given'     => 'nullable|string|max:1000',
            'iv_lines'              => 'nullable|string|max:500',
            'pending_tasks'         => 'nullable|string|max:2000',
            'special_instructions'  => 'nullable|string|max:1000',
        ]);

        $data['admission_id'] = $admission->id;
        $data['written_by']   = auth()->id();

        NursingShift::updateOrCreate(
            ['admission_id' => $admission->id, 'shift_date' => $data['shift_date'], 'shift' => $data['shift']],
            $data
        );

        ActivityLog::log('Nursing Shift Saved', "Shift note saved for {$admission->ipd_no} ({$data['shift']})");

        return back()->with('success', 'Shift note saved.')->withFragment('tab-nursing');
    }

    public function destroy(Admission $admission, NursingShift $shift)
    {
        abort_if($shift->admission_id !== $admission->id, 404);
        abort_if($shift->written_by !== auth()->id() && !auth()->user()->isAdmin(), 403);
        $shift->delete();
        return back()->with('success', 'Shift note deleted.')->withFragment('tab-nursing');
    }

    public function handover(Admission $admission)
    {
        $admission->load(['patient', 'ward', 'bed', 'admittingDoctor']);
        $date   = request('date', today()->toDateString());
        $shifts = NursingShift::where('admission_id', $admission->id)
            ->whereDate('shift_date', $date)
            ->with('writtenBy')
            ->orderByRaw("FIELD(shift,'morning','evening','night')")
            ->get()
            ->keyBy('shift');

        $latestVitals = $admission->vitals()->latest('recorded_at')->first();

        return view('admissions.nursing-handover', compact('admission', 'shifts', 'date', 'latestVitals'));
    }
}
