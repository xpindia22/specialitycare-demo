<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\ActivityLog;

class WardController extends Controller
{
    // Ward overview — all wards with bed summary
    public function index()
    {
        $wards = Ward::with('beds')->orderBy('name')->get();
        $summary = [
            'total'     => Bed::where('is_active', true)->count(),
            'available' => Bed::where('is_active', true)->where('status', 'available')->count(),
            'occupied'  => Bed::where('status', 'occupied')->count(),
            'reserved'  => Bed::where('status', 'reserved')->count(),
            'maintenance'=> Bed::where('status', 'maintenance')->count(),
        ];
        return view('wards.index', compact('wards', 'summary'));
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

    // Live bed board — all wards + all beds with occupant info
    public function bedBoard()
    {
        $wards = Ward::with(['beds' => fn($q) => $q->where('is_active', true)->orderBy('bed_number')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load all current admissions keyed by bed_id
        $admissions = \App\Models\Admission::with('patient','admittingDoctor')
            ->where('status', 'admitted')
            ->get()
            ->keyBy('bed_id');

        $summary = [
            'total'       => $wards->sum(fn($w) => $w->beds->count()),
            'available'   => $wards->sum(fn($w) => $w->beds->where('status', 'available')->count()),
            'occupied'    => $wards->sum(fn($w) => $w->beds->where('status', 'occupied')->count()),
            'reserved'    => $wards->sum(fn($w) => $w->beds->where('status', 'reserved')->count()),
            'maintenance' => $wards->sum(fn($w) => $w->beds->where('status', 'maintenance')->count()),
        ];

        return view('wards.bed-board', compact('wards', 'admissions', 'summary'));
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
            'status' => 'required|in:available,occupied,reserved,maintenance',
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
