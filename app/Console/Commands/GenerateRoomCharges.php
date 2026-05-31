<?php
namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\IpdCharge;
use App\Models\ServiceCharge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRoomCharges extends Command
{
    protected $signature   = 'ipd:room-charges {date? : Date in Y-m-d format (default: today)}';
    protected $description = 'Generate daily room rent charges for all currently admitted patients';

    // Map ward type → ServiceCharge name pattern
    const WARD_CHARGE_MAP = [
        'general'     => 'General Ward Room Rent',
        'semi_private'=> 'Semi-Private Room Rent',
        'private'     => 'Private Room Rent',
        'icu'         => 'ICU Room Rent',
        'hdu'         => 'HDU Room Rent',
        'emergency'   => 'Emergency Ward Room Rent',
    ];

    public function handle(): int
    {
        $date = $this->argument('date') ? now()->parse($this->argument('date')) : today();
        $dateStr = $date->toDateString();

        $this->info("Generating room charges for {$dateStr}…");

        $rateCache = [];
        $created = 0;
        $skipped = 0;

        $admissions = Admission::where('status', 'admitted')
            ->where('admission_date', '<=', $date->endOfDay())
            ->with(['ward', 'bed'])
            ->get();

        foreach ($admissions as $admission) {
            // Skip if charge already exists for this date
            $exists = IpdCharge::where('admission_id', $admission->id)
                ->where('charge_date', $dateStr)
                ->where('category', 'room_rent')
                ->exists();

            if ($exists) { $skipped++; continue; }

            $wardType = $admission->ward->type;
            if (!isset($rateCache[$wardType])) {
                $chargeName = self::WARD_CHARGE_MAP[$wardType] ?? null;
                $rateCache[$wardType] = $chargeName
                    ? ServiceCharge::where('name', $chargeName)->where('is_active', true)->value('price') ?? 0
                    : 0;
            }
            $price = $rateCache[$wardType];
            if ($price <= 0) { $skipped++; continue; }

            IpdCharge::create([
                'admission_id' => $admission->id,
                'charge_date'  => $dateStr,
                'category'     => 'room_rent',
                'description'  => $admission->ward->name . ' — Room Rent',
                'unit_price'   => $price,
                'quantity'     => 1,
                'amount'       => $price,
                'notes'        => 'Auto-generated',
                'created_by'   => 1, // system user (admin id=1)
            ]);
            $created++;
        }

        $this->info("Done — {$created} charges created, {$skipped} skipped (already exists or no rate).");
        return Command::SUCCESS;
    }
}
