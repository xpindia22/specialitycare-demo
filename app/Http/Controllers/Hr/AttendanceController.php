<?php
namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Staff;
use App\Services\AttendanceAutoMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Build a single unified list of people (active staff + active doctors),
     * sorted alphabetically by name. Each entry carries the bits the views need.
     */
    private function people(): Collection
    {
        $staff = Staff::where('is_active', true)->get()->map(fn ($s) => (object) [
            'kind'         => 'staff',
            'key'          => 'staff_' . $s->id,
            'id'           => $s->id,
            'staff_id'     => $s->id,
            'doctor_id'    => null,
            'name'         => trim($s->first_name . ' ' . $s->last_name),
            'designation'  => $s->designation ?: 'Staff',
            'code'         => $s->staff_id,
            'joining_date' => $s->joining_date,
        ]);

        $doctors = Doctor::where('is_active', true)->get()->map(fn ($d) => (object) [
            'kind'         => 'doctor',
            'key'          => 'doctor_' . $d->id,
            'id'           => $d->id,
            'staff_id'     => null,
            'doctor_id'    => $d->id,
            'name'         => 'Dr. ' . trim($d->first_name . ' ' . $d->last_name),
            'designation'  => $d->specialization ?: 'Doctor',
            'code'         => $d->doctor_id,
            'joining_date' => $d->created_at,
        ]);

        return $staff->concat($doctors)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Fetch attendance records in a date range and index them by the
     * synthetic person key (matches the keys produced by `people()`).
     */
    private function recordsByPersonKey($from, $to): Collection
    {
        $rows = Attendance::whereBetween('date', [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()])->get();
        return $rows->groupBy(fn ($r) => $r->doctor_id ? 'doctor_' . $r->doctor_id : 'staff_' . $r->staff_id);
    }

    public function index(Request $request, AttendanceAutoMarker $marker)
    {
        $date = $request->date('date') ?: today();
        $marker->ensureForDate($date);

        $people = $this->people();
        $records = Attendance::whereDate('date', $date)->get()->keyBy(
            fn ($r) => $r->doctor_id ? 'doctor_' . $r->doctor_id : 'staff_' . $r->staff_id
        );

        return view('hr.attendance', [
            'date'     => $date,
            'people'   => $people,
            'records'  => $records,
            'statuses' => Attendance::STATUSES,
        ]);
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'date'                 => 'required|date',
            'rows'                 => 'required|array',
            'rows.*.status'        => ['required', Rule::in(array_keys(Attendance::STATUSES))],
            'rows.*.check_in'      => 'nullable|date_format:H:i',
            'rows.*.check_out'     => 'nullable|date_format:H:i',
            'rows.*.notes'         => 'nullable|string|max:255',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $count = 0;
        foreach ($data['rows'] as $key => $row) {
            // Keys are "staff_<id>" or "doctor_<id>"
            if (!preg_match('/^(staff|doctor)_(\d+)$/', $key, $m)) continue;
            $kind = $m[1];
            $id   = (int) $m[2];

            if ($kind === 'staff') {
                if (!Staff::whereKey($id)->exists()) continue;
                $lookup = ['staff_id' => $id, 'doctor_id' => null, 'date' => $date];
            } else {
                if (!Doctor::whereKey($id)->exists()) continue;
                $lookup = ['doctor_id' => $id, 'staff_id' => null, 'date' => $date];
            }

            Attendance::updateOrCreate($lookup, [
                'status'    => $row['status'],
                'check_in'  => $row['check_in']  ?? null,
                'check_out' => $row['check_out'] ?? null,
                'notes'     => $row['notes']     ?? null,
                'marked_by' => auth()->id(),
            ]);
            $count++;
        }

        return redirect()->route('hr.attendance.index', ['date' => $date])
            ->with('success', "Attendance saved for {$count} people on " . Carbon::parse($date)->format('d M Y') . '.');
    }

    public function range(Request $request, AttendanceAutoMarker $marker)
    {
        $today = today();
        $from  = $request->date('from') ?: $today->copy()->subDays(6);
        $to    = $request->date('to')   ?: $today->copy();

        if ($from->gt($to))   [$from, $to] = [$to, $from];
        if ($to->gt($today))  $to   = $today->copy();
        if ($from->diffInDays($to) > 366) $from = $to->copy()->subDays(366);

        $marker->ensureForRange($from, $to);

        $people  = $this->people();
        $grouped = $this->recordsByPersonKey($from, $to);
        $totalDays = $from->diffInDays($to) + 1;

        $rows = [];
        foreach ($people as $p) {
            $bucket = ['present'=>0,'half_day'=>0,'absent'=>0,'paid_leave'=>0,'unpaid_leave'=>0,'week_off'=>0,'holiday'=>0];
            $marked = 0;
            foreach ($grouped->get($p->key, collect()) as $r) {
                if (isset($bucket[$r->status])) { $bucket[$r->status]++; $marked++; }
            }
            $rows[] = [
                'person'       => $p,
                'bucket'       => $bucket,
                'marked'       => $marked,
                'unmarked'     => max(0, $totalDays - $marked),
                'present_days' => $bucket['present'] + ($bucket['half_day'] * 0.5),
                'absent_days'  => $bucket['absent'] + $bucket['unpaid_leave'],
            ];
        }

        return view('hr.attendance-range', [
            'from'      => $from,
            'to'        => $to,
            'totalDays' => $totalDays,
            'rows'      => $rows,
            'statuses'  => Attendance::STATUSES,
        ]);
    }

    public function month(Request $request, AttendanceAutoMarker $marker)
    {
        $monthStr = $request->input('month') ?: today()->format('Y-m');
        try {
            $cursor = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        } catch (\Throwable $e) {
            $cursor = today()->startOfMonth();
        }
        $start = $cursor->copy()->startOfMonth();
        $end   = $cursor->copy()->endOfMonth();
        $daysInMonth = $end->day;

        $marker->ensureForRange($start, $end);

        $people  = $this->people();
        $grouped = $this->recordsByPersonKey($start, $end);

        $grid = [];
        $totals = [];
        foreach ($people as $p) {
            $byDay = [];
            $bucket = ['present'=>0,'half_day'=>0,'absent'=>0,'paid_leave'=>0,'unpaid_leave'=>0,'week_off'=>0,'holiday'=>0];
            foreach ($grouped->get($p->key, collect()) as $r) {
                $d = Carbon::parse($r->date)->day;
                $byDay[$d] = $r->status;
                if (isset($bucket[$r->status])) $bucket[$r->status]++;
            }
            $grid[$p->key]   = $byDay;
            $totals[$p->key] = [
                'bucket'       => $bucket,
                'present_days' => $bucket['present'] + ($bucket['half_day'] * 0.5),
                'absent_days'  => $bucket['absent'] + $bucket['unpaid_leave'],
            ];
        }

        $codes = [
            'present'      => ['P',  '#16a34a', '#dcfce7'],
            'half_day'     => ['½',  '#0891b2', '#cffafe'],
            'absent'       => ['A',  '#dc2626', '#fee2e2'],
            'paid_leave'   => ['PL', '#7c3aed', '#ede9fe'],
            'unpaid_leave' => ['UL', '#b45309', '#fef3c7'],
            'week_off'     => ['WO', '#475569', '#e2e8f0'],
            'holiday'      => ['H',  '#0f766e', '#ccfbf1'],
        ];

        return view('hr.attendance-month', [
            'cursor'      => $cursor,
            'start'       => $start,
            'end'         => $end,
            'daysInMonth' => $daysInMonth,
            'people'      => $people,
            'grid'        => $grid,
            'totals'      => $totals,
            'statuses'    => Attendance::STATUSES,
            'codes'       => $codes,
        ]);
    }
}
