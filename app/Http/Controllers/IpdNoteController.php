<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\IpdNote;
use App\Models\IpdVitals;
use Illuminate\Http\Request;

class IpdNoteController extends Controller
{
    public function storeNote(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'note_type' => 'required|in:' . implode(',', array_keys(IpdNote::TYPES)),
            'noted_at'  => 'required|date',
            'note'      => 'required|string|max:5000',
        ]);
        $data['admission_id'] = $admission->id;
        $data['written_by']   = auth()->id();

        IpdNote::create($data);

        ActivityLog::log(
            'IPD Note Added',
            "Added {$data['note_type']} note to admission {$admission->ipd_no}"
        );

        return back()->with('success', 'Note added.')->withFragment('tab-notes');
    }

    public function destroyNote(Admission $admission, IpdNote $note)
    {
        abort_if($note->admission_id !== $admission->id, 404);
        abort_if($note->written_by !== auth()->id() && !auth()->user()->isAdmin(), 403);

        $note->delete();

        ActivityLog::log('IPD Note Deleted', "Deleted note from admission {$admission->ipd_no}");

        return back()->with('success', 'Note deleted.')->withFragment('tab-notes');
    }

    public function storeVitals(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'recorded_at'      => 'required|date',
            'temperature'      => 'nullable|numeric|between:90,115',
            'pulse'            => 'nullable|integer|between:20,300',
            'respiration_rate' => 'nullable|integer|between:5,60',
            'bp_systolic'      => 'nullable|integer|between:50,300',
            'bp_diastolic'     => 'nullable|integer|between:20,200',
            'spo2'             => 'nullable|integer|between:50,100',
            'weight'           => 'nullable|numeric|between:1,300',
            'blood_glucose'    => 'nullable|numeric|between:20,800',
            'urine_output'     => 'nullable|string|max:100',
            'avpu'             => ['nullable', \Illuminate\Validation\Rule::in(array_keys(IpdVitals::AVPU))],
            'o2_device'        => ['nullable', \Illuminate\Validation\Rule::in(array_keys(IpdVitals::O2_DEVICES))],
            'notes'            => 'nullable|string|max:500',
        ]);
        $data['admission_id'] = $admission->id;
        $data['recorded_by']  = auth()->id();

        IpdVitals::create($data);

        ActivityLog::log('IPD Vitals Recorded', "Vitals recorded for admission {$admission->ipd_no}");

        return back()->with('success', 'Vitals recorded.')->withFragment('tab-vitals');
    }

    public function updateVitals(Request $request, Admission $admission, IpdVitals $vitals)
    {
        abort_if($vitals->admission_id !== $admission->id, 404);
        abort_if($vitals->recorded_by !== auth()->id() && !auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'recorded_at'      => 'required|date',
            'temperature'      => 'nullable|numeric|between:90,115',
            'pulse'            => 'nullable|integer|between:20,300',
            'respiration_rate' => 'nullable|integer|between:5,60',
            'bp_systolic'      => 'nullable|integer|between:50,300',
            'bp_diastolic'     => 'nullable|integer|between:20,200',
            'spo2'             => 'nullable|integer|between:50,100',
            'weight'           => 'nullable|numeric|between:1,300',
            'blood_glucose'    => 'nullable|numeric|between:20,800',
            'urine_output'     => 'nullable|string|max:100',
            'avpu'             => ['nullable', \Illuminate\Validation\Rule::in(array_keys(IpdVitals::AVPU))],
            'o2_device'        => ['nullable', \Illuminate\Validation\Rule::in(array_keys(IpdVitals::O2_DEVICES))],
            'notes'            => 'nullable|string|max:500',
        ]);

        $vitals->update($data);

        ActivityLog::log('IPD Vitals Updated', "Vitals updated for admission {$admission->ipd_no}");

        return back()->with('success', 'Vitals updated.')->withFragment('tab-vitals');
    }

    public function destroyVitals(Admission $admission, IpdVitals $vitals)
    {
        abort_if($vitals->admission_id !== $admission->id, 404);
        abort_if($vitals->recorded_by !== auth()->id() && !auth()->user()->isAdmin(), 403);

        $vitals->delete();

        return back()->with('success', 'Vitals entry deleted.')->withFragment('tab-vitals');
    }
}
