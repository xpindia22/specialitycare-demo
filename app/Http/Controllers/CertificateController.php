<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['patient', 'doctor'])
            ->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('certificate_no', 'like', "%{$q}%")
                    ->orWhereHas('patient', function ($p) use ($q) {
                        $p->where('first_name', 'like', "%{$q}%")
                          ->orWhere('last_name', 'like', "%{$q}%")
                          ->orWhere('jhc_id', 'like', "%{$q}%");
                    });
            });
        }

        $certificates = $query->paginate(15)->withQueryString();

        return view('certificates.index', compact('certificates'));
    }

    public function create()
    {
        $patients = Patient::orderBy('first_name')
            ->select('id', 'jhc_id', 'first_name', 'middle_name', 'last_name')
            ->get();

        $doctors = Doctor::where('is_active', true)
            ->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'specialization')
            ->get();

        $types = Certificate::TYPES;

        return view('certificates.create', compact('patients', 'doctors', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'nullable|exists:doctors,id',
            'certificate_type' => 'required|in:' . implode(',', array_keys(Certificate::TYPES)),
            'issued_date'      => 'required|date',
            'purpose'          => 'nullable|string|max:255',
            'diagnosis'        => 'nullable|string|max:255',
            'rest_from'        => 'nullable|date',
            'rest_to'          => 'nullable|date|after_or_equal:rest_from',
            'remarks'          => 'nullable|string',
        ]);

        // Auto-generate certificate number: CERT-YYYY-NNNNN
        $year  = now()->year;
        $count = Certificate::whereYear('created_at', $year)->count() + 1;
        $certNo = 'CERT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        $validated['certificate_no'] = $certNo;
        $validated['created_by']     = auth()->id();

        $cert = Certificate::create($validated);

        return redirect()->route('certificates.show', $cert)
            ->with('success', "Certificate {$certNo} issued successfully.");
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['patient', 'doctor', 'createdBy']);

        return view('certificates.show', ['cert' => $certificate]);
    }

    public function print(Certificate $certificate)
    {
        $certificate->load(['patient', 'doctor']);

        return view('certificates.print', ['cert' => $certificate]);
    }

    public function destroy(Certificate $certificate)
    {
        $no = $certificate->certificate_no;
        $certificate->delete();

        return redirect()->route('certificates.index')
            ->with('success', "Certificate {$no} deleted.");
    }
}
