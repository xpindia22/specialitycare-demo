<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tumourCat = DB::table('lab_categories')->where('name', 'like', '%umour Marker%')->value('id')
                  ?? DB::table('lab_categories')->where('name', 'like', '%umor Marker%')->value('id')
                  ?? DB::table('lab_categories')->where('name', 'Biochemistry')->value('id') ?? 2;

        // 1) Cancer-marker tests that weren't in the system.
        foreach ([
            ['name' => 'PSA (Prostate Specific Antigen)', 'short_code' => 'PSA',   'price' => 700],
            ['name' => 'CA-125',                          'short_code' => 'CA125', 'price' => 1300],
            ['name' => 'CEA (Carcinoembryonic Antigen)',  'short_code' => 'CEA',   'price' => 900],
        ] as $m) {
            DB::table('lab_tests')->updateOrInsert(
                ['name' => $m['name']],
                ['lab_category_id' => $tumourCat, 'short_code' => $m['short_code'],
                 'sample_type' => 'Serum', 'price' => $m['price'], 'is_active' => 1, 'updated_at' => now()]
            );
        }

        // resolve test ids by name (dedup duplicate names → lowest id)
        $ids = fn (array $names) => DB::table('lab_tests')->whereIn('name', $names)
            ->selectRaw('name, MIN(id) AS id')->groupBy('name')->pluck('id')->all();

        $core      = $ids(['CBC', 'ESR', 'Blood Glucose Fasting', 'HbA1c', 'Total Cholesterol',
                           'HDL Cholesterol', 'LDL Cholesterol', 'Triglycerides', 'Creatinine',
                           'Urea', 'TSH', 'Vitamin D 25-OH', 'Vitamin B12']);
        $maleAdd   = $ids(['PSA (Prostate Specific Antigen)', 'CEA (Carcinoembryonic Antigen)',
                           'Stool Occult Blood', 'Alpha-Fetoprotein (AFP)']);
        $femaleAdd = $ids(['CA-125', 'CEA (Carcinoembryonic Antigen)',
                           'Stool Occult Blood', 'Alpha-Fetoprotein (AFP)']);

        // 2) The four gender × age yearly-checkup panels.
        $panels = [
            ['name' => 'Annual Health Check – Male (up to 40)', 'code' => 'AHC_M40',  'gender' => 'male',   'min' => 0,  'max' => 40,   'price' => 2500,
             'desc' => 'Yearly screening for men ≤40 — diabetes, lipids/CAD risk, kidney, thyroid, vitamins.', 'tests' => $core],
            ['name' => 'Annual Health Check – Male (40+)',      'code' => 'AHC_M40P', 'gender' => 'male',   'min' => 40, 'max' => null, 'price' => 4500,
             'desc' => 'Yearly screening for men 40+ — adds prostate (PSA) and GI/colorectal cancer markers.', 'tests' => array_values(array_unique(array_merge($core, $maleAdd)))],
            ['name' => 'Annual Health Check – Female (up to 40)', 'code' => 'AHC_F40', 'gender' => 'female', 'min' => 0,  'max' => 40,   'price' => 2500,
             'desc' => 'Yearly screening for women ≤40 — diabetes, lipids/CAD risk, kidney, thyroid, vitamins.', 'tests' => $core],
            ['name' => 'Annual Health Check – Female (40+)',    'code' => 'AHC_F40P', 'gender' => 'female', 'min' => 40, 'max' => null, 'price' => 4500,
             'desc' => 'Yearly screening for women 40+ — adds ovarian (CA-125) and GI/colorectal cancer markers.', 'tests' => array_values(array_unique(array_merge($core, $femaleAdd)))],
        ];

        foreach ($panels as $p) {
            DB::table('lab_panels')->updateOrInsert(
                ['name' => $p['name']],
                ['short_code' => $p['code'], 'description' => $p['desc'], 'gender' => $p['gender'],
                 'min_age' => $p['min'], 'max_age' => $p['max'], 'price' => $p['price'], 'is_active' => 1, 'updated_at' => now()]
            );
            $pid = DB::table('lab_panels')->where('name', $p['name'])->value('id');
            DB::table('lab_panel_tests')->where('lab_panel_id', $pid)->delete();
            foreach ($p['tests'] as $tid) {
                DB::table('lab_panel_tests')->insert(['lab_panel_id' => $pid, 'lab_test_id' => $tid]);
            }
        }
    }

    public function down(): void
    {
        $names = ['Annual Health Check – Male (up to 40)', 'Annual Health Check – Male (40+)',
                  'Annual Health Check – Female (up to 40)', 'Annual Health Check – Female (40+)'];
        $ids = DB::table('lab_panels')->whereIn('name', $names)->pluck('id')->all();
        DB::table('lab_panel_tests')->whereIn('lab_panel_id', $ids)->delete();
        DB::table('lab_panels')->whereIn('id', $ids)->delete();
        DB::table('lab_tests')->whereIn('name', ['PSA (Prostate Specific Antigen)', 'CA-125', 'CEA (Carcinoembryonic Antigen)'])->delete();
    }
};
