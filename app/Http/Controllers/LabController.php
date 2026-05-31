<?php
// FILE: app/Http/Controllers/LabController.php
namespace App\Http\Controllers;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\LabPanel;
use App\Models\LabCategory;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Support\LabResultInterpreter;
use Illuminate\Http\Request;

class LabController extends Controller
{
    public function index(Request $request)
    {
        $orders = LabOrder::with('patient', 'doctor', 'items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('ordered_date', $request->date))
            ->latest()
            ->paginate(20);
        return view('lab.index', compact('orders'));
    }

    public function createOrder(Request $request)
    {
        $patients = Patient::where('is_active',true)->orderBy('first_name')->get();
        $doctors = Doctor::where('is_active',true)->orderBy('first_name')->get();
        $patient = $request->patient_id ? Patient::find($request->patient_id) : null;
        $encounter_id = $request->encounter_id;
        $admission_id = $request->admission_id;
        $admission = $admission_id ? \App\Models\Admission::find($admission_id) : null;
        return view('lab.create-order', array_merge(
            compact('patients','doctors','patient','encounter_id','admission_id','admission'),
            $this->pickerData()
        ));
    }

    /**
     * Shared data for the test/panel picker partial (lab._test-picker):
     * categories + their tests, panels, pinned tests/panels, and the most
     * frequently ordered tests — so the picker can surface favourites and
     * common tests at the top on both the create-order and add-tests screens.
     */
    private function pickerData(): array
    {
        $labCategories = LabCategory::with(['tests' => fn($q) => $q->where('is_active',true)->orderBy('name')])
            ->orderBy('name')->get();
        $panels = LabPanel::with('tests')->where('is_active',true)->orderBy('name')->get();
        $pinnedTests  = LabTest::where('is_active',true)->where('is_pinned',true)->orderBy('name')->get();
        $pinnedPanels = LabPanel::where('is_active',true)->where('is_pinned',true)->orderBy('name')->get();

        $frequentIds = LabOrderItem::query()
            ->whereNotNull('lab_test_id')->whereNull('lab_panel_id')
            ->selectRaw('lab_test_id, COUNT(*) as c')
            ->groupBy('lab_test_id')->orderByDesc('c')->limit(8)
            ->pluck('lab_test_id')->all();
        $frequentTests = empty($frequentIds) ? collect()
            : LabTest::where('is_active',true)->whereIn('id',$frequentIds)->get()
                ->sortBy(fn($t) => array_search($t->id, $frequentIds))->values();

        $frequentPanelIds = LabOrderItem::query()
            ->whereNotNull('lab_panel_id')
            ->selectRaw('lab_panel_id, COUNT(DISTINCT lab_order_id) as c')
            ->groupBy('lab_panel_id')->orderByDesc('c')->limit(6)
            ->pluck('lab_panel_id')->all();
        $frequentPanels = empty($frequentPanelIds) ? collect()
            : LabPanel::where('is_active',true)->whereIn('id',$frequentPanelIds)->get()
                ->sortBy(fn($p) => array_search($p->id, $frequentPanelIds))->values();

        return compact('labCategories','panels','pinnedTests','pinnedPanels','frequentTests','frequentPanels');
    }

    /** Resolve tests[]/panels[]/test_names[]/panel_names[] into [items[], totalAmount]. */
    private function resolveSelectedItems(Request $request): array
    {
        $testIds  = array_filter((array) $request->tests);
        $panelIds = array_filter((array) $request->panels);
        if ($request->filled('test_names')) {
            $testIds = array_merge($testIds, LabTest::whereIn('name',(array)$request->test_names)->pluck('id')->all());
        }
        if ($request->filled('panel_names')) {
            $panelIds = array_merge($panelIds, LabPanel::whereIn('name',(array)$request->panel_names)->pluck('id')->all());
        }
        $testIds  = array_values(array_unique($testIds));
        $panelIds = array_values(array_unique($panelIds));

        $items = []; $total = 0;
        foreach ($testIds  as $id) { if ($t = LabTest::find($id))  { $total += $t->price; $items[] = ['type'=>'test','model'=>$t]; } }
        foreach ($panelIds as $id) { if ($p = LabPanel::find($id)) { $total += $p->price; $items[] = ['type'=>'panel','model'=>$p]; } }
        return [$items, $total];
    }

    /** Create lab_order_items for resolved items (panels expand into their tests). */
    private function createItems(LabOrder $order, array $items): void
    {
        foreach ($items as $item) {
            if ($item['type'] === 'panel') {
                $panel = $item['model']->load('tests');
                foreach ($panel->tests as $test) {
                    $order->items()->create(['lab_test_id'=>$test->id,'lab_panel_id'=>$panel->id,'price'=>0,'status'=>'Ordered']);
                }
            } else {
                $order->items()->create(['lab_test_id'=>$item['model']->id,'lab_panel_id'=>null,'price'=>$item['model']->price,'status'=>'Ordered']);
            }
        }
    }

    /** Append tests/panels to an existing order and bump its totals. */
    public function addTests(Request $request, LabOrder $order)
    {
        if ($order->status === 'Cancelled') {
            return back()->withErrors(['items' => 'Cannot add tests to a cancelled order.']);
        }
        [$items, $addAmount] = $this->resolveSelectedItems($request);
        if (empty($items)) {
            return back()->withErrors(['items' => 'Please select at least one test or panel to add.'])->withInput();
        }
        $this->createItems($order, $items);
        $order->total_amount = (float) $order->total_amount + $addAmount;
        $order->net_amount   = max(0, (float) $order->total_amount - (float) $order->discount);
        $order->save();

        $count = count($items);
        return redirect()->route('lab.order', $order)
            ->with('success', "{$count} item(s) added to {$order->order_no}.");
    }

    /** Toggle the pinned/favourite flag on a test or panel (AJAX). */
    public function togglePin(string $type, int $id)
    {
        $model = $type === 'panel' ? LabPanel::findOrFail($id) : LabTest::findOrFail($id);
        $model->is_pinned = ! $model->is_pinned;
        $model->save();
        return response()->json(['success' => true, 'pinned' => (bool) $model->is_pinned]);
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            // doctor_id resolved below
            'encounter_id' => 'nullable|exists:encounters,id',
        ]);

