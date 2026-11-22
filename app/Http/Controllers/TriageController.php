<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OpdToken;
use App\Models\TriageRecord;
use App\Models\ActivityLog;
use App\Models\Doctor;

class TriageController extends Controller
{
    // Nursing Station — combined OPD queue + triage
    public function index()
    {
        $doctors = Doctor::where('is_active', true)->with('departments')->orderBy('first_name')->get();
        $departments = OpdController::opdDepartments();

        $allToday = OpdToken::with('patient', 'doctor', 'triage', 'department')
            ->whereDate('token_date', today())
            ->orderBy('priority', 'desc')
            ->orderBy('token_no')
            ->get();

        // Active tokens (pending triage first, then in progress)
        $tokens = $allToday->whereNotIn('status', ['done', 'skipped'])
            ->sortBy([['is_triaged', 'asc'], ['priority', 'desc'], ['token_no', 'asc']]);

        // Completed today (triaged + done/skipped), last 10
        $done = $allToday->whereIn('status', ['done', 'skipped'])
            ->sortByDesc('updated_at')
            ->take(10);

        $stats = [
            'total'          => $allToday->count(),
            'waiting'        => $allToday->where('status', 'waiting')->count(),
            'in_consultation'=> $allToday->where('status', 'in_consultation')->count(),
            'pending_triage' => $allToday->whereNotIn('status', ['done', 'skipped'])->where('is_triaged', false)->count(),
            'done'           => $allToday->whereIn('status', ['done', 'skipped'])->count(),
        ];

        return view('triage.index', compact('tokens', 'done', 'doctors', 'stats', 'departments'));
    }

    // Show triage form for a token
    public function create(OpdToken $token)
    {
        $token->load('patient','doctor','triage');
        return view('triage.create', compact('token'));
    }

    // Save triage record
    public function store(Request $request, OpdToken $token)
    {
        $request->validate([
            'triage_level'    => 'required|in:green,yellow,red',
            'chief_complaint' => 'nullable|string|max:300',
            'bp_systolic'     => 'nullable|integer|min:40|max:300',
            'bp_diastolic'    => 'nullable|integer|min:20|max:200',
            'pulse'           => 'nullable|integer|min:20|max:300',
            'temperature'     => 'nullable|numeric|min:90|max:115',
            'spo2'            => 'nullable|numeric|min:50|max:100',
            'weight'          => 'nullable|numeric|min:1|max:500',
            'height'          => 'nullable|numeric|min:10|max:300',
            'blood_glucose'   => 'nullable|string|max:30',
            'nurse_notes'     => 'nullable|string',
        ]);

        $bmi = TriageRecord::computeBmi($request->weight, $request->height);

        // Update or create (re-triage allowed)
        $triage = TriageRecord::updateOrCreate(
            ['opd_token_id' => $token->id],
            array_merge($request->only([
                'triage_level','chief_complaint',
                'bp_systolic','bp_diastolic','pulse','temperature',
                'spo2','weight','height','blood_glucose','nurse_notes',
            ]), [
                'patient_id'  => $token->patient_id,
                'bmi'         => $bmi,
                'assessed_by' => auth()->id(),
            ])
        );

        // If urgent triage, escalate token priority
        if ($request->triage_level === 'red') {
            $token->update(['priority' => 'urgent', 'is_triaged' => true]);
        } else {
            $token->update(['is_triaged' => true]);
        }

        $token->load('patient');
        ActivityLog::log('created', "Triaged {$token->display} — {$token->patient->first_name} {$token->patient->last_name} | Level: " . strtoupper($request->triage_level) . " | BP: {$triage->bp} | Pulse: {$request->pulse} | Temp: {$request->temperature}°F | SpO2: {$request->spo2}%");

        return redirect()->route('triage.index')->with('success', "Triage saved for {$token->display}.");
    }

    // View saved triage record
    public function show(OpdToken $token)
    {
        $token->load('patient','doctor','triage.assessedBy');
        abort_if(!$token->triage, 404, 'No triage record found.');
        return view('triage.show', compact('token'));
    }

    // Quick vitals capture without requiring a triage level
    public function quickVitals(Request $request, OpdToken $token)
    {
        $request->validate([
            'bp_systolic'  => 'nullable|integer|min:40|max:300',
            'bp_diastolic' => 'nullable|integer|min:20|max:200',
            'pulse'        => 'nullable|integer|min:20|max:300',
            'temperature'  => 'nullable|numeric|min:90|max:115',
            'spo2'         => 'nullable|numeric|min:50|max:100',
            'weight'       => 'nullable|numeric|min:1|max:500',
        ]);

        TriageRecord::updateOrCreate(
            ['opd_token_id' => $token->id],
            array_merge($request->only(['bp_systolic','bp_diastolic','pulse','temperature','spo2','weight']), [
                'patient_id'  => $token->patient_id,
                'assessed_by' => auth()->id(),
            ])
        );

        return response()->json(['success' => true, 'message' => 'Vitals saved.']);
    }
}
