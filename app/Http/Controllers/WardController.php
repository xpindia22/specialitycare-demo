<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\ActivityLog;

class WardController extends Controller
{
    // Ward overview — all wards with bed summary (live or as-of a date)
    public function index(Request $request)
    {
        $wards = Ward::with('beds')->orderBy('name')->get();

        // "As of" date — live (today) by default; a past date reconstructs
        // occupancy from admissions (reserved/maintenance are live-only).
        $asOfDate     = $request->get('date') ? \Carbon\Carbon::parse($request->get('date'))->startOfDay() : today();
        if ($asOfDate->gt(today())) { $asOfDate = today(); }
        $asOf         = $asOfDate->toDateString();
        $isHistorical = $asOfDate->lt(today());

        if ($isHistorical) {
            $admissions = \App\Models\Admission::whereDate('admission_date', '<=', $asOf)
                ->where(fn($q) => $q->whereNull('discharge_date')
                                    ->orWhereDate('discharge_date', '>=', $asOf))
                ->get()
                ->keyBy('bed_id');

            $total    = $wards->sum(fn($w) => $w->beds->where('is_active', true)->count());
            $occupied = $wards->sum(fn($w) => $w->beds->where('is_active', true)->filter(fn($b) => $admissions->has($b->id))->count());
            $summary  = ['total' => $total, 'available' => $total - $occupied, 'occupied' => $occupied, 'reserved' => 0, 'maintenance' => 0];
        } else {
            $admissions = collect();
            $summary = [
                'total'     => Bed::where('is_active', true)->count(),
                'available' => Bed::where('is_active', true)->where('status', 'available')->count(),
                'occupied'  => Bed::where('status', 'occupied')->count(),
                'reserved'  => Bed::where('status', 'reserved')->count(),
                'maintenance'=> Bed::where('status', 'maintenance')->count(),
                'cleaning'   => Bed::where('status', 'cleaning')->count(),
            ];
        }

        return view('wards.index', compact('wards', 'summary', 'admissions', 'asOf', 'asOfDate', 'isHistorical'));
    }

    // Ward detail — bed grid
    public function show(Ward $ward)
    {
        $ward->load('beds');
        // Load current occupants for occupied beds
        $occupants = \App\Models\Admission::with('patient')
            ->where('ward_id', $ward->id)
            ->where('status', 'admitted')
            ->get()
            ->keyBy('bed_id');
        return view('wards.show', compact('ward', 'occupants'));
    }

