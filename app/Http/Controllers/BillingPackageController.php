<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\AdmissionPackage;
use App\Models\BillingPackage;
use App\Models\BillingPackageItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingPackageController extends Controller
{
    // ── Package Master ─────────────────────────────────────────────────────

    public function index()
    {
        $packages = BillingPackage::withCount('admissions')->orderBy('name')->paginate(30);
        return view('billing.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('billing.packages.form', ['package' => new BillingPackage]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $pkg = BillingPackage::create($this->validated($request) + ['created_by' => auth()->id()]);
            $this->syncItems($pkg, $request->input('items', []));
        });
        return redirect()->route('packages.index')->with('success', 'Package created.');
    }

    public function edit(BillingPackage $package)
    {
        $package->load('items');
        return view('billing.packages.form', compact('package'));
    }

    public function update(Request $request, BillingPackage $package)
    {
        DB::transaction(function () use ($request, $package) {
            $package->update($this->validated($request));
            $this->syncItems($package, $request->input('items', []));
        });
        return redirect()->route('packages.index')->with('success', 'Package updated.');
    }

    public function destroy(BillingPackage $package)
    {
        $package->update(['is_active' => false]);
        return back()->with('success', 'Package deactivated.');
    }

    public function apiList()
    {
        $packages = BillingPackage::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'category', 'package_price', 'max_days',
                   'includes_room', 'includes_medicines', 'includes_investigations', 'includes_ot']);
        return response()->json($packages);
    }

    public function apiItems(BillingPackage $package)
    {
        $package->load('items');
        return response()->json([
            'id'            => $package->id,
            'name'          => $package->name,
            'package_price' => (float) $package->package_price,
            'items'         => $package->items->map(fn ($i) => [
                'item_name'   => $i->item_name,
                'item_type'   => $i->item_type,
                'unit_price'  => (float) $i->unit_price,
                'quantity'    => (float) $i->quantity,
                'total_price' => (float) $i->total_price,
                'is_included' => (bool) $i->is_included,
            ])->values(),
        ]);
    }

    // ── Admission Package Assignment ───────────────────────────────────────

    public function assign(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'package_id'    => 'required|exists:billing_packages,id',
            'quoted_price'  => 'required|numeric|min:0',
            'notes'         => 'nullable|string|max:500',
        ]);
        $data['admission_id'] = $admission->id;
        $data['created_by']   = auth()->id();

        AdmissionPackage::create($data);

        $pkg = BillingPackage::find($data['package_id']);
        ActivityLog::log('Package Assigned', "Package '{$pkg->name}' assigned to {$admission->ipd_no}");

        return back()->with('success', "Package '{$pkg->name}' assigned.");
    }

    public function updatePackageStatus(Request $request, Admission $admission, AdmissionPackage $ap)
    {
        abort_if($ap->admission_id !== $admission->id, 404);
        $data = $request->validate([
            'status'      => 'required|in:active,settled,cancelled',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);
        $ap->update($data);
        return back()->with('success', 'Package status updated.');
    }

    public function removePackage(Admission $admission, AdmissionPackage $ap)
    {
        abort_if($ap->admission_id !== $admission->id, 404);
        $ap->delete();
        return back()->with('success', 'Package removed.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                     => 'required|string|max:150',
            'category'                 => 'nullable|in:' . implode(',', array_keys(BillingPackage::CATEGORIES)),
            'description'              => 'nullable|string',
            'package_price'            => 'required|numeric|min:0',
            'includes_room'            => 'boolean',
            'includes_medicines'       => 'boolean',
            'includes_investigations'  => 'boolean',
            'includes_ot'              => 'boolean',
            'max_days'                 => 'nullable|integer|min:1',
            'is_active'                => 'boolean',
        ]);
    }

    private function syncItems(BillingPackage $pkg, array $items): void
    {
        $pkg->items()->delete();
        foreach ($items as $item) {
            if (empty(trim($item['item_name'] ?? ''))) continue;
            $qty   = (float) ($item['quantity']   ?? 1);
            $price = (float) ($item['unit_price']  ?? 0);
            BillingPackageItem::create([
                'package_id'  => $pkg->id,
                'item_name'   => trim($item['item_name']),
                'item_type'   => $item['item_type'] ?? 'service',
                'unit_price'  => $price,
                'quantity'    => $qty,
                'total_price' => $qty * $price,
                'is_included' => !empty($item['is_included']),
            ]);
        }
    }
}
