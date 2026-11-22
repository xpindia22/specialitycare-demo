<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\OpdDiscount;
use App\Models\OpdFeeRule;
use App\Models\Patient;
use App\Models\ServiceCharge;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * OPD consultation fee for a visit.
 *
 * The most specific active rule wins: department + doctor, doctor, department,
 * then the default rule. Within free_days of the last paid consultation with
 * the same doctor (free_visits times) the visit is free; within followup_days
 * it is charged the follow-up fee; otherwise the new-visit fee. The best
 * automatic (age) discount is then taken off.
 */
class OpdFee
{
    public static function rule(?Doctor $doctor, ?int $departmentId): ?OpdFeeRule
    {
        $rules = OpdFeeRule::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('doctor_id')->orWhere('doctor_id', $doctor?->id))
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $departmentId))
            ->get();
        // department + doctor, then doctor only, then department only, then default
        return $rules->sortByDesc(fn ($r) => ($r->doctor_id ? 2 : 0) + ($r->department_id ? 1 : 0))->first();
    }

    /** Last consultation charged (> 0) to this patient with this doctor: ['date' => Carbon, 'encounter_id' => int] */
    public static function lastPaid(Patient $patient, ?Doctor $doctor, CarbonInterface $before, ?int $excludeEncounter = null): ?array
    {
        $row = DB::table('bills as b')
            ->join('bill_items as i', 'i.bill_id', '=', 'b.id')
            ->leftJoin('encounters as e', 'e.id', '=', 'b.encounter_id')
            ->where('b.patient_id', $patient->id)
            ->where('i.unit_price', '>', 0)
            ->where(fn ($q) => $q->where('i.item_type', 'Consultation')->orWhere('i.item_name', 'like', '%onsult%'))
            ->whereDate('b.bill_date', '<=', $before)
            ->when($excludeEncounter, fn ($q) => $q->where(fn ($w) => $w->whereNull('b.encounter_id')->orWhere('b.encounter_id', '!=', $excludeEncounter)))
            // bills not tied to a visit count for any doctor
            ->when($doctor, fn ($q) => $q->where(fn ($w) => $w->whereNull('e.id')->orWhere('e.doctor_id', $doctor->id)))
            ->orderByDesc('b.bill_date')->orderByDesc('b.id')
            ->first(['b.bill_date', 'b.encounter_id']);
        return $row ? ['date' => Carbon::parse($row->bill_date)->startOfDay(), 'encounter_id' => $row->encounter_id] : null;
    }

    /**
     * @return array{kind:string, label:string, base:float, fee:float, net:float, discount:?array,
     *               last_paid:?string, days:?int, rule:?string, new_fee:float, followup_fee:float, discounts:array}
     */
    public static function quote(Patient $patient, ?Doctor $doctor, ?int $departmentId = null, ?CarbonInterface $date = null, ?Encounter $encounter = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $rule = self::rule($doctor, $departmentId);

        $general = (float) (ServiceCharge::where('name', 'General Consultation')->value('price') ?? 0);
        $newFee = $rule?->new_fee !== null ? (float) $rule->new_fee
            : ((float) ($doctor?->consultation_fee ?? 0) > 0 ? (float) $doctor->consultation_fee : $general);
        $followFee = $rule?->followup_fee !== null ? (float) $rule->followup_fee
            : (float) (ServiceCharge::where('name', 'Follow-up Consultation')->value('price') ?? $newFee);

        $last = self::lastPaid($patient, $doctor, $date, $encounter?->id);
        $days = $last ? (int) $last['date']->diffInDays($date) : null;
        $kind = 'new';
        $label = 'New consultation';

        if ($last && $rule?->free_days !== null && $days <= $rule->free_days) {
            // visits with this doctor since the paid one (not counting the paid visit or this one)
            $used = Encounter::where('patient_id', $patient->id)
                ->when($doctor, fn ($q) => $q->where('doctor_id', $doctor->id))
                ->whereDate('encounter_date', '>=', $last['date'])->whereDate('encounter_date', '<=', $date)
                ->when($last['encounter_id'], fn ($q) => $q->where('id', '!=', $last['encounter_id']))
                ->when($encounter, fn ($q) => $q->where('id', '!=', $encounter->id))
                ->count();
            if ($used < max(1, (int) $rule->free_visits)) {
                $kind = 'free';
                $label = 'Free follow-up (paid ' . $last['date']->format('d M') . ", {$days} day" . ($days === 1 ? '' : 's') . " ago, free within {$rule->free_days})";
            }
        }
        if ($kind === 'new' && $last && $rule?->followup_days !== null && $days <= $rule->followup_days) {
            $kind = 'followup';
            $label = 'Follow-up (paid ' . $last['date']->format('d M') . ", within {$rule->followup_days} days)";
        }
        // No windows set up: keep the old behaviour of the visit type deciding
        if ($kind === 'new' && $encounter && str_contains(strtolower((string) $encounter->visit_type), 'follow')
            && ($rule?->followup_days === null)) {
            $kind = 'followup';
            $label = 'Follow-up (visit type)';
        }

        $base = $kind === 'free' ? 0.0 : ($kind === 'followup' ? $followFee : $newFee);
        $age = $patient->date_of_birth ? (int) Carbon::parse($patient->date_of_birth)->diffInYears($date) : null;
        $discounts = OpdDiscount::where('is_active', true)->orderBy('name')->get();
        $auto = $discounts->filter(fn ($d) => $d->appliesToAge($age))->sortByDesc('percent')->first();
        $discount = $auto && $base > 0 ? self::discountRow($auto, $base) : null;

        return [
            'kind'         => $kind,
            'label'        => $label,
            'base'         => $base,
            'fee'          => $base,
            'net'          => $discount ? round($base - $discount['amount'], 2) : $base,
            'discount'     => $discount,
            'last_paid'    => $last ? $last['date']->toDateString() : null,
            'days'         => $days,
            'rule'         => $rule?->scope_label,
            'new_fee'      => $newFee,
            'followup_fee' => $followFee,
            'age'          => $age,
            'discounts'    => $discounts->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'percent' => (float) $d->percent, 'auto' => $d->is_auto])->values()->all(),
        ];
    }

    public static function discountRow(OpdDiscount $d, float $base): array
    {
        return ['id' => $d->id, 'name' => $d->name, 'percent' => (float) $d->percent, 'amount' => round($base * (float) $d->percent / 100, 2)];
    }

    /** Bill line for the consultation: name says what was applied, price is what the patient pays. */
    public static function billItem(array $q, ?Doctor $doctor): array
    {
        $name = match ($q['kind']) {
            'free'     => 'Follow-up Consultation — free',
            'followup' => 'Follow-up Consultation',
            default    => 'General Consultation',
        };
        if ($doctor) $name .= ' (' . $doctor->full_name . ')';
        if ($q['discount']) $name .= " — {$q['discount']['name']} {$q['discount']['percent']}% off ₹" . number_format($q['base']);
        return ['name' => $name, 'type' => 'Consultation', 'price' => $q['net'], 'qty' => 1, 'base' => $q['base'], 'fee_note' => $q['label']];
    }

    /** Department of a visit: its token, else its appointment, else the doctor's main department. */
    public static function departmentOf(Encounter $e): ?int
    {
        $e->loadMissing(['opdToken', 'appointment', 'doctor']);
        return $e->opdToken?->department_id ?? $e->appointment?->department_id ?? $e->doctor?->department_id;
    }
}