    // Bed board — all wards + all beds with occupant info (live or as-of a date)
    public function bedBoard(Request $request)
    {
        $wards = Ward::with(['beds' => fn($q) => $q->where('is_active', true)->orderBy('bed_number')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // "As of" date — live (today) by default. A past date shows historical
        // occupancy reconstructed from admissions; reserved/maintenance are
        // live-only states, so a non-occupied bed reads as Available back then.
        $asOfDate     = $request->get('date') ? \Carbon\Carbon::parse($request->get('date'))->startOfDay() : today();
        if ($asOfDate->gt(today())) { $asOfDate = today(); }
        $asOf         = $asOfDate->toDateString();
        $isHistorical = $asOfDate->lt(today());

        if ($isHistorical) {
            // Admissions occupying a bed on $asOf: admitted on/before it and not
            // yet discharged by then (null discharge = still in at that point).
            $admissions = \App\Models\Admission::with('patient', 'admittingDoctor')
                ->whereDate('admission_date', '<=', $asOf)
                ->where(fn($q) => $q->whereNull('discharge_date')
                                    ->orWhereDate('discharge_date', '>=', $asOf))
                ->get()
                ->keyBy('bed_id');

            $total    = $wards->sum(fn($w) => $w->beds->count());
            $occupied = $wards->sum(fn($w) => $w->beds->filter(fn($b) => $admissions->has($b->id))->count());
            $summary  = ['total' => $total, 'available' => $total - $occupied, 'occupied' => $occupied, 'reserved' => 0, 'maintenance' => 0];
        } else {
            $admissions = \App\Models\Admission::with('patient', 'admittingDoctor')
                ->where('status', 'admitted')
                ->get()
                ->keyBy('bed_id');

            $summary = [
                'total'       => $wards->sum(fn($w) => $w->beds->count()),
                'available'   => $wards->sum(fn($w) => $w->beds->where('status', 'available')->count()),
                'occupied'    => $wards->sum(fn($w) => $w->beds->where('status', 'occupied')->count()),
                'reserved'    => $wards->sum(fn($w) => $w->beds->where('status', 'reserved')->count()),
                'maintenance' => $wards->sum(fn($w) => $w->beds->where('status', 'maintenance')->count()),
                'cleaning'    => $wards->sum(fn($w) => $w->beds->where('status', 'cleaning')->count()),
            ];
        }

        return view('wards.bed-board', compact('wards', 'admissions', 'summary', 'asOf', 'asOfDate', 'isHistorical'));
    }

    // Create ward form
    public function create()
    {
        return view('wards.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:general,semi_private,private,icu,hdu,emergency',
            'floor'       => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);
        $ward = Ward::create($request->only('name','type','floor','description') + ['is_active' => true]);
        ActivityLog::log('created', "Created ward: {$ward->name} ({$ward->type_label})");
        return redirect()->route('wards.show', $ward)->with('success', "Ward '{$ward->name}' created.");
    }

    public function edit(Ward $ward)
    {
        return view('wards.edit', compact('ward'));
    }

    public function update(Request $request, Ward $ward)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:general,semi_private,private,icu,hdu,emergency',
            'floor'       => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);
        $ward->update($request->only('name','type','floor','description','is_active'));
        ActivityLog::log('updated', "Updated ward: {$ward->name}");
        return redirect()->route('wards.show', $ward)->with('success', 'Ward updated.');
    }

    // Add beds to a ward
    public function addBeds(Request $request, Ward $ward)
    {
        $request->validate([
            'prefix'    => 'nullable|string|max:10',
            'start'     => 'required|integer|min:1',
            'count'     => 'required|integer|min:1|max:50',
            'bed_type'  => 'required|in:general,semi_private,private,icu,hdu',
        ]);

        $prefix = $request->prefix ? strtoupper($request->prefix).'-' : '';
        $added  = 0;
        for ($i = $request->start; $i < $request->start + $request->count; $i++) {
            $number = $prefix . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!Bed::where('ward_id', $ward->id)->where('bed_number', $number)->exists()) {
                Bed::create([
                    'ward_id'    => $ward->id,
                    'bed_number' => $number,
                    'type'       => $request->bed_type,
                    'status'     => 'available',
                    'is_active'  => true,
                ]);
                $added++;
            }
        }
        ActivityLog::log('created', "Added {$added} beds to ward {$ward->name}");
        return back()->with('success', "{$added} bed(s) added to {$ward->name}.");
    }

    // Update single bed status
    public function updateBed(Request $request, Bed $bed)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance,cleaning',
            'notes'  => 'nullable|string|max:255',
        ]);
        $old = $bed->status;
        $bed->update($request->only('status','notes'));
        ActivityLog::log('updated', "Bed {$bed->bed_number} ({$bed->ward->name}): {$old} → {$bed->status}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $bed->status, 'label' => $bed->status_label, 'color' => $bed->status_color]);
        }
        return back()->with('success', "Bed {$bed->bed_number} updated.");
    }

    // Toggle bed active/inactive
    public function toggleBed(Bed $bed)
    {
        $bed->update(['is_active' => !$bed->is_active]);
        return back()->with('success', "Bed {$bed->bed_number} " . ($bed->is_active ? 'activated' : 'deactivated') . ".");
    }

    // Delete a bed (only if never used)
    public function destroyBed(Bed $bed)
    {
        $ward = $bed->ward;
        $bed->delete();
        return back()->with('success', "Bed deleted from {$ward->name}.");
    }
}
