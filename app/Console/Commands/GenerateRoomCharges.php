<?php
namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\IpdCharge;
use App\Services\RoomRent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRoomCharges extends Command
{
    protected $signature   = 'ipd:room-charges {date? : Date in Y-m-d format (default: today)}';
    protected $description = 'Generate daily room rent charges for all currently admitted patients';

    public function handle(): int
    {
        $date = $this->argument('date') ? now()->parse($this->argument('date')) : today();
        $dateStr = $date->toDateString();

        $this->info("Generating room charges for {$dateStr}…");

        $rent = new RoomRent();
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

            // ward the patient was in that day; the higher rate on a transfer day
            $charge = $rent->forDate($admission, $date);
            if (!$charge) { $skipped++; continue; }
            $price = $charge['rate'];

            IpdCharge::create([
                'admission_id' => $admission->id,
                'charge_date'  => $dateStr,
                'category'     => 'room_rent',
                'description'  => $charge['description'],
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
