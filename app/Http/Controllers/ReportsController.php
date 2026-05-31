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

        return view('reports.index', compact(
            'tab','fromDate','toDate','wardId','doctorId',
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
            if ($src->rate_type === 'percent') {
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

        // Lab orders created today (billable)
        $labRevenue = DB::table('lab_order_items')
            ->join('lab_orders', 'lab_orders.id', '=', 'lab_order_items.lab_order_id')
            ->whereDate('lab_orders.ordered_date', $ds)
            ->sum('lab_order_items.price');

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
            'radio_revenue'  => (float) $radioRevenue,
            'total_collected'=> $opdPayments->sum('amount') + $ipdDeposits->sum('amount'),
        ];

        return view('reports.day-end', compact(
            'date', 'opdBills', 'ipdDeposits', 'ipdCharges',
            'labRevenue', 'labCount', 'radioRevenue', 'radioCount',
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

        return view('reports.follow-up', compact('encounters', 'doctors', 'filter', 'doctorId', 'q', 'counts'));
    }

    public function revenueDashboard(Request $request)
    {
        $months = collect(range(11, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i));

        $opdRev = DB::table('bills')
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, SUM(paid_amount) as total")
            ->whereNotNull('paid_amount')
            ->groupBy('ym')->pluck('total','ym');

        $ipdRev = DB::table('ipd_deposits')
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total','ym');

        $labRev = DB::table('lab_order_items')
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, SUM(price) as total")
            ->groupBy('ym')->pluck('total','ym');

        $chartLabels  = $months->map(fn($m) => $m->format('M Y'));
        $chartOpd     = $months->map(fn($m) => (float)($opdRev[$m->format('Y-m')] ?? 0));
        $chartIpd     = $months->map(fn($m) => (float)($ipdRev[$m->format('Y-m')] ?? 0));
        $chartLab     = $months->map(fn($m) => (float)($labRev[$m->format('Y-m')] ?? 0));

        $currentMonth = now()->format('Y-m');
        $donut = [
            'OPD'   => (float)($opdRev[$currentMonth] ?? 0),
            'IPD'   => (float)($ipdRev[$currentMonth] ?? 0),
            'Lab'   => (float)($labRev[$currentMonth] ?? 0),
        ];

        $topDoctors = DB::table('bills')
            ->join('encounters','encounters.id','=','bills.encounter_id')
            ->join('doctors','doctors.id','=','encounters.doctor_id')
            ->selectRaw("CONCAT(doctors.first_name,' ',doctors.last_name) as name, SUM(bills.paid_amount) as total, COUNT(*) as cnt")
            ->whereRaw("DATE_FORMAT(bills.created_at,'%Y-%m') = ?", [$currentMonth])
            ->groupBy('doctors.id','name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('reports.revenue', compact('chartLabels','chartOpd','chartIpd','chartLab','donut','topDoctors'));
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
