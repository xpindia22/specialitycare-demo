<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BillingPackage;
use App\Models\BillingPackageItem;

class BillingPackagesSeeder extends Seeder
{
    /**
     * Idempotent — keyed on package name. Re-running just refreshes line items.
     * Pricing is rough — user has explicitly said they'll adjust later.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Normal Vaginal Delivery — 3 Day',
                'category' => 'maternity',
                'description' => 'Routine NVD with 3-day stay in twin-sharing room.',
                'package_price' => 35000,
                'max_days' => 3,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['Room (Twin Sharing) x 3 days',     'room',     2500, 3],
                    ['Delivery Charges',                 'service', 12000, 1],
                    ['Obstetrician Fee',                 'service',  8000, 1],
                    ['Paediatrician Fee',                'service',  3000, 1],
                    ['Anaesthetist (if epidural)',       'service',  3000, 1, false],
                    ['Drugs & Consumables',              'medicine', 4000, 1],
                    ['Routine ANC Investigations',       'lab',      2000, 1],
                ],
            ],
            [
                'name' => 'C-Section — 5 Day',
                'category' => 'maternity',
                'description' => 'Elective LSCS with 5-day stay including OT and anaesthesia.',
                'package_price' => 65000,
                'max_days' => 5,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['Room (Twin Sharing) x 5 days', 'room',      2500, 5],
                    ['LSCS / OT Charges',            'service',  20000, 1],
                    ['Surgeon Fee',                  'service',  15000, 1],
                    ['Anaesthetist Fee',             'service',   6000, 1],
                    ['Paediatrician Fee',            'service',   3000, 1],
                    ['Drugs & Consumables',          'medicine',  6000, 1],
                    ['Investigations',               'lab',       3000, 1],
                ],
            ],
            [
                'name' => 'Cataract Surgery (Phaco) — Day Care',
                'category' => 'surgery',
                'description' => 'Day-care phacoemulsification with foldable IOL.',
                'package_price' => 28000,
                'max_days' => 1,
                'inc' => ['room' => false, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['Phaco + Foldable IOL',  'service', 18000, 1],
                    ['Surgeon Fee',           'service',  6000, 1],
                    ['OT Charges',            'service',  3000, 1],
                    ['Pre-op Investigations', 'lab',      1000, 1],
                ],
            ],
            [
                'name' => 'Coronary Angiography',
                'category' => 'cardiac',
                'description' => 'Diagnostic CAG, day-care via radial / femoral.',
                'package_price' => 22000,
                'max_days' => 1,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => false],
                'items' => [
                    ['Cath Lab Charges',     'service', 12000, 1],
                    ['Cardiologist Fee',     'service',  6000, 1],
                    ['Drugs & Consumables',  'medicine', 2500, 1],
                    ['Pre-cath Workup',      'lab',      1500, 1],
                ],
            ],
            [
                'name' => 'PTCA + Drug-Eluting Stent x 1',
                'category' => 'cardiac',
                'description' => 'Single-vessel PTCA with one DES, 2-day stay (excludes implant cost).',
                'package_price' => 145000,
                'max_days' => 2,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['Cath Lab + PTCA',          'service', 60000, 1],
                    ['Drug-Eluting Stent x 1',   'service', 45000, 1],
                    ['Cardiologist Fee',         'service', 15000, 1],
                    ['Anaesthetist',             'service',  5000, 1],
                    ['ICU x 1 day',              'room',     5000, 1],
                    ['Step-down Room x 1 day',   'room',     3000, 1],
                    ['Post-procedure Drugs',     'medicine', 6000, 1],
                    ['Investigations',           'lab',      4000, 1],
                ],
            ],
            [
                'name' => 'Dialysis Session (HD)',
                'category' => 'dialysis',
                'description' => 'Single haemodialysis session — outpatient.',
                'package_price' => 2500,
                'max_days' => 1,
                'inc' => ['room' => false, 'medicines' => true, 'investigations' => false, 'ot' => false],
                'items' => [
                    ['HD Session (4h)',     'service', 1800, 1],
                    ['Dialysis Consumables','medicine', 700, 1],
                ],
            ],
            [
                'name' => 'Knee Arthroscopy',
                'category' => 'ortho',
                'description' => 'Diagnostic / minor therapeutic arthroscopy of knee, 1-day stay.',
                'package_price' => 55000,
                'max_days' => 1,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['OT + Arthroscopy',    'service', 25000, 1],
                    ['Surgeon Fee',         'service', 15000, 1],
                    ['Anaesthetist',        'service',  6000, 1],
                    ['Room (Day Care)',     'room',     3000, 1],
                    ['Drugs & Consumables', 'medicine', 4000, 1],
                    ['Investigations',      'lab',      2000, 1],
                ],
            ],
            [
                'name' => 'Total Knee Replacement (Unilateral)',
                'category' => 'ortho',
                'description' => 'Single knee TKR with 5-day stay (excludes implant cost).',
                'package_price' => 185000,
                'max_days' => 5,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['OT Charges',                  'service',  35000, 1],
                    ['Surgeon Fee',                 'service',  60000, 1],
                    ['Anaesthetist',                'service',  10000, 1],
                    ['Implant (excluded — billed separately)','service', 0, 1, false],
                    ['Room (Twin Sharing) x 5 days','room',      2500, 5],
                    ['Drugs & Consumables',         'medicine', 18000, 1],
                    ['Investigations',              'lab',       7000, 1],
                    ['Physiotherapy',               'service',   5000, 1],
                ],
            ],
            [
                'name' => 'Appendicectomy (Open)',
                'category' => 'surgery',
                'description' => 'Open appendectomy with 2-day stay.',
                'package_price' => 38000,
                'max_days' => 2,
                'inc' => ['room' => true, 'medicines' => true, 'investigations' => true, 'ot' => true],
                'items' => [
                    ['OT Charges',           'service', 12000, 1],
                    ['Surgeon Fee',          'service', 12000, 1],
                    ['Anaesthetist',         'service',  4000, 1],
                    ['Room x 2 days',        'room',     2500, 2],
                    ['Drugs & Consumables',  'medicine', 4000, 1],
                    ['Investigations',       'lab',      1500, 1],
                ],
            ],
            [
                'name' => 'Master Health Check — Comprehensive',
                'category' => 'other',
                'description' => 'Full-body executive checkup (OPD, half-day).',
                'package_price' => 5500,
                'max_days' => 1,
                'inc' => ['room' => false, 'medicines' => false, 'investigations' => true, 'ot' => false],
                'items' => [
                    ['Consultation (Internal Medicine)', 'service',  500, 1],
                    ['CBC + ESR + Peripheral Smear',     'lab',      450, 1],
                    ['Liver Function Test',              'lab',      550, 1],
                    ['Renal Function Test',              'lab',      550, 1],
                    ['Lipid Profile',                    'lab',      500, 1],
                    ['Blood Sugar (F + PP) + HbA1c',     'lab',      650, 1],
                    ['Urine Routine',                    'lab',      150, 1],
                    ['ECG',                              'service',  300, 1],
                    ['Chest X-Ray',                      'service',  400, 1],
                    ['Ultrasound Abdomen',               'service',  900, 1],
                    ['Echocardiography',                 'service',  900, 1],
                ],
            ],
        ];

        $upserted = 0;
        foreach ($packages as $p) {
            $pkg = BillingPackage::updateOrCreate(
                ['name' => $p['name']],
                [
                    'category'                 => $p['category'],
                    'description'              => $p['description'],
                    'package_price'            => $p['package_price'],
                    'max_days'                 => $p['max_days'] ?? null,
                    'includes_room'            => $p['inc']['room'] ?? false,
                    'includes_medicines'       => $p['inc']['medicines'] ?? false,
                    'includes_investigations'  => $p['inc']['investigations'] ?? false,
                    'includes_ot'              => $p['inc']['ot'] ?? false,
                    'is_active'                => true,
                ]
            );
            // Refresh items
            $pkg->items()->delete();
            foreach ($p['items'] as $row) {
                [$name, $type, $unit, $qty] = $row;
                $isInc = $row[4] ?? true;
                BillingPackageItem::create([
                    'package_id'  => $pkg->id,
                    'item_name'   => $name,
                    'item_type'   => $type,
                    'unit_price'  => $unit,
                    'quantity'    => $qty,
                    'total_price' => $unit * $qty,
                    'is_included' => $isInc,
                ]);
            }
            $upserted++;
        }
        $this->command->info("Upserted {$upserted} billing packages with itemised breakdowns.");
    }
}
