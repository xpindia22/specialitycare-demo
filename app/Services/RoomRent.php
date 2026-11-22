<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\ServiceCharge;
use App\Models\Ward;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Daily room rent from the ward the patient was in.
 *
 * Rates come from Service Charges ("ICU Room Rent", "General Ward Room Rent" …).
 * On a day the patient was in more than one ward (a transfer day) the higher
 * rate is charged, which is the usual hospital rule.
 */
class RoomRent
{
    const WARD_CHARGE = [
        'general'      => 'General Ward Room Rent',
        'semi_private' => 'Semi-Private Room Rent',
        'private'      => 'Private Room Rent',
        'icu'          => 'ICU Room Rent',
        'hdu'          => 'HDU Room Rent',
        'emergency'    => 'Emergency Ward Room Rent',
    ];

    private array $rates = [];

    public function rate(?Ward $ward): float
    {
        if (!$ward) return 0;
        if (!array_key_exists($ward->type, $this->rates)) {
            $name = self::WARD_CHARGE[$ward->type] ?? null;
            // some installs have "Semi-Private Room Room Rent" / "Private Room Room Rent"
            $this->rates[$ward->type] = $name
                ? (float) (ServiceCharge::whereIn('name', [$name, str_replace(' Room Rent', ' Room Room Rent', $name)])->where('is_active', true)->value('price') ?? 0)
                : 0;
        }
        return $this->rates[$ward->type];
    }

    /**
     * Ward stays from admission to discharge (or now):
     * [['ward' => Ward, 'bed' => ?Bed, 'from' => Carbon, 'to' => ?Carbon], …]
     */
    public static function stays(Admission $a): Collection
    {
        $a->loadMissing(['bedTransfers.fromWard', 'bedTransfers.fromBed', 'bedTransfers.toWard', 'bedTransfers.toBed', 'ward', 'bed']);
        $stays = [];
        $start = $a->admission_date;
        foreach ($a->bedTransfers as $t) {
            $stays[] = ['ward' => $t->fromWard, 'bed' => $t->fromBed, 'from' => $start, 'to' => $t->transferred_at];
            $start = $t->transferred_at;
        }
        $stays[] = ['ward' => $a->ward, 'bed' => $a->bed, 'from' => $start, 'to' => $a->discharge_date];
        return collect($stays);
    }

    /** Ward to charge for $date and its rate, or null when there is nothing to charge. */
    public function forDate(Admission $a, CarbonInterface $date): ?array
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        $wards = self::stays($a)
            ->filter(fn ($s) => $s['from']->lte($dayEnd) && (!$s['to'] || $s['to']->gte($dayStart)))
            ->pluck('ward')->filter()->unique('id')->values();
        if ($wards->isEmpty()) return null;

        $best = $wards->sortByDesc(fn ($w) => $this->rate($w))->first();
        $rate = $this->rate($best);
        if ($rate <= 0) return null;
        $others = $wards->where('id', '!=', $best->id)->pluck('name');
        return [
            'ward'        => $best,
            'rate'        => $rate,
            'description' => $best->name . ' — Room Rent' . ($others->isNotEmpty() ? ' (transfer day, also ' . $others->implode(', ') . ')' : ''),
        ];
    }
}
