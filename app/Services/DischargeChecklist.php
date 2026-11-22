<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\DischargeClearance;
use App\Models\IcuInfusion;
use App\Models\IpdDeposit;
use App\Models\IpdCharge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sign-offs needed before a patient is discharged.
 *
 * Some items clear themselves (no running infusions, nothing pending, bill
 * settled); otherwise the responsible role ticks them with a note. Running
 * infusions can only be cleared by stopping them.
 */
class DischargeChecklist
{
    const ITEMS = [
        'doctor'    => ['label' => 'Doctor: discharge advised, summary and medicines reviewed', 'icon' => 'fa-user-doctor',      'roles' => ['admin', 'doctor']],
        'infusions' => ['label' => 'ICU infusions stopped',                                       'icon' => 'fa-syringe',          'roles' => []],
        'results'   => ['label' => 'Pending lab / radiology results seen',                        'icon' => 'fa-flask',            'roles' => ['admin', 'doctor', 'nurse']],
        'pharmacy'  => ['label' => 'Pharmacy: unused medicines returned, take-home medicines given', 'icon' => 'fa-pills',        'roles' => ['admin', 'nurse', 'pharmacist', 'doctor']],
        'nursing'   => ['label' => 'Nursing: lines / catheter removed, belongings returned, instructions explained', 'icon' => 'fa-user-nurse', 'roles' => ['admin', 'nurse']],
        'billing'   => ['label' => 'Billing: final bill settled',                                 'icon' => 'fa-file-invoice-dollar', 'roles' => ['admin', 'receptionist', 'accountant']],
    ];

    /** @return array<string, array{label:string, icon:string, ok:bool, auto:bool, detail:?string, clearance:?DischargeClearance, can:bool}> */
    public static function for(Admission $a, ?User $user = null): array
    {
        $user ??= auth()->user();
        $cleared = DischargeClearance::with('user')->where('admission_id', $a->id)->get()->keyBy('item');
        $out = [];

        foreach (self::ITEMS as $key => $def) {
            $auto = false;
            $detail = null;
            switch ($key) {
                case 'infusions':
                    $running = IcuInfusion::where('admission_id', $a->id)->whereNull('stopped_at')->pluck('drug_name');
                    $auto = $running->isEmpty();
                    $detail = $auto ? 'None running' : 'Still running: ' . $running->implode(', ') . ' — stop them on the ICU chart';
                    break;
                case 'results':
                    $lab = DB::table('lab_orders')->where('admission_id', $a->id)->where('status', 'Ordered')->count();
                    $rad = DB::table('radiology_orders')->where('admission_id', $a->id)->where('status', 'ordered')->count();
                    $auto = $lab + $rad === 0;
                    $detail = $auto ? 'Nothing pending' : trim(($lab ? "{$lab} lab order(s) " : '') . ($rad ? "{$rad} radiology order(s) " : '') . 'not completed');
                    break;
                case 'billing':
                    $bal = self::balance($a);
                    $auto = $bal <= 0.5;
                    $detail = $auto ? ($bal < -0.5 ? 'Refund due ₹' . number_format(-$bal) : 'Settled') : 'Balance due ₹' . number_format($bal);
                    break;
                case 'pharmacy':
                    $active = $a->prescriptions()->where('is_active', true)->count();
                    $detail = $active ? "{$active} medicine(s) still active on the chart" : 'Medicine chart closed';
                    break;
            }
            $c = $cleared->get($key);
            $out[$key] = $def + [
                'ok'        => $auto || ($c && $key !== 'infusions'),
                'auto'      => $auto,
                'detail'    => $detail,
                'clearance' => $c,
                'can'       => $key !== 'infusions' && $user && $user->hasRole($def['roles']),
            ];
        }
        return $out;
    }

    public static function outstanding(Admission $a): array
    {
        return array_keys(array_filter(self::for($a), fn ($i) => !$i['ok']));
    }

    /** Charges + packages − deposits − package payments (same as the admission billing badge). */
    public static function balance(Admission $a): float
    {
        $charges = (float) IpdCharge::where('admission_id', $a->id)->sum('amount');
        $pk = DB::table('admission_packages')->where('admission_id', $a->id)->selectRaw('COALESCE(SUM(quoted_price),0) q, COALESCE(SUM(paid_amount),0) p')->first();
        $deposits = (float) IpdDeposit::where('admission_id', $a->id)->sum('amount');
        return round($charges + (float) $pk->q - (float) $pk->p - $deposits, 2);
    }
}