        $totalAmount = 0;
        // Build doctor_id from encounter or first available doctor
        $doctorId = $request->doctor_id;
        if (!$doctorId && $request->encounter_id) {
            $enc = \DB::table('encounters')->find($request->encounter_id);
            $doctorId = $enc->doctor_id ?? null;
        }
        if (!$doctorId) {
            $doctorId = \DB::table('doctors')->where('is_active',true)->value('id');
        }

        // Build items from tests[] and panels[] (IDs) — also resolve test_names[]/panel_names[]
        $testIds  = array_filter((array) $request->tests);
        $panelIds = array_filter((array) $request->panels);

        if ($request->filled('test_names')) {
            $testIds = array_merge(
                $testIds,
                LabTest::whereIn('name', (array) $request->test_names)->pluck('id')->all()
            );
        }
        if ($request->filled('panel_names')) {
            $panelIds = array_merge(
                $panelIds,
                LabPanel::whereIn('name', (array) $request->panel_names)->pluck('id')->all()
            );
        }
        $testIds  = array_values(array_unique($testIds));
        $panelIds = array_values(array_unique($panelIds));

        $rawItems = [];
        foreach ($testIds  as $testId)  { $rawItems[] = ['type' => 'test',  'id' => $testId];  }
        foreach ($panelIds as $panelId) { $rawItems[] = ['type' => 'panel', 'id' => $panelId]; }
        if (empty($rawItems)) {
            return back()->withErrors(['items' => 'Please select at least one test or panel.'])->withInput();
        }

        $items = [];
        foreach ($rawItems as $item) {
            if ($item['type'] === 'test') {
                $test = LabTest::find($item['id']);
                if ($test) { $totalAmount += $test->price; $items[] = ['type' => 'test', 'model' => $test]; }
            } else {
                $panel = LabPanel::find($item['id']);
                if ($panel) { $totalAmount += $panel->price; $items[] = ['type' => 'panel', 'model' => $panel]; }
            }
        }

