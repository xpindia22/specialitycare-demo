<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\InsuranceClaim;
use App\Models\InsuranceDocument;
use App\Models\Insurer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InsuranceClaimController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $q = $request->get('q');

        $claims = InsuranceClaim::with(['patient', 'insurer', 'admission'])
            ->when($status !== 'all', fn($qry) => $qry->where('status', $status))
            ->when($q, function ($qry) use ($q) {
                $qry->where('claim_no', 'like', "%$q%")
                    ->orWhere('policy_no', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%"));
            })
            ->orderByDesc('created_at')
            ->paginate(25);

        $stats = [
            'pending'  => InsuranceClaim::whereIn('status', ['submitted', 'query_raised'])->count(),
            'approved' => InsuranceClaim::whereIn('status', ['approved', 'partially_approved'])->count(),
            'settled'  => InsuranceClaim::where('status', 'settled')->count(),
            'rejected' => InsuranceClaim::where('status', 'rejected')->count(),
        ];

        return view('insurance.claims.index', compact('claims', 'stats', 'status', 'q'));
    }

    public function create(Request $request)
    {
        $admission = $request->admission_id ? Admission::with(['patient'])->findOrFail($request->admission_id) : null;
        $insurers  = Insurer::where('is_active', true)->orderBy('name')->get();
        return view('insurance.claims.form', compact('admission', 'insurers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();

        $claim = InsuranceClaim::create($data);
        ActivityLog::log('Claim Created', "Insurance claim {$claim->claim_no} for admission #{$claim->admission_id}");

        return redirect()->route('insurance.claims.show', $claim)->with('success', 'Claim created successfully.');
    }

    public function show(InsuranceClaim $claim)
    {
        $claim->load(['admission.patient', 'insurer', 'documents.uploadedBy', 'createdBy']);
        return view('insurance.claims.show', compact('claim'));
    }

    public function edit(InsuranceClaim $claim)
    {
        $insurers = Insurer::where('is_active', true)->orderBy('name')->get();
        return view('insurance.claims.form', compact('claim', 'insurers'));
    }

    public function update(Request $request, InsuranceClaim $claim)
    {
        $claim->update($this->validated($request));
        ActivityLog::log('Claim Updated', "Insurance claim #{$claim->id} status → {$claim->status}");
        return redirect()->route('insurance.claims.show', $claim)->with('success', 'Claim updated.');
    }

    public function updateStatus(Request $request, InsuranceClaim $claim)
    {
        $data = $request->validate([
            'status'           => 'required|in:' . implode(',', array_keys(InsuranceClaim::STATUSES)),
            'approved_amount'  => 'nullable|numeric|min:0',
            'settled_amount'   => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string|max:500',
            'remarks'          => 'nullable|string|max:500',
        ]);

        if ($data['status'] === 'submitted' && !$claim->submitted_date) {
            $data['submitted_date'] = today();
        }
        if (in_array($data['status'], ['approved', 'partially_approved']) && !$claim->approved_date) {
            $data['approved_date'] = today();
        }
        if ($data['status'] === 'settled' && !$claim->settled_date) {
            $data['settled_date'] = today();
        }

        $claim->update($data);
        ActivityLog::log('Claim Status Updated', "Claim #{$claim->id} → {$claim->status}");

        return back()->with('success', 'Claim status updated.');
    }

    public function uploadDocument(Request $request, InsuranceClaim $claim)
    {
        $request->validate([
            'document_type' => 'required|in:' . implode(',', array_keys(InsuranceDocument::DOCUMENT_TYPES)),
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('document_file');
        $path = $file->store("insurance/claim-{$claim->id}", 'public');

        $claim->documents()->create([
            'document_type' => $request->document_type,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function deleteDocument(InsuranceClaim $claim, InsuranceDocument $document)
    {
        abort_if($document->claim_id !== $claim->id, 404);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Document removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'admission_id'        => 'required|exists:admissions,id',
            'patient_id'          => 'required|exists:patients,id',
            'insurer_id'          => 'required|exists:insurers,id',
            'claim_no'            => 'nullable|string|max:80',
            'policy_no'           => 'nullable|string|max:80',
            'member_id'           => 'nullable|string|max:80',
            'policy_holder_name'  => 'nullable|string|max:150',
            'policy_expiry'       => 'nullable|date',
            'sum_insured'         => 'nullable|numeric|min:0',
            'approved_amount'     => 'nullable|numeric|min:0',
            'settled_amount'      => 'nullable|numeric|min:0',
            'co_pay_amount'       => 'nullable|numeric|min:0',
            'status'              => 'required|in:' . implode(',', array_keys(InsuranceClaim::STATUSES)),
            'submitted_date'      => 'nullable|date',
            'diagnosis_icd'       => 'nullable|string|max:500',
            'remarks'             => 'nullable|string',
            'rejection_reason'    => 'nullable|string',
        ]);
    }
}
