<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\IpdCharge;
use App\Models\Bill;
use App\Models\OpdToken;
use App\Models\IpdDeposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $tab      = $request->get('tab', 'admissions');
        $fromDate = $request->get('from', now()->startOfMonth()->toDateString());
        $toDate   = $request->get('to',   today()->toDateString());
        $wardId   = $request->get('ward_id');
        $doctorId = $request->get('doctor_id');

        $wards   = DB::table('wards')->where('is_active', true)->orderBy('name')->get();
        $doctors = DB::table('doctors')->where('is_active', true)->orderByRaw("CONCAT(first_name,' ',last_name)")->get();

        $admissionsReport = $revenueReport = $dischargeReport = $opdReport = $opdBillingReport = collect();
        $revenueSummary   = [];
        $opdByDoctor = $opdByDay = [];
        $demoAgeGroups = $demoGender = $demoMonthly = $demoTopDiagnoses = [];
        $opdBillingSummary = [];
        $doctorRevenue = collect();
        $dailyCollectionData = [];

        if ($tab === 'admissions') {
            $admissionsReport = Admission::with(['patient','ward','admittingDoctor'])
                ->whereBetween('admission_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->when($wardId,   fn($q) => $q->where('ward_id',   $wardId))
                ->when($doctorId, fn($q) => $q->where('admitting_doctor_id', $doctorId))
                ->orderByDesc('admission_date')
                ->get();
        }

        if ($tab === 'revenue') {
            $revenueSummary = IpdCharge::whereBetween('charge_date', [$fromDate, $toDate])
                ->when($wardId, fn($q) => $q->whereHas('admission', fn($a) => $a->where('ward_id', $wardId)))
                ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();

            $revenueReport = IpdCharge::with(['admission.patient','admission.ward'])
                ->whereBetween('charge_date', [$fromDate, $toDate])
                ->when($wardId, fn($q) => $q->whereHas('admission', fn($a) => $a->where('ward_id', $wardId)))
                ->orderBy('charge_date')
                ->get();
        }

        if ($tab === 'opd') {
            $opdReport = OpdToken::with(['patient', 'doctor'])
                ->whereBetween('token_date', [$fromDate, $toDate])
                ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
                ->orderByDesc('token_date')
                ->orderBy('token_no')
                ->get();

            $opdByDoctor = $opdReport->groupBy('doctor_id')->map(fn($tokens) => [
                'doctor'    => $tokens->first()->doctor,
                'total'     => $tokens->count(),
                'done'      => $tokens->where('status', 'done')->count(),
                'waiting'   => $tokens->whereIn('status', ['waiting','called','in_consultation'])->count(),
                'skipped'   => $tokens->where('status', 'skipped')->count(),
            ])->sortByDesc('total');

            $opdByDay = $opdReport->groupBy(fn($t) => $t->token_date->toDateString())
                ->map(fn($tokens, $date) => [
                    'date'    => $date,
                    'total'   => $tokens->count(),
                    'done'    => $tokens->where('status', 'done')->count(),
                    'skipped' => $tokens->where('status', 'skipped')->count(),
                ])->sortKeysDesc();
        }

        if ($tab === 'opd-billing') {
            $opdBillingReport = Bill::with(['patient', 'encounter.doctor'])
                ->whereBetween('bill_date', [$fromDate, $toDate])
                ->when($doctorId, fn($q) => $q->whereHas('encounter', fn($e) => $e->where('doctor_id', $doctorId)))
                ->orderByDesc('bill_date')
                ->orderByDesc('id')
                ->get();

            $opdBillingSummary = [
                'count'    => $opdBillingReport->count(),
                'billed'   => $opdBillingReport->sum('total_amount'),
                'paid'     => $opdBillingReport->sum('paid_amount'),
                'due'      => $opdBillingReport->sum('due_amount'),
                'discount' => $opdBillingReport->sum('discount_amount'),
                'unpaid'   => $opdBillingReport->where('status', 'Unpaid')->count(),
                'partial'  => $opdBillingReport->where('status', 'Partial')->count(),
                'paid_ct'  => $opdBillingReport->where('status', 'Paid')->count(),
            ];
        }

        if ($tab === 'demographics') {
            $patients = DB::table('patients')->where('is_active', true);

            $demoGender = (clone $patients)->select('gender', DB::raw('COUNT(*) as count'))
                ->groupBy('gender')->get()->keyBy('gender');

            $demoMonthly = DB::table('patients')
                ->selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderBy('mo')
                ->get()->keyBy('mo');

            // Age group bucketing via raw CASE
            $demoAgeGroups = DB::table('patients')
                ->where('is_active', true)
                ->whereNotNull('date_of_birth')
                ->selectRaw("
                    CASE
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 1  THEN 'Infant (<1)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 6  THEN 'Child (1–5)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 13 THEN 'Child (6–12)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Teen (13–17)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 30 THEN 'Young Adult (18–29)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 45 THEN 'Adult (30–44)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 60 THEN 'Middle Age (45–59)'
                        WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 75 THEN 'Senior (60–74)'
                        ELSE 'Elderly (75+)'
                    END AS age_group,
                    COUNT(*) as count
                ")
                ->groupByRaw('age_group')
                ->get();

            $demoTopDiagnoses = DB::table('admissions')
                ->whereNotNull('provisional_diagnosis')
                ->where('provisional_diagnosis', '!=', '')
                ->select('provisional_diagnosis', DB::raw('COUNT(*) as count'))
                ->groupBy('provisional_diagnosis')
                ->orderByDesc('count')
                ->limit(15)
                ->get();
        }

        if ($tab === 'daily-collection') {
            $dcFrom = $request->get('from', today()->toDateString());
            $dcTo   = $request->get('to',   today()->toDateString());

            // OPD bills by payment method
            $opdBillsByMethod = DB::table('bills')
                ->whereBetween('bill_date', [$dcFrom, $dcTo])
                ->where('status', '!=', 'Unpaid')
                ->select('payment_method', DB::raw('SUM(paid_amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get();

            // IPD charges total
            $ipdChargesTotal = IpdCharge::whereBetween('charge_date', [$dcFrom, $dcTo])->sum('amount');

            // IPD deposits by payment method
            $depositsByMethod = IpdDeposit::whereBetween(DB::raw('DATE(created_at)'), [$dcFrom, $dcTo])
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get();

            // Individual bills for the day
            $dailyBills = Bill::with(['patient', 'encounter.doctor'])
                ->whereBetween('bill_date', [$dcFrom, $dcTo])
                ->where('status', '!=', 'Unpaid')
                ->orderByDesc('bill_date')
                ->orderByDesc('id')
                ->get();

            // Individual deposits for the day
            $dailyDeposits = IpdDeposit::with(['admission.patient', 'receivedBy'])
                ->whereBetween(DB::raw('DATE(created_at)'), [$dcFrom, $dcTo])
                ->orderByDesc('created_at')
                ->get();

            $dailyCollectionData = [
                'from'              => $dcFrom,
                'to'                => $dcTo,
                'opd_bills'         => $opdBillsByMethod,
                'opd_total'         => $opdBillsByMethod->sum('total'),
                'ipd_charges_total' => $ipdChargesTotal,
                'deposits'          => $depositsByMethod,
                'deposits_total'    => $depositsByMethod->sum('total'),
                'grand_total'       => $opdBillsByMethod->sum('total') + $depositsByMethod->sum('total'),
                'daily_bills'       => $dailyBills,
                'daily_deposits'    => $dailyDeposits,
            ];
        }

        if ($tab === 'discharge') {
            $dischargeReport = Admission::with(['patient','ward','admittingDoctor'])
                ->where('status', 'discharged')
                ->whereBetween('discharge_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
                ->when($wardId,   fn($q) => $q->where('ward_id',   $wardId))
                ->when($doctorId, fn($q) => $q->where('admitting_doctor_id', $doctorId))
                ->orderByDesc('discharge_date')
                ->get();
        }

        if ($tab === 'doctor-revenue') {
            // OPD revenue: sum of bill totals grouped by encounter doctor
            $opdRev = DB::table('bills')
                ->join('encounters', 'bills.encounter_id', '=', 'encounters.id')
                ->join('doctors', 'encounters.doctor_id', '=', 'doctors.id')
                ->whereBetween('bills.bill_date', [$fromDate, $toDate])
                ->select(
                    'doctors.id as doctor_id',
                    DB::raw("CONCAT('Dr. ', doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                    'doctors.specialization',
                    DB::raw('COUNT(DISTINCT bills.id) as opd_count'),
                    DB::raw('SUM(bills.paid_amount) as opd_revenue')
                )
                ->groupBy('doctors.id', 'doctors.first_name', 'doctors.last_name', 'doctors.specialization')
                ->get()
                ->keyBy('doctor_id');

            // IPD revenue: sum of IPD charges grouped by admitting doctor
            $ipdRev = DB::table('ipd_charges')
                ->join('admissions', 'ipd_charges.admission_id', '=', 'admissions.id')
                ->join('doctors', 'admissions.admitting_doctor_id', '=', 'doctors.id')
                ->whereBetween('ipd_charges.charge_date', [$fromDate, $toDate])
                ->select(
                    'doctors.id as doctor_id',
                    DB::raw("CONCAT('Dr. ', doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                    'doctors.specialization',
                    DB::raw('COUNT(DISTINCT admissions.id) as ipd_count'),
                    DB::raw('SUM(ipd_charges.amount) as ipd_revenue')
                )
                ->groupBy('doctors.id', 'doctors.first_name', 'doctors.last_name', 'doctors.specialization')
                ->get()
                ->keyBy('doctor_id');

            // Merge both into a unified collection
            $allDoctorIds = $opdRev->keys()->merge($ipdRev->keys())->unique();
            $doctorRevenue = $allDoctorIds->map(function($id) use ($opdRev, $ipdRev) {
                $opd = $opdRev[$id] ?? null;
                $ipd = $ipdRev[$id] ?? null;
                return (object)[
                    'doctor_id'    => $id,
                    'doctor_name'  => ($opd ?? $ipd)->doctor_name,
                    'specialization' => ($opd ?? $ipd)->specialization,
                    'opd_count'    => $opd?->opd_count ?? 0,
                    'opd_revenue'  => (float)($opd?->opd_revenue ?? 0),
                    'ipd_count'    => $ipd?->ipd_count ?? 0,
                    'ipd_revenue'  => (float)($ipd?->ipd_revenue ?? 0),
                ];
            })->sortByDesc(fn($d) => $d->opd_revenue + $d->ipd_revenue)->values();
        }

        // ── Revenue breakdown tabs (lab / pharmacy / consultation / services) ──
        $breakdown = [];
        if ($tab === 'lab-revenue') {
            // Same rules as labDaily(): order totals incl. panels, cancelled excluded;
            // profit = test lines' (snapshot or current) + each panel once per order.
            $orders = DB::table('lab_orders as o')
                ->leftJoin('patients as pt', 'pt.id', '=', 'o.patient_id')
                ->whereBetween(DB::raw('DATE(o.ordered_date)'), [$fromDate, $toDate])
                ->where('o.status', '!=', 'Cancelled')
                ->when($doctorId, fn ($q) => $q->where('o.doctor_id', $doctorId))
                ->orderByDesc('o.ordered_date')->orderByDesc('o.id')
                ->get(['o.id', 'o.order_no', 'o.ordered_date', 'o.status', 'o.total_amount', 'o.discount',
                       DB::raw("CONCAT(pt.first_name,' ',pt.last_name) as patient"), 'pt.jhc_id']);
            $ids = $orders->pluck('id');
            $testProfit = DB::table('lab_order_items as i')->leftJoin('lab_tests as t', 't.id', '=', 'i.lab_test_id')
                ->whereIn('i.lab_order_id', $ids)->whereNull('i.lab_panel_id')
                ->groupBy('i.lab_order_id')->selectRaw('i.lab_order_id, SUM(COALESCE(i.profit, t.profit, 0)) p')->pluck('p', 'lab_order_id');
            $panelProfit = DB::query()->fromSub(
                    DB::table('lab_order_items')->whereIn('lab_order_id', $ids)->whereNotNull('lab_panel_id')
                        ->groupBy('lab_order_id', 'lab_panel_id')->selectRaw('lab_order_id, lab_panel_id, MAX(profit) snap'), 'g')
                ->leftJoin('lab_panels as p', 'p.id', '=', 'g.lab_panel_id')
                ->groupBy('g.lab_order_id')->selectRaw('g.lab_order_id, SUM(COALESCE(g.snap, p.profit, 0)) p')->pluck('p', 'lab_order_id');
            $testNames = DB::table('lab_order_items as i')->join('lab_tests as t', 't.id', '=', 'i.lab_test_id')
                ->whereIn('i.lab_order_id', $ids)->whereNull('i.lab_panel_id')
                ->groupBy('i.lab_order_id')->selectRaw("i.lab_order_id, GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') n")->pluck('n', 'lab_order_id');
            $panelNames = DB::table('lab_order_items as i')->join('lab_panels as p', 'p.id', '=', 'i.lab_panel_id')
                ->whereIn('i.lab_order_id', $ids)
                ->groupBy('i.lab_order_id')->selectRaw("i.lab_order_id, GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') n")->pluck('n', 'lab_order_id');
            foreach ($orders as $o) {
                $o->profit = (float) ($testProfit[$o->id] ?? 0) + (float) ($panelProfit[$o->id] ?? 0);
                $o->panels = $panelNames[$o->id] ?? null;
                $o->tests  = $testNames[$o->id] ?? null;
            }
            $breakdown = ['rows' => $orders, 'revenue' => (float) $orders->sum('total_amount'), 'profit' => (float) $orders->sum('profit')];
        }

        if ($tab === 'pharmacy-revenue') {
            // Same pricing rule as pharmacyDaily() / the accounts' pharmacy sales voucher.
            $markup = 1 + (float) (setting('accounts_pharmacy_sale_markup_pct') ?? 0) / 100;
            $lines = DB::table('stock_transactions as t')
                ->leftJoin('medicines as m', 'm.id', '=', 't.medicine_id')
                ->leftJoin('pharmacy_stock as s', 's.id', '=', 't.pharmacy_stock_id')
                ->leftJoin('prescriptions as rx', 'rx.id', '=', 't.prescription_id')
                ->leftJoin('patients as pt', 'pt.id', '=', 'rx.patient_id')
                ->where('t.type', 'dispensed')->where('t.quantity', '>', 0)
                ->whereBetween(DB::raw('DATE(t.created_at)'), [$fromDate, $toDate])
                ->when($doctorId, fn ($q) => $q->where('rx.doctor_id', $doctorId))
                ->orderByDesc('t.created_at')
                ->get(['t.id', 't.created_at', 't.quantity', 't.batch_number', 't.reference', 'm.name as medicine', 'm.unit_price', 's.unit_cost',
                       DB::raw("CONCAT(pt.first_name,' ',pt.last_name) as patient"), 'pt.jhc_id']);
            foreach ($lines as $l) {
                $q = (float) $l->quantity;
                $l->sale   = $l->unit_price > 0 ? $q * $l->unit_price : ($l->unit_cost > 0 ? $q * $l->unit_cost * $markup : 0);
                $l->cost   = $l->unit_cost > 0 ? $q * $l->unit_cost : null;
                $l->profit = ($l->sale > 0 && $l->cost !== null) ? $l->sale - $l->cost : null;   // null = can't be priced
            }
            $breakdown = ['rows' => $lines, 'sale' => (float) $lines->sum('sale'), 'cost' => (float) $lines->whereNotNull('profit')->sum('cost'),
                          'profit' => (float) $lines->sum('profit'), 'unpriced' => $lines->whereNull('profit')->count()];
        }

        if (in_array($tab, ['consultation-revenue', 'services-revenue'], true)) {
            $category = $request->get('category');
            $items = DB::table('bill_items as i')
                ->join('bills as b', 'b.id', '=', 'i.bill_id')
                ->leftJoin('patients as pt', 'pt.id', '=', 'b.patient_id')
                ->leftJoin('encounters as e', 'e.id', '=', 'b.encounter_id')
                ->leftJoin('doctors as d', 'd.id', '=', 'e.doctor_id')
                ->whereBetween('b.bill_date', [$fromDate, $toDate])
                ->where('b.status', '!=', 'Unpaid')
                ->when($tab === 'consultation-revenue', fn ($q) => $q->where('i.item_type', 'Consultation'))
                ->when($tab === 'services-revenue', fn ($q) => $q->where('i.item_type', '!=', 'Consultation')
                    ->when($category, fn ($q2) => $q2->where('i.item_type', $category)))
                ->when($doctorId, fn ($q) => $q->where('e.doctor_id', $doctorId))
                ->orderByDesc('b.bill_date')->orderByDesc('b.id')
                ->get(['i.id', 'i.item_type', 'i.item_name', 'i.quantity', 'i.unit_price', 'i.total', 'b.id as bill_id', 'b.bill_no', 'b.bill_date',
                       DB::raw("CONCAT(pt.first_name,' ',pt.last_name) as patient"), 'pt.jhc_id',
                       DB::raw("CONCAT(d.first_name,' ',d.last_name) as doctor")]);
            $breakdown = [
                'rows'     => $items,
                'total'    => (float) $items->sum('total'),
                'byDoctor' => $items->groupBy(fn ($r) => $r->doctor ?: '—')->map(fn ($g) => ['count' => $g->count(), 'total' => (float) $g->sum('total')])->sortByDesc('total'),
                'byType'   => $items->groupBy('item_type')->map(fn ($g) => ['count' => $g->count(), 'total' => (float) $g->sum('total')])->sortByDesc('total'),
                'category' => $category,
                'types'    => DB::table('bill_items')->where('item_type', '!=', 'Consultation')->distinct()->orderBy('item_type')->pluck('item_type'),
            ];
        }

        return view('reports.index', compact(
            'tab','fromDate','toDate','wardId','doctorId','breakdown',
            'wards','doctors',
            'admissionsReport','revenueReport','revenueSummary','dischargeReport',
            'opdReport','opdByDoctor','opdByDay',
            'opdBillingReport','opdBillingSummary',
            'doctorRevenue',
            'demoAgeGroups','demoGender','demoMonthly','demoTopDiagnoses',
            'dailyCollectionData'
        ));
    }

    public function doctorRevenue(\Illuminate\Http\Request $request)
    {
        $fromDate = $request->get('from', now()->startOfMonth()->toDateString());
        $toDate   = $request->get('to',   today()->toDateString());

        // OPD revenue grouped by doctor
        $opdRev = DB::table('bills')
            ->join('encounters', 'bills.encounter_id', '=', 'encounters.id')
            ->join('doctors', 'encounters.doctor_id', '=', 'doctors.id')
            ->whereBetween('bills.bill_date', [$fromDate, $toDate])
            ->select(
                'doctors.id as doctor_id',
                DB::raw("CONCAT('Dr. ', doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                'doctors.specialization',
                DB::raw('COUNT(DISTINCT bills.id) as opd_count'),
                DB::raw('SUM(bills.total_amount) as opd_billed'),
                DB::raw('SUM(bills.paid_amount) as opd_paid')
            )
            ->groupBy('doctors.id', 'doctors.first_name', 'doctors.last_name', 'doctors.specialization')
            ->get()->keyBy('doctor_id');

        // IPD charges grouped by admitting doctor
        $ipdRev = DB::table('ipd_charges')
            ->join('admissions', 'ipd_charges.admission_id', '=', 'admissions.id')
            ->join('doctors', 'admissions.admitting_doctor_id', '=', 'doctors.id')
            ->whereBetween('ipd_charges.charge_date', [$fromDate, $toDate])
            ->select(
                'doctors.id as doctor_id',
                DB::raw("CONCAT('Dr. ', doctors.first_name, ' ', doctors.last_name) as doctor_name"),
                'doctors.specialization',
                DB::raw('COUNT(DISTINCT admissions.id) as ipd_admissions'),
                DB::raw('SUM(ipd_charges.amount) as ipd_charges')
            )
            ->groupBy('doctors.id', 'doctors.first_name', 'doctors.last_name', 'doctors.specialization')
            ->get()->keyBy('doctor_id');

        // IPD payments (deposits) grouped by admitting doctor
        $ipdPaid = DB::table('ipd_deposits')
            ->join('admissions', 'ipd_deposits.admission_id', '=', 'admissions.id')
            ->join('doctors', 'admissions.admitting_doctor_id', '=', 'doctors.id')
            ->whereBetween(DB::raw('DATE(ipd_deposits.created_at)'), [$fromDate, $toDate])
            ->select(
                'doctors.id as doctor_id',
                DB::raw('SUM(ipd_deposits.amount) as ipd_paid')
            )
            ->groupBy('doctors.id')
            ->get()->keyBy('doctor_id');

        $allDoctorIds = $opdRev->keys()->merge($ipdRev->keys())->unique();
        $rows = $allDoctorIds->map(function ($id) use ($opdRev, $ipdRev, $ipdPaid) {
            $opd  = $opdRev[$id]  ?? null;
            $ipd  = $ipdRev[$id]  ?? null;
            $paid = $ipdPaid[$id] ?? null;
            $ipdCharges  = (float)($ipd?->ipd_charges ?? 0);
            $ipdPayments = (float)($paid?->ipd_paid   ?? 0);
            $opdBilled   = (float)($opd?->opd_billed  ?? 0);
            $opdPaid     = (float)($opd?->opd_paid    ?? 0);
            return (object)[
                'doctor_id'       => $id,
                'doctor_name'     => ($opd ?? $ipd)->doctor_name,
                'specialization'  => ($opd ?? $ipd)->specialization,
                'opd_count'       => (int)($opd?->opd_count ?? 0),
                'opd_billed'      => $opdBilled,
                'opd_paid'        => $opdPaid,
                'ipd_admissions'  => (int)($ipd?->ipd_admissions ?? 0),
                'ipd_charges'     => $ipdCharges,
                'ipd_payments'    => $ipdPayments,
                'total'           => $ipdCharges + $opdBilled,
            ];
        })->sortByDesc('total')->values();

        return view('reports.doctor-revenue', compact('fromDate', 'toDate', 'rows'));
    }

    public function referralPerformance(\Illuminate\Http\Request $request)
    {
        $fromDate = $request->get('from', now()->startOfMonth()->toDateString());
        $toDate   = $request->get('to',   today()->toDateString());

        $sources = \App\Models\ReferralSource::where('is_active', true)->orderBy('name')->get();

        // Admissions grouped by referral_source_id in date range
        $admissionStats = DB::table('admissions')
            ->whereNotNull('referral_source_id')
            ->whereBetween('admission_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->select(
                'referral_source_id',
                DB::raw('COUNT(*) as patient_count')
            )
            ->groupBy('referral_source_id')
            ->get()->keyBy('referral_source_id');

        // IPD charges sum per referral source
        $chargeStats = DB::table('ipd_charges')
            ->join('admissions', 'ipd_charges.admission_id', '=', 'admissions.id')
            ->whereNotNull('admissions.referral_source_id')
            ->whereBetween('ipd_charges.charge_date', [$fromDate, $toDate])
            ->select(
                'admissions.referral_source_id',
                DB::raw('SUM(ipd_charges.amount) as ipd_total')
            )
            ->groupBy('admissions.referral_source_id')
            ->get()->keyBy('referral_source_id');

        // OPD bills sum per referral source (via patient's referral_source_id)
        $opdStats = DB::table('bills')
            ->join('patients', 'bills.patient_id', '=', 'patients.id')
            ->whereNotNull('patients.referral_source_id')
            ->whereBetween('bills.bill_date', [$fromDate, $toDate])
            ->select(
                'patients.referral_source_id',
                DB::raw('SUM(bills.total_amount) as opd_total')
            )
            ->groupBy('patients.referral_source_id')
            ->get()->keyBy('referral_source_id');

        // Payments already made per referral source
        $paymentStats = DB::table('referral_payments')
            ->select(
                'referral_source_id',
                DB::raw('SUM(calculated_amount) as incentive_due'),
                DB::raw('SUM(paid_amount) as paid_amount')
            )
            ->groupBy('referral_source_id')
            ->get()->keyBy('referral_source_id');

        $rows = $sources->map(function ($src) use ($admissionStats, $chargeStats, $opdStats, $paymentStats) {
            $admStat  = $admissionStats[$src->id]  ?? null;
            $chgStat  = $chargeStats[$src->id]     ?? null;
            $opdStat  = $opdStats[$src->id]         ?? null;
            $payStat  = $paymentStats[$src->id]     ?? null;

            $ipdTotal  = (float)($chgStat?->ipd_total ?? 0);
            $opdTotal  = (float)($opdStat?->opd_total ?? 0);
            $billTotal = $ipdTotal + $opdTotal;
            $patients  = (int)($admStat?->patient_count ?? 0);

            // Calculate incentive
            $incentiveDue = 0;
            if ($src->rate_type === 'percentage') {
                $incentiveDue = $billTotal * ((float)$src->rate_value / 100);
            } elseif ($src->rate_type === 'fixed') {
                $incentiveDue = (float)$src->rate_value * $patients;
            }

            $paidAmount = (float)($payStat?->paid_amount ?? 0);

            return (object)[
                'source'        => $src,
                'patients'      => $patients,
                'ipd_total'     => $ipdTotal,
                'opd_total'     => $opdTotal,
                'bill_total'    => $billTotal,
                'incentive_due' => $incentiveDue,
                'paid_amount'   => $paidAmount,
                'balance'       => max(0, $incentiveDue - $paidAmount),
            ];
        })->filter(fn($r) => $r->patients > 0 || $r->bill_total > 0)
          ->sortByDesc('bill_total')->values();

        return view('reports.referral-performance', compact('fromDate', 'toDate', 'rows'));
    }

    public function occupancy(\Illuminate\Http\Request $request)
    {
        $days = 30;
        $totalBeds = \App\Models\Bed::where('is_active', true)->count();

        // Build daily occupancy for last 30 days
        $dates = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->toDateString());
        }

        // Count admitted patients per day (admitted on/before that day, not yet discharged or discharged after)
        $dailyData = $dates->map(function ($date) use ($totalBeds) {
            $occupied = DB::table('admissions')
                ->where('status', '!=', 'transferred')
                ->where(function ($q) use ($date) {
                    $q->whereDate('admission_date', '<=', $date)
                      ->where(function ($q2) use ($date) {
                          $q2->whereNull('discharge_date')
                             ->orWhereDate('discharge_date', '>=', $date);
                      });
                })
                ->count();
            $pct = $totalBeds > 0 ? round(($occupied / $totalBeds) * 100, 1) : 0;
            return [
                'date'     => $date,
                'label'    => \Carbon\Carbon::parse($date)->format('d M'),
                'occupied' => $occupied,
                'pct'      => $pct,
            ];
        });

        $avgOccupancy  = round($dailyData->avg('pct'), 1);
        $peakDay       = $dailyData->sortByDesc('pct')->first();
        $currentOccupancy = $dailyData->last()['pct'];

        // Today's additional stats
        $todayAdmissions = \App\Models\Admission::whereDate('admission_date', today())->count();
        $todayDischarges = \App\Models\Admission::where('status', 'discharged')->whereDate('discharge_date', today())->count();

        $chartLabels = $dailyData->pluck('label')->toJson();
        $chartData   = $dailyData->pluck('pct')->toJson();
        $chartOccupied = $dailyData->pluck('occupied')->toJson();

        return view('reports.occupancy', compact(
            'dailyData', 'totalBeds', 'avgOccupancy', 'peakDay',
            'currentOccupancy', 'todayAdmissions', 'todayDischarges',
            'chartLabels', 'chartData', 'chartOccupied'
        ));
    }

    public function dayEndReport(Request $request)
    {
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();
        $ds   = $date->toDateString();

        // OPD Billing payments for the day
        $opdPayments = DB::table('payments')
            ->join('bills', 'bills.id', '=', 'payments.bill_id')
            ->whereDate('payments.payment_date', $ds)
            ->select('payments.*', 'bills.patient_id')
            ->get();

        $opdBills = Bill::with('patient')->whereDate('bill_date', $ds)->get();

        // IPD Deposits received today
        $ipdDeposits = IpdDeposit::with(['admission.patient', 'receivedBy'])
            ->whereDate('created_at', $ds)->get();

        // IPD Charges billed today
        $ipdCharges = IpdCharge::with(['admission.patient'])
            ->whereDate('charge_date', $ds)->get();

        // Lab orders created today (billable, incl. panels; cancelled excluded)
        $labDay     = $this->labDaily($ds, $ds)[$ds] ?? [];
        $labRevenue = $labDay['revenue'] ?? 0;
        $labProfit  = $labDay['profit'] ?? 0;

        $labCount = DB::table('lab_orders')->whereDate('ordered_date', $ds)->count();

        // Radiology orders today (no price column — charges go through billing)
        $radioRevenue = 0;
        $radioCount   = DB::table('radiology_orders')->whereDate('created_at', $ds)->count();

        // Payment breakdown by method
        $byMethod = DB::table('payments')
            ->whereDate('payment_date', $ds)
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as cnt')
            ->get()->keyBy('payment_method');

        // IPD deposits by method
        $depositByMethod = DB::table('ipd_deposits')
            ->whereDate('created_at', $ds)
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->get()->keyBy('payment_method');

        $summary = [
            'opd_billed'     => $opdBills->sum('total_amount'),
            'opd_collected'  => $opdPayments->sum('amount'),
            'opd_discount'   => $opdBills->sum('discount_amount'),
            'ipd_deposits'   => $ipdDeposits->sum('amount'),
            'ipd_charges'    => $ipdCharges->sum('amount'),
            'lab_revenue'    => (float) $labRevenue,
            'lab_profit'     => $labProfit,
            'radio_revenue'  => (float) $radioRevenue,
            'total_collected'=> $opdPayments->sum('amount') + $ipdDeposits->sum('amount'),
        ];

        return view('reports.day-end', compact(
            'date', 'opdBills', 'ipdDeposits', 'ipdCharges',
            'labRevenue', 'labProfit', 'labCount', 'radioRevenue', 'radioCount',
            'byMethod', 'depositByMethod', 'summary'
        ));
    }

    public function outstandingDues(Request $request)
    {
        $type    = $request->get('type', 'all');   // all | opd | ipd
        $minDays = (int) $request->get('min_days', 0);
        $q       = $request->get('q');

        // OPD outstanding bills
        $opdDues = collect();
        if ($type !== 'ipd') {
            $opdDues = Bill::with('patient')
                ->where('due_amount', '>', 0)
                ->whereIn('status', ['partial', 'unpaid'])
                ->when($q, fn($qr) => $qr->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%$q%")
                      ->orWhere('last_name',  'like', "%$q%")
                      ->orWhere('jhc_id',     'like', "%$q%")))
                ->when($minDays > 0, fn($qr) => $qr->whereDate('bill_date', '<=', now()->subDays($minDays)))
                ->orderByDesc('bill_date')
                ->get()
                ->map(fn($b) => [
                    'source'    => 'OPD',
                    'patient'   => $b->patient->full_name,
                    'jhc_id'    => $b->patient->jhc_id,
                    'ref_no'    => $b->bill_no,
                    'bill_date' => $b->bill_date,
                    'total'     => (float) $b->total_amount,
                    'paid'      => (float) $b->total_amount - (float) $b->due_amount,
                    'due'       => (float) $b->due_amount,
                    'days_old'  => (int) now()->diffInDays($b->bill_date),
                    'link'      => route('billing.show', $b->id),
                ]);
        }

        // IPD outstanding (charges - deposits)
        $ipdDues = collect();
        if ($type !== 'opd') {
            $ipdDues = Admission::with('patient')
                ->where('status', 'admitted')
                ->when($q, fn($qr) => $qr->where('ipd_no', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) =>
                        $p->where('first_name', 'like', "%$q%")
                          ->orWhere('last_name',  'like', "%$q%")
                          ->orWhere('jhc_id',     'like', "%$q%")))
                ->get()
                ->map(fn($a) => [
                    'source'    => 'IPD',
                    'patient'   => $a->patient->full_name,
                    'jhc_id'    => $a->patient->jhc_id,
                    'ref_no'    => $a->ipd_no,
                    'bill_date' => $a->admission_date,
                    'total'     => (float) $a->total_charges,
                    'paid'      => (float) $a->total_deposits,
                    'due'       => max(0, (float) $a->total_charges - (float) $a->total_deposits),
                    'days_old'  => (int) now()->diffInDays($a->admission_date),
                    'link'      => route('admissions.show', $a->id),
                ])
                ->filter(fn($r) => $r['due'] > 0)
                ->filter(fn($r) => $minDays <= 0 || $r['days_old'] >= $minDays)
                ->values();
        }

        // Also discharged with outstanding IPD balance
        $dischargeDues = collect();
        if ($type !== 'opd') {
            $dischargeDues = Admission::with('patient')
                ->where('status', 'discharged')
                ->when($q, fn($qr) => $qr->where('ipd_no', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) =>
                        $p->where('first_name', 'like', "%$q%")
                          ->orWhere('last_name',  'like', "%$q%")))
                ->get()
                ->map(fn($a) => [
                    'source'    => 'IPD (Discharged)',
                    'patient'   => $a->patient->full_name,
                    'jhc_id'    => $a->patient->jhc_id,
                    'ref_no'    => $a->ipd_no,
                    'bill_date' => $a->discharge_date ?? $a->admission_date,
                    'total'     => (float) $a->total_charges,
                    'paid'      => (float) $a->total_deposits,
                    'due'       => max(0, (float) $a->total_charges - (float) $a->total_deposits),
                    'days_old'  => (int) now()->diffInDays($a->discharge_date ?? $a->admission_date),
                    'link'      => route('admissions.show', $a->id),
                ])
                ->filter(fn($r) => $r['due'] > 0)
                ->filter(fn($r) => $minDays <= 0 || $r['days_old'] >= $minDays)
                ->values();
        }

        $allDues = $opdDues->concat($ipdDues)->concat($dischargeDues)
            ->sortByDesc('due')->values();

        $totals = [
            'count'    => $allDues->count(),
            'total'    => $allDues->sum('total'),
            'paid'     => $allDues->sum('paid'),
            'due'      => $allDues->sum('due'),
        ];

        return view('reports.outstanding-dues', compact('allDues', 'totals', 'type', 'minDays', 'q'));
    }

    public function followUpTracker(Request $request)
    {
        $filter = $request->get('filter', 'overdue'); // overdue | today | upcoming
        $doctorId = $request->get('doctor_id');
        $q = $request->get('q');

        $query = \App\Models\Encounter::with(['patient', 'doctor'])
            ->whereNotNull('follow_up_date')
            ->where('status', '!=', 'cancelled');

        if ($filter === 'overdue') {
            $query->whereDate('follow_up_date', '<', today());
        } elseif ($filter === 'today') {
            $query->whereDate('follow_up_date', today());
        } elseif ($filter === 'upcoming') {
            $query->whereDate('follow_up_date', '>', today())
                  ->whereDate('follow_up_date', '<=', today()->addDays(7));
        }

        if ($doctorId) $query->where('doctor_id', $doctorId);

        if ($q) {
            $query->whereHas('patient', fn($p) =>
                $p->where('first_name', 'like', "%$q%")
                  ->orWhere('last_name', 'like', "%$q%")
                  ->orWhere('jhc_id', 'like', "%$q%")
            );
        }

        $encounters = $query->orderBy('follow_up_date')->get();
        $doctors    = \App\Models\Doctor::where('is_active', true)->orderBy('first_name')->get();

        $counts = [
            'overdue'  => \App\Models\Encounter::whereNotNull('follow_up_date')
                            ->where('status','!=','cancelled')
                            ->whereDate('follow_up_date','<', today())->count(),
            'today'    => \App\Models\Encounter::whereNotNull('follow_up_date')
                            ->where('status','!=','cancelled')
                            ->whereDate('follow_up_date', today())->count(),
            'upcoming' => \App\Models\Encounter::whereNotNull('follow_up_date')
                            ->where('status','!=','cancelled')
                            ->whereDate('follow_up_date','>', today())
                            ->whereDate('follow_up_date','<=', today()->addDays(7))->count(),
        ];

        // Patients who already came back (a later encounter) or booked an
        // appointment on/after the follow-up date don't need a reminder; the
        // view flags them and leaves them out of "select all".
        $patientIds = $encounters->pluck('patient_id')->unique();
        $lastVisit = \App\Models\Encounter::whereIn('patient_id', $patientIds)
            ->where('status', '!=', 'cancelled')
            ->groupBy('patient_id')->selectRaw('patient_id, MAX(encounter_date) as d')->pluck('d', 'patient_id');
        $lastBooking = \App\Models\Appointment::whereIn('patient_id', $patientIds)
            ->where('status', '!=', 'Cancelled')
            ->groupBy('patient_id')->selectRaw('patient_id, MAX(appointment_date) as d')->pluck('d', 'patient_id');
        $returned = [];
        foreach ($encounters as $e) {
            if (($v = $lastVisit[$e->patient_id] ?? null) && $v > $e->encounter_date->toDateString()) {
                $returned[$e->id] = 'Returned';
            } elseif (($b = $lastBooking[$e->patient_id] ?? null) && substr($b, 0, 10) >= $e->follow_up_date->toDateString()) {
                $returned[$e->id] = 'Booked';
            }
        }

        return view('reports.follow-up', compact('encounters', 'doctors', 'filter', 'doctorId', 'q', 'counts', 'returned'));
    }

    // Follow-up reminders deliberately leave out the doctor's follow-up note and
    // diagnosis (often empty or clinical); they use a fixed review sentence.
    private const FOLLOWUP_REVIEW_LINE = 'Your doctor would like to review your progress.';

    /** First phone number in the clinic_phone setting ("Call: 9242001215, …" -> "9242001215"). */
    private function followUpPhone(): ?string
    {
        return preg_match('/\+?\d[\d -]{7,}\d/', (string) setting('clinic_phone'), $m) ? $m[0] : null;
    }

    /** Patient name for the greeting, with stray spaces collapsed ("Kenny  David" -> "Kenny David"). */
    private function followUpName(\App\Models\Encounter $e): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $e->patient->full_name)) ?: 'there';
    }

    /** Email reminder text. */
    /**
     * Lab revenue and profit per day (keyed 'Y-m-d') between $from and $to inclusive,
     * by lab order date, excluding cancelled orders.
     *  - revenue: lab_orders.total_amount (includes panel prices; panel test lines
     *    themselves are priced 0, so summing lines would miss panels).
     *  - profit: individual tests: the profit snapshotted on the line when ordered,
     *    else the test's current profit; panels: the panel profit snapshotted on the
     *    panel's first line, else the panel's current profit, counted once per
     *    panel per order.
     */
    private function labDaily(string $from, string $to): array
    {
        $orders = fn () => DB::table('lab_orders')
            ->whereBetween(DB::raw('DATE(lab_orders.ordered_date)'), [$from, $to])
            ->where('lab_orders.status', '!=', 'Cancelled');

        $out = [];
        foreach ($orders()->selectRaw('DATE(ordered_date) d, SUM(total_amount) t')->groupBy('d')->pluck('t', 'd') as $d => $v) {
            $out[$d]['revenue'] = (float) $v;
        }

        $testProfit = $orders()
            ->join('lab_order_items as i', 'i.lab_order_id', '=', 'lab_orders.id')
            ->leftJoin('lab_tests as t', 't.id', '=', 'i.lab_test_id')
            ->whereNull('i.lab_panel_id')
            ->selectRaw('DATE(lab_orders.ordered_date) d, SUM(COALESCE(i.profit, t.profit, 0)) t')
            ->groupBy('d')->pluck('t', 'd');

        $panelGroups = $orders()
            ->join('lab_order_items as i', 'i.lab_order_id', '=', 'lab_orders.id')
            ->whereNotNull('i.lab_panel_id')
            ->groupBy('lab_orders.id', 'i.lab_panel_id', 'd')
            ->selectRaw('DATE(lab_orders.ordered_date) d, i.lab_panel_id, MAX(i.profit) snap');
        $panelProfit = DB::query()->fromSub($panelGroups, 'g')
            ->leftJoin('lab_panels as p', 'p.id', '=', 'g.lab_panel_id')
            ->selectRaw('g.d, SUM(COALESCE(g.snap, p.profit, 0)) t')
            ->groupBy('g.d')->pluck('t', 'd');

        foreach ([$testProfit, $panelProfit] as $rows) {
            foreach ($rows as $d => $v) {
                $out[$d]['profit'] = ($out[$d]['profit'] ?? 0) + (float) $v;
            }
        }
        return $out;
    }

    /**
     * In-house pharmacy sales and profit per day (keyed 'Y-m-d'), from 'dispensed'
     * stock transactions. Sale value uses the same rule as PharmacySaleVoucherPoster
     * (the accounts' pharmacy sales): medicine.unit_price, else batch unit_cost plus
     * the accounts_pharmacy_sale_markup_pct markup. Cost is the batch's unit_cost.
     * Profit is only counted on lines with both a sale value and a known cost;
     * 'unpriced' counts the other lines so the dashboard can say how many were left out.
     */
    private function pharmacyDaily(string $from, string $to): array
    {
        $markup = 1 + (float) (setting('accounts_pharmacy_sale_markup_pct') ?? 0) / 100;
        $sale = "CASE WHEN m.unit_price > 0 THEN t.quantity * m.unit_price
                      WHEN s.unit_cost > 0 THEN t.quantity * s.unit_cost * {$markup} ELSE 0 END";
        $rows = DB::table('stock_transactions as t')
            ->leftJoin('medicines as m', 'm.id', '=', 't.medicine_id')
            ->leftJoin('pharmacy_stock as s', 's.id', '=', 't.pharmacy_stock_id')
            ->where('t.type', 'dispensed')->where('t.quantity', '>', 0)
            ->whereBetween(DB::raw('DATE(t.created_at)'), [$from, $to])
            ->groupBy('d')
            ->selectRaw("DATE(t.created_at) d,
                SUM({$sale}) sale,
                SUM(CASE WHEN s.unit_cost > 0 AND ({$sale}) > 0 THEN ({$sale}) - t.quantity * s.unit_cost ELSE 0 END) profit,
                SUM(CASE WHEN s.unit_cost > 0 AND ({$sale}) > 0 THEN 0 ELSE 1 END) unpriced,
                COUNT(*) n")
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $out[$row->d] = ['sale' => (float) $row->sale, 'profit' => (float) $row->profit, 'unpriced' => (int) $row->unpriced, 'n' => (int) $row->n];
        }
        return $out;
    }

    private function followUpBody(\App\Models\Encounter $e): string
    {
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $phone  = $this->followUpPhone();
        return "Hi " . $this->followUpName($e) . ",\n\n"
             . "Friendly reminder: you are advised a follow-up visit on {$e->follow_up_date->format('d M Y')}. "
             . self::FOLLOWUP_REVIEW_LINE . "\n\n"
             . "Please call us" . ($phone ? " at {$phone}" : '') . " to confirm your slot.\n\n"
             . "Regards,\n{$clinic} team";
    }

    /** Follow-up tracker: WhatsApp this encounter's follow-up reminder now. */
    public function followUpRemindWhatsApp(\App\Models\Encounter $encounter, \App\Services\WhatsAppService $wa)
    {
        abort_unless($wa->isEnabled(), 422, 'WhatsApp is not enabled (notifications module is off).');
        $e = $encounter->load('patient', 'doctor');
        abort_unless($e->follow_up_date, 422, 'This encounter has no follow-up date.');
        abort_unless($e->patient && ($e->patient->phone || $e->patient->alt_phone), 422, 'This patient has no phone on file.');

        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $name   = $this->followUpName($e);
        $phone  = $this->followUpPhone();
        $date   = $e->follow_up_date->format('d M Y');

        // Approved template followup (fixed text, only {{n}} can vary):
        //   "Hi {{1}}, friendly reminder: {{2}} advised a follow-up visit on {{3}}. {{4}}
        //    Please call us to confirm your slot. Regards, {{5}} team."
        // {{2}} = "you are" -> "you are advised…"; the number can't go into the fixed
        // "Please call us…" sentence, so it rides in {{4}} after the review line.
        $params = [$name, 'you are', $date, self::FOLLOWUP_REVIEW_LINE . ($phone ? " Our number is {$phone}." : ''), $clinic];
        // Same ref key as encounters:send-followup-whatsapp so the cron won't resend.
        $body = "Hi {$params[0]}, friendly reminder: {$params[1]} advised a follow-up visit on {$params[2]}. {$params[3]} "
              . "Please call us to confirm your slot. Regards, {$params[4]} team.\n[ref:followup_{$e->id}]";

        $r = $wa->sendTemplateToPatient(
            $e->patient,
            'followup',
            $params,
            $body,
            'followup',
            auth()->id(),
        );
        if ($r['ok'] ?? false) {
            $e->forceFill(['followup_whatsapp_sent_at' => now()])->save();
            return response()->json(['success' => true, 'message' => 'WhatsApp reminder sent.']);
        }
        return response()->json(['success' => false, 'message' => 'WhatsApp not sent: ' . ($r['skipped'] ?? $r['error'] ?? 'unknown')], 422);
    }

    /** Follow-up tracker: email this encounter's follow-up reminder now (to patient, CC clinic). */
    public function followUpRemindEmail(\App\Models\Encounter $encounter)
    {
        $e = $encounter->load('patient', 'doctor');
        abort_unless($e->follow_up_date, 422, 'This encounter has no follow-up date.');
        abort_unless($e->patient && $e->patient->email, 422, 'This patient has no email on file.');
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $cc = setting('clinic_email') ?: 'demo@specialitycare.co.in';
        try {
            \Illuminate\Support\Facades\Mail::raw($this->followUpBody($e), function ($m) use ($e, $cc, $clinic) {
                $m->to($e->patient->email)->subject("Follow-up reminder — {$clinic}");
                if ($cc) $m->cc($cc);
            });
            $e->forceFill(['followup_email_sent_at' => now()])->save();
            return response()->json(['success' => true, 'message' => 'Email reminder sent to ' . $e->patient->email]);
        } catch (\Throwable $ex) {
            return response()->json(['success' => false, 'message' => 'Email failed: ' . $ex->getMessage()], 500);
        }
    }

    public function revenueDashboard(Request $request)
    {
        // Range: ?from=Y-m-d&to=Y-m-d (default: last 12 months), grouped by
        // ?group=day|week|month (default: picked from the range length).
        try {
            $to   = $request->filled('to')   ? \Carbon\Carbon::parse($request->to)->startOfDay()   : today();
            $from = $request->filled('from') ? \Carbon\Carbon::parse($request->from)->startOfDay() : today()->startOfMonth()->subMonths(11);
        } catch (\Throwable $e) {
            $to = today(); $from = today()->startOfMonth()->subMonths(11);
        }
        if ($from->gt($to)) [$from, $to] = [$to, $from];
        $days  = $from->diffInDays($to) + 1;
        $group = in_array($request->group, ['day', 'week', 'month'], true)
            ? $request->group
            : ($days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month'));
        if ($group === 'day' && $days > 366) $group = 'week';   // keep the chart readable

        $f = $from->toDateString(); $t = $to->toDateString();
        $daily = [];
        $add = function ($rows, string $key) use (&$daily) {
            foreach ($rows as $d => $v) $daily[$d][$key] = ($daily[$d][$key] ?? 0) + (float) $v;
        };
        $add(DB::table('bills')->whereNotNull('paid_amount')
                ->whereBetween(DB::raw('DATE(created_at)'), [$f, $t])
                ->selectRaw('DATE(created_at) d, SUM(paid_amount) v')->groupBy('d')->pluck('v', 'd'), 'opd');
        $add(DB::table('ipd_deposits')
                ->whereBetween(DB::raw('DATE(created_at)'), [$f, $t])
                ->selectRaw('DATE(created_at) d, SUM(amount) v')->groupBy('d')->pluck('v', 'd'), 'ipd');
        foreach ($this->labDaily($f, $t) as $d => $v) {
            $daily[$d]['lab']    = $v['revenue'] ?? 0;
            $daily[$d]['profit'] = $v['profit'] ?? 0;
        }
        $pharmLines = $pharmUnpriced = 0;
        foreach ($this->pharmacyDaily($f, $t) as $d => $v) {
            $daily[$d]['pharm']        = $v['sale'];
            $daily[$d]['pharm_profit'] = $v['profit'];
            $pharmLines += $v['n']; $pharmUnpriced += $v['unpriced'];
        }

        // Buckets covering the whole range (weeks start Monday).
        $periods = [];
        $cursor = match ($group) {
            'day'   => $from->copy(),
            'week'  => $from->copy()->startOfWeek(\Carbon\Carbon::MONDAY),
            'month' => $from->copy()->startOfMonth(),
        };
        while ($cursor->lte($to)) {
            $end = match ($group) {
                'day'   => $cursor->copy(),
                'week'  => $cursor->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->startOfDay(),
                'month' => $cursor->copy()->endOfMonth()->startOfDay(),
            };
            $label = match ($group) {
                'day'   => $cursor->format('d M'),
                'week'  => $cursor->format('d M') . ' – ' . $end->format('d M'),
                'month' => $cursor->format('M Y'),
            };
            $periods[$cursor->toDateString()] = ['label' => $label, 'start' => $cursor->copy()->max($from), 'end' => $end->copy()->min($to),
                                                 'opd' => 0, 'ipd' => 0, 'lab' => 0, 'profit' => 0, 'pharm' => 0, 'pharm_profit' => 0];
            $cursor = $end->copy()->addDay();
        }
        $keys = array_keys($periods);
        foreach ($daily as $d => $v) {
            $date = \Carbon\Carbon::parse($d);
            $k = match ($group) {
                'day'   => $d,
                'week'  => $date->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString(),
                'month' => $date->copy()->startOfMonth()->toDateString(),
            };
            if (!isset($periods[$k])) continue;
            foreach (['opd', 'ipd', 'lab', 'profit', 'pharm', 'pharm_profit'] as $m) $periods[$k][$m] += $v[$m] ?? 0;
        }

        $totals = ['opd' => 0, 'ipd' => 0, 'lab' => 0, 'profit' => 0, 'pharm' => 0, 'pharm_profit' => 0];
        foreach ($periods as $p) foreach ($totals as $m => $_) $totals[$m] += $p[$m];
        $totals['all'] = $totals['opd'] + $totals['ipd'] + $totals['lab'] + $totals['pharm'];
        $totals['profit_all'] = $totals['profit'] + $totals['pharm_profit'];

        $topDoctors = DB::table('bills')
            ->join('encounters','encounters.id','=','bills.encounter_id')
            ->join('doctors','doctors.id','=','encounters.doctor_id')
            ->selectRaw("CONCAT(doctors.first_name,' ',doctors.last_name) as name, SUM(bills.paid_amount) as total, COUNT(*) as cnt")
            ->whereBetween(DB::raw('DATE(bills.created_at)'), [$f, $t])
            ->groupBy('doctors.id','name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Quick ranges (Indian financial year runs Apr–Mar).
        $fyStart = today()->month >= 4 ? today()->setDate(today()->year, 4, 1) : today()->setDate(today()->year - 1, 4, 1);
        $presets = [
            'Today'         => [today(), today()],
            'This Week'     => [today()->startOfWeek(\Carbon\Carbon::MONDAY), today()],
            'This Month'    => [today()->startOfMonth(), today()],
            'Last Month'    => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'Last 3 Months' => [today()->startOfMonth()->subMonths(2), today()],
            'This FY'       => [$fyStart, today()],
            'Last 12 Months'=> [today()->startOfMonth()->subMonths(11), today()],
        ];

        return view('reports.revenue', compact('from', 'to', 'group', 'periods', 'totals', 'topDoctors', 'presets', 'pharmLines', 'pharmUnpriced'));
    }

    // ── CSV Exports ──────────────────────────────────────────────────────────

    public function exportOutstandingDues(Request $request)
    {
        // Re-use the same logic as outstandingDues but stream CSV
        $response = $this->outstandingDues($request);
        $data = $response->getData()['allDues'] ?? null;
        // Just re-query for simplicity
        $type = $request->get('type', 'all');
        $q    = $request->get('q', '');
        $minDays = (int)$request->get('min_days', 0);

        // Quick re-fetch (delegates to outstandingDues logic via shared private method would be cleaner, but inline here)
        $req = clone $request;
        ob_start();
        $view = $this->outstandingDues($req);
        ob_end_clean();

        // Actually stream the CSV from view data — simplest: re-run query inline
        $rows = $this->_buildOutstandingDues($request);

        $filename = 'outstanding-dues-'.now()->format('Y-m-d').'.csv';
        return response()->streamDownload(function() use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Patient','JHC ID','Ref No','Source','Date','Total','Paid','Due','Age (days)']);
            foreach ($rows as $r) {
                fputcsv($f, [$r['patient'],$r['jhc_id']??'',$r['ref_no'],$r['source'],
                    $r['bill_date'],$r['total'],$r['paid'],$r['due'],$r['days_old']]);
            }
            fclose($f);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportFollowUp(Request $request)
    {
        $filter   = $request->get('filter','overdue');
        $doctorId = $request->get('doctor_id');
        $q        = $request->get('q');

        $query = \App\Models\Encounter::with(['patient','doctor','diagnoses'])
            ->whereNotNull('follow_up_date')->where('status','!=','cancelled');
        if ($filter === 'overdue')   $query->whereDate('follow_up_date','<', today());
        elseif ($filter === 'today') $query->whereDate('follow_up_date', today());
        else                         $query->whereDate('follow_up_date','>', today())->whereDate('follow_up_date','<=', today()->addDays(7));
        if ($doctorId) $query->where('doctor_id', $doctorId);
        if ($q) $query->whereHas('patient', fn($p) => $p->where('first_name','like',"%$q%")->orWhere('last_name','like',"%$q%")->orWhere('jhc_id','like',"%$q%"));

        $rows = $query->orderBy('follow_up_date')->get();
        $filename = 'follow-up-'.$filter.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function() use ($rows) {
            $f = fopen('php://output','w');
            fputcsv($f,['Patient','JHC ID','Doctor','Encounter Date','Follow-Up Date','Days Overdue/Remaining','Diagnosis']);
            foreach ($rows as $enc) {
                $days = (int) now()->diffInDays($enc->follow_up_date, false);
                fputcsv($f,[
                    $enc->patient->full_name, $enc->patient->jhc_id, $enc->doctor->full_name,
                    $enc->encounter_date->format('d M Y'), $enc->follow_up_date->format('d M Y'),
                    $days < 0 ? abs($days).' overdue' : ($days === 0 ? 'Today' : $days.' days'),
                    $enc->diagnoses->first()?->icd_description ?? $enc->impression ?? '',
                ]);
            }
            fclose($f);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportDayEnd(Request $request)
    {
        $date = \Carbon\Carbon::parse($request->get('date', today()->toDateString()));
        $bills    = Bill::with('patient')->whereDate('created_at', $date)->get();
        $deposits = IpdDeposit::with(['admission.patient'])->whereDate('created_at', $date)->get();
        $filename = 'day-end-'.$date->format('Y-m-d').'.csv';

        return response()->streamDownload(function() use ($date, $bills, $deposits) {
            $f = fopen('php://output','w');
            fputcsv($f, ['=== OPD Bills — '.$date->format('d M Y').' ===']);
            fputcsv($f, ['Bill No','Patient','Total','Discount','Due','Status']);
            foreach ($bills as $b) {
                fputcsv($f, [$b->bill_no, $b->patient->full_name, $b->total_amount, $b->discount_amount, $b->due_amount, $b->status]);
            }
            fputcsv($f, []);
            fputcsv($f, ['=== IPD Deposits — '.$date->format('d M Y').' ===']);
            fputcsv($f, ['IPD No','Patient','Amount','Method','Time']);
            foreach ($deposits as $d) {
                fputcsv($f, [$d->admission->ipd_no, $d->admission->patient->full_name, $d->amount, $d->payment_method, $d->created_at->format('H:i')]);
            }
            fclose($f);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // Shared helper used by outstandingDues view + CSV export
    private function _buildOutstandingDues(Request $request): \Illuminate\Support\Collection
    {
        $type    = $request->get('type','all');
        $q       = $request->get('q','');
        $minDays = (int)$request->get('min_days', 0);

        $opdDues = collect();
        if (in_array($type, ['all','opd'])) {
            $opdQ = Bill::with('patient')->where('due_amount','>',0);
            if ($q) $opdQ->whereHas('patient', fn($p) => $p->where('first_name','like',"%$q%")->orWhere('last_name','like',"%$q%")->orWhere('jhc_id','like',"%$q%"));
            $opdDues = $opdQ->get()->map(fn($b) => [
                'patient'   => $b->patient->full_name,
                'jhc_id'    => $b->patient->jhc_id,
                'ref_no'    => $b->bill_no,
                'source'    => 'OPD',
                'bill_date' => $b->created_at->toDateString(),
                'total'     => (float)$b->total_amount,
                'paid'      => (float)$b->paid_amount,
                'due'       => (float)$b->due_amount,
                'days_old'  => (int)now()->diffInDays($b->created_at),
                'link'      => route('billing.show', $b->id),
            ])->filter(fn($r) => $minDays <= 0 || $r['days_old'] >= $minDays);
        }

        return $opdDues->values();
    }

    public function generateRoomCharges()
    {
        \Artisan::call('ipd:room-charges');
        $output = \Artisan::output();
        return redirect()->route('dashboard')->with('success', 'Room charges: ' . trim($output));
    }

    public function diagnosisAnalytics(Request $request)
    {
        $fromDate = $request->get('from', now()->subDays(90)->toDateString());
        $toDate   = $request->get('to',   today()->toDateString());

        $base = DB::table('diagnoses')
            ->join('encounters', 'encounters.id', '=', 'diagnoses.encounter_id')
            ->whereNull('encounters.deleted_at')
            ->whereBetween('encounters.encounter_date', [$fromDate, $toDate])
            ->whereNotNull('diagnoses.diagnosis_name')
            ->where('diagnoses.diagnosis_name', '!=', '');

        $top = (clone $base)
            ->select(
                'diagnoses.diagnosis_name',
                'diagnoses.icd_code',
                DB::raw('COUNT(diagnoses.id) as count'),
                DB::raw('COUNT(DISTINCT encounters.patient_id) as patient_count')
            )
            ->groupBy('diagnoses.diagnosis_name','diagnoses.icd_code')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        $byType = (clone $base)
            ->select('diagnoses.type', DB::raw('COUNT(*) as count'))
            ->groupBy('diagnoses.type')
            ->orderByDesc('count')
            ->get();

        $monthly = (clone $base)
            ->select(
                DB::raw("DATE_FORMAT(encounters.encounter_date, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $totals = [
            'total_codings'    => (clone $base)->count(),
            'unique_diagnoses' => (clone $base)->distinct()->count(DB::raw('LOWER(diagnoses.diagnosis_name)')),
            'unique_patients'  => (clone $base)->distinct()->count('encounters.patient_id'),
        ];

        return view('reports.diagnoses', compact('top', 'byType', 'monthly', 'totals', 'fromDate', 'toDate'));
    }

    public function packageUsage(Request $request)
    {
        $fromDate = $request->get('from', now()->subDays(90)->toDateString());
        $toDate   = $request->get('to',   today()->toDateString());

        // Bills tagged with a package within window
        $billRows = DB::table('bills')
            ->join('billing_packages', 'billing_packages.id', '=', 'bills.billing_package_id')
            ->whereBetween('bills.bill_date', [$fromDate, $toDate])
            ->select(
                'billing_packages.id',
                'billing_packages.name',
                'billing_packages.category',
                'billing_packages.package_price',
                DB::raw('COUNT(bills.id)        as bill_count'),
                DB::raw('SUM(bills.total_amount) as bill_total'),
                DB::raw('SUM(bills.paid_amount)  as bill_paid')
            )
            ->groupBy('billing_packages.id','billing_packages.name','billing_packages.category','billing_packages.package_price')
            ->get()->keyBy('id');

        // Admission packages within window (count + quoted price + paid)
        $admRows = DB::table('admission_packages')
            ->join('billing_packages', 'billing_packages.id', '=', 'admission_packages.package_id')
            ->whereBetween('admission_packages.created_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59'])
            ->select(
                'billing_packages.id',
                'billing_packages.name',
                'billing_packages.category',
                'billing_packages.package_price',
                DB::raw('COUNT(admission_packages.id) as adm_count'),
                DB::raw('SUM(admission_packages.quoted_price) as adm_quoted'),
                DB::raw('SUM(admission_packages.paid_amount)  as adm_paid')
            )
            ->groupBy('billing_packages.id','billing_packages.name','billing_packages.category','billing_packages.package_price')
            ->get()->keyBy('id');

        // Merge by package id
        $allIds = $billRows->keys()->concat($admRows->keys())->unique();
        $rows = $allIds->map(function ($id) use ($billRows, $admRows) {
            $b = $billRows[$id] ?? null;
            $a = $admRows[$id] ?? null;
            $base = $b ?: $a;
            return (object) [
                'id'            => $base->id,
                'name'          => $base->name,
                'category'      => $base->category,
                'package_price' => $base->package_price,
                'bill_count'    => (int)   ($b->bill_count ?? 0),
                'bill_total'    => (float) ($b->bill_total ?? 0),
                'bill_paid'     => (float) ($b->bill_paid  ?? 0),
                'adm_count'     => (int)   ($a->adm_count  ?? 0),
                'adm_quoted'    => (float) ($a->adm_quoted ?? 0),
                'adm_paid'      => (float) ($a->adm_paid   ?? 0),
                'total_uses'    => ((int)($b->bill_count ?? 0)) + ((int)($a->adm_count ?? 0)),
                'total_revenue' => ((float)($b->bill_paid ?? 0)) + ((float)($a->adm_paid ?? 0)),
            ];
        })->sortByDesc('total_uses')->values();

        $summary = [
            'packages_in_use' => $rows->count(),
            'total_uses'      => $rows->sum('total_uses'),
            'total_revenue'   => $rows->sum('total_revenue'),
            'opd_uses'        => $rows->sum('bill_count'),
            'ipd_uses'        => $rows->sum('adm_count'),
        ];

        return view('reports.packages', compact('rows', 'summary', 'fromDate', 'toDate'));
    }
}
