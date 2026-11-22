<?php
// FILE: app/Http/Controllers/PharmacyController.php
namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\PharmacyStock;
use App\Models\StockTransaction;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyController extends Controller
{
    public function dashboard()
    {
        $totalMedicines = Medicine::where('is_active', true)->count();
        // Low stock is judged PER MEDICINE on its total live (active, in-date,
        // incl. no-expiry) stock vs reorder level — so adding a new batch
        // clears the alert instead of an old depleted batch keeping it "low".
        $liveStock = function ($q) {
            $q->where('is_active', true)->where('quantity', '>', 0)
              ->where(function ($e) { $e->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()); });
        };
        $lowStock = Medicine::where('is_active', true)
            ->withSum(['stocks as live_qty' => $liveStock], 'quantity')
            ->get()
            ->filter(fn ($m) => (int) $m->live_qty > 0 && (int) $m->live_qty <= (int) $m->reorder_level)
            ->values();
        $outOfStock = Medicine::where('is_active', true)
            ->whereDoesntHave('stocks', $liveStock)
            ->count();
        $expiringSoon = PharmacyStock::with('medicine')
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereBetween('expiry_date', [today(), today()->addDays(90)])
            ->get();
        $pendingPrescriptions = Prescription::where('status', 'Pending')
            ->whereHas('items')
            ->with('patient', 'items.medicine')
            ->latest()
            ->take(10)
            ->get();
        $unavailablePrescribedMedicines = PrescriptionItem::with('medicine', 'prescription.patient')
            ->where('is_dispensed', false)
            ->whereHas('prescription', fn($q) => $q->where('status', 'Pending'))
            ->get()
            ->filter(fn($item) => $item->medicine && $item->medicine->total_stock < (int) $item->quantity)
            ->groupBy('medicine_id')
            ->map(function ($items) {
                $medicine = $items->first()->medicine;
                $needed = $items->sum(fn($item) => (int) $item->quantity);
                $available = $medicine->total_stock;

                return (object) [
                    'medicine' => $medicine,
                    'needed_quantity' => $needed,
                    'available_quantity' => $available,
                    'shortage_quantity' => max(0, $needed - $available),
                    'items' => $items->values(),
                ];
            })
            ->sortByDesc('shortage_quantity')
            ->values();

        return view('pharmacy.dashboard', compact(
            'totalMedicines', 'lowStock', 'outOfStock', 'expiringSoon', 'pendingPrescriptions', 'unavailablePrescribedMedicines'
        ));
    }

    public function medicines(Request $request)
    {
        $q = Medicine::with('category');
        if ($request->q) $q->where(function($query) use ($request) {
            $query->where('name','like',"%{$request->q}%")
                  ->orWhere('generic_name','like',"%{$request->q}%");
        });
        if ($request->category) $q->where('medicine_category_id', $request->category);
        if ($request->status === 'active') $q->where('is_active', true);
        elseif ($request->status === 'inactive') $q->where('is_active', false);
        $medicines = $q->orderBy('name')->paginate(25)->withQueryString();
        $categories = MedicineCategory::orderBy('name')->get();
        $suppliers = \App\Models\AccountLedger::where('is_active', true)
            ->where('party_type', 'Supplier')->orderBy('name')->get(['id', 'name']);
        $defaultSupplierName = \App\Models\AccountLedger::defaultSupplier()?->name;
        return view('pharmacy.medicines', compact('medicines', 'categories', 'suppliers', 'defaultSupplierName'));
    }

    public function storeMedicine(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'generic_name'         => 'nullable|string|max:255',
            'medicine_category_id' => 'required|exists:medicine_categories,id',
            'dosage_form'          => 'required|string',
            'strength'             => 'nullable|string',
            'unit'                 => 'nullable|string',
            'unit_price'           => 'nullable|numeric|min:0',
            'reorder_level'        => 'nullable|integer|min:0',
            'indications'          => 'nullable|string',
            'side_effects'         => 'nullable|string',
            'contraindications'    => 'nullable|string',
            'drug_interactions'    => 'nullable|string',
            'dosage_instructions'  => 'nullable|string',
            'manufacturer'         => 'nullable|string',
            'hsn_code'             => 'nullable|string|max:16',
            'gst_rate_id'          => 'nullable|integer|exists:gst_rates,id',
            'is_active'            => 'boolean',
        ]);
        $validated['is_active']   = $request->boolean('is_active', true);
        $validated['hsn_code']    = $validated['hsn_code']    ?: '30049099';
        $validated['gst_rate_id'] = $validated['gst_rate_id'] ?: (\App\Models\GstRate::where('rate', 0)->value('id') ?? 1);

        DB::transaction(function () use ($validated) {
            $medicine = Medicine::create($validated);

            $stock = PharmacyStock::create([
                'medicine_id' => $medicine->id,
                'batch_number' => 'AUTO-' . str_pad((string) $medicine->id, 5, '0', STR_PAD_LEFT),
                'expiry_date' => now()->addYears(2)->toDateString(),
                'quantity' => 10000,
                'min_stock_level' => $medicine->reorder_level ?? 10,
                'unit_cost' => $medicine->unit_price ?? 0,
                'supplier' => 'Opening Stock',
                'notes' => 'Default opening stock for new medicine.',
                'added_by' => auth()->id(),
                'is_active' => true,
            ]);

            StockTransaction::create([
                'pharmacy_stock_id' => $stock->id,
                'medicine_id' => $medicine->id,
                'type' => 'stock_in',
                'quantity' => 10000,
                'balance_after' => 10000,
                'batch_number' => $stock->batch_number,
                'reference' => 'Opening Stock',
                'notes' => 'Default stock added with new medicine.',
                'user_id' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Medicine added successfully.');
    }

    /**
     * JSON endpoint — quickly create a medicine from the Stock Receipt modal
     * without leaving the page. Minimal fields, HSN/GST soft-defaults,
     * no auto-stock (the stock will come from the receipt currently being
     * entered). Returns the new medicine in dropdown-friendly shape.
     */
    public function quickStoreMedicine(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'medicine_category_id' => 'nullable|integer|exists:medicine_categories,id',
            'dosage_form'          => 'required|string|max:64',
            'strength'             => 'nullable|string|max:64',
            'generic_name'         => 'nullable|string|max:255',
        ]);
        $validated['hsn_code']    = '30049099';
        $validated['gst_rate_id'] = \App\Models\GstRate::where('rate', 12)->value('id') ?? 1;
        $validated['is_active']   = true;

        $medicine = Medicine::create($validated)->load('gstRate');
        $label = trim($medicine->name . ' ' . ($medicine->strength ?? ''));

        return response()->json([
            'id'    => $medicine->id,
            'name'  => $label,
            'hsn'   => $medicine->hsn_code,
            'gst'   => (float) ($medicine->gstRate->rate ?? 12),
        ]);
    }

    public function updateMedicine(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'generic_name'         => 'nullable|string|max:255',
            'medicine_category_id' => 'required|exists:medicine_categories,id',
            'dosage_form'          => 'required|string',
            'strength'             => 'nullable|string',
            'unit'                 => 'nullable|string',
            'unit_price'           => 'nullable|numeric|min:0',
            'reorder_level'        => 'nullable|integer|min:0',
            'indications'          => 'nullable|string',
            'side_effects'         => 'nullable|string',
            'contraindications'    => 'nullable|string',
            'drug_interactions'    => 'nullable|string',
            'dosage_instructions'  => 'nullable|string',
            'manufacturer'         => 'nullable|string',
            'hsn_code'             => 'nullable|string|max:16',
            'gst_rate_id'          => 'nullable|integer|exists:gst_rates,id',
            'is_active'            => 'boolean',
        ]);
        $validated['is_active']   = $request->boolean('is_active', true);
        $validated['hsn_code']    = $validated['hsn_code']    ?: '30049099';
        $validated['gst_rate_id'] = $validated['gst_rate_id'] ?: (\App\Models\GstRate::where('rate', 0)->value('id') ?? 1);

        if ($medicine->is_active && !$validated['is_active']) {
            $pendingCount = \App\Models\PrescriptionItem::where('medicine_id', $medicine->id)
                ->where('is_dispensed', false)
                ->whereHas('prescription', fn($q) => $q->whereIn('status', ['Pending', 'Active']))
                ->count();
            if ($pendingCount > 0) {
                return back()->with('error', "Cannot deactivate {$medicine->name} — it appears on {$pendingCount} undispensed prescription(s). Dispense or cancel those first.");
            }
        }

        $medicine->update($validated);
        return back()->with('success', 'Medicine updated.');
    }

    public function stock(Request $request)
    {
        $stocks = PharmacyStock::with('medicine', 'receivedBy')
            ->where('is_active', true)
            ->when($request->q, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%")
                        ->orWhereHas('medicine', function ($medicineQuery) use ($search) {
                            $medicineQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('generic_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('expiry_date')
            ->paginate(25)
            ->withQueryString();
        $medicines = Medicine::where('is_active', true)->get();
        $categories = MedicineCategory::orderBy('name')->get();

        // Expiry alerts
        $expiredItems = PharmacyStock::with('medicine')
            ->where('is_active', true)->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')->where('expiry_date', '<', today())
            ->get();
        $expiring30 = PharmacyStock::with('medicine')
            ->where('is_active', true)->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), today()->addDays(30)])
            ->get();
        $expiring90 = PharmacyStock::with('medicine')
            ->where('is_active', true)->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today()->addDays(31), today()->addDays(90)])
            ->get();

        $suppliers = \App\Models\AccountLedger::where('is_active', true)
            ->where('party_type', 'Supplier')->orderBy('name')->get(['id', 'name']);
        $defaultSupplierName = \App\Models\AccountLedger::defaultSupplier()?->name;
        return view('pharmacy.stock', compact('stocks', 'medicines', 'categories', 'expiredItems', 'expiring30', 'expiring90', 'suppliers', 'defaultSupplierName'));
    }

    public function addStock(Request $request)
    {
        $validated = $request->validate([
            'medicine_id'     => 'required|exists:medicines,id',
            'batch_number'    => 'nullable|string',
            'batch_no'        => 'nullable|string',
            'expiry_date'     => 'nullable|date|after:today',
            'quantity'        => 'required|integer|min:1',
            'unit_cost'       => 'nullable|numeric|min:0',
            'purchase_price'  => 'nullable|numeric|min:0',
            'supplier'        => 'nullable|string',
            'min_stock_level' => 'nullable|integer|min:0',
            'notes'           => 'nullable|string',
        ]);

        $stockData = [
            'medicine_id'     => $validated['medicine_id'],
            'batch_number'    => $validated['batch_number'] ?? $validated['batch_no'] ?? null,
            'expiry_date'     => $validated['expiry_date'] ?? null,
            'quantity'        => $validated['quantity'],
            'min_stock_level' => $validated['min_stock_level'] ?? 10,
            'unit_cost'       => $validated['unit_cost'] ?? $validated['purchase_price'] ?? 0,
            'supplier'        => $validated['supplier'] ?? null,
            'notes'           => $validated['notes'] ?? null,
            'added_by'        => auth()->id(),
            'is_active'       => true,
        ];

        $stock = PharmacyStock::create($stockData);

        StockTransaction::create([
            'pharmacy_stock_id' => $stock->id,
            'medicine_id'       => $stock->medicine_id,
            'type'              => 'stock_in',
            'quantity'          => $stock->quantity,
            'balance_after'     => $stock->quantity,
            'batch_number'      => $stock->batch_number,
            'reference'         => 'Initial Stock',
            'notes'             => "Stock added. Batch: {$stock->batch_number}",
            'user_id'           => auth()->id(),
        ]);

        return back()->with('success', 'Stock added successfully.');
    }

    public function updateStock(Request $request, PharmacyStock $stock)
    {
        $validated = $request->validate([
            'batch_number'    => 'nullable|string|max:255',
            'expiry_date'     => 'nullable|date',
            'quantity'        => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'unit_cost'       => 'nullable|numeric|min:0',
            'supplier'        => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $stock) {
            $oldQuantity = (int) $stock->quantity;

            $stock->update([
                'batch_number'    => $validated['batch_number'] ?? null,
                'expiry_date'     => $validated['expiry_date'] ?? null,
                'quantity'        => $validated['quantity'],
                'min_stock_level' => $validated['min_stock_level'] ?? 10,
                'unit_cost'       => $validated['unit_cost'] ?? 0,
                'supplier'        => $validated['supplier'] ?? null,
                'notes'           => $validated['notes'] ?? null,
            ]);

            $delta = (int) $stock->quantity - $oldQuantity;
            if ($delta !== 0) {
                StockTransaction::create([
                    'pharmacy_stock_id' => $stock->id,
                    'medicine_id'       => $stock->medicine_id,
                    'type'              => 'adjustment',
                    'quantity'          => $delta,
                    'balance_after'     => $stock->quantity,
                    'batch_number'      => $stock->batch_number,
                    'reference'         => 'Stock Edit',
                    'notes'             => "Stock adjusted from {$oldQuantity} to {$stock->quantity}.",
                    'user_id'           => auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Stock updated successfully.');
    }

    public function dispensePrescription(Request $request, Prescription $prescription)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:prescription_items,id',
            'items.*.dispense' => 'boolean',
            'items.*.stock_id' => 'nullable|exists:pharmacy_stock,id',
        ]);

        DB::transaction(function () use ($request, $prescription) {
            foreach ($request->items as $itemData) {
                if (empty($itemData['dispense'])) {
                    continue;
                }

                $item = $prescription->items()
                    ->whereKey($itemData['item_id'])
                    ->where('is_dispensed', false)
                    ->first();

                if (!$item) {
                    continue;
                }

                $remaining = (int) $item->quantity;
                $stockQuery = PharmacyStock::where('medicine_id', $item->medicine_id)
                    ->where('is_active', true)
                    ->where('quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>=', now()->toDateString());
                    });

                if (!empty($itemData['stock_id'])) {
                    $stockQuery->whereKey($itemData['stock_id']);
                }

                if ((clone $stockQuery)->sum('quantity') < $remaining) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Not enough stock to dispense {$item->medicine->name}.",
                    ]);
                }

                $stocks = $stockQuery->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($stocks as $stock) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $dispensedQty = min($remaining, (int) $stock->quantity);
                    $stock->decrement('quantity', $dispensedQty);
                    $stock->refresh();

                    StockTransaction::create([
                        'pharmacy_stock_id' => $stock->id,
                        'medicine_id'       => $item->medicine_id,
                        'prescription_id'   => $prescription->id,
                        'type'              => 'dispensed',
                        'quantity'          => $dispensedQty,
                        'balance_after'     => $stock->quantity,
                        'batch_number'      => $stock->batch_number,
                        'reference'         => $prescription->prescription_no,
                        'notes'             => "Dispensed for prescription {$prescription->prescription_no}",
                        'user_id'           => auth()->id(),
                    ]);

                    $remaining -= $dispensedQty;
                }

                $item->update(['is_dispensed' => true]);
            }

            $allDispensed = $prescription->items()
                ->where('is_dispensed', false)
                ->doesntExist();

            $prescription->update([
                'status' => $allDispensed ? 'Dispensed' : 'Partially Dispensed',
            ]);
        });

        return back()->with('success', 'Prescription dispensed successfully.');
    }

    public function getMedicineInfo(Medicine $medicine)
    {
        $medicine->load('category');
        return response()->json(array_merge($medicine->toArray(), [
            'total_stock'  => $medicine->total_stock,
            'stock_status' => $medicine->stock_status,
            'is_in_stock'  => $medicine->isInStock(),
        ]));
    }

    public function stockRegister(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $q = StockTransaction::with('medicine', 'user')
            ->when($request->medicine_id, fn($q) => $q->where('medicine_id', $request->medicine_id))
            ->when($request->type,        fn($q) => $q->where('type', $request->type))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest();

        $transactions = $q->paginate(30)->withQueryString();
        $medicineList = Medicine::orderBy('name')->get(['id', 'name']);

        $allQ = StockTransaction::whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
        $summary = [
            'stock_in'    => (clone $allQ)->whereIn('type', ['stock_in', 'return'])->sum('quantity'),
            'dispensed'   => (clone $allQ)->where('type', 'dispensed')->sum('quantity'),
            'adjustments' => (clone $allQ)->whereIn('type', ['adjustment', 'expired'])->count(),
            'total_txns'  => (clone $allQ)->count(),
        ];

        return view('pharmacy.stock-register', compact('transactions', 'medicineList', 'summary'));
    }

    /**
     * List active stock rows with expiry_date < today. User can checkbox-select
     * specific rows (or "select all") and remove them in one POST. Removal is
     * SOFT — quantity → 0, is_active → false — with a stock_transactions audit
     * entry per row so the write-off is traceable in the Stock Register.
     */
    public function expiredStock(Request $request)
    {
        $expired = PharmacyStock::with('medicine')
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', today())
            ->orderBy('expiry_date')
            ->get();
        return view('pharmacy.expired', compact('expired'));
    }

    public function removeExpiredStock(Request $request)
    {
        $data = $request->validate([
            'stock_ids'       => 'required|array|min:1',
            'stock_ids.*'     => 'integer|exists:pharmacy_stock,id',
            'destroy_method'  => 'required|string|max:100',
            'destroyed_on'    => 'nullable|date',
            'destroy_witness' => 'nullable|string|max:191',
        ]);

        $method  = $data['destroy_method'];
        $date    = $data['destroyed_on'] ?: today()->toDateString();
        $witness = $data['destroy_witness'] ?? null;

        $removed = 0; $totalQty = 0;
        DB::transaction(function () use ($data, $method, $date, $witness, &$removed, &$totalQty) {
            foreach (PharmacyStock::whereIn('id', $data['stock_ids'])->where('is_active', true)->get() as $s) {
                if (!$s->expiry_date || $s->expiry_date->gte(today())) continue;
                $qtyBefore = (int) $s->quantity;
                $totalQty += $qtyBefore;
                $noteParts = ["Removed {$qtyBefore} units of expired stock (batch '{$s->batch_number}', exp {$s->expiry_date->format('Y-m-d')})", "Method: {$method}", "Destroyed on: {$date}"];
                if ($witness) $noteParts[] = "Witness: {$witness}";
                StockTransaction::create([
                    'pharmacy_stock_id' => $s->id,
                    'medicine_id'       => $s->medicine_id,
                    'type'              => 'expired',
                    'quantity'          => -$qtyBefore,
                    'balance_after'     => 0,
                    'batch_number'      => $s->batch_number,
                    'reference'         => 'Expired stock removal',
                    'notes'             => implode('. ', $noteParts) . '.',
                    'user_id'           => auth()->id(),
                ]);
                $s->update([
                    'quantity'         => 0,
                    'is_active'        => false,
                    'destroy_method'   => $method,
                    'destroyed_on'     => $date,
                    'destroy_witness'  => $witness,
                ]);
                $removed++;
            }
        });

        return back()->with('success', "Removed {$removed} expired stock row(s) totalling {$totalQty} units. Method: {$method}. Each logged in Stock Register as type='expired'.");
    }

    /**
     * Single-row delete from Edit Stock modal. Soft-removes (qty → 0, is_active
     * → false) and logs to stock_transactions as type='removed' with the user
     * and destroy_method captured for the history page.
     */
    public function deleteStock(Request $request, PharmacyStock $stock)
    {
        $data = $request->validate([
            'destroy_method'  => 'required|string|max:100',
            'destroyed_on'    => 'nullable|date',
            'destroy_witness' => 'nullable|string|max:191',
            'reason'          => 'nullable|string|max:500',
        ]);
        if (!$stock->is_active) {
            return back()->with('error', 'This stock row is already inactive.');
        }
        $this->softRemoveStock(collect([$stock]), $data['destroy_method'], ($data['destroyed_on'] ?? null) ?: today()->toDateString(), $data['destroy_witness'] ?? null, 'removed', $data['reason'] ?? null);
        return back()->with('success', "Removed stock for {$stock->medicine?->name} (batch '{$stock->batch_number}'). Logged in Stock Register.");
    }

    /** GET — list of all active stock with checkboxes for bulk remove. */
    public function bulkRemoveStockIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $stocks = PharmacyStock::with('medicine')
            ->where('is_active', true)
            ->when($q, fn($x) => $x->where(fn($w) => $w->where('batch_number', 'like', "%{$q}%")
                ->orWhere('supplier', 'like', "%{$q}%")
                ->orWhereHas('medicine', fn($m) => $m->where('name', 'like', "%{$q}%"))))
            ->orderBy('medicine_id')->orderBy('expiry_date')
            ->paginate(50)->withQueryString();
        return view('pharmacy.bulk-remove-stock', compact('stocks', 'q'));
    }

    /** POST — bulk remove selected stock rows. */
    public function bulkRemoveStock(Request $request)
    {
        $data = $request->validate([
            'stock_ids'       => 'required|array|min:1',
            'stock_ids.*'     => 'integer|exists:pharmacy_stock,id',
            'destroy_method'  => 'required|string|max:100',
            'destroyed_on'    => 'nullable|date',
            'destroy_witness' => 'nullable|string|max:191',
            'reason'          => 'nullable|string|max:500',
        ]);
        $rows = PharmacyStock::whereIn('id', $data['stock_ids'])->where('is_active', true)->get();
        $result = $this->softRemoveStock($rows, $data['destroy_method'], ($data['destroyed_on'] ?? null) ?: today()->toDateString(), $data['destroy_witness'] ?? null, 'removed', $data['reason'] ?? null);
        return back()->with('success', "Removed {$result['count']} stock row(s) totalling {$result['qty']} units. Method: {$data['destroy_method']}.");
    }

    /** Hub for stock cleanup operations — expired, bulk remove, history. */
    public function stockManagement()
    {
        $expiredCount = PharmacyStock::where('is_active', true)
            ->whereNotNull('expiry_date')->where('expiry_date', '<', today())->count();
        $expiringCount = PharmacyStock::where('is_active', true)->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')->whereBetween('expiry_date', [today(), today()->addDays(90)])->count();
        $activeStockCount = PharmacyStock::where('is_active', true)->count();
        $removalCount30d = StockTransaction::whereIn('type', ['expired', 'removed'])
            ->where('created_at', '>=', today()->subDays(30))->count();
        return view('pharmacy.stock-management', compact('expiredCount', 'expiringCount', 'activeStockCount', 'removalCount30d'));
    }

    /** GET — history of all expired/removed stock_transactions. */
    public function removalHistory(Request $request)
    {
        $from = $request->from ?: now()->subMonths(3)->toDateString();
        $to   = $request->to   ?: now()->toDateString();
        $type = $request->type; // 'expired' or 'removed' or null=both

        $txns = StockTransaction::with('medicine', 'user', 'stock.medicine')
            ->whereIn('type', ['expired', 'removed'])
            ->when($type, fn($q) => $q->where('type', $type))
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->paginate(50)->withQueryString();

        return view('pharmacy.removal-history', compact('txns', 'from', 'to', 'type'));
    }

    /**
     * Shared soft-remove helper. Updates pharmacy_stock + writes one
     * stock_transactions row per stock, capturing destroy_method, date,
     * witness, reason, and the acting user.
     */
    private function softRemoveStock($rows, string $method, string $date, ?string $witness, string $type, ?string $reason): array
    {
        $count = 0; $qty = 0;
        DB::transaction(function () use ($rows, $method, $date, $witness, $type, $reason, &$count, &$qty) {
            foreach ($rows as $s) {
                $qtyBefore = (int) $s->quantity;
                $qty += $qtyBefore;
                $noteParts = ["Removed {$qtyBefore} units (batch '{$s->batch_number}', exp " . ($s->expiry_date?->format('Y-m-d') ?: 'n/a') . ")", "Method: {$method}", "Date: {$date}"];
                if ($witness) $noteParts[] = "Witness: {$witness}";
                if ($reason)  $noteParts[] = "Reason: {$reason}";
                StockTransaction::create([
                    'pharmacy_stock_id' => $s->id,
                    'medicine_id'       => $s->medicine_id,
                    'type'              => $type,
                    'quantity'          => -$qtyBefore,
                    'balance_after'     => 0,
                    'batch_number'      => $s->batch_number,
                    'reference'         => $type === 'expired' ? 'Expired stock removal' : 'Manual stock removal',
                    'notes'             => implode('. ', $noteParts) . '.',
                    'user_id'           => auth()->id(),
                ]);
                $s->update([
                    'quantity'         => 0,
                    'is_active'        => false,
                    'destroy_method'   => $method,
                    'destroyed_on'     => $date,
                    'destroy_witness'  => $witness,
                ]);
                $count++;
            }
        });
        return ['count' => $count, 'qty' => $qty];
    }
}
