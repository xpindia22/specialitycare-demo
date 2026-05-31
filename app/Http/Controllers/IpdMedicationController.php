<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\IpdMedLog;
use App\Models\IpdPrescription;
use App\Models\Medicine;
use Illuminate\Http\Request;

class IpdMedicationController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'drug_name'      => 'required|string|max:200',
            'medicine_id'    => 'nullable|exists:medicines,id',
            'dose'           => 'required|string|max:100',
            'route'          => 'required|string|max:50',
            'frequency'      => 'required|string|max:20',
            'schedule_times' => 'nullable|array',
            'schedule_times.*'=> 'date_format:H:i',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'instructions'   => 'nullable|string|max:300',
        ]);

        // Use frequency defaults if no custom times given
        if (empty($data['schedule_times'])) {
            $data['schedule_times'] = IpdPrescription::FREQUENCIES[$data['frequency']]['times'] ?? [];
        }

        $data['admission_id']  = $admission->id;
        $data['prescribed_by'] = auth()->id();

        // Resolve generic name from medicine or drug_name
        if (!empty($data['medicine_id'])) {
            $med = Medicine::find($data['medicine_id']);
            $data['generic_name'] = $med?->resolved_generic;
        }
        if (empty($data['generic_name']) && !empty($data['drug_name'])) {
            $data['generic_name'] = \App\Models\DrugInteraction::BRAND_MAP[strtolower($data['drug_name'])] ?? null;
        }

        IpdPrescription::create($data);

        ActivityLog::log('IPD Prescription Added', "Added {$data['drug_name']} to admission {$admission->ipd_no}");

        return back()->with('success', "{$data['drug_name']} added to Medicine Chart.")->withFragment('tab-mar');
    }

    public function update(Request $request, Admission $admission, IpdPrescription $prescription)
    {
        abort_if($prescription->admission_id !== $admission->id, 404);
        $data = $request->validate([
            'dose'           => 'required|string|max:100',
            'route'          => 'required|string|max:50',
            'frequency'      => 'required|string|max:20',
            'schedule_times' => 'nullable|array',
            'schedule_times.*'=> 'date_format:H:i',
            'end_date'       => 'nullable|date',
            'instructions'   => 'nullable|string|max:300',
        ]);
        if (empty($data['schedule_times'])) {
            $data['schedule_times'] = IpdPrescription::FREQUENCIES[$data['frequency']]['times'] ?? [];
        }
        $prescription->update($data);
        ActivityLog::log('IPD Prescription Edited', "Updated {$prescription->drug_name} in {$admission->ipd_no}");
        return response()->json(['success' => true]);
    }

    public function stop(Admission $admission, IpdPrescription $prescription)
    {
        abort_if($prescription->admission_id !== $admission->id, 404);
        $prescription->update(['is_active' => false, 'end_date' => today()]);
        ActivityLog::log('IPD Prescription Stopped', "Stopped {$prescription->drug_name} in {$admission->ipd_no}");
        return back()->with('success', "{$prescription->drug_name} stopped.")->withFragment('tab-mar');
    }

    public function logDose(Request $request, Admission $admission, IpdPrescription $prescription)
    {
        abort_if($prescription->admission_id !== $admission->id, 404);

        $data = $request->validate([
            'log_date'       => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'status'         => 'required|in:given,missed,held,refused',
            'actual_time'    => 'nullable|date_format:H:i',
            'remarks'        => 'nullable|string|max:200',
        ]);
        $data['prescription_id'] = $prescription->id;
        $data['admission_id']    = $admission->id;
        $data['recorded_by']     = auth()->id();

        // Upsert — one log per prescription+date+scheduled_time
        IpdMedLog::updateOrCreate(
            [
                'prescription_id' => $prescription->id,
                'log_date'        => $data['log_date'],
                'scheduled_time'  => $data['scheduled_time'],
            ],
            $data
        );

        return response()->json(['ok' => true]);
    }

    public function mar(Admission $admission)
    {
        $admission->load(['patient', 'ward', 'bed', 'admittingDoctor']);
        $date  = request('date', today()->toDateString());
        $prescriptions = $admission->prescriptions()
            ->with(['logs' => fn($q) => $q->whereDate('log_date', $date)])
            ->where(function ($q) use ($date) {
                $q->where('start_date', '<=', $date)
                  ->where(function ($q2) use ($date) {
                      $q2->whereNull('end_date')->orWhere('end_date', '>=', $date);
                  });
            })
            ->orderBy('drug_name')
            ->get();

        return view('admissions.mar', compact('admission', 'prescriptions', 'date'));
    }
}
