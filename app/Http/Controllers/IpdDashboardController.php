<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\IpdCharge;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;

class IpdDashboardController extends Controller
{
    public function index()
    {
        $totalBeds     = Bed::where('is_active', true)->count();
        $occupiedBeds  = Bed::where('status', 'occupied')->count();
        $availableBeds = Bed::where('is_active', true)->where('status', 'available')->count();
        $occupancyPct  = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;

        $currentAdmissions = Admission::where('status', 'admitted')
            ->with(['patient', 'ward', 'bed', 'admittingDoctor'])
            ->orderBy('admission_date')
            ->get();

        $todayAdmissions  = Admission::whereDate('admission_date', today())->count();
        $todayDischarges  = Admission::where('status', 'discharged')->whereDate('discharge_date', today())->count();

        $revenueMonth = IpdCharge::whereMonth('charge_date', now()->month)
            ->whereYear('charge_date', now()->year)
            ->sum('amount');

        $revenueToday = IpdCharge::whereDate('charge_date', today())->sum('amount');

        $wards = Ward::where('is_active', true)->with(['beds'])->get();

        $avgStay = Admission::where('status', 'discharged')
            ->whereMonth('discharge_date', now()->month)
            ->selectRaw('AVG(DATEDIFF(discharge_date, admission_date) + 1) as avg_days')
            ->value('avg_days');

        $longStay = $currentAdmissions->filter(fn($a) => $a->days_admitted > 7);

        $recentDischarges = Admission::where('status', 'discharged')
            ->with(['patient', 'admittingDoctor'])
            ->orderByDesc('discharge_date')
            ->limit(8)
            ->get();

        $chargeByCategory = IpdCharge::whereMonth('charge_date', now()->month)
            ->whereYear('charge_date', now()->year)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('ipd.dashboard', compact(
            'totalBeds','occupiedBeds','availableBeds','occupancyPct',
            'currentAdmissions','todayAdmissions','todayDischarges',
            'revenueMonth','revenueToday','wards','avgStay',
            'longStay','recentDischarges','chargeByCategory'
        ));
    }

    public function census()
    {
        $admissions = Admission::where('status', 'admitted')
            ->with(['patient', 'ward', 'bed', 'admittingDoctor',
                'vitals' => fn($q) => $q->latest('recorded_at')->limit(1)])
            ->orderBy('ward_id')
            ->orderBy('bed_id')
            ->get();

        $wardGroups   = $admissions->groupBy('ward_id');
        $totalBeds    = \App\Models\Bed::where('is_active', true)->count();
        $occupiedBeds = $admissions->count();
        $availableBeds = \App\Models\Bed::where('is_active', true)->where('status', 'available')->count();

        $todayAdmissions = Admission::whereDate('admission_date', today())
            ->with(['patient', 'ward', 'admittingDoctor'])
            ->orderByDesc('admission_date')
            ->get();

        $todayDischarges = Admission::where('status', 'discharged')
            ->whereDate('discharge_date', today())
            ->with(['patient', 'ward', 'admittingDoctor'])
            ->orderByDesc('discharge_date')
            ->get();

        // Per-ward breakdown including all wards (not just occupied ones)
        $allWards = \App\Models\Ward::where('is_active', true)
            ->with(['beds'])
            ->orderBy('name')
            ->get();

        return view('ipd.census', compact(
            'admissions', 'wardGroups', 'totalBeds', 'occupiedBeds', 'availableBeds',
            'todayAdmissions', 'todayDischarges', 'allWards'
        ));
    }

    public function censusPanel()
    {
        $admissions = Admission::where('status', 'admitted')
            ->with(['patient', 'ward', 'bed', 'admittingDoctor',
                'vitals' => fn($q) => $q->latest('recorded_at')->limit(1)])
            ->orderBy('ward_id')->orderBy('bed_id')->get();

        $wardGroups    = $admissions->groupBy('ward_id');
        $totalBeds     = \App\Models\Bed::where('is_active', true)->count();
        $occupiedBeds  = $admissions->count();
        $availableBeds = \App\Models\Bed::where('is_active', true)->where('status', 'available')->count();

        $todayAdmissions = Admission::whereDate('admission_date', today())
            ->with(['patient', 'ward', 'admittingDoctor'])->orderByDesc('admission_date')->get();

        $todayDischarges = Admission::where('status', 'discharged')
            ->whereDate('discharge_date', today())
            ->with(['patient', 'ward', 'admittingDoctor'])->orderByDesc('discharge_date')->get();

        $allWards = \App\Models\Ward::where('is_active', true)->with('beds')->orderBy('name')->get();

        return view('ipd.census-panel', compact(
            'admissions', 'wardGroups', 'totalBeds', 'occupiedBeds', 'availableBeds',
            'todayAdmissions', 'todayDischarges', 'allWards'
        ));
    }
}
