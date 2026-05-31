<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LabCategory;
use App\Models\LabTest;
use App\Models\LabPanel;

class AdditionalLabTestsAndPanelsSeeder extends Seeder
{
    /**
     * Adds individual lab tests and panels referenced by the encounter
     * templates if they don't already exist. Pricing is rough — user
     * has explicitly said they'll fine-tune later.
     */
    public function run(): void
    {
        // Resolve / create categories
        $cat = function (string $name, string $code = null) {
            return LabCategory::firstOrCreate(
                ['name' => $name],
                ['code' => $code]
            )->id;
        };

        $haem    = $cat('Haematology', 'HAEM');
        $bio     = $cat('Biochemistry', 'BIO');
        $micro   = $cat('Microbiology', 'MICRO');
        $immuno  = $cat('Immunology', 'IMMUNO');
        $horm    = $cat('Hormones', 'HORM');
        $sero    = $cat('Serology', 'SERO');
        $tumour  = $cat('Tumour Markers', 'TUMOUR');

        // ─── INDIVIDUAL TESTS ────────────────────────────────────────────────
        $tests = [
            // Autoimmune / Rheumatology
            ['name'=>'Anti-dsDNA',                      'lab_category_id'=>$immuno,  'short_code'=>'dsDNA',     'sample_type'=>'Serum', 'unit'=>'IU/mL',    'normal_range'=>'< 30 IU/mL',         'price'=>900,  'turnaround_hours'=>48],
            ['name'=>'Anti-TPO',                        'lab_category_id'=>$immuno,  'short_code'=>'TPO',       'sample_type'=>'Serum', 'unit'=>'IU/mL',    'normal_range'=>'< 35 IU/mL',         'price'=>700,  'turnaround_hours'=>24],
            ['name'=>'Uric Acid (Serum)',               'lab_category_id'=>$bio,     'short_code'=>'UA',        'sample_type'=>'Serum', 'unit'=>'mg/dL',    'normal_range'=>'M 3.4-7.0 / F 2.4-6.0','price'=>180, 'turnaround_hours'=>4],

            // Liver / Cirrhosis
            ['name'=>'Alpha-Fetoprotein (AFP)',         'lab_category_id'=>$tumour,  'short_code'=>'AFP',       'sample_type'=>'Serum', 'unit'=>'ng/mL',    'normal_range'=>'< 10 ng/mL',         'price'=>700,  'turnaround_hours'=>24],

            // Renal / Endocrine
            ['name'=>'Intact Parathyroid Hormone (iPTH)', 'lab_category_id'=>$horm,  'short_code'=>'iPTH',      'sample_type'=>'Serum', 'unit'=>'pg/mL',    'normal_range'=>'15-65 pg/mL',        'price'=>900, 'turnaround_hours'=>24],
            ['name'=>'Total Testosterone',              'lab_category_id'=>$horm,    'short_code'=>'TESTO',     'sample_type'=>'Serum', 'unit'=>'ng/dL',    'normal_range'=>'M 280-1100 / F 15-70','price'=>650, 'turnaround_hours'=>24],
            ['name'=>'DHEA-S',                          'lab_category_id'=>$horm,    'short_code'=>'DHEAS',     'sample_type'=>'Serum', 'unit'=>'µg/dL',    'normal_range'=>'F 80-560',           'price'=>900,  'turnaround_hours'=>24],
            ['name'=>'Luteinising Hormone (LH)',        'lab_category_id'=>$horm,    'short_code'=>'LH',        'sample_type'=>'Serum', 'unit'=>'mIU/mL',   'normal_range'=>'cycle dependent',    'price'=>500,  'turnaround_hours'=>24],
            ['name'=>'Follicle Stimulating Hormone (FSH)','lab_category_id'=>$horm,  'short_code'=>'FSH',       'sample_type'=>'Serum', 'unit'=>'mIU/mL',   'normal_range'=>'cycle dependent',    'price'=>500,  'turnaround_hours'=>24],
            ['name'=>'Prolactin',                       'lab_category_id'=>$horm,    'short_code'=>'PRL',       'sample_type'=>'Serum', 'unit'=>'ng/mL',    'normal_range'=>'F 5-25',             'price'=>500,  'turnaround_hours'=>24],

            // Microbiology / Derm
            ['name'=>'KOH Mount',                       'lab_category_id'=>$micro,   'short_code'=>'KOH',       'sample_type'=>'Skin scraping','unit'=>'-', 'normal_range'=>'No fungal elements','price'=>200,  'turnaround_hours'=>4],
            ['name'=>'High Vaginal Swab Culture',       'lab_category_id'=>$micro,   'short_code'=>'HVS',       'sample_type'=>'Swab',  'unit'=>'-',        'normal_range'=>'Normal flora',       'price'=>500,  'turnaround_hours'=>72],

            // Haematology / ANC / Allergy
            ['name'=>'Blood Group and Rh Typing',       'lab_category_id'=>$haem,    'short_code'=>'BG',        'sample_type'=>'EDTA',  'unit'=>'-',        'normal_range'=>'-',                   'price'=>200, 'turnaround_hours'=>4],
            ['name'=>'Total IgE',                       'lab_category_id'=>$immuno,  'short_code'=>'IgE',       'sample_type'=>'Serum', 'unit'=>'IU/mL',    'normal_range'=>'< 100 IU/mL',         'price'=>700,  'turnaround_hours'=>24],

            // Stroke / Cardiac
            ['name'=>'Carotid Doppler',                 'lab_category_id'=>$bio,     'short_code'=>'CDOP',      'sample_type'=>'-',     'unit'=>'-',        'normal_range'=>'No significant stenosis','price'=>1500,'turnaround_hours'=>2],
        ];

        $createdTests = 0;
        foreach ($tests as $t) {
            $exists = LabTest::where('name', $t['name'])->first();
            if ($exists) continue;
            LabTest::create($t + [
                'description'      => null,
                'normal_value'     => null,
                'instructions'     => null,
                'is_active'        => true,
            ]);
            $createdTests++;
        }

        // ─── PANELS ──────────────────────────────────────────────────────────
        $panels = [
            [
                'name'  => 'DMARD Monitoring Panel',
                'price' => 800,
                'turnaround_hours' => 24,
                'tests' => ['CBC', 'LFT', 'RFT'],
            ],
            [
                'name'  => 'Antenatal Profile',
                'price' => 1500,
                'turnaround_hours' => 24,
                'tests' => ['CBC', 'Blood Group and Rh Typing', 'Random Blood Glucose', 'HBsAg', 'HIV', 'VDRL / RPR', 'Urine Routine', 'TSH'],
            ],
            [
                'name'  => 'PCOS Hormonal Profile',
                'price' => 3500,
                'turnaround_hours' => 24,
                'tests' => ['TSH', 'Luteinising Hormone (LH)', 'Follicle Stimulating Hormone (FSH)', 'Total Testosterone', 'DHEA-S', 'Prolactin', 'Blood Glucose Fasting', 'HbA1c'],
            ],
            [
                'name'  => 'CKD Workup Panel',
                'price' => 2500,
                'turnaround_hours' => 24,
                'tests' => ['RFT', 'CBC', 'Urine Routine', 'Urine Albumin Creatinine Ratio', 'Calcium Total', 'Phosphorus', 'Vitamin D 25-OH', 'Intact Parathyroid Hormone (iPTH)'],
            ],
            [
                'name'  => 'Lupus / SLE Panel',
                'price' => 2800,
                'turnaround_hours' => 48,
                'tests' => ['ANA', 'Anti-dsDNA', 'Complement C3', 'Complement C4', 'CBC', 'Urine Routine', 'RFT'],
            ],
            [
                'name'  => 'Rheumatoid Arthritis Panel',
                'price' => 1800,
                'turnaround_hours' => 24,
                'tests' => ['Rheumatoid Factor', 'Anti-CCP', 'CRP', 'ESR', 'CBC'],
            ],
            [
                'name'  => 'Hepatitis Screen',
                'price' => 1200,
                'turnaround_hours' => 24,
                'tests' => ['HBsAg', 'Anti-HCV', 'HIV'],
            ],
            [
                'name'  => 'Gout Workup',
                'price' => 800,
                'turnaround_hours' => 8,
                'tests' => ['Uric Acid (Serum)', 'RFT', 'CBC', 'ESR'],
            ],
            [
                'name'  => 'Heart Failure Panel',
                'price' => 1800,
                'turnaround_hours' => 8,
                'tests' => ['NT-proBNP', 'CBC', 'RFT', 'Electrolytes'],
            ],
        ];

        $createdPanels = 0;
        foreach ($panels as $p) {
            $panel = LabPanel::firstWhere('name', $p['name']);
            if (!$panel) {
                $panel = LabPanel::create([
                    'name'             => $p['name'],
                    'short_code'       => null,
                    'description'      => null,
                    'price'            => $p['price'],
                    'turnaround_hours' => $p['turnaround_hours'],
                    'is_active'        => true,
                ]);
                $createdPanels++;
            }
            // Sync tests by name → id
            $testIds = collect($p['tests'])
                ->map(fn ($n) => LabTest::where('name', $n)->value('id'))
                ->filter()
                ->values()
                ->all();
            if (!empty($testIds)) {
                $panel->tests()->sync($testIds);
            }
        }

        $this->command->info("Created {$createdTests} new lab tests and {$createdPanels} new panels (and synced panel-test mappings).");
    }
}
