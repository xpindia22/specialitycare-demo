<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\OpdReferral;
use App\Models\OpdToken;
use App\Services\OpdFee;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** OPD referral to another department / doctor: today → token in that queue, later → reception books it. */
class OpdReferralController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'open');
        $deptId = $request->integer('department_id') ?: null;
        $referrals = OpdReferral::with(['patient', 'fromDoctor', 'fromDepartment', 'toDepartment', 'toDoctor', 'token', 'appointment', 'toEncounter'])
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['pending', 'queued', 'booked']))
            ->when($status !== 'open' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($status !== 'open', fn ($q) => $q->where('created_at', '>=', now()->subDays(60)))
            ->when($deptId, fn ($q) => $q->where('to_department_id', $deptId))
            ->orderByRaw("FIELD(status, 'pending', 'queued', 'booked', 'seen', 'cancelled')")
            ->orderByDesc('priority')->latest('id')
            ->limit(300)->get();
        $departments = OpdController::opdDepartments();
        $pending = OpdReferral::where('status', 'pending')->count();
        return view('opd.referrals', compact('referrals', 'departments', 'status', 'deptId', 'pending'));
    }

    /** Refer from a visit */
    public function store(Request $request, Encounter $encounter)
    {
        abort_if($encounter->trashed(), 404);
        $data = $request->validate([
            'to_department_id' => 'required|exists:departments,id',
            'to_doctor_id'     => 'nullable|exists:doctors,id',
            'when'             => 'required|in:today,later',
            'preferred_date'   => 'nullable|required_if:when,later|date|after_or_equal:today',
            'priority'         => 'required|in:normal,urgent',
            'reason'           => 'required|string|max:255',
            'notes'            => 'nullable|string|max:3000',
            'finish_mine'      => 'nullable|boolean',
        ], ['preferred_date.required_if' => 'Pick the date for a later referral.']);

        $toDoctor = !empty($data['to_doctor_id']) ? Doctor::find($data['to_doctor_id']) : null;
        if ($toDoctor && !in_array((int) $data['to_department_id'], $toDoctor->departmentIds())) {
            throw ValidationException::withMessages(['to_doctor_id' => "{$toDoctor->full_name} does not sit in that department."]);
        }
        if ($toDoctor && $toDoctor->id === $encounter->doctor_id) {
            throw ValidationException::withMessages(['to_doctor_id' => 'That is the doctor of this visit — pick another doctor.']);
        }

        $referral = OpdReferral::create([
            'patient_id'         => $encounter->patient_id,
            'from_encounter_id'  => $encounter->id,
            'from_doctor_id'     => $encounter->doctor_id,
            'from_department_id' => OpdFee::departmentOf($encounter),
            'to_department_id'   => $data['to_department_id'],
            'to_doctor_id'       => $toDoctor?->id,
            'when'               => $data['when'],
            'preferred_date'     => $data['when'] === 'later' ? $data['preferred_date'] : today(),
            'priority'           => $data['priority'],
            'reason'             => trim($data['reason']),
            'notes'              => $data['notes'] ?? null,
            'status'             => 'pending',
            'created_by'         => auth()->id(),
        ]);
        $referral->load('toDepartment', 'patient');

        $msg = "Referred to {$referral->toDepartment->name}" . ($toDoctor ? " ({$toDoctor->full_name})" : '');
        if ($data['when'] === 'today') {
            $token = $this->queue($referral);
            $msg .= ". Token {$token->display} is in the {$referral->toDepartment->name} queue.";
        } else {
            $msg .= ' for ' . $referral->preferred_date->format('d M') . '. Reception will book it from OPD → Referrals.';
        }

        if ($request->boolean('finish_mine') && $encounter->opdToken && in_array($encounter->opdToken->status, ['waiting', 'called', 'in_consultation'])) {
            $encounter->opdToken->update(['status' => 'done', 'completed_at' => now()]);
            $msg .= " Your token {$encounter->opdToken->display} is marked done.";
        }

        ActivityLog::log('created', "OPD referral: {$referral->patient->full_name} from {$referral->from_label} to {$referral->toDepartment->name}" . ($toDoctor ? " / {$toDoctor->full_name}" : '') . " — {$referral->reason}");
        return back()->with('success', $msg);
    }

    /** Reception: the patient has come — put them in the department's queue today */
    public function issueToken(OpdReferral $referral)
    {
        if (!in_array($referral->status, ['pending', 'booked'])) {
            return back()->with('error', 'This referral is already ' . strtolower($referral->status_label) . '.');
        }
        $token = $this->queue($referral);
        ActivityLog::log('created', "OPD referral token {$token->display} issued for {$referral->patient->full_name} ({$referral->toDepartment->name})");
        return back()->with('success', "Token {$token->display} issued in the {$referral->toDepartment->name} queue.");
    }

    public function cancel(Request $request, OpdReferral $referral)
    {
        if (in_array($referral->status, ['seen', 'cancelled'])) {
            return back()->with('error', 'This referral is already ' . strtolower($referral->status_label) . '.');
        }
        // A queued token that nobody has started goes off the queue too
        if ($referral->token && in_array($referral->token->status, ['waiting', 'called']) && !$referral->token->encounter) {
            $referral->token->update(['status' => 'skipped', 'completed_at' => now()]);
        }
        $referral->update(['status' => 'cancelled']);
        $referral->load('patient', 'toDepartment');
        ActivityLog::log('updated', "OPD referral cancelled: {$referral->patient->full_name} to {$referral->toDepartment->name}");
        return back()->with('success', 'Referral cancelled.');
    }

    /** Referral letter for the patient to carry / the other doctor to read */
    public function print(OpdReferral $referral)
    {
        $referral->load(['patient', 'fromDoctor', 'fromDepartment', 'toDepartment', 'toDoctor', 'token', 'appointment',
            'fromEncounter.diagnoses', 'fromEncounter.prescriptions.items.medicine']);
        return view('opd.referral-print', ['r' => $referral]);
    }

    /** Token in the referred department's series; reuses the patient's open token there today */
    private function queue(OpdReferral $referral): OpdToken
    {
        $open = OpdToken::where('patient_id', $referral->patient_id)->whereDate('token_date', today())
            ->where('department_id', $referral->to_department_id)
            ->when($referral->to_doctor_id, fn ($q) => $q->where('doctor_id', $referral->to_doctor_id))
            ->whereIn('status', ['waiting', 'called'])->first();
        $token = $open ?? OpdToken::issue([
            'patient_id'    => $referral->patient_id,
            'doctor_id'     => $referral->to_doctor_id,
            'department_id' => $referral->to_department_id,
            'status'        => 'waiting',
            'priority'      => $referral->priority,
            'purpose'       => $referral->purposeText(),
            'created_by'    => auth()->id(),
        ]);
        if ($open && $referral->priority === 'urgent') $open->update(['priority' => 'urgent']);
        $referral->update(['token_id' => $token->id, 'status' => 'queued']);
        return $token;
    }
}
