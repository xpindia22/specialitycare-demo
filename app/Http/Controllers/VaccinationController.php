<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Vaccination;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VaccinationController extends Controller
{
    public function index(Patient $patient)
    {
        $given = Vaccination::where('patient_id', $patient->id)
            ->orderBy('given_date')
            ->get()
            ->groupBy('vaccine_code');

        $dob = $patient->date_of_birth ? Carbon::parse($patient->date_of_birth) : null;
        $today = today();

        $rows = collect(Vaccination::SCHEDULE)->map(function ($item) use ($given, $dob, $today) {
            $list = $given->get($item['code'], collect());
            $latest = $list->first();
            $status = 'pending';
            $dueDate = $dob ? $dob->copy()->addDays($item['rec_days']) : null;
            $maxDate = $dob ? $dob->copy()->addDays($item['max_days']) : null;
            if ($latest) {
                $status = 'given';
            } elseif ($dob && $dueDate && $today->greaterThan($maxDate)) {
                $status = 'overdue';
            } elseif ($dob && $dueDate && $today->greaterThanOrEqualTo($dueDate)) {
                $status = 'due';
            } elseif ($dob && $dueDate) {
                $status = 'upcoming';
            }
            return (object) [
                'code'     => $item['code'],
                'name'     => $item['name'],
                'dose'     => $item['dose'],
                'rec_days' => $item['rec_days'],
                'rec_label'=> $this->daysToLabel($item['rec_days']),
                'due_date' => $dueDate?->toDateString(),
                'max_date' => $maxDate?->toDateString(),
                'given'    => $latest,
                'all'      => $list,
                'status'   => $status,
            ];
        });

        return view('vaccinations.index', compact('patient', 'rows'));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'vaccine_code' => 'required|string|max:60',
            'dose_label'   => 'nullable|string|max:60',
            'given_date'   => 'required|date|before_or_equal:today',
            'site'         => 'nullable|string|max:80',
            'batch_number' => 'nullable|string|max:80',
            'manufacturer' => 'nullable|string|max:120',
            'expiry_date'  => 'nullable|date|after_or_equal:given_date',
            'notes'        => 'nullable|string|max:500',
        ]);

        $schedule = collect(Vaccination::SCHEDULE)->firstWhere('code', $data['vaccine_code']);
        if (!$schedule) {
            return back()->with('error', 'Unknown vaccine code.');
        }

        Vaccination::create($data + [
            'patient_id'   => $patient->id,
            'vaccine_name' => $schedule['name'],
            'dose_label'   => $data['dose_label'] ?: $schedule['dose'],
            'given_by'     => auth()->id(),
        ]);

        return back()->with('success', "{$schedule['name']} ({$schedule['dose']}) recorded.");
    }

    public function destroy(Patient $patient, Vaccination $vaccination)
    {
        abort_if($vaccination->patient_id !== $patient->id, 404);
        $vaccination->delete();
        return back()->with('success', 'Vaccination record removed.');
    }

    public function print(Patient $patient)
    {
        $given = Vaccination::where('patient_id', $patient->id)
            ->orderBy('given_date')->get();
        return view('vaccinations.print', compact('patient', 'given'));
    }

    private function daysToLabel(int $d): string
    {
        if ($d <= 0)         return 'Birth';
        if ($d < 30)         return "Day {$d}";
        if ($d < 365) {
            $w = round($d / 7);
            $m = round($d / 30);
            return $w <= 16 ? "{$w} wk" : "{$m} mo";
        }
        $y   = (int) floor($d / 365);
        $rem = $d - $y * 365;
        $mo  = (int) round($rem / 30);
        return $mo > 0 ? "{$y} yr {$mo} mo" : "{$y} yr";
    }
}
