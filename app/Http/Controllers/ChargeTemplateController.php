<?php
namespace App\Http\Controllers;

use App\Models\ChargeTemplate;
use App\Models\IpdCharge;
use Illuminate\Http\Request;

class ChargeTemplateController extends Controller
{
    public function index()
    {
        $templates = ChargeTemplate::with('createdBy')->orderBy('name')->get();
        return view('charge-templates.index', compact('templates'));
    }

    public function create()
    {
        $categories = IpdCharge::CATEGORIES;
        return view('charge-templates.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.category'    => 'required|string',
            'items.*.description' => 'required|string',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.quantity'    => 'required|numeric|min:0.01',
        ]);

        $items = collect($request->items)->map(fn($i) => [
            'category'    => $i['category'],
            'description' => $i['description'],
            'unit_price'  => (float)$i['unit_price'],
            'quantity'    => (float)$i['quantity'],
        ])->values()->all();

        ChargeTemplate::create([
            'name'        => $request->name,
            'description' => $request->description,
            'items'       => $items,
            'is_active'   => true,
            'created_by'  => auth()->id(),
        ]);

        return redirect()->route('charge-templates.index')->with('success', 'Template saved.');
    }

    public function edit(ChargeTemplate $chargeTemplate)
    {
        $categories = IpdCharge::CATEGORIES;
        return view('charge-templates.edit', compact('chargeTemplate', 'categories'));
    }

    public function update(Request $request, ChargeTemplate $chargeTemplate)
    {
        $request->validate([
            'name'     => 'required|string|max:200',
            'items'    => 'required|array|min:1',
            'items.*.category'    => 'required|string',
            'items.*.description' => 'required|string',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.quantity'    => 'required|numeric|min:0.01',
        ]);

        $items = collect($request->items)->map(fn($i) => [
            'category'    => $i['category'],
            'description' => $i['description'],
            'unit_price'  => (float)$i['unit_price'],
            'quantity'    => (float)$i['quantity'],
        ])->values()->all();

        $chargeTemplate->update([
            'name'        => $request->name,
            'description' => $request->description,
            'items'       => $items,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('charge-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(ChargeTemplate $chargeTemplate)
    {
        $chargeTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }

    // AJAX: return template items as JSON for applying to an admission
    public function items(ChargeTemplate $chargeTemplate)
    {
        return response()->json($chargeTemplate->items);
    }

    // Return all active templates for the picker dropdown
    public function list()
    {
        return response()->json(
            ChargeTemplate::where('is_active', true)->orderBy('name')
                ->get(['id','name','description','items'])
        );
    }
}
