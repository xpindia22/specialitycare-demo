<?php
namespace App\Http\Controllers;

use App\Models\DrugInteraction;
use App\Models\Medicine;
use Illuminate\Http\Request;

class DrugInteractionController extends Controller
{
    public function index()
    {
        $interactions = DrugInteraction::orderByRaw("FIELD(severity,'contraindicated','major','moderate','minor')")
            ->orderBy('drug1_name')
            ->paginate(50);

        $severities = DrugInteraction::SEVERITIES;
        $stats = [
            'contraindicated' => DrugInteraction::where('severity', 'contraindicated')->count(),
            'major'           => DrugInteraction::where('severity', 'major')->count(),
            'moderate'        => DrugInteraction::where('severity', 'moderate')->count(),
            'minor'           => DrugInteraction::where('severity', 'minor')->count(),
        ];

        return view('pharmacy.interactions', compact('interactions', 'severities', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'drug1_name'  => 'required|string|max:100',
            'drug2_name'  => 'required|string|max:100',
            'severity'    => 'required|in:minor,moderate,major,contraindicated',
            'description' => 'required|string|max:1000',
            'management'  => 'nullable|string|max:1000',
        ]);

        $data['drug1_name'] = strtolower(trim($data['drug1_name']));
        $data['drug2_name'] = strtolower(trim($data['drug2_name']));

        DrugInteraction::create($data);

        return back()->with('success', 'Drug interaction added successfully.');
    }

    public function destroy(DrugInteraction $interaction)
    {
        $interaction->delete();
        return back()->with('success', 'Interaction removed.');
    }

    // AJAX endpoint: check interactions for a list of drug names
    public function check(Request $request)
    {
        $request->validate(['names' => 'required|array|min:1']);

        $names = $request->input('names');

        // If medicine IDs are passed, resolve their generic names
        if ($request->has('medicine_ids')) {
            $ids = $request->input('medicine_ids', []);
            $meds = Medicine::whereIn('id', $ids)->get(['name', 'generic_name']);
            foreach ($meds as $m) {
                if ($m->generic_name) $names[] = strtolower($m->generic_name);
                $names[] = strtolower($m->name);
            }
        }

        $interactions = DrugInteraction::checkNames(array_unique($names));

        $result = $interactions->map(fn($i) => [
            'drug1'       => $i->drug1_name,
            'drug2'       => $i->drug2_name,
            'matched1'    => $i->matched_drug1 ?? $i->drug1_name,
            'matched2'    => $i->matched_drug2 ?? $i->drug2_name,
            'severity'    => $i->severity,
            'label'       => DrugInteraction::SEVERITIES[$i->severity]['label'] ?? $i->severity,
            'color'       => DrugInteraction::SEVERITIES[$i->severity]['color'] ?? '#555',
            'bg'          => DrugInteraction::SEVERITIES[$i->severity]['bg'] ?? '#fff',
            'border'      => DrugInteraction::SEVERITIES[$i->severity]['border'] ?? '#ccc',
            'description' => $i->description,
            'management'  => $i->management,
        ])->values();

        return response()->json(['interactions' => $result]);
    }
}
