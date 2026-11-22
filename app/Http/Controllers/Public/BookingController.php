<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Services\DoctorAvailability;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    /** Doctors patients can book online */
    private function bookableDoctors()
    {
        return Doctor::where('is_active', true)->where('show_online', true)
            ->with('department', 'departments', 'schedules')
            ->orderBy('first_name')->get();
    }

    public function show(DoctorAvailability $avail)
    {
        $doctors = $this->bookableDoctors();
        $timetables = $doctors->mapWithKeys(fn ($d) => [$d->id => $avail->timetableLabel($d)]);
        // per speciality: [deptId][doctorId] => "Tue, Thu 08:00–19:00"
        $deptTimetables = [];
        foreach ($doctors as $d) foreach ($d->departmentIds() as $dep) $deptTimetables[$dep][$d->id] = $avail->timetableLabel($d, $dep);
        // Specialities that have at least one bookable doctor
        $departments = Department::where('is_active', true)->where('show_online', true)->ordered()->get()
            ->map(function ($dep) use ($doctors) {
                $dep->bookable = $doctors->filter(fn ($doc) => in_array($dep->id, $doc->departmentIds()))->count();
                return $dep;
            })->filter(fn ($dep) => $dep->bookable > 0)->values();

        $clinic = [
            'name'    => setting('clinic_name', config('app.name')),
            'address' => setting('clinic_address'),
            'phone'   => setting('clinic_phone'),
            'email'   => setting('clinic_email'),
        ];

        return view('public.booking.show', compact('doctors', 'departments', 'timetables', 'deptTimetables', 'clinic'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:120',
            'phone'            => 'required|string|max:20',
            'age'              => 'required|integer|min:0|max:120',
            'gender'           => 'required|in:Male,Female,Other',
            'department_id'    => 'nullable|exists:departments,id',
            'doctor_id'        => ['required', 'regex:/^(any|\d+)$/'],
            'appointment_date' => 'required|date|after_or_equal:today|before:'.now()->addDays(60)->toDateString(),
            'appointment_time' => 'nullable|date_format:H:i',
            'reason'           => 'nullable|string|max:300',
            'website'          => 'nullable|max:0', // honeypot — must be empty
        ], [
            'website.max' => 'Spam blocked.',
        ]);

        $avail = app(DoctorAvailability::class);
        $date  = Carbon::parse($data['appointment_date'])->startOfDay();
        $bookable = $this->bookableDoctors();

        if ($data['doctor_id'] === 'any') {
            // "Any available doctor" in the chosen speciality: earliest free slot that day
            $dept = isset($data['department_id']) ? Department::find($data['department_id']) : null;
            $pool = $dept ? $bookable->filter(fn ($d) => in_array($dept->id, $d->departmentIds())) : collect();
            if (!empty($data['appointment_time'])) {
                // a time was picked: only doctors free at that time
                $wanted = Carbon::parse($data['appointment_time'])->format('H:i:s');
                $pool = $pool->filter(fn ($d) => (bool) collect($avail->slots($d, $date, $dept?->id))->first(fn ($sl) => $sl['value'] === $wanted && $sl['available']));
            }
            $pick = $pool->isNotEmpty() ? $avail->anyDoctor($pool, $date, $dept?->id) : null;
            if (!$pick) {
                return back()->withErrors(['doctor_id' => 'No ' . ($dept->name ?? '') . ' doctor has a free slot on ' . $date->format('d M Y') . '. Please pick another date.'])->withInput();
            }
            [$doctor, $firstSlot] = $pick;
            $data['appointment_time'] = $data['appointment_time'] ?: substr($firstSlot, 0, 5);
        } else {
            $doctor = $bookable->firstWhere('id', (int) $data['doctor_id']);
            abort_unless($doctor, 404);
            // a speciality the doctor does not work in is ignored
            if (!empty($data['department_id']) && !in_array((int) $data['department_id'], $doctor->departmentIds())) {
                $data['department_id'] = null;
            }
        }

        // Respect the doctor's timetable, leave and session limits
        if ($leave = $avail->leaveOn($doctor, $date)) {
            return back()->withErrors(['appointment_date' => "Dr. {$doctor->first_name} {$doctor->last_name} is on leave on " . $date->format('d M Y') . '. Please pick another date or doctor.'])->withInput();
        }
        $deptId = !empty($data['department_id']) ? (int) $data['department_id'] : null;
        $slots = collect($avail->slots($doctor, $date, $deptId));
        $hasTimetable = $doctor->schedules->isNotEmpty();
        if ($hasTimetable && $slots->isEmpty()) {
            return back()->withErrors(['appointment_date' => "Dr. {$doctor->first_name} {$doctor->last_name} has no " . ($deptId ? Department::find($deptId)?->name . ' ' : '') . "OPD on " . $date->format('l, d M Y') . '. Please pick another date.'])->withInput();
        }
        if (!empty($data['appointment_time']) && $slots->isNotEmpty()) {
            $wanted = Carbon::parse($data['appointment_time'])->format('H:i:s');
            $slot = $slots->firstWhere('value', $wanted);
            if ($slot && !$slot['available']) {
                return back()->withErrors(['appointment_time' => 'Sorry, that time was just taken or the session is full. Please pick another slot.'])->withInput();
            }
        }
        if ($slots->isNotEmpty() && $slots->where('available', true)->isEmpty()) {
            return back()->withErrors(['appointment_date' => 'All slots on ' . $date->format('d M Y') . ' are booked. Please pick another date.'])->withInput();
        }
        if (empty($data['appointment_time']) && $hasTimetable) {
            $data['appointment_time'] = $slots->firstWhere('available', true)['time'];
        }

        $phoneNorm = preg_replace('/\D+/', '', $data['phone']);
        if (strlen($phoneNorm) < 10) {
            return back()->withErrors(['phone' => 'Please enter a valid phone number.'])->withInput();
        }
        $phoneNorm = substr($phoneNorm, -10); // last 10 digits

        // Find or create patient by phone (last-10 match)
        $patient = Patient::where(function ($q) use ($phoneNorm) {
            $q->whereRaw("RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 10) = ?", [$phoneNorm])
              ->orWhereRaw("RIGHT(REGEXP_REPLACE(alt_phone, '[^0-9]', ''), 10) = ?", [$phoneNorm]);
        })->first();

        if (!$patient) {
            $parts = preg_split('/\s+/', trim($data['name']), 2);
            $first = $parts[0];
            $last  = $parts[1] ?? '';
            $patient = Patient::create([
                'jhc_id'        => Patient::generateJhcId(),
                'first_name'    => $first,
                'last_name'     => $last ?: '-',
                'phone'         => $phoneNorm,
                'gender'        => $data['gender'],
                'date_of_birth' => now()->subYears((int)$data['age'])->toDateString(),
                'address'       => '',
                'is_active'     => true,
                'referred_by'   => 'Online Booking',
            ]);
        }

        // NOTE: slot conflict check intentionally omitted — the clinic allows
        // multiple patients in the same slot (walk-ins, family appointments,
        // overflow). Matches staff booking flow in AppointmentController@store.

        // appointment_time is NOT NULL in the schema, but the public form
        // marks it optional ("we'll confirm a slot"). When patient leaves it
        // blank, default to the doctor's start_time so the row inserts; staff
        // adjust to a real slot when they phone-confirm.
        $time = $data['appointment_time']
            ?: ($doctor->start_time ? Carbon::parse($doctor->start_time)->format('H:i') : '10:00');

        $appointment = Appointment::create([
            'appointment_no'   => Appointment::generateNo(),
            'patient_id'       => $patient->id,
            'doctor_id'        => $doctor->id,
            'department_id'    => $data['department_id'] ?? $doctor->department_id,
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $time,
            'duration'         => $doctor->slot_duration ?? 15,
            'type'             => 'New Consultation',
            'status'           => 'Scheduled',
            'chief_complaint'  => $data['reason'] ?? null,
            'booking_source'   => 'public_online',
            'public_name'      => $data['name'],
            'public_phone'     => $phoneNorm,
            'created_by'       => null,
        ]);

        return redirect()->route('public.book.confirmed', $appointment->appointment_no);
    }

    /** Free slots for the booking page: ?doctor_id=N|any&department_id=&date= */
    public function slots(Request $request, DoctorAvailability $avail)
    {
        $request->validate(['date' => 'required|date|after_or_equal:today', 'doctor_id' => ['required', 'regex:/^(any|\d+)$/'], 'department_id' => 'nullable|integer']);
        $date = Carbon::parse($request->date)->startOfDay();
        $bookable = $this->bookableDoctors();

        if ($request->doctor_id === 'any') {
            $pool = $bookable->filter(fn ($d) => in_array((int) $request->department_id, $d->departmentIds()));
            // union of every doctor's free times; the doctor is assigned when booking
            $times = $pool->flatMap(fn ($d) => collect($avail->slots($d, $date, (int) $request->department_id))->where('available', true)->pluck('time'))
                ->unique()->sort()->values();
            return response()->json(['slots' => $times->map(fn ($t) => ['time' => $t, 'available' => true])->all(), 'note' => $times->isEmpty() ? 'No free slots this day.' : null]);
        }

        $doctor = $bookable->firstWhere('id', (int) $request->doctor_id);
        if (!$doctor) return response()->json(['slots' => [], 'note' => 'Doctor not available for online booking.']);
        if ($leave = $avail->leaveOn($doctor, $date)) {
            return response()->json(['slots' => [], 'note' => 'Doctor is on leave this day. Please pick another date.']);
        }
        $deptId = (int) $request->department_id;
        if (!$deptId || !in_array($deptId, $doctor->departmentIds())) $deptId = null;
        $slots = collect($avail->slots($doctor, $date, $deptId))->map(fn ($s) => ['time' => $s['time'], 'available' => $s['available']]);
        $note = $slots->isEmpty() ? 'Doctor has no OPD this day. Please pick another date.'
              : ($slots->where('available', true)->isEmpty() ? 'All slots are booked this day. Please pick another date.' : null);
        return response()->json(['slots' => $slots->values()->all(), 'note' => $note]);
    }

    public function confirmed(string $appointmentNo)
    {
        $appointment = Appointment::with('doctor', 'patient', 'department')
            ->where('appointment_no', $appointmentNo)
            ->where('booking_source', 'public_online')
            ->firstOrFail();

        $clinic = [
            'name'    => setting('clinic_name', config('app.name')),
            'address' => setting('clinic_address'),
            'phone'   => setting('clinic_phone'),
        ];

        return view('public.booking.confirmed', compact('appointment', 'clinic'));
    }
}
