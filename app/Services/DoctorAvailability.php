<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * When a doctor sits in OPD and which slots are free.
 *
 * A doctor's weekly timetable (doctor_schedules, several sessions a day allowed) is used
 * when it has any rows; otherwise the older single window on the doctor record
 * (available_days + start_time/end_time) still applies, so nothing changes for doctors
 * not set up yet. Leave dates close the whole day. A session with max_patients is
 * full once that many appointments are booked in it.
 *
 * Pass a department id to get only the sessions the doctor holds for that department
 * (e.g. Cardiology on Tue/Thu). A timetable session without a department counts for any
 * of the doctor's departments, and so does the old single window.
 */
class DoctorAvailability
{
    /** Appointment statuses that no longer hold a slot */
    private const FREE_STATUSES = ['Cancelled', 'No-Show', 'Rescheduled'];

    public function leaveOn(Doctor $doctor, CarbonInterface $date): ?DoctorLeave
    {
        return DoctorLeave::where('doctor_id', $doctor->id)
            ->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->first();
    }

    /**
     * Sessions on that date: [['start' => 'H:i:s', 'end' => 'H:i:s', 'slot' => min, 'max' => ?int, 'room' => ?string, 'department_id' => ?int]]
     * Empty when the doctor does not sit that day or is on leave.
     */
    public function sessionsOn(Doctor $doctor, CarbonInterface $date, ?int $deptId = null): array
    {
        if (!$doctor->is_active || $this->leaveOn($doctor, $date)) return [];

        $schedules = $doctor->relationLoaded('schedules') ? $doctor->schedules : $doctor->schedules()->get();
        $default   = (int) ($doctor->slot_duration ?: 15);

        if ($schedules->isNotEmpty()) {
            return $schedules->where('day_of_week', $date->dayOfWeek)
                ->filter(fn ($s) => !$deptId || !$s->department_id || (int) $s->department_id === $deptId)
                ->sortBy('start_time')
                ->map(fn ($s) => [
                    'start' => substr($s->start_time, 0, 8), 'end' => substr($s->end_time, 0, 8),
                    'slot'  => (int) ($s->slot_minutes ?: $default), 'max' => $s->max_patients,
                    'room'  => $s->room, 'department_id' => $s->department_id,
                ])->values()->all();
        }

        $days = is_array($doctor->available_days) ? $doctor->available_days : (json_decode((string) $doctor->available_days, true) ?: []);
        if ($days && !in_array($date->format('l'), $days, true)) return [];
        if (!$doctor->start_time || !$doctor->end_time) return [];
        return [[
            'start' => substr($doctor->start_time, 0, 8), 'end' => substr($doctor->end_time, 0, 8),
            'slot'  => $default, 'max' => null, 'room' => null, 'department_id' => null,
        ]];
    }

    /** Booked appointment times ('H:i:s') of the doctor on that date */
    private function bookedTimes(Doctor $doctor, CarbonInterface $date): Collection
    {
        return Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', self::FREE_STATUSES)
            ->pluck('appointment_time')
            ->map(fn ($t) => substr((string) $t, 0, 8));
    }

    /**
     * Slots for the date, session by session:
     * [['time' => 'H:i', 'value' => 'H:i:s', 'available' => bool, 'session' => 0, 'full' => bool]]
     */
    public function slots(Doctor $doctor, CarbonInterface $date, ?int $deptId = null): array
    {
        $booked  = $this->bookedTimes($doctor, $date);
        $taken   = $booked->flip();
        $nowCut  = $date->isToday() ? now()->format('H:i:s') : null;
        $out     = [];

        foreach ($this->sessionsOn($doctor, $date, $deptId) as $i => $s) {
            $inSession = $booked->filter(fn ($t) => $t >= $s['start'] && $t < $s['end'])->count();
            $full      = $s['max'] && $inSession >= $s['max'];
            $t   = Carbon::parse($date->toDateString() . ' ' . $s['start']);
            $end = Carbon::parse($date->toDateString() . ' ' . $s['end']);
            while ($t < $end) {
                $v = $t->format('H:i:s');
                $out[] = [
                    'time'      => $t->format('H:i'),
                    'value'     => $v,
                    'available' => !$full && !isset($taken[$v]) && (!$nowCut || $v > $nowCut),
                    'session'   => $i,
                    'full'      => (bool) $full,
                ];
                $t->addMinutes(max(1, $s['slot']));
            }
        }
        return $out;
    }

    /** Earliest free slot ('H:i:s') of the doctor on that date, or null */
    public function firstFreeSlot(Doctor $doctor, CarbonInterface $date, ?int $deptId = null): ?string
    {
        foreach ($this->slots($doctor, $date, $deptId) as $slot) {
            if ($slot['available']) return $slot['value'];
        }
        return null;
    }

    /**
     * "Any available doctor" in a department: the doctor with the earliest free slot that
     * day (ties go to the one with fewer bookings). Returns [Doctor, 'H:i:s'] or null.
     */
    public function anyDoctor(iterable $doctors, CarbonInterface $date, ?int $deptId = null): ?array
    {
        $best = null;
        foreach ($doctors as $doctor) {
            $slot = $this->firstFreeSlot($doctor, $date, $deptId);
            if (!$slot) continue;
            $load = $this->bookedTimes($doctor, $date)->count();
            if (!$best || $slot < $best[1] || ($slot === $best[1] && $load < $best[2])) $best = [$doctor, $slot, $load];
        }
        return $best ? [$best[0], $best[1]] : null;
    }

    /** Short human timetable, e.g. "Mon, Wed, Fri 10:00–13:00 · Sat 17:00–20:00" */
    public function timetableLabel(Doctor $doctor, ?int $deptId = null): string
    {
        $schedules = $doctor->relationLoaded('schedules') ? $doctor->schedules : $doctor->schedules()->get();
        $fmt = fn ($t) => Carbon::parse($t)->format('H:i');
        if ($deptId && $schedules->isNotEmpty()) {
            $schedules = $schedules->filter(fn ($s) => !$s->department_id || (int) $s->department_id === $deptId);
            if ($schedules->isEmpty()) return '';
        }
        if ($schedules->isEmpty()) {
            $days = is_array($doctor->available_days) ? $doctor->available_days : (json_decode((string) $doctor->available_days, true) ?: []);
            if (!$days || !$doctor->start_time) return '';
            return implode(', ', array_map(fn ($d) => substr($d, 0, 3), $days)) . ' ' . $fmt($doctor->start_time) . '–' . $fmt($doctor->end_time);
        }
        // group days that share the same hours
        $byHours = [];
        foreach ([1, 2, 3, 4, 5, 6, 0] as $dow) {
            $hours = $schedules->where('day_of_week', $dow)->sortBy('start_time')
                ->map(fn ($s) => $fmt($s->start_time) . '–' . $fmt($s->end_time))->implode(', ');
            if ($hours !== '') $byHours[$hours][] = substr(\App\Models\DoctorSchedule::DAYS[$dow], 0, 3);
        }
        return collect($byHours)->map(fn ($days, $hours) => implode(', ', $days) . ' ' . $hours)->implode(' · ');
    }
}
