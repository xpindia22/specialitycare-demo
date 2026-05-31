<?php

namespace App\Http\Controllers;

use App\Models\DutyRoster;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DutyRosterController extends Controller
{
    private const DAYS   = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    private const SHIFTS = ['morning','evening','night','custom'];

    public function index(Request $request)
    {
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $roster = DutyRoster::with(['staff', 'ward'])
            ->where('week_start', $weekStart->toDateString())
            ->get();

        $grid = [];
        foreach (self::SHIFTS as $shift) {
            foreach (self::DAYS as $day) {
                $grid[$shift][$day] = $roster->filter(
                    fn($r) => $r->shift === $shift && $r->day_of_week === $day
                );
            }
        }

        $staff = User::orderBy('name')->get();
        $wards = Ward::where('is_active', true)->orderBy('name')->get();

        return view('staff.duty-roster', compact('weekStart', 'grid', 'staff', 'wards'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id'        => 'required|exists:users,id',
            'week_start'      => 'required|date',
            'shift'           => 'required|in:morning,evening,night,custom',
            'start_time'      => 'required_if:shift,custom|nullable|date_format:H:i',
            'end_time'        => 'required_if:shift,custom|nullable|date_format:H:i|different:start_time',
            'days_of_week'    => 'required|array|min:1',
            'days_of_week.*'  => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'apply_scope'     => 'nullable|in:week,month',
            'ward_id'         => 'nullable|exists:wards,id',
            'notes'           => 'nullable|string|max:200',
        ]);

        // Only persist times for custom shifts; the three named shifts have implicit hours
        $startTime = $data['shift'] === 'custom' ? $data['start_time'] : null;
        $endTime   = $data['shift'] === 'custom' ? $data['end_time']   : null;

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY);

        // Build the list of week-starts to apply to
        if (($data['apply_scope'] ?? 'week') === 'month') {
            // Every Monday whose week overlaps the month containing $weekStart
            $monthStart = $weekStart->copy()->startOfMonth();
            $monthEnd   = $weekStart->copy()->endOfMonth();
            $cursor     = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $weeks = [];
            while ($cursor->lte($monthEnd)) {
                $weeks[] = $cursor->copy()->toDateString();
                $cursor->addWeek();
            }
        } else {
            $weeks = [$weekStart->toDateString()];
        }

        $created = 0; $skipped = 0;
        foreach ($weeks as $ws) {
            foreach ($data['days_of_week'] as $day) {
                // Idempotent within the same shift/time combination so the same
                // custom shift can coexist with a named shift on the same day.
                $key = [
                    'staff_id'    => $data['staff_id'],
                    'week_start'  => $ws,
                    'shift'       => $data['shift'],
                    'day_of_week' => $day,
                ];
                if ($data['shift'] === 'custom') {
                    $key['start_time'] = $startTime;
                    $key['end_time']   = $endTime;
                }
                $row = DutyRoster::firstOrCreate(
                    $key,
                    [
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                        'ward_id'    => $data['ward_id'] ?? null,
                        'notes'      => $data['notes']   ?? null,
                    ]
                );
                $row->wasRecentlyCreated ? $created++ : $skipped++;
            }
        }

        $msg = "Assigned {$created} dut" . ($created === 1 ? 'y' : 'ies');
        if ($skipped > 0) $msg .= " ({$skipped} already existed)";
        $msg .= '.';

        return back()->with('success', $msg);
    }

    public function destroy(DutyRoster $roster)
    {
        $roster->delete();
        return back()->with('success', 'Assignment removed.');
    }
}
