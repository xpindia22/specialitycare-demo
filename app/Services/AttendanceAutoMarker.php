<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Staff;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Auto-creates "Present" attendance records for staff & doctors flagged
 * `auto_present = true`, for any date in a range that has no manual entry.
 * Never overwrites an existing record.
 */
class AttendanceAutoMarker
{
    public function ensureForDate(CarbonInterface $date): int
    {
        return $this->ensureForRange($date, $date);
    }

    public function ensureForRange(CarbonInterface $from, CarbonInterface $to): int
    {
        $from  = Carbon::parse($from)->startOfDay();
        $to    = Carbon::parse($to)->startOfDay();
        if ($from->gt($to)) [$from, $to] = [$to, $from];

        $today = today();
        if ($to->gt($today))   $to   = $today->copy();
        if ($from->gt($today)) return 0;

        return $this->fillPeople(
            Staff::where('is_active', true)->where('auto_present', true)->get(['id', 'joining_date']),
            'staff_id',
            'joining_date',
            $from, $to
        ) + $this->fillPeople(
            Doctor::where('is_active', true)->where('auto_present', true)->get(['id', 'created_at']),
            'doctor_id',
            'created_at',
            $from, $to
        );
    }

    private function fillPeople($people, string $fkColumn, string $floorColumn, Carbon $from, Carbon $to): int
    {
        if ($people->isEmpty()) return 0;

        $existing = Attendance::whereIn($fkColumn, $people->pluck('id'))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get([$fkColumn, 'date'])
            ->groupBy($fkColumn)
            ->map(fn ($rows) => $rows->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all());

        $rows = [];
        $now  = now();
        foreach ($people as $p) {
            $floor = $p->{$floorColumn} ? Carbon::parse($p->{$floorColumn})->startOfDay() : null;
            $start = ($floor && $floor->gt($from)) ? $floor->copy() : $from->copy();
            if ($start->gt($to)) continue;
            $haveSet = array_flip($existing->get($p->id, []));

            for ($d = $start->copy(); $d->lte($to); $d->addDay()) {
                $ds = $d->toDateString();
                if (isset($haveSet[$ds])) continue;
                $rows[] = [
                    $fkColumn    => $p->id,
                    'date'       => $ds,
                    'status'     => 'present',
                    'marked_by'  => null,
                    'notes'      => 'Auto-marked',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($rows)) return 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            Attendance::insert($chunk);
        }
        return count($rows);
    }
}
