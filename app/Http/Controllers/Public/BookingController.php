<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    public function show()
    {
        $doctors = Doctor::where('is_active', true)
            ->with('department')
            ->orderBy('first_name')
            ->get(['id','first_name','last_name','specialization','qualification','photo','consultation_fee','available_days','start_time','end_time','department_id','bio']);

        $clinic = [
            'name'    => setting('clinic_name', config('app.name')),
            'address' => setting('clinic_address'),
            'phone'   => setting('clinic_phone'),
            'email'   => setting('clinic_email'),
        ];

        return view('public.booking.show', compact('doctors', 'clinic'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:120',
            'phone'            => 'required|string|max:20',
            'age'              => 'required|integer|min:0|max:120',
            'gender'           => 'required|in:Male,Female,Other',
            'doctor_id'        => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today|before:'.now()->addDays(60)->toDateString(),
            'appointment_time' => 'nullable|date_format:H:i',
            'reason'           => 'nullable|string|max:300',
            'website'          => 'nullable|max:0', // honeypot — must be empty
        ], [
            'website.max' => 'Spam blocked.',
        ]);

        $doctor = Doctor::where('is_active', true)->findOrFail($data['doctor_id']);

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

    public function confirmed(string $appointmentNo)
    {
        $appointment = Appointment::with('doctor', 'patient')
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
