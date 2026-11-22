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
use App\Services\LabReportParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LabController extends Controller
{
    // ───────────────────────── Upload lab report (PDF) ─────────────────────────

    /** Step 1: show the upload form (pick patient, optional encounter, choose PDF). */
    public function uploadReportForm(Request $request)
    {
        $patients = Patient::where('is_active', true)->orderBy('first_name')->get();
        $patient  = $request->patient_id ? Patient::find($request->patient_id) : null;
        $encounter_id = $request->encounter_id;
        return view('lab.upload-report', compact('patients', 'patient', 'encounter_id'));
    }

    /** Step 2: store + parse the PDF, then show the editable review screen. */
    public function parseReport(Request $request, LabReportParser $parser)
    {
        $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            'report'       => 'required|file|mimes:pdf|max:20480',
            'encounter_id' => 'nullable|exists:encounters,id',
        ]);

        $path   = $request->file('report')->store('lab-reports');
        $parsed = $parser->parse(Storage::path($path));

        // Post/Redirect/Get: stash the parse + context and redirect to a GET review
        // page, so refreshing or a validation back() never re-POSTs this route (405).
        session()->put('lab_report_review', [
            'path'         => $path,
            'patient_id'   => (int) $request->patient_id,
            'encounter_id' => $request->encounter_id ? (int) $request->encounter_id : null,
            'parsed'       => $parsed,
        ]);

        return redirect()->route('lab.report.review');
    }

    /** Step 2b (GET): render the review screen from the stashed upload (PRG target). */
    public function reviewReport(Request $request, LabReportParser $parser)
    {
        $ctx = session('lab_report_review');
        if (!$ctx || empty($ctx['path']) || !Storage::exists($ctx['path'])) {
            return redirect()->route('lab.report.upload')
                ->withErrors(['report' => 'Upload session expired — please choose the PDF again.']);
        }

        $parsed = $ctx['parsed'] ?? $parser->parse(Storage::path($ctx['path']));
        $path   = $ctx['path'];

        $tests = LabTest::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'short_code', 'unit', 'normal_range', 'price']);

        foreach ($parsed['analytes'] as &$a) {
            $a['test_id'] = $a['test_id'] ?? $this->guessTest($a['parameter'], $tests);
            $a['price']   = $a['price'] ?? (is_numeric($a['test_id'])
                ? (float) optional($tests->firstWhere('id', (int) $a['test_id']))->price : 0);
        }
        unset($a);

        $patient    = Patient::findOrFail($ctx['patient_id']);
        $sampleDate = $parsed['header']['sample_date'] ?? today()->toDateString();
        $encounterId = $ctx['encounter_id']
            ?: Encounter::where('patient_id', $patient->id)
                ->whereDate('encounter_date', $sampleDate)->value('id');

        // Panels for the per-row grouping dropdown, and auto-detect which panel
        // each report section (LFT/RFT/CBC…) belongs to so results save grouped.
        $panels = LabPanel::with('tests:id')->where('is_active', true)->orderBy('name')->get();
        $this->assignSectionPanels($parsed['analytes'], $panels);

        return view('lab.review-report', compact(
            'patient', 'parsed', 'tests', 'path', 'sampleDate', 'encounterId', 'panels'
        ));
    }

    /** Step 3: create the lab order (dated to the sample day) + items + results. */
    public function storeReport(Request $request)
    {
        $data = $request->validate([
            'patient_id'          => 'required|exists:patients,id',
            'encounter_id'        => 'nullable|string', // numeric encounter id, or "new" to create a visit
            'sample_date'         => 'required|date',
            'report_path'         => 'nullable|string',
            'rows'                => 'nullable|array',
            'rows.*.parameter'    => 'required|string|max:255',
            'rows.*.value'        => 'nullable|string|max:255',
            'rows.*.unit'         => 'nullable|string|max:100',
            'rows.*.normal_range' => 'nullable|string|max:255',
            'rows.*.test_id'      => 'nullable|string', // test id, "new", or "" to skip
            'rows.*.price'        => 'nullable|numeric|min:0',
            'rows.*.include'      => 'nullable',
            'rows.*.panel_id'     => 'nullable|string', // panel id, or "new:<Name>" to auto-create
            'panel_id'            => 'nullable|exists:lab_panels,id', // optional whole-report panel (fallback)
            'pending_id'          => 'nullable|exists:pending_lab_reports,id',
        ]);

        // When billing as a panel, the single panel price replaces per-test charges.
        $panel = !empty($data['panel_id']) ? LabPanel::find($data['panel_id']) : null;

        // Resolve which visit (encounter) the results attach to.
        //  - a numeric id picked in the review dropdown -> use it
        //  - "new" -> force a fresh visit
        //  - empty -> reuse the patient's visit on the sample day if one exists
        //  - still none -> auto-create a visit (user: "if there is no encounter, create one")
        $sel = $data['encounter_id'] ?? null;
        if ($sel === 'new') {
            $encId = null;
        } elseif ($sel && is_numeric($sel) && Encounter::whereKey($sel)->exists()) {
            $encId = (int) $sel;
        } else {
            $encId = Encounter::where('patient_id', $data['patient_id'])
                ->whereDate('encounter_date', $data['sample_date'])->value('id');
        }

        // Doctor: from the chosen visit if any, else any active doctor.
        $doctorId = $encId ? Encounter::whereKey($encId)->value('doctor_id') : null;
        $doctorId = $doctorId ?: Doctor::where('is_active', true)->value('id');

        if (!$encId) {
            $enc = Encounter::create([
                'encounter_no'    => Encounter::generateNo(),
                'patient_id'      => $data['patient_id'],
                'doctor_id'       => $doctorId,
                'encounter_date'  => $data['sample_date'],
                'encounter_time'  => now()->format('H:i:s'),
                'visit_type'      => 'Revisit',
                'status'          => 'Open',
                'chief_complaint' => 'Lab investigation (from uploaded report)',
                'created_by'      => auth()->id(),
            ]);
            $encId = $enc->id;
        }
        $data['encounter_id'] = $encId;

        $order = DB::transaction(function () use ($data, $doctorId, $panel) {
            $order = LabOrder::create([
                'order_no'     => LabOrder::generateNo(),
                'patient_id'   => $data['patient_id'],
                'doctor_id'    => $doctorId,
                'encounter_id' => $data['encounter_id'] ?? null,
                'ordered_date' => $data['sample_date'],
                'status'       => 'Completed',
                'notes'        => 'Imported from uploaded lab report.',
                'report_path'  => $data['report_path'] ?? null,
                'source'       => 'upload',
                'total_amount' => 0,
                'discount'     => 0,
                'net_amount'   => 0,
                'created_by'   => auth()->id(),
            ]);

            $uploadCat = null;
            $total = 0.0;
            $panelCache = [];       // name(lower) => LabPanel, so a "new:" panel is made once
            $createdPanelIds = [];  // ids of panels created in THIS import (attach members below)
            foreach (($data['rows'] ?? []) as $row) {
                if (empty($row['include'])) continue;
                $value = trim((string) ($row['value'] ?? ''));
                if ($value === '') continue;

                $price = 0.0; // uploaded reports are unpriced: no charge is shown or billed

                $testId = $row['test_id'] ?? '';
                $test   = null;
                if ($testId === 'new') {
                    $uploadCat = $uploadCat ?: LabCategory::firstOrCreate(['name' => 'Uploaded Reports']);
                    $test = LabTest::create([
                        'lab_category_id' => $uploadCat->id,
                        'name'            => $row['parameter'],
                        'unit'            => $row['unit'] ?? null,
                        'normal_range'    => $row['normal_range'] ?? null,
                        'price'           => $price,
                        'is_active'       => true,
                    ]);
                    $testId = $test->id;
                } elseif (is_numeric($testId)) {
                    $test = LabTest::find($testId);
                    // Keep the catalog test's reference range / unit in step with the lab's report.
                    if ($test) {
                        $patch = [];
                        if (!empty($row['normal_range']) && $test->normal_range !== $row['normal_range']) {
                            $patch['normal_range'] = $row['normal_range'];
                        }
                        if (!empty($row['unit']) && $test->unit !== $row['unit']) {
                            $patch['unit'] = $row['unit'];
                        }
                        if ($patch) $test->update($patch);
                    }
                } else {
                    $testId = null; // unmatched + not creating: keep as free-text parameter
                }

                $interp = LabResultInterpreter::interpret($value, $row['normal_range'] ?? null);

                // Group this result under the panel detected/chosen for its row
                // (LFT/RFT/CBC…). A "new:<Name>" value means the report grouped these
                // as a panel we don't have yet → create it once and reuse it. Falls
                // back to a whole-report panel if one was set. Uploaded reports are
                // unpriced, so grouping is purely organisational.
                $rp = (string) ($row['panel_id'] ?? '');
                if (is_numeric($rp)) {
                    $rowPanelId = LabPanel::whereKey((int) $rp)->exists() ? (int) $rp : ($panel?->id);
                } elseif (str_starts_with($rp, 'new:')) {
                    $pname = trim(substr($rp, 4));
                    if ($pname !== '') {
                        $key = mb_strtolower($pname);
                        if (!isset($panelCache[$key])) {
                            $existing = LabPanel::whereRaw('LOWER(name) = ?', [$key])->first();
                            $panelCache[$key] = $existing ?: tap(
                                LabPanel::create(['name' => $pname, 'is_active' => true, 'price' => 0, 'profit' => 0]),
                                function ($p) use (&$createdPanelIds) { $createdPanelIds[$p->id] = true; }
                            );
                        }
                        $rowPanelId = $panelCache[$key]->id;
                    } else {
                        $rowPanelId = $panel?->id;
                    }
                } else {
                    $rowPanelId = $panel?->id;
                }

                $item = $order->items()->create([
                    'lab_test_id'       => $test?->id,
                    'lab_panel_id'      => $rowPanelId,
                    'price'             => 0.0,
                    'profit'            => null,
                    'status'            => 'Completed',
                    'result_value'      => $value,
                    'result_status'     => 'Final',
                    'abnormal_flag'     => $interp['code'],
                    'result_entered_at' => now(),
                    'result_entered_by' => auth()->id(),
                ]);

                $item->results()->create([
                    'lab_test_id'    => $test?->id,
                    'parameter_name' => $row['parameter'],
                    'result_value'   => $value,
                    'unit'           => $row['unit'] ?? null,
                    'normal_range'   => $row['normal_range'] ?? null, // the report's own range
                    'flag'           => $interp['label'],
                    'comments'       => null,
                    'reported_by'    => auth()->id(),
                    'reported_at'    => now(),
                ]);

                // For a panel auto-created in this import, record its membership so
                // it's a proper, reusable panel next time the same report is imported.
                if ($rowPanelId && $test && !empty($createdPanelIds[$rowPanelId])) {
                    LabPanel::find($rowPanelId)?->tests()->syncWithoutDetaching([$test->id]);
                }
            }

            // Panel selected but no result rows parsed: still create the panel line so
            // the order records the panel (staff can add results later via the order screen).
            if ($panel && $order->items()->count() === 0) {
                $order->items()->create([
                    'lab_panel_id'      => $panel->id,
                    'lab_test_id'       => null,
                    'price'             => 0,
                    'status'            => 'Ordered',
                    'result_entered_at' => null,
                    'result_entered_by' => null,
                ]);
            }

            // Uploaded reports are NOT billed here. Like a manual OPD lab entry, the
            // tests are simply recorded — no charge is shown and no bill is created or
            // settled. Any billing is handled by staff through the normal billing
            // screen, exactly as for a manually entered lab order.

            return $order;
        });

        if ($order->items()->count() === 0) {
            $order->delete();
            return back()->withErrors(['rows' => 'No rows were included. Tick at least one row to import.'])->withInput();
        }

        // Clear the upload review session (PRG) now that it's saved.
        session()->forget('lab_report_review');

        // Close out the pending (WhatsApp/queued) report, if this came from one.
        if (!empty($data['pending_id'])) {
            \App\Models\PendingLabReport::whereKey($data['pending_id'])->update([
                'status'       => 'Imported',
                'patient_id'   => $data['patient_id'],
                'lab_order_id' => $order->id,
                'handled_by'   => auth()->id(),
                'handled_at'   => now(),
            ]);
        }

        if (!empty($data['encounter_id'])) {
            return redirect()->route('encounters.show', $data['encounter_id'])
                ->with('success', "Lab report imported as {$order->order_no}.")
                ->withFragment('tab-labs');
        }
        return redirect()->route('lab.order', $order)
            ->with('success', "Lab report imported as {$order->order_no}.");
    }

    // ───────────── Pending lab reports (queued from WhatsApp / inbound) ─────────────

    /** List reports waiting to be matched to a patient and confirmed. */
    public function pendingReports()
    {
        $pending = \App\Models\PendingLabReport::where('status', 'Pending')
            ->latest()->paginate(20);
        return view('lab.pending', compact('pending'));
    }

    /** Open a queued report on the same review screen (staff picks the patient). */
    public function reviewPending(\App\Models\PendingLabReport $pending)
    {
        $parsed = $pending->parsed ?: ['source' => $pending->source, 'header' => [], 'analytes' => [], 'raw_text' => ''];

        $tests = LabTest::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'short_code', 'unit', 'normal_range', 'price']);
        foreach ($parsed['analytes'] as &$a) {
            $a['test_id'] = $a['test_id'] ?? $this->guessTest($a['parameter'], $tests);
            $a['price']   = $a['price'] ?? (is_numeric($a['test_id'])
                ? (float) optional($tests->firstWhere('id', (int) $a['test_id']))->price : 0);
        }
        unset($a);

        $patients = Patient::where('is_active', true)->orderBy('first_name')->get();
        $patient  = $pending->patient_id ? Patient::find($pending->patient_id) : null;
        $sampleDate = optional($pending->sample_date)->toDateString() ?? today()->toDateString();
        $encounterId = null; // resolved from the chosen patient + date on save
        $panels = LabPanel::with('tests:id')->where('is_active', true)->orderBy('name')->get();
        $this->assignSectionPanels($parsed['analytes'], $panels);

        return view('lab.review-report', compact(
            'patient', 'patients', 'parsed', 'tests', 'sampleDate', 'encounterId',
            'panels', 'pending'
        ) + ['path' => $pending->report_path]);
    }

    /** Dismiss a queued report (spam, duplicate, unreadable). */
    public function rejectPending(\App\Models\PendingLabReport $pending)
    {
        $pending->update(['status' => 'Rejected', 'handled_by' => auth()->id(), 'handled_at' => now()]);
        return redirect()->route('lab.pending')->with('success', 'Pending report dismissed.');
    }

    /**
     * Best-effort match of a report parameter name to an existing lab test.
     * Returns a test id, or 'new' when nothing matches (review screen can change it).
     */
    private function guessTest(string $parameter, $tests): string
    {
        $norm = fn($s) => preg_replace('/[^a-z0-9]/', '', strtolower($s));
        $p = $norm($parameter);
        if ($p === '') return 'new';

        // exact normalized name / short_code
        foreach ($tests as $t) {
            if ($norm($t->name) === $p || ($t->short_code && $norm($t->short_code) === $p)) {
                return (string) $t->id;
            }
        }
        // common aliases seen on reports
        $aliases = [
            'thyroidstimulatinghormones' => ['tsh'],
            'thyroidstimulatinghormone'  => ['tsh'],
            't3totaltriiodothyronine'    => ['totalt3', 't3'],
            't4totalthyroxine'           => ['totalt4', 't4'],
        ];
        if (isset($aliases[$p])) {
            foreach ($tests as $t) {
                if (in_array($norm($t->name), $aliases[$p], true)) return (string) $t->id;
            }
        }
        // containment either direction (longer names)
        foreach ($tests as $t) {
            $n = $norm($t->name);
            if ($n !== '' && strlen($n) >= 4 && (str_contains($p, $n) || str_contains($n, $p))) {
                return (string) $t->id;
            }
        }
        return 'new';
    }

    /**
     * Conservative panel suggestion from the report text. Returns a panel id when
     * confident (short-code present, or a single distinctive name token matches),
     * else '' so staff pick it themselves. Never guesses when ambiguous.
     */
    private function guessPanel(string $rawText, $panels): string
    {
        $norm = fn($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $text = $norm($rawText);
        if ($text === '') return '';

        // 1) exact short-code appearing in the text (e.g. "LIPID", "LFT")
        foreach ($panels as $p) {
            $sc = $norm($p->short_code);
            if ($sc !== '' && strlen($sc) >= 3 && str_contains($text, $sc)) return (string) $p->id;
        }
        // 2) distinctive name token (ignore generic words); require a UNIQUE winner
        $generic = ['profile','panel','test','total','free','function','study','studies','workup','screen','basic'];
        $hits = [];
        foreach ($panels as $p) {
            foreach (preg_split('/\s+/', strtolower($p->name)) as $w) {
                $w = preg_replace('/[^a-z0-9]/', '', $w);
                if (strlen($w) >= 5 && !in_array($w, $generic, true) && str_contains($text, $w)) {
                    $hits[$p->id] = ($hits[$p->id] ?? 0) + 1;
                }
            }
        }
        if (count($hits) === 1) return (string) array_key_first($hits);
        return '';
    }

    /**
     * Give each parsed analyte a default panel id from the report's own section
     * headings, so member results save grouped under their panel (LFT/RFT/CBC…):
     *  - a section whose heading matches a catalog panel groups its rows there;
     *  - an unnamed sub-section (e.g. "DIFFERENTIAL COUNT") merges into the
     *    preceding panel when every matched test in it is a member of that panel;
     *  - standalone blocks (URIC ACID, ESR) that resolve to no panel stay
     *    individual, and reset the running panel so later blocks don't inherit it.
     * Mutates $analytes, adding 'panel_id' ('' = individual test).
     *
     * @param array $analytes rows (each may carry 'section' and 'test_id')
     * @param \Illuminate\Support\Collection $panels panels eager-loaded WITH tests
     */
    private function assignSectionPanels(array &$analytes, $panels): void
    {
        $norm = fn($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));

        $members = [];
        foreach ($panels as $p) {
            $members[$p->id] = $p->relationLoaded('tests')
                ? $p->tests->pluck('id')->map(fn($x) => (int) $x)->all() : [];
        }

        $lastPanelId = null;
        $i = 0; $n = count($analytes);
        while ($i < $n) {
            $sec = $analytes[$i]['section'] ?? null;
            $run = [];
            for ($j = $i; $j < $n && (($analytes[$j]['section'] ?? null) === $sec); $j++) $run[] = $j;

            $panelId = $this->panelForSectionName((string) $sec, $panels, $norm);

            // matched catalog test-ids present in this section run
            $runTids = [];
            foreach ($run as $k) {
                if (ctype_digit((string) ($analytes[$k]['test_id'] ?? ''))) $runTids[] = (int) $analytes[$k]['test_id'];
            }

            // (a) un-named sub-section (e.g. "DIFFERENTIAL COUNT"): merge into the
            //     preceding panel when its matched tests are all members of it.
            if ($panelId === null && $lastPanelId !== null && $runTids) {
                $all = true;
                foreach ($runTids as $tid) { if (!in_array($tid, $members[$lastPanelId] ?? [], true)) { $all = false; break; } }
                if ($all) $panelId = $lastPanelId;
            }

            // (b) member-based fallback: a multi-test section (e.g. "THYROID
            //     FUNCTION TEST") whose every matched test belongs to exactly ONE
            //     catalog panel is grouped there. Singletons stay individual.
            if ($panelId === null && count($runTids) >= 2) {
                $cand = [];
                foreach ($panels as $p) {
                    $mem = $members[$p->id] ?? [];
                    if (!$mem) continue;
                    $all = true;
                    foreach ($runTids as $tid) { if (!in_array($tid, $mem, true)) { $all = false; break; } }
                    if ($all) $cand[] = (int) $p->id;
                }
                if (count($cand) === 1) $panelId = $cand[0];
            }

            if ($panelId !== null) {
                $assign = (string) $panelId;
                $lastPanelId = $panelId;
            } elseif (count($run) >= 2 && trim((string) $sec) !== '') {
                // a grouping section (≥2 tests) with no catalog panel → flag it to be
                // auto-created on save, named after the report's heading.
                $assign = 'new:' . ucwords(strtolower(trim((string) $sec)));
                $lastPanelId = null;
            } else {
                $assign = '';                // single standalone test → individual
                $lastPanelId = null;
            }
            foreach ($run as $k) $analytes[$k]['panel_id'] = $assign;
            $i = $j;
        }
    }

    /** Match a report section heading to a catalog panel id, else null (conservative). */
    private function panelForSectionName(string $section, $panels, callable $norm): ?int
    {
        $s = $norm($section);
        if ($s === '') return null;
        foreach ($panels as $p) {                                   // exact name / short code
            if ($norm($p->name) === $s) return (int) $p->id;
            $sc = $norm($p->short_code);
            if ($sc !== '' && strlen($sc) >= 3 && $sc === $s) return (int) $p->id;
        }
        foreach ($panels as $p) {                                   // full-name containment
            $pn = $norm($p->name);
            if (strlen($pn) >= 8 && (str_contains($s, $pn) || str_contains($pn, $s))) return (int) $p->id;
        }
        return null;
    }

    public function index(Request $request)
    {
        $orders = LabOrder::with('patient', 'doctor', 'items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('ordered_date', $request->date))
            ->latest()
            ->paginate(20);
        $pendingCount = \App\Models\PendingLabReport::where('status', 'Pending')->count();
        return view('lab.index', compact('orders', 'pendingCount'));
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
                foreach ($panel->tests->values() as $i => $test) {
                    // Panel profit is recorded once, on the panel's first line.
                    $order->items()->create(['lab_test_id'=>$test->id,'lab_panel_id'=>$panel->id,'price'=>0,'profit'=>$i === 0 ? $panel->profit : null,'status'=>'Ordered']);
                }
            } else {
                $order->items()->create(['lab_test_id'=>$item['model']->id,'lab_panel_id'=>null,'price'=>$item['model']->price,'profit'=>$item['model']->profit,'status'=>'Ordered']);
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
                foreach ($panel->tests->values() as $i => $test) {
                    $order->items()->create([
                        'lab_test_id'  => $test->id,
                        'lab_panel_id' => $panel->id,
                        'price'        => 0,
                        // Panel profit is recorded once, on the panel's first line.
                        'profit'       => $i === 0 ? $panel->profit : null,
                        'status'       => 'Ordered',
                    ]);
                }
            } else {
                $order->items()->create([
                    'lab_test_id'  => $item['model']->id,
                    'lab_panel_id' => null,
                    'price'        => $item['model']->price,
                    'profit'       => $item['model']->profit,
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

    // JSON: a patient's recent encounters, for the "attach to visit" dropdown on report review.
    public function patientEncounters(Patient $patient)
    {
        $encs = Encounter::where('patient_id', $patient->id)
            ->with('doctor')
            ->orderByDesc('encounter_date')->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'encounter_no', 'encounter_date', 'doctor_id', 'chief_complaint']);

        return response()->json([
            'encounters' => $encs->map(function ($e) {
                $date = \Carbon\Carbon::parse($e->encounter_date);
                $doc  = $e->doctor?->name ? ' · Dr '.$e->doctor->name : '';
                $cc   = $e->chief_complaint ? ' — '.\Illuminate\Support\Str::limit(strip_tags($e->chief_complaint), 30) : '';
                return [
                    'id'    => $e->id,
                    'date'  => $date->toDateString(),
                    'label' => $date->format('d M Y').' · '.$e->encounter_no.$doc.$cc,
                ];
            })->values(),
        ]);
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
        $wasCompleted = $order->status === 'Completed';
        $order->update(['status' => $request->status]);

        // Patient-facing auto-send on Completed REMOVED 2026-08-26: no more
        // auto WhatsApp/email to the patient when status flips to Completed
        // -- staff must click Send/Email/WhatsApp on the lab order to send it.

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
            'profit'          => 'nullable|numeric|min:0|lte:price',
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
            'profit'          => 'nullable|numeric|min:0|lte:price',
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
        if ($test->profit !== null && $request->price < $test->profit) {
            return response()->json(['success' => false, 'message' => "Price can't be lower than the test's profit (₹{$test->profit})."], 422);
        }
        $test->update(['price' => $request->price]);
        return response()->json(['success' => true]);
    }

    /** Inline-save a test's profit (₹ the clinic actually earns out of its price). */
    public function updateTestProfit(Request $request, LabTest $test)
    {
        $request->validate(['profit' => 'nullable|numeric|min:0']);
        $profit = $request->filled('profit') ? (float) $request->profit : null;
        if ($profit !== null && $profit > (float) $test->price) {
            return response()->json(['success' => false, 'message' => "Profit can't be more than the price (₹{$test->price})."], 422);
        }
        $test->update(['profit' => $profit]);
        return response()->json(['success' => true, 'profit' => $profit, 'price' => (float) $test->price]);
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

    /**
     * Set profit on many tests and/or panels at once: a % of each one's price, or a
     * fixed ₹ amount (capped at the price). Optionally also re-snapshot the profit on
     * past lab orders: test lines get the % of the price actually charged on that
     * line (or the fixed amount, capped); for panels, each (order, panel) group's
     * first line gets the panel's new profit and its other lines are cleared.
     */
    public function bulkSetProfit(Request $request)
    {
        $data = $request->validate([
            'apply_tests'  => 'nullable|boolean',
            'apply_panels' => 'nullable|boolean',
            'category_id'  => 'nullable|exists:lab_categories,id',
            'mode'         => 'required|in:percent,fixed',
            'value'        => ['required', 'numeric', 'min:0', $request->mode === 'percent' ? 'max:100' : 'max:10000000'],
            'past_orders'  => 'nullable|boolean',
        ]);
        $tests = !empty($data['apply_tests']); $panels = !empty($data['apply_panels']);
        if (!$tests && !$panels) {
            return back()->withErrors(['apply_tests' => 'Choose tests, panels, or both.'])->with('settings_tab', $request->input('settings_tab', 'lab'));
        }
        $v = (float) $data['value'];
        // SQL for "profit from price column $col" in the chosen mode.
        $expr = fn (string $col) => $data['mode'] === 'percent' ? "ROUND({$col} * {$v} / 100, 2)" : "LEAST({$v}, {$col})";

        $nTests = $nPanels = $nLines = 0;
        DB::transaction(function () use ($data, $tests, $panels, $expr, &$nTests, &$nPanels, &$nLines) {
            if ($tests) {
                $q = LabTest::query();
                if (!empty($data['category_id'])) $q->where('lab_category_id', $data['category_id']);
                $ids = $q->pluck('id');
                $nTests = LabTest::whereIn('id', $ids)->update(['profit' => DB::raw($expr('price'))]);
                if (!empty($data['past_orders'])) {
                    $nLines += DB::table('lab_order_items')->whereNull('lab_panel_id')->whereIn('lab_test_id', $ids)
                        ->update(['profit' => DB::raw($expr('price'))]);
                }
            }
            if ($panels) {
                $nPanels = LabPanel::query()->update(['profit' => DB::raw($expr('price'))]);
                if (!empty($data['past_orders'])) {
                    DB::table('lab_order_items')->whereNotNull('lab_panel_id')->update(['profit' => null]);
                    $nLines += DB::update(
                        'UPDATE lab_order_items i
                           JOIN (SELECT MIN(id) id FROM lab_order_items WHERE lab_panel_id IS NOT NULL GROUP BY lab_order_id, lab_panel_id) f ON f.id = i.id
                           JOIN lab_panels p ON p.id = i.lab_panel_id
                            SET i.profit = p.profit'
                    );
                }
            }
        });

        $how = $data['mode'] === 'percent' ? "{$v}% of price" : '₹' . number_format($v, 2) . ' (capped at price)';
        $what = implode(' and ', array_filter([$tests ? "{$nTests} tests" : null, $panels ? "{$nPanels} panels" : null]));
        return back()
            ->with('success', "Profit set to {$how} for {$what}." . (!empty($data['past_orders']) ? " Past orders updated ({$nLines} order lines)." : ''))
            ->with('settings_tab', $request->input('settings_tab', 'lab'));
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
            'profit'           => 'nullable|numeric|min:0|lte:price',
            'test_ids'         => 'required|array|min:1',
            'test_ids.*'       => 'exists:lab_tests,id',
        ]);
        $panel = LabPanel::create([
            'name'             => $data['name'],
            'short_code'       => $data['short_code'] ?? null,
            'description'      => $data['description'] ?? null,
            'turnaround_hours' => $data['turnaround_hours'] ?? null,
            'price'            => $data['price'],
            'profit'           => $data['profit'] ?? null,
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
            'profit'           => 'nullable|numeric|min:0|lte:price',
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
        if ($panel->profit !== null && $request->price < $panel->profit) {
            return response()->json(['success' => false, 'message' => "Price can't be lower than the panel's profit (₹{$panel->profit})."], 422);
        }
        $panel->update(['price' => $request->price]);
        return response()->json(['success' => true]);
    }

    /** Inline-save a panel's profit (₹ the clinic actually earns out of its price). */
    public function updatePanelProfit(Request $request, LabPanel $panel)
    {
        $request->validate(['profit' => 'nullable|numeric|min:0']);
        $profit = $request->filled('profit') ? (float) $request->profit : null;
        if ($profit !== null && $profit > (float) $panel->price) {
            return response()->json(['success' => false, 'message' => "Profit can't be more than the price (₹{$panel->price})."], 422);
        }
        $panel->update(['profit' => $profit]);
        return response()->json(['success' => true, 'profit' => $profit, 'price' => (float) $panel->price]);
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
