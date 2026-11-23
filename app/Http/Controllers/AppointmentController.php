<?php
// FILE: app/Http/Controllers/AppointmentController.php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Encounter;
use App\Models\OpdToken;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function calendar(Request $request)
    {
        // FullCalendar handles rendering + navigation client-side; we just seed
        // the initial view/date and the doctor filter. Events come from the
        // calendarEvents() JSON feed.
        $view = in_array($request->get('view'), ['week','month','year'], true) ? $request->get('view') : 'week';
        $ref  = Carbon::parse($request->get('ref', $request->get('week', today()->toDateString())));
        $doctorId = $request->get('doctor_id');
        $doctors  = Doctor::where('is_active', true)->orderBy('first_name')->get();

        return view('appointments.calendar', compact('view','ref','doctorId','doctors'));
    }

    /** JSON event feed for FullCalendar (date range + optional doctor filter). */
    public function calendarEvents(Request $request)
    {
        $startDate = $request->filled('start') ? Carbon::parse($request->get('start'))->toDateString() : today()->startOfMonth()->toDateString();
        $endDate   = $request->filled('end')   ? Carbon::parse($request->get('end'))->toDateString()   : today()->endOfMonth()->toDateString();

        $q = Appointment::with([
                'patient','doctor',
                'encounter.diagnoses',
                'encounter.prescriptions.items',
                'encounter.labOrders.items.test',
                'encounter.labOrders.items.panel',
            ])
            ->whereBetween('appointment_date', [$startDate, $endDate]);
        if ($request->doctor_id) $q->where('doctor_id', $request->doctor_id);

        $colors = [
            'Scheduled'=>'#2563eb','Confirmed'=>'#16a34a','Checked-In'=>'#ca8a04',
            'In-Progress'=>'#ea580c','Completed'=>'#15803d','Cancelled'=>'#dc2626',
            'No-Show'=>'#6b7280','Rescheduled'=>'#7c3aed',
        ];
        $withDoctor = ! $request->doctor_id;

        $events = $q->get()->map(function ($a) use ($colors, $withDoctor) {
            $time  = $a->appointment_time ? Carbon::parse($a->appointment_time)->format('H:i:s') : '09:00:00';
            $start = $a->appointment_date->toDateString().'T'.$time;
            $dur   = (int) ($a->duration ?: 30);
            $end   = Carbon::parse($start)->addMinutes($dur)->format('Y-m-d\TH:i:s');
            $c     = $colors[$a->status] ?? '#2563eb';
            $title = $a->patient->full_name ?? $a->public_name ?? 'Patient';
            if ($withDoctor && $a->doctor) $title .= ' · '.$a->doctor->full_name;

            // Clinical summary for the hover bubble — only present once the
            // appointment has produced an encounter (i.e. the patient was seen).
            $clinical = null;
            if ($enc = $a->encounter) {
                $dx = $enc->diagnoses->pluck('diagnosis_name')->filter()->take(4)->implode(', ');
                if (! $dx && $enc->impression) $dx = \Illuminate\Support\Str::limit(strip_tags($enc->impression), 80);
                $rxCount = $enc->prescriptions->sum(fn ($r) => $r->items->count());
                $labs = $enc->labOrders
                    ->flatMap(fn ($lo) => $lo->items->map(fn ($i) => $i->test?->name ?? $i->panel?->name))
                    ->filter()->unique()->take(5)->implode(', ');
                $clinical = [
                    'diagnosis' => $dx ?: null,
                    'treatment' => $enc->plan ? \Illuminate\Support\Str::limit(strip_tags($enc->plan), 90) : null,
                    'rx_count'  => $rxCount ?: null,
                    'labs'      => $labs ?: null,
                ];
                // Drop the whole block if nothing clinical was recorded.
                if (! array_filter($clinical)) $clinical = null;
            }

            return [
                'id'    => $a->id,
                'title' => $title,
                'start' => $start,
                'end'   => $end,
                'backgroundColor' => $c,
                'borderColor'     => $c,
                'url'   => route('appointments.show', $a),
                // extendedProps powers the hover bubble + the drag guard. `locked`
                // means the visit already happened — the calendar must offer a
                // follow-up instead of moving the historical record.
                'extendedProps'   => [
                    'status'      => $a->status,
                    'no'          => $a->appointment_no,
                    'type'        => $a->type,
                    'patient'     => $a->patient->full_name ?? $a->public_name ?? 'Patient',
                    'doctor'      => $a->doctor->full_name ?? null,
                    'patient_id'  => $a->patient_id,
                    'doctor_id'   => $a->doctor_id,
                    'locked'      => $a->isSeen(),
                    'checked_in'  => $a->checked_in_at ? $a->checked_in_at->format('d M, h:i A') : null,
                    'completed'   => $a->completed_at ? $a->completed_at->format('d M, h:i A') : null,
                    'encounter_id'=> $a->encounter?->id,
                    'clinical'    => $clinical,
                ],
            ];
        });

        return response()->json($events);
    }

    /** Lightweight in-place move from a calendar drag/resize (no new appt, no reason). */
    public function move(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'time'     => 'required',
            'duration' => 'nullable|integer|min:5|max:600',
        ]);
        // Can't reschedule into the past — block a move onto a past date (today
        // is fine). Date granularity so an earlier clock-time today isn't "past".
        if (Carbon::parse($data['date'])->startOfDay()->isBefore(today())) {
            return response()->json([
                'ok' => false,
                'message' => 'Can’t move an appointment to a past date — pick today or later.',
            ], 422);
        }
        // A visit that already happened is a historical record — refuse to move
        // it. The calendar UI offers a follow-up instead; this guards direct POSTs.
        if ($appointment->isSeen()) {
            return response()->json([
                'ok'    => false,
                'error' => 'seen',
                'message' => 'This patient has already been seen — book a follow-up instead of moving the original visit.',
            ], 422);
        }
        $appointment->appointment_date = Carbon::parse($data['date'])->toDateString();
        $appointment->appointment_time = Carbon::parse($data['time'])->format('H:i:s');
        if (!empty($data['duration'])) $appointment->duration = (int) $data['duration'];
        $appointment->save();

        return response()->json(['ok' => true, 'no' => $appointment->appointment_no]);
    }

    /**
     * Book a follow-up from a calendar drag onto an already-seen visit. The
     * original (historical) appointment is left untouched and stays visible;
     * a NEW Follow-up is created at the drop slot for the same patient+doctor,
     * pre-filled from the source encounter's follow-up instructions when present.
     * Returns JSON so the calendar can refetch and show both visits.
     */
    public function followUpFromCalendar(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable',
        ]);
        if (!$appointment->patient_id) {
            return response()->json([
                'ok' => false,
                'message' => 'This visit has no linked patient — open the booking form to add one.',
            ], 422);
        }

        $enc  = $appointment->encounter;
        $time = !empty($data['time']) ? Carbon::parse($data['time'])->format('H:i:s') : '17:00:00';

        // A follow-up is a future visit — refuse a drop onto a past DATE (today
        // is fine, matching the booking form's after_or_equal:today). Compared
        // at date granularity so an earlier clock-time today isn't "past".
        if (Carbon::parse($data['date'])->startOfDay()->isBefore(today())) {
            return response()->json([
                'ok' => false,
                'message' => 'A follow-up can’t be booked on a past date — pick today or later.',
            ], 422);
        }

        $followUp = Appointment::create([
            'appointment_no'   => Appointment::generateNo(),
            'patient_id'       => $appointment->patient_id,
            'doctor_id'        => $appointment->doctor_id,
            'appointment_date' => Carbon::parse($data['date'])->toDateString(),
            'appointment_time' => $time,
            'duration'         => $appointment->doctor?->slot_duration ?: ($appointment->duration ?: 15),
            'type'             => 'Follow-up',
            'status'           => 'Scheduled',
            'chief_complaint'  => \Illuminate\Support\Str::limit($enc?->follow_up_instructions ?: 'Follow-up visit', 200),
            'notes'            => 'Follow-up of ' . $appointment->appointment_no,
            'created_by'       => auth()->id(),
            'booking_source'   => 'calendar',
        ]);

        return response()->json(['ok' => true, 'id' => $followUp->id, 'no' => $followUp->appointment_no]);
    }

    public function index(Request $request)
    {
        $range = $request->get('range', 'today');   // today | upcoming | all
        $day   = $request->get('day');               // today|tomorrow|dayafter|later|past (legend filter)
        $query = Appointment::with('patient', 'doctor');
        // Appointments cancelled because their visit was deleted are listed only under status = Cancelled.
        if ($request->status !== 'Cancelled') $query->withoutDeletedVisits();
        if ($request->date) {
            $query->whereDate('appointment_date', $request->date);
        } elseif ($request->search) {
            // search spans all dates
        } elseif ($day === 'today') {
            $query->whereDate('appointment_date', today());
        } elseif ($day === 'tomorrow') {
            $query->whereDate('appointment_date', today()->addDay());
        } elseif ($day === 'dayafter') {
            $query->whereDate('appointment_date', today()->addDays(2));
        } elseif ($day === 'later') {
            $query->whereDate('appointment_date', '>', today()->addDays(2));
        } elseif ($day === 'past') {
            $query->whereDate('appointment_date', '<', today());
        } elseif ($range === 'upcoming') {
            $query->whereDate('appointment_date', '>=', today())
                  ->whereNotIn('status', ['Cancelled', 'No-Show', 'Rescheduled']);
        } elseif ($range === 'all') {
            // no date filter
        } else {
            $query->whereDate('appointment_date', today());
        }
        if ($request->doctor_id) $query->where('doctor_id', $request->doctor_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('appointment_no', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%")
                        ->orWhere('jhc_id', 'like', "%$q%")
                        ->orWhere('phone', 'like', "%$q%"));
            });
        }
        // Newest first — bias to the most-recently-CREATED appointment so a
        // booking you just made shows at the top, even on the "today" filter
        // where every row shares the same date. Secondary: latest future date.
        $appointments = $query->orderBy('appointment_date', 'desc')->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $doctors = Doctor::where('is_active', true)->get();
        return view('appointments.index', compact('appointments', 'doctors', 'range', 'day'));
    }

    public function create(Request $request)
    {
        $doctors = Doctor::where('is_active', true)->with('departments', 'department')->orderBy('first_name')->get();
        $departments = \App\Models\Department::where('is_active', true)->ordered()->get()
            ->filter(fn ($d) => $doctors->contains(fn ($doc) => in_array($d->id, $doc->departmentIds())))->values();
        $patient = $request->patient_id ? Patient::find($request->patient_id) : null;
        $patients = $patient ? collect() : Patient::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'jhc_id', 'phone']);
        return view('appointments.create', compact('doctors', 'departments', 'patient', 'patients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'required|exists:doctors,id',
            'department_id'    => 'nullable|exists:departments,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'nullable',
            'type'             => 'required|in:New,New Consultation,Follow-up,Review,Emergency,Teleconsult',
            'chief_complaint'  => 'nullable|string',
            'notes'            => 'nullable|string',
            'meeting_url'      => 'nullable|url|max:500',
        ]);


        // Auto-assign a 1-minute-incrementing slot starting 17:00 when the
        // user didn't pick a specific time (or accepted the default 17:00:00
        // hidden value). 1st patient → 17:00, 2nd → 17:01, 3rd → 17:02, …
        // Lets the appointment list view encode arrival order naturally.
        $defaultTimes = [null, '', '17:00', '17:00:00'];
        if (in_array($validated['appointment_time'] ?? null, $defaultTimes, true)) {
            $validated['appointment_time'] = self::nextAvailableSlot(
                (int) $validated['doctor_id'],
                $validated['appointment_date']
            );
        }

        $validated['appointment_no'] = Appointment::generateNo();
        $validated['status'] = 'Scheduled';
        $validated['created_by'] = auth()->id();
        $validated['duration'] = Doctor::find($validated['doctor_id'])->slot_duration ?? 15;

        // Teleconsult auto-generates a Jitsi Meet room URL if the user didn't
        // paste one. Room name is unguessable (8-char base32 of random bytes)
        // — Jitsi public rooms are URL-protected, not auth-protected.
        if ($validated['type'] === 'Teleconsult' && empty($validated['meeting_url'])) {
            $slug = 'jhc-' . strtolower(\Illuminate\Support\Str::slug($validated['appointment_no'])) . '-' . \Illuminate\Support\Str::random(8);
            $validated['meeting_url'] = "https://meet.jit.si/{$slug}";
        }

        $appointment = Appointment::create($validated);

        // Booked for an OPD referral: link it so the referral shows as booked, then seen
        if ($request->filled('referral_id')) {
            \App\Models\OpdReferral::whereKey($request->integer('referral_id'))
                ->where('patient_id', $appointment->patient_id)->whereIn('status', ['pending', 'booked'])
                ->update(['appointment_id' => $appointment->id, 'status' => 'booked']);
        }

        // Teleconsult: fire the meeting-link notification (rides on the
        // appointment_confirm preference). Free-form WA until the
        // teleconsult_link Gupshup template is approved.
        if ($appointment->type === 'Teleconsult' && !empty($appointment->meeting_url)) {
            app(\App\Services\PatientNotifier::class)->sendTeleconsultLink($appointment);
        }

        return redirect()->route('appointments.show', $appointment)
            ->with('success', "Appointment {$appointment->appointment_no} created successfully.");
    }

    /**
     * Book Follow-up entry point: redirects to /appointments/create with the
     * patient + doctor + suggested date + chief-complaint pre-filled from the
     * encounter. Staff then picks the exact time (defaults to 17:00) and
     * confirms. Replaces the prior one-click auto-create flow (which didn't
     * let staff override date/time).
     */
    public function bookFollowUp(Encounter $encounter)
    {
        abort_unless($encounter->follow_up_date, 422, 'This visit has no follow-up date set.');
        $date = Carbon::parse($encounter->follow_up_date);
        if ($date->isPast()) $date = Carbon::today();

        return redirect()->route('appointments.create', [
            'patient_id'      => $encounter->patient_id,
            'doctor_id'       => $encounter->doctor_id,
            'date'            => $date->toDateString(),
            'type'            => 'Follow-up',
            'chief_complaint' => \Illuminate\Support\Str::limit($encounter->follow_up_instructions ?: 'Follow-up visit', 200),
            'from_encounter'  => $encounter->id,
        ]);
    }

    /** Shared reminder message text for an appointment. */
    private function reminderBody(Appointment $a): string
    {
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $phone  = setting('clinic_phone');
        $when   = $a->appointment_date->format('d M Y')
                . ($a->appointment_time ? ' at ' . Carbon::parse($a->appointment_time)->format('h:i A') : '');
        // Doctor::full_name already includes the "Dr." prefix.
        $doctor = $a->doctor->full_name ?? null;
        return "Hi " . ($a->patient->full_name ?: 'there') . ",\n\n"
             . "Reminder: your appointment" . ($doctor ? " with {$doctor}" : '') . " is on {$when}.\n"
             . "Appointment no: " . ($a->appointment_no ?: $a->id) . ".\n"
             . "Please arrive about 10 minutes early" . ($phone ? "; to reschedule call {$phone}" : '') . ".\n\n— {$clinic}";
    }

    /** Manually email this appointment's reminder now (to patient, CC clinic). */
    public function remindEmail(Appointment $appointment)
    {
        abort_unless($appointment->patient && $appointment->patient->email, 422, 'This patient has no email on file.');
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $cc = setting('clinic_email') ?: 'demo@specialitycare.co.in';
        try {
            \Illuminate\Support\Facades\Mail::raw($this->reminderBody($appointment), function ($m) use ($appointment, $cc, $clinic) {
                $m->to($appointment->patient->email)->subject("Appointment reminder — {$clinic} ({$appointment->appointment_date->format('d M Y')})");
                if ($cc) $m->cc($cc);
            });
            $appointment->forceFill(['reminder_email_sent_at' => now()])->save();
            return response()->json(['success' => true, 'message' => 'Email reminder sent to ' . $appointment->patient->email]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Email failed: ' . $e->getMessage()], 500);
        }
    }

    /** Manually send this appointment's reminder over WhatsApp now. */
    public function remindWhatsApp(Appointment $appointment, \App\Services\WhatsAppService $wa)
    {
        abort_unless($wa->isEnabled(), 422, 'WhatsApp is not enabled (notifications module is off).');
        abort_unless($appointment->patient && ($appointment->patient->phone || $appointment->patient->alt_phone), 422, 'This patient has no phone on file.');

        $a = $appointment;
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $when   = $a->appointment_date->format('d M Y')
                . ($a->appointment_time ? ' at ' . Carbon::parse($a->appointment_time)->format('h:i A') : '');
        $doctor = $a->doctor->full_name ?? null;          // already includes "Dr."
        $name      = $a->patient->full_name ?: 'there';
        $docClause = $doctor ? "with {$doctor}" : 'with our doctor';
        $apptNo    = (string) ($a->appointment_no ?: $a->id);

        // Template appointment_reminder: {{1}} name, {{2}} "with Dr. X", {{3}} when, {{4}} appt no, {{5}} clinic.
        $r = $wa->sendTemplateToPatient(
            $a->patient,
            'appointment_reminder',
            [$name, $docClause, $when, $apptNo, $clinic],
            $this->reminderBody($a),
            'appointment_reminder',
            auth()->id(),
        );
        if ($r['ok'] ?? false) {
            return response()->json(['success' => true, 'message' => 'WhatsApp reminder sent.']);
        }
        return response()->json(['success' => false, 'message' => 'WhatsApp not sent: ' . ($r['skipped'] ?? $r['error'] ?? 'unknown')], 422);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load('patient', 'doctor', 'encounter');
        return view('appointments.show', compact('appointment'));
    }

    public function checkin(Appointment $appointment)
    {
        $appointment->update([
            'status'        => 'Checked-In',
            'checked_in_at' => now(),
        ]);

        // Issue OPD token if one doesn't already exist for this patient+doctor today
        $alreadyHasToken = OpdToken::whereDate('token_date', today())
            ->where('patient_id', $appointment->patient_id)
            ->where('doctor_id', $appointment->doctor_id)
            ->exists();

        if (!$alreadyHasToken) {
            $token = OpdToken::issue([
                'patient_id'    => $appointment->patient_id,
                'doctor_id'     => $appointment->doctor_id,
                'department_id' => $appointment->department_id,
                'status'     => 'waiting',
                'priority'   => 'normal',
                'purpose'    => $appointment->type . ($appointment->chief_complaint ? ' — ' . $appointment->chief_complaint : ''),
                'created_by' => auth()->id(),
            ]);
            return back()->with('success', "Patient checked in. OPD token {$token->display} issued.");
        }

        return back()->with('success', 'Patient checked in successfully.');
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'nullable',
            'reschedule_reason'=> 'required|string|max:255',
        ]);

        // NOTE: slot conflict check intentionally omitted — see store() comment.

        // Mark old as rescheduled
        $appointment->update(['status' => 'Rescheduled']);

        // Create new appointment
        $new = Appointment::create([
            'appointment_no'    => Appointment::generateNo(),
            'patient_id'        => $appointment->patient_id,
            'doctor_id'         => $appointment->doctor_id,
            'appointment_date'  => $validated['appointment_date'],
            'appointment_time'  => $validated['appointment_time'],
            'duration'          => $appointment->duration,
            'type'              => $appointment->type,
            'status'            => 'Scheduled',
            'chief_complaint'   => $appointment->chief_complaint,
            'rescheduled_from'  => $appointment->id,
            'reschedule_reason' => $validated['reschedule_reason'],
            'created_by'        => auth()->id(),
        ]);

        return redirect()->route('appointments.show', $new)
            ->with('success', "Appointment rescheduled. New appointment: {$new->appointment_no}");
    }

    public function noShow(Appointment $appointment)
    {
        if (!in_array($appointment->status, ['Scheduled', 'Confirmed', 'Checked-In'])) {
            return back()->with('error', 'This appointment cannot be marked as No-Show.');
        }
        $appointment->update(['status' => 'No-Show']);
        return back()->with('success', "Appointment {$appointment->appointment_no} marked as No-Show.");
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        $appointment->update([
            'status' => 'Cancelled',
            'notes'  => $appointment->notes . "\nCancellation reason: " . ($request->reason ?? 'Not specified'),
        ]);
        return back()->with('success', 'Appointment cancelled.');
    }

    public function edit(Appointment $appointment)
    {
        if (!in_array($appointment->status, ['Scheduled', 'Confirmed'])) {
            return redirect()->route('appointments.show', $appointment)
                ->with('error', 'Only Scheduled or Confirmed appointments can be edited.');
        }
        $appointment->load('patient', 'doctor');
        $doctors = Doctor::where('is_active', true)->get();
        return view('appointments.edit', compact('appointment', 'doctors'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        if (!in_array($appointment->status, ['Scheduled', 'Confirmed'])) {
            return redirect()->route('appointments.show', $appointment)
                ->with('error', 'Cannot edit this appointment.');
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'nullable',
            'type'             => 'required|in:New Consultation,Follow-up,Review,Emergency,Teleconsult',
            'status'           => 'required|in:Scheduled,Confirmed',
            'chief_complaint'  => 'nullable|string',
            'notes'            => 'nullable|string',
            'meeting_url'      => 'nullable|url|max:500',
        ]);

        // Generate a Jitsi URL on edit if the type is being flipped to
        // Teleconsult and no meeting_url has ever been saved on this row.
        if ($validated['type'] === 'Teleconsult' && empty($appointment->meeting_url) && empty($validated['meeting_url'] ?? null)) {
            $slug = 'jhc-' . strtolower(\Illuminate\Support\Str::slug($appointment->appointment_no)) . '-' . \Illuminate\Support\Str::random(8);
            $validated['meeting_url'] = "https://meet.jit.si/{$slug}";
        }

        $wasNotTeleconsult = $appointment->getOriginal('type') !== 'Teleconsult';
        $appointment->update($validated);

        // If the appointment just became a Teleconsult (with a URL), send
        // the meeting link. No re-send on every edit — only on the transition.
        if ($wasNotTeleconsult && $appointment->type === 'Teleconsult' && !empty($appointment->meeting_url)) {
            app(\App\Services\PatientNotifier::class)->sendTeleconsultLink($appointment);
        }

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment updated.');
    }

    public function getSlots(Request $request, \App\Services\DoctorAvailability $avail)
    {
        $request->validate(['doctor_id' => 'required', 'date' => 'required|date']);
        $doctor = Doctor::with('schedules')->findOrFail($request->doctor_id);
        $date   = Carbon::parse($request->date);
        $leave  = $avail->leaveOn($doctor, $date);
        $deptId = $request->integer('department_id') ?: null;
        if ($deptId && !in_array($deptId, $doctor->departmentIds())) $deptId = null;

        return response()->json([
            'slots'    => $avail->slots($doctor, $date, $deptId),
            'on_leave' => $leave ? ($leave->reason ?: 'On leave') : null,
        ]);
    }

    public function startEncounter(Appointment $appointment)
    {
        if ($appointment->encounter) {
            return redirect()->route('encounters.show', $appointment->encounter);
        }

        // A visit happening today on an appointment booked for a later date (e.g. a
        // "Book follow-up" form saved with the pre-filled follow-up date) would
        // otherwise drop out of today's appointment list. Move it to today.
        $movedFrom = null;
        if ($appointment->appointment_date && $appointment->appointment_date->isAfter(today())) {
            $movedFrom = $appointment->appointment_date->format('d M Y');
            $appointment->update([
                'appointment_date'  => today()->toDateString(),
                'reschedule_reason' => "Visit started on " . today()->format('d M Y') . " (was booked for {$movedFrom})",
            ]);
        }

        $appointment->update(['status' => 'In-Progress']);

        $encounter = Encounter::create([
            'encounter_no'    => Encounter::generateNo(),
            'patient_id'      => $appointment->patient_id,
            'doctor_id'       => $appointment->doctor_id,
            'appointment_id'  => $appointment->id,
            'encounter_date'  => today(),
            'encounter_time'  => now()->format('H:i:s'),
            'visit_type'      => $appointment->type === 'New Consultation' ? 'New Visit' : 'Revisit',
            'status'          => 'Open',
            'chief_complaint' => $appointment->chief_complaint,
            'created_by'      => auth()->id(),
        ]);

        $redirect = redirect()->route('encounters.show', $encounter);
        return $movedFrom
            ? $redirect->with('success', "This appointment was booked for {$movedFrom}; it has been moved to today because the visit is happening now.")
            : $redirect;
    }

    /**
     * Next available 1-minute slot for a doctor on a date, starting from $startTime.
     * Returns "HH:MM:00". Skips times already taken by Scheduled/Confirmed/
     * Checked-In/In-Progress appointments for the same doctor on the same date.
     * Used by store() (manual booking) + EncounterController::autoBookFollowUp.
     */
    public static function nextAvailableSlot(int $doctorId, string $date, string $startTime = '17:00:00'): string
    {
        $taken = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['Cancelled', 'No-Show', 'Rescheduled'])
            ->pluck('appointment_time')
            ->map(fn ($t) => substr((string) $t, 0, 8))
            ->flip()
            ->all();

        $time = Carbon::createFromTimeString($startTime);
        // Try up to 6 hours of 1-min slots (17:00 → 23:00). Effectively unlimited.
        for ($i = 0; $i < 360; $i++) {
            $candidate = $time->format('H:i:s');
            if (!isset($taken[$candidate])) {
                return $candidate;
            }
            $time->addMinute();
        }
        // Fallback (shouldn't reach — 360 1-min slots is more than a day's bookings)
        return $startTime;
    }
}
