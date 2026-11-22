<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function revenueDrilldown(\Illuminate\Http\Request $request)
    {
        $month = (int)($request->month ?? now()->month);
        $year  = (int)($request->year  ?? now()->year);
        $month = max(1, min(12, $month));

        $opd = DB::table('payments')
            ->selectRaw('DAY(created_at) as day, SUM(amount) as total')
            ->whereYear('created_at', $year)->whereMonth('created_at', $month)
            ->groupBy('day')->get()->keyBy('day');

        $ipd = DB::table('ipd_charges')
            ->selectRaw('DAY(charge_date) as day, SUM(amount) as total')
            ->whereYear('charge_date', $year)->whereMonth('charge_date', $month)
            ->groupBy('day')->get()->keyBy('day');

        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $days = range(1, $daysInMonth);

        return response()->json([
            'label'     => \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y'),
            'days'      => $days,
            'opd'       => array_map(fn($d) => (float)($opd[$d]->total ?? 0), $days),
            'ipd'       => array_map(fn($d) => (float)($ipd[$d]->total ?? 0), $days),
            'opd_total' => (float)$opd->sum('total'),
            'ipd_total' => (float)$ipd->sum('total'),
        ]);
    }

    public function stats()
    {
        try {
            $data = [
                'today_opd_tokens'     => DB::table('opd_tokens')->whereDate('token_date', today())->count(),
                'opd_waiting'          => DB::table('opd_tokens')->whereDate('token_date', today())->where('status','waiting')->count(),
                'today_appointments'   => DB::table('appointments')->whereDate('appointment_date', today())->count(),
                'pending_appointments' => DB::table('appointments')->whereDate('appointment_date', today())->whereIn('status',['Scheduled','Confirmed'])->count(),
                'ipd_current'          => DB::table('admissions')->where('status','admitted')->count(),
                'ipd_today_admitted'   => DB::table('admissions')->whereDate('admission_date', today())->count(),
                'ipd_today_discharged' => DB::table('admissions')->where('status','discharged')->whereDate('discharge_date', today())->count(),
                'beds_available'       => DB::table('beds')->where('is_active',true)->where('status','available')->count(),
                'beds_total'           => DB::table('beds')->where('is_active',true)->count(),
                'opd_collection_today' => (float) DB::table('payments')->whereDate('created_at', today())->sum('amount'),
                'ipd_charges_today'    => (float) DB::table('ipd_charges')->whereDate('charge_date', today())->sum('amount'),
                'pending_bills'        => DB::table('bills')->whereIn('status', ['Unpaid','Partial'])->count(),
                'pending_dues_amount'  => (float) DB::table('bills')->whereIn('status', ['Unpaid','Partial'])->where('bill_no','not like','OE/%')->sum('due_amount'),
                'today_patients'       => DB::table('patients')->whereDate('created_at', today())->count(),
                'today_encounters'     => DB::table('encounters')->whereNull('deleted_at')->whereDate('encounter_date', today())->count(),
                'pending_labs'         => DB::table('lab_orders')->whereIn('status',['Ordered','Collected','Processing'])->count(),
                'low_stock'            => \App\Models\Medicine::where('is_active', true)->withSum(['stocks as live_qty' => function ($q) { $q->where('is_active', true)->where('quantity', '>', 0)->where(function ($e) { $e->whereNull('expiry_date')->orWhere('expiry_date', '>=', today()); }); }], 'quantity')->get()->filter(fn ($m) => (int) $m->live_qty > 0 && (int) $m->live_qty <= (int) $m->reorder_level)->count(),
            ];
            $data['total_today_revenue'] = $data['opd_collection_today'] + $data['ipd_charges_today'];
            $data['beds_occ_pct'] = $data['beds_total'] > 0
                ? round((($data['beds_total'] - $data['beds_available']) / $data['beds_total']) * 100)
                : 0;
        } catch (\Exception $e) {
            $data = [];
        }
        return response()->json($data);
    }

    public function index()
    {
        try {
            $stats = [
                // OPD
                'today_opd_tokens'     => DB::table('opd_tokens')->whereDate('token_date', today())->count(),
                'opd_waiting'          => DB::table('opd_tokens')->whereDate('token_date', today())->where('status','waiting')->count(),
                'today_appointments'   => DB::table('appointments')->whereDate('appointment_date', today())->count(),
                'pending_appointments' => DB::table('appointments')->whereDate('appointment_date', today())->whereIn('status', ['Scheduled','Confirmed'])->count(),

                // IPD
                'ipd_current'          => DB::table('admissions')->where('status','admitted')->count(),
                'ipd_today_admitted'   => DB::table('admissions')->whereDate('admission_date', today())->count(),
                'ipd_today_discharged' => DB::table('admissions')->where('status','discharged')->whereDate('discharge_date', today())->count(),
                'beds_available'       => DB::table('beds')->where('is_active',true)->where('status','available')->count(),
                'beds_total'           => DB::table('beds')->where('is_active',true)->count(),

                // Finance
                'opd_collection_today' => DB::table('payments')->whereDate('created_at', today())->sum('amount'),
                'ipd_charges_today'    => DB::table('ipd_charges')->whereDate('charge_date', today())->sum('amount'),
                'pending_bills'        => DB::table('bills')->whereIn('status', ['Unpaid','Partial'])->count(),
                'pending_dues_amount'  => (float) DB::table('bills')->whereIn('status', ['Unpaid','Partial'])->where('bill_no','not like','OE/%')->sum('due_amount'),

                // Other
                'total_patients'       => DB::table('patients')->count(),
                'today_patients'       => DB::table('patients')->whereDate('created_at', today())->count(),
                'today_encounters'     => DB::table('encounters')->whereNull('deleted_at')->whereDate('encounter_date', today())->count(),
                'active_doctors'       => DB::table('doctors')->where('is_active', true)->count(),
                'pending_labs'         => DB::table('lab_orders')->whereIn('status', ['Ordered','Collected','Processing'])->count(),
                'low_stock'            => DB::table('pharmacy_stock')->where('is_active', true)->whereColumn('quantity','<=','min_stock_level')->where('quantity','>',0)->count(),
            ];

            $stats['total_today_revenue'] = $stats['opd_collection_today'] + $stats['ipd_charges_today'];

            $todayAppointments = Appointment::with('patient','doctor')
                ->whereDate('appointment_date', today())
                ->withoutDeletedVisits()
                ->orderBy('appointment_time')
                ->limit(10)
                ->get();

            $recentPatients = Patient::orderByDesc('created_at')->limit(5)->get();

            // Combined monthly revenue: OPD payments + IPD charges
            $opdRevenue = DB::table('payments')
                ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')->get()->keyBy('month');

            $ipdRevenue = DB::table('ipd_charges')
                ->selectRaw('MONTH(charge_date) as month, SUM(amount) as total')
                ->whereYear('charge_date', date('Y'))
                ->groupBy('month')->get()->keyBy('month');

            // IPD census for sidebar
            $ipdCensus = DB::table('admissions')
                ->join('wards','wards.id','=','admissions.ward_id')
                ->where('admissions.status','admitted')
                ->select('wards.name as ward_name', DB::raw('COUNT(*) as count'))
                ->groupBy('wards.name')
                ->orderBy('wards.name')
                ->get();

            $pendingTriage = DB::table('opd_tokens')
                ->whereDate('token_date', today())
                ->where('is_triaged', false)
                ->whereNotIn('status', ['done','skipped'])
                ->count();

            $followUpsToday = Encounter::with(['patient', 'doctor'])
                ->whereDate('follow_up_date', today())
                ->whereIn('status', ['Open', 'Closed'])
                ->orderBy('follow_up_date')
                ->limit(10)
                ->get();

            // Pharmacy: stock batches expiring within 90 days (still in stock, active)
            $expiringStock = DB::table('pharmacy_stock')
                ->join('medicines', 'medicines.id', '=', 'pharmacy_stock.medicine_id')
                ->where('pharmacy_stock.is_active', true)
                ->where('pharmacy_stock.quantity', '>', 0)
                ->whereNotNull('pharmacy_stock.expiry_date')
                ->whereBetween('pharmacy_stock.expiry_date', [today(), today()->addDays(90)])
                ->orderBy('pharmacy_stock.expiry_date')
                ->select(
                    'medicines.name as medicine_name',
                    'pharmacy_stock.batch_number',
                    'pharmacy_stock.quantity',
                    'pharmacy_stock.expiry_date'
                )
                ->limit(8)
                ->get();
            $stats['expiring_stock_count'] = DB::table('pharmacy_stock')
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [today(), today()->addDays(90)])
                ->count();

            // Lab orders awaiting review: items with results but the order isn't marked Completed yet,
            // OR orders Completed today but not visited yet (best-effort heuristic)
            $labsAwaitingReview = DB::table('lab_orders')
                ->join('patients', 'patients.id', '=', 'lab_orders.patient_id')
                ->where('lab_orders.status', 'Completed')
                ->whereDate('lab_orders.updated_at', today())
                ->orderByDesc('lab_orders.updated_at')
                ->select(
                    'lab_orders.id',
                    'lab_orders.order_no',
                    'lab_orders.updated_at',
                    'patients.first_name',
                    'patients.last_name',
                    'patients.jhc_id'
                )
                ->limit(8)
                ->get();
            $stats['labs_awaiting_review'] = $labsAwaitingReview->count();

            // Prescriptions expiring within 7 days (active, not yet dispensed)
            $expiringRx = DB::table('prescription_items')
                ->join('prescriptions', 'prescriptions.id', '=', 'prescription_items.prescription_id')
                ->leftJoin('encounters', 'encounters.id', '=', 'prescriptions.encounter_id')
                ->whereNull('encounters.deleted_at')   // skip deleted visits
                ->join('patients',      'patients.id',      '=', 'prescriptions.patient_id')
                ->join('medicines',     'medicines.id',     '=', 'prescription_items.medicine_id')
                ->whereRaw('DATE_ADD(DATE(prescriptions.prescribed_date), INTERVAL prescription_items.duration_days DAY) BETWEEN ? AND ?',
                    [today()->toDateString(), today()->addDays(7)->toDateString()])
                ->where('prescription_items.is_dispensed', false)
                ->orderByRaw('DATE_ADD(DATE(prescriptions.prescribed_date), INTERVAL prescription_items.duration_days DAY)')
                ->select(
                    'patients.id as patient_id', 'patients.first_name', 'patients.last_name', 'patients.jhc_id',
                    'medicines.name as medicine_name',
                    'prescription_items.duration_days',
                    'prescriptions.prescribed_date',
                    'prescriptions.id as rx_id',
                    DB::raw('DATE_ADD(DATE(prescriptions.prescribed_date), INTERVAL prescription_items.duration_days DAY) as end_date')
                )
                ->limit(10)->get();
            $stats['expiring_rx_count'] = $expiringRx->count();

            // Critical low-stock medicines (≤ reorder_level)
            $lowStockList = DB::table('pharmacy_stock')
                ->join('medicines', 'medicines.id', '=', 'pharmacy_stock.medicine_id')
                ->where('pharmacy_stock.is_active', true)
                ->select(
                    'medicines.name as medicine_name',
                    'medicines.reorder_level',
                    DB::raw('SUM(pharmacy_stock.quantity) as total_qty')
                )
                ->groupBy('medicines.id', 'medicines.name', 'medicines.reorder_level')
                ->havingRaw('SUM(pharmacy_stock.quantity) > 0')
                ->havingRaw('SUM(pharmacy_stock.quantity) <= medicines.reorder_level')
                ->orderBy('total_qty')
                ->limit(8)
                ->get();

        } catch (\Exception $e) {
            $stats = array_fill_keys([
                'today_opd_tokens','opd_waiting','today_appointments','pending_appointments',
                'ipd_current','ipd_today_admitted','ipd_today_discharged','beds_available','beds_total',
                'opd_collection_today','ipd_charges_today','total_today_revenue','pending_bills','pending_dues_amount',
                'total_patients','today_patients','today_encounters','active_doctors','pending_labs','low_stock',
                'expiring_rx_count',
            ], 0);
            $todayAppointments = $recentPatients = $ipdCensus = $followUpsToday = collect();
            $opdRevenue = $ipdRevenue = collect();
            $pendingTriage = 0;
            $expiringStock = $labsAwaitingReview = $lowStockList = $expiringRx = collect();
            $stats['expiring_stock_count'] = $stats['labs_awaiting_review'] = 0;
        }

        return view('dashboard', compact(
            'stats','todayAppointments','recentPatients',
            'opdRevenue','ipdRevenue','ipdCensus','pendingTriage','followUpsToday',
            'expiringStock','labsAwaitingReview','lowStockList','expiringRx'
        ));
    }
}
