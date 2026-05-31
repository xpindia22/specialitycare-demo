<?php
namespace App\Http\Controllers;

use App\Models\EncounterTemplate;
use App\Models\LabTest;
use App\Models\LabPanel;
use Illuminate\Http\Request;

class EncounterTemplateController extends Controller
{
    public function index()
    {
        $templates = EncounterTemplate::with('createdBy')->orderBy('name')->get();
        return view('encounter-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('encounter-templates.create', $this->labCatalog());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:200',
            'specialization'   => 'nullable|string|max:100',
            'chief_complaint'  => 'nullable|string',
            'clinical_notes'   => 'nullable|string',
        ]);

        EncounterTemplate::create([
            'name'                  => $request->name,
            'specialization'        => $request->specialization,
            'chief_complaint'       => $request->chief_complaint,
            'clinical_notes'        => $request->clinical_notes,
            'diagnoses'             => array_filter((array)$request->diagnoses),
            'prescription_template' => $this->parsePrescriptionTemplate($request),
            'lab_orders'            => $this->parseLabOrders($request),
            'is_active'             => true,
            'created_by'            => auth()->id(),
        ]);

        return redirect()->route('encounter-templates.index')->with('success', 'Template saved.');
    }

    public function edit(EncounterTemplate $encounterTemplate)
    {
        return view('encounter-templates.edit', ['encounterTemplate' => $encounterTemplate] + $this->labCatalog());
    }

    public function update(Request $request, EncounterTemplate $encounterTemplate)
    {
        $request->validate(['name' => 'required|string|max:200']);

        $encounterTemplate->update([
            'name'                  => $request->name,
            'specialization'        => $request->specialization,
            'chief_complaint'       => $request->chief_complaint,
            'clinical_notes'        => $request->clinical_notes,
            'diagnoses'             => array_filter((array)$request->diagnoses),
            'prescription_template' => $this->parsePrescriptionTemplate($request),
            'lab_orders'            => $this->parseLabOrders($request),
            'is_active'             => $request->boolean('is_active', true),
        ]);

        return redirect()->route('encounter-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(EncounterTemplate $encounterTemplate)
    {
        $encounterTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function list()
    {
        return response()->json(
            EncounterTemplate::where('is_active', true)->orderBy('name')
                ->get(['id','name','specialization','chief_complaint','clinical_notes','diagnoses','prescription_template','lab_orders'])
        );
    }

    private function parsePrescriptionTemplate(Request $request): array
    {
        $drugs = $request->input('rx_drug', []);
        $doses = $request->input('rx_dose', []);
        $routes = $request->input('rx_route', []);
        $freqs  = $request->input('rx_frequency', []);
        $instrs = $request->input('rx_instructions', []);

        $items = [];
        foreach ($drugs as $i => $drug) {
            if (trim($drug)) {
                $items[] = [
                    'drug_name'    => $drug,
                    'dose'         => $doses[$i] ?? '',
                    'route'        => $routes[$i] ?? 'Oral',
                    'frequency'    => $freqs[$i] ?? 'OD',
                    'instructions' => $instrs[$i] ?? '',
                ];
            }
        }
        return $items;
    }

    private function parseLabOrders(Request $request): array
    {
        return [
            'tests'  => array_values(array_filter((array) $request->input('lab_tests', []),  fn ($v) => trim((string)$v) !== '')),
            'panels' => array_values(array_filter((array) $request->input('lab_panels', []), fn ($v) => trim((string)$v) !== '')),
        ];
    }

    private function labCatalog(): array
    {
        return [
            'labTests'  => LabTest::where('is_active', true)->orderBy('name')->get(['id','name','price','unit']),
            'labPanels' => LabPanel::where('is_active', true)->orderBy('name')->get(['id','name','price']),
        ];
    }
}
