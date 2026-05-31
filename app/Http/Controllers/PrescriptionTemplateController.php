<?php
// FILE: app/Http/Controllers/PrescriptionTemplateController.php
namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\PrescriptionTemplate;
use App\Models\Doctor;
use Illuminate\Http\Request;

class PrescriptionTemplateController extends Controller
{
    public function index(Request $request)
    {
        // Return JSON for AJAX requests (used by Load Template modal in encounters)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(
                PrescriptionTemplate::with('doctor')
                    ->orderBy('name')
                    ->get()
                    ->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'doctor' => $t->doctor?->full_name, 'items' => $t->items])
            );
        }
        $templates = PrescriptionTemplate::with('doctor', 'createdBy')
            ->orderBy('name')
            ->get();
        $doctors   = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $medicines = Medicine::where('is_active', true)->orderBy('name')->pluck('name');
        return view('prescription-templates.index', compact('templates', 'doctors', 'medicines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:doctors,id',
            'items'     => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string|max:255',
            'items.*.dosage'        => 'nullable|string|max:100',
            'items.*.frequency'     => 'nullable|string|max:100',
            'items.*.duration'      => 'nullable|string|max:50',
            'items.*.instructions'  => 'nullable|string|max:500',
        ]);

        PrescriptionTemplate::create([
            'name'       => $validated['name'],
            'doctor_id'  => $validated['doctor_id'] ?? null,
            'items'      => $validated['items'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Template saved.');
    }

    public function update(Request $request, PrescriptionTemplate $template)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:doctors,id',
            'items'     => 'required|array|min:1',
            'items.*.medicine_name' => 'required|string|max:255',
            'items.*.dosage'        => 'nullable|string|max:100',
            'items.*.frequency'     => 'nullable|string|max:100',
            'items.*.duration'      => 'nullable|string|max:50',
            'items.*.instructions'  => 'nullable|string|max:500',
        ]);

        $template->update([
            'name'      => $validated['name'],
            'doctor_id' => $validated['doctor_id'] ?? null,
            'items'     => array_values($validated['items']),
        ]);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(PrescriptionTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function items(PrescriptionTemplate $template)
    {
        return response()->json($template->items);
    }
}