        $discount = (float) ($request->discount ?? 0);
        $netAmount = max(0, $totalAmount - $discount);

        $order = LabOrder::create([
            'order_no'      => LabOrder::generateNo(),
            'patient_id'    => $request->patient_id,
            'doctor_id'     => $doctorId,
            'encounter_id'  => $request->encounter_id,
            'admission_id'  => $request->admission_id ?: null,
            'ordered_date'  => today(),
            'status'        => 'Ordered',
            'clinical_info' => $request->clinical_info,
            'total_amount'  => $totalAmount,
            'discount'      => $discount,
            'net_amount'    => $netAmount,
            'created_by'    => auth()->id(),
        ]);

        foreach ($items as $item) {
            if ($item['type'] === 'panel') {
                // Expand panel into individual test items
                $panel = $item['model']->load('tests');
                foreach ($panel->tests as $test) {
                    $order->items()->create([
                        'lab_test_id'  => $test->id,
                        'lab_panel_id' => $panel->id,
                        'price'        => 0,
                        'status'       => 'Ordered',
                    ]);
                }
            } else {
                $order->items()->create([
                    'lab_test_id'  => $item['model']->id,
                    'lab_panel_id' => null,
                    'price'        => $item['model']->price,
                    'status'       => 'Ordered',
                ]);
            }
        }

