<?php

namespace App\Console\Commands;

use App\Models\ChargeTemplate;
use Illuminate\Console\Command;

/**
 * Seeds common IPD charge-bundle templates. Idempotent — matches on name, so
 * re-running never duplicates and never overwrites edited rows.
 * Each item = {category, description, unit_price, quantity}; categories must be
 * valid IpdCharge::CATEGORIES keys. Prices are placeholders — edit to your tariff.
 */
class SeedChargeTemplates extends Command
{
    protected $signature = 'charge:seed-templates';
    protected $description = 'Create common IPD charge templates (idempotent)';

    public function handle(): int
    {
        $templates = [
            [
                'name' => 'General Ward — Daily Charges',
                'description' => 'Per-day bundle for a General Ward bed. Edit rates to match your tariff.',
                'items' => [
                    ['category' => 'room_rent',      'description' => 'Room Rent — General Ward', 'unit_price' => 1000, 'quantity' => 1],
                    ['category' => 'nursing_charge', 'description' => 'Nursing Care (per day)',    'unit_price' => 300,  'quantity' => 1],
                    ['category' => 'doctor_visit',   'description' => 'Doctor Visit (per day)',    'unit_price' => 300,  'quantity' => 1],
                ],
            ],
            [
                'name' => 'Semi-Private Room — Daily Charges',
                'description' => 'Per-day bundle for a Semi-Private room.',
                'items' => [
                    ['category' => 'room_rent',      'description' => 'Room Rent — Semi-Private', 'unit_price' => 2000, 'quantity' => 1],
                    ['category' => 'nursing_charge', 'description' => 'Nursing Care (per day)',    'unit_price' => 400,  'quantity' => 1],
                    ['category' => 'doctor_visit',   'description' => 'Doctor Visit (per day)',    'unit_price' => 400,  'quantity' => 1],
                ],
            ],
            [
                'name' => 'Private Room — Daily Charges',
                'description' => 'Per-day bundle for a Private room.',
                'items' => [
                    ['category' => 'room_rent',      'description' => 'Room Rent — Private',    'unit_price' => 3500, 'quantity' => 1],
                    ['category' => 'nursing_charge', 'description' => 'Nursing Care (per day)', 'unit_price' => 500,  'quantity' => 1],
                    ['category' => 'doctor_visit',   'description' => 'Doctor Visit (per day)', 'unit_price' => 500,  'quantity' => 1],
                ],
            ],
            [
                'name' => 'ICU — Daily Charges',
                'description' => 'Per-day bundle for ICU. Add ventilator / monitoring separately if needed.',
                'items' => [
                    ['category' => 'room_rent',      'description' => 'ICU Bed Charges',        'unit_price' => 5000, 'quantity' => 1],
                    ['category' => 'nursing_charge', 'description' => 'ICU Nursing (per day)',   'unit_price' => 1000, 'quantity' => 1],
                    ['category' => 'doctor_visit',   'description' => 'Intensivist Visit',      'unit_price' => 800,  'quantity' => 1],
                    ['category' => 'oxygen',         'description' => 'Oxygen (per day)',        'unit_price' => 500,  'quantity' => 1],
                ],
            ],
            [
                'name' => 'Normal Delivery Package',
                'description' => 'Indicative package — 2 days stay. Adjust days/rates as required.',
                'items' => [
                    ['category' => 'procedure',      'description' => 'Conduct of Normal Delivery', 'unit_price' => 8000, 'quantity' => 1],
                    ['category' => 'room_rent',      'description' => 'Room Rent (2 days)',          'unit_price' => 1000, 'quantity' => 2],
                    ['category' => 'nursing_charge', 'description' => 'Nursing Care (2 days)',       'unit_price' => 300,  'quantity' => 2],
                    ['category' => 'doctor_visit',   'description' => 'Doctor Visit (2 days)',       'unit_price' => 500,  'quantity' => 2],
                ],
            ],
            [
                'name' => 'LSCS (Caesarean) Package',
                'description' => 'Indicative package — 3 days stay. Excludes high-end consumables.',
                'items' => [
                    ['category' => 'procedure',      'description' => 'LSCS — Surgeon & OT',   'unit_price' => 18000, 'quantity' => 1],
                    ['category' => 'room_rent',      'description' => 'Room Rent (3 days)',     'unit_price' => 1500,  'quantity' => 3],
                    ['category' => 'nursing_charge', 'description' => 'Nursing Care (3 days)',  'unit_price' => 400,   'quantity' => 3],
                    ['category' => 'doctor_visit',   'description' => 'Doctor Visit (3 days)',  'unit_price' => 600,   'quantity' => 3],
                ],
            ],
            [
                'name' => 'Day-Care Procedure',
                'description' => 'Same-day admission & discharge minor procedure bundle.',
                'items' => [
                    ['category' => 'procedure',      'description' => 'Day-Care Procedure', 'unit_price' => 3000, 'quantity' => 1],
                    ['category' => 'nursing_charge', 'description' => 'Nursing Care',        'unit_price' => 300,  'quantity' => 1],
                    ['category' => 'doctor_visit',   'description' => 'Doctor Charges',      'unit_price' => 300,  'quantity' => 1],
                ],
            ],
            [
                'name' => 'Casualty / Emergency Attendance',
                'description' => 'Emergency room attendance — add investigations / procedures as used.',
                'items' => [
                    ['category' => 'doctor_visit', 'description' => 'Casualty Consultation',       'unit_price' => 500, 'quantity' => 1],
                    ['category' => 'procedure',    'description' => 'Dressing / Injection / Minor', 'unit_price' => 200, 'quantity' => 1],
                ],
            ],
        ];

        $created = 0; $skipped = 0;
        foreach ($templates as $t) {
            if (ChargeTemplate::where('name', $t['name'])->exists()) {
                $skipped++; $this->line("  skip (exists): {$t['name']}"); continue;
            }
            ChargeTemplate::create($t + ['is_active' => true]);
            $created++; $this->info("  created: {$t['name']}");
        }
        $this->newLine();
        $this->info("Done. Created {$created}, skipped {$skipped} (already present).");
        return self::SUCCESS;
    }
}