        if ($request->admission_id) {
            return redirect()->route('admissions.show', $request->admission_id)
                ->with('success', "Lab order {$order->order_no} created.")
                ->withFragment('tab-labs');
        }
        if ($request->encounter_id && $request->boolean('return_to_encounter')) {
            return redirect()->route('encounters.show', $request->encounter_id)
                ->with('success', "Lab order {$order->order_no} created.")
                ->withFragment('tab-labs');
        }
        return redirect()->route('lab.order', $order)->with('success', "Lab order {$order->order_no} created.");
    }

    public function showOrder(LabOrder $order)
    {
        $order->load('patient', 'doctor', 'items.test.category', 'items.panel.tests', 'items.results', 'admission.ward', 'admission.bed');

        // Build trend data for numeric tests with 2+ historical readings for this patient
        $patientId = $order->patient_id;
        $labTrends = collect();
        foreach ($order->items as $item) {
            if (!$item->lab_test_id || !$item->result_value) continue;
            $historical = LabOrderItem::whereHas('order', fn($q) => $q->where('patient_id', $patientId))
                ->where('lab_test_id', $item->lab_test_id)
                ->whereNotNull('result_value')
                ->with('order')
                ->get()
                ->filter(fn($i) => is_numeric($i->result_value));
            if ($historical->count() < 2) continue;
            $labTrends[$item->lab_test_id] = [
                'name'   => $item->test->name ?? 'Test',
                'unit'   => $item->test->unit ?? '',
                'points' => $historical->sortBy(fn($i) => $i->order->ordered_date)->map(fn($i) => [
                    'date'  => \Carbon\Carbon::parse($i->order->ordered_date)->format('d M Y'),
                    'value' => (float) $i->result_value,
                ])->values(),
            ];
        }

        return view('lab.order', array_merge(compact('order', 'labTrends'), $this->pickerData()));
    }

    public function updateStatus(Request $request, LabOrder $order)
    {
        $request->validate(['status' => 'required|in:Ordered,Collected,Processing,Completed,Cancelled']);
        $order->update(['status' => $request->status]);
        return back()->with('success', 'Order status updated.');
    }

    public function saveResults(Request $request, LabOrderItem $item)
    {
        $data = $request->validate([
            'result_value'  => 'required|string|max:255',
            'result_status' => 'nullable|in:Pending,Preliminary,Final',
            'remarks'       => 'nullable|string',
        ]);

        if (!$item->lab_test_id) {
            return response()->json(['success' => false, 'message' => 'Result entry requires an individual lab test.'], 422);
        }

        if (($item->order?->status ?? null) === 'Cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot enter results for a cancelled lab order.'], 422);
        }

        // Keep one current result row for this order item and also mirror the
        // latest value on lab_order_items for list/report screens.
        $item->results()->delete();
        $testName = optional($item->test)->name ?? optional($item->panel)->name ?? 'Result';
        $interpretation = LabResultInterpreter::interpret($data['result_value'], optional($item->test)->normal_range);
        $item->results()->create([
            'lab_test_id'    => $item->lab_test_id,
            'parameter_name' => $testName,
            'result_value'   => $data['result_value'],
            'unit'           => optional($item->test)->unit ?? null,
            'normal_range'   => optional($item->test)->normal_range ?? null,
            'flag'           => $interpretation['label'],
            'comments'       => $data['remarks'] ?? null,
            'reported_by'    => auth()->id(),
            'reported_at'    => now(),
        ]);

        $item->update([
            'status'            => 'Completed',
            'result_value'      => $data['result_value'],
            'result_status'     => $data['result_status'] ?? 'Final',
            'abnormal_flag'     => $interpretation['code'],
            'remarks'           => $data['remarks'] ?? null,
            'result_entered_at' => now(),
            'result_entered_by' => auth()->id(),
        ]);

        $order = $item->load('order.items')->order;
        if ($order->items->every(function($i) { return $i->status === 'Completed'; })) {
            $order->update(['status' => 'Completed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Result saved.',
            'result_status' => $item->result_status,
            'abnormal_flag' => $item->abnormal_flag,
            'flag_label' => $interpretation['label'],
            'flag_severity' => $interpretation['severity'],
            'entered_at' => optional($item->result_entered_at)->format('d M Y, h:i A'),
        ]);
    }

    public function tests(Request $request)
    {
        $q = LabTest::with('category');
        if ($request->q) $q->where('name', 'like', "%{$request->q}%");
        if ($request->category) $q->where('lab_category_id', $request->category);
        if ($request->status === 'active') $q->where('is_active', true);
        elseif ($request->status === 'inactive') $q->where('is_active', false);
        $tests = $q->orderBy('name')->paginate(30)->withQueryString();
        $categories = LabCategory::orderBy('name')->get();
        return view('lab.tests', compact('tests', 'categories'));
    }

    public function storeTest(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'short_code'      => 'nullable|string|max:20',
            'lab_category_id' => 'required|exists:lab_categories,id',
            'sample_type'     => 'nullable|string',
            'price'           => 'required|numeric|min:0',
            'turnaround_hours'=> 'nullable|integer|min:1',
            'unit'            => 'nullable|string|max:50',
            'normal_range'    => 'nullable|string',
            'normal_value'    => 'nullable|string|max:100',
            'description'     => 'nullable|string',
            'instructions'    => 'nullable|string',
        ]);
        LabTest::create($data + ['is_active' => true]);
        return back()
            ->with('success', 'Test added successfully.')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    public function updateTest(Request $request, LabTest $test)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'short_code'      => 'nullable|string|max:20',
            'lab_category_id' => 'required|exists:lab_categories,id',
            'sample_type'     => 'nullable|string',
            'price'           => 'required|numeric|min:0',
            'turnaround_hours'=> 'nullable|integer|min:1',
            'unit'            => 'nullable|string|max:50',
            'normal_range'    => 'nullable|string',
            'normal_value'    => 'nullable|string|max:100',
            'description'     => 'nullable|string',
            'instructions'    => 'nullable|string',
        ]);
        $test->update($data);
        return back()
            ->with('success', 'Test updated.')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    public function updateTestPrice(Request $request, LabTest $test)
    {
        $request->validate(['price' => 'required|numeric|min:0']);
        $test->update(['price' => $request->price]);
        return response()->json(['success' => true]);
    }

    public function toggleTest(Request $request, LabTest $test)
    {
        $test->update(['is_active' => !$test->is_active]);
        return back()
            ->with('success', $test->is_active ? 'Test reactivated.' : 'Test deactivated — hidden from individual-test ordering (still works inside its panels).')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    /** Inline-save a test's reference values (range / unit / default value). */
    public function updateTestNormalValues(Request $request, LabTest $test)
    {
        $data = $request->validate([
            'normal_range' => 'nullable|string|max:255',
            'unit'         => 'nullable|string|max:50',
            'normal_value' => 'nullable|string|max:100',
        ]);
        $test->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyTest(Request $request, LabTest $test)
    {
        if (LabOrderItem::where('lab_test_id', $test->id)->exists()) {
            return back()
                ->with('error', 'Test is used in lab orders. Deactivate it instead of deleting.')
                ->with('settings_tab', $request->input('settings_tab'));
        }

        if ($test->panels()->exists()) {
            return back()
                ->with('error', 'Test is included in panels. Remove it from panels before deleting.')
                ->with('settings_tab', $request->input('settings_tab'));
        }

        $test->delete();

        return back()
            ->with('success', 'Test deleted.')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    public function testInfo(LabTest $test)
    {
        return response()->json($test->load('category'));
    }

    public function bulkUpdatePrice(Request $request)
    {
        $request->validate([
            'adjustment_type' => 'required|in:increase_pct,decrease_pct,set_fixed',
            'amount'          => 'required|numeric|min:0',
        ]);
        $q = LabTest::query();
        if ($request->category_id) $q->where('lab_category_id', $request->category_id);
        $tests = $q->get();
        foreach ($tests as $test) {
            $newPrice = match ($request->adjustment_type) {
                'increase_pct' => $test->price * (1 + $request->amount / 100),
                'decrease_pct' => $test->price * (1 - $request->amount / 100),
                'set_fixed'    => $request->amount,
            };
            $test->update(['price' => max(0, round($newPrice, 2))]);
        }
        return back()->with('success', "Prices updated for {$tests->count()} tests.");
    }

    public function panels()
    {
        $panels = LabPanel::with('tests')->get();
        $allTests = LabTest::where('is_active', true)->orderBy('name')->get();
        return view('lab.panels', compact('panels', 'allTests'));
    }

    public function storePanel(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'short_code'       => 'nullable|string|max:20',
            'description'      => 'nullable|string',
            'turnaround_hours' => 'nullable|integer|min:1',
            'price'            => 'required|numeric|min:0',
            'test_ids'         => 'required|array|min:1',
            'test_ids.*'       => 'exists:lab_tests,id',
        ]);
        $panel = LabPanel::create([
            'name'             => $data['name'],
            'short_code'       => $data['short_code'] ?? null,
            'description'      => $data['description'] ?? null,
            'turnaround_hours' => $data['turnaround_hours'] ?? null,
            'price'            => $data['price'],
            'is_active'        => true,
        ]);
        $panel->tests()->sync($data['test_ids']);
        return back()
            ->with('success', 'Panel created successfully.')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    public function updatePanel(Request $request, LabPanel $panel)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'short_code'       => 'nullable|string|max:20',
            'description'      => 'nullable|string',
            'turnaround_hours' => 'nullable|integer|min:1',
            'price'            => 'required|numeric|min:0',
            'test_ids'         => 'required|array|min:1',
            'test_ids.*'       => 'exists:lab_tests,id',
        ]);
        $panel->update($data);
        $panel->tests()->sync($data['test_ids']);
        return back()
            ->with('success', 'Panel updated.')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    public function updatePanelPrice(Request $request, LabPanel $panel)
    {
        $request->validate(['price' => 'required|numeric|min:0']);
        $panel->update(['price' => $request->price]);
        return response()->json(['success' => true]);
    }

    public function togglePanel(LabPanel $panel)
    {
        $panel->update(['is_active' => !$panel->is_active]);
        return back()->with('success', 'Panel status updated.');
    }

    public function destroyPanel(Request $request, LabPanel $panel)
    {
        if (LabOrderItem::where('lab_panel_id', $panel->id)->exists()) {
            return back()
                ->with('error', 'Panel is used in lab orders. Deactivate it instead of deleting.')
                ->with('settings_tab', $request->input('settings_tab'));
        }

        $panel->tests()->detach();
        $panel->delete();

        return back()
            ->with('success', 'Panel deleted.')
            ->with('settings_tab', $request->input('settings_tab'));
    }

    public function panelInfo(LabPanel $panel)
    {
        return response()->json($panel->load('tests'));
    }

    public function printReport(LabOrder $order)
    {
        $order->load('patient', 'doctor', 'items.test', 'items.results');
        return view('lab.report-print', compact('order'));
    }
}
