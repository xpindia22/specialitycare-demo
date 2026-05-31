<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medicine;
use App\Models\PharmacyStock;

class TemplateMedicineSeeder extends Seeder
{
    // cat IDs: 1=Antibiotics 2=Analgesics 3=Antihypertensives 4=Antidiabetics
    //          5=Antacids 6=Antihistamines 7=Vitamins&Supplements 8=Cardiovascular
    //          9=Respiratory 10=Neurological 11=Hormones

    private array $medicines = [
        // ── Cardiovascular / Antihypertensive ─────────────────────────────────
        ['name'=>'Tab Aspirin 75mg',              'generic'=>'Aspirin',              'form'=>'Tab','cat'=>8, 'price'=>2],
        ['name'=>'Tab Aspirin 150mg',             'generic'=>'Aspirin',              'form'=>'Tab','cat'=>8, 'price'=>2],
        ['name'=>'Tab Amlodipine 5mg',            'generic'=>'Amlodipine',           'form'=>'Tab','cat'=>3, 'price'=>5],
        ['name'=>'Tab Amlodipine 10mg',           'generic'=>'Amlodipine',           'form'=>'Tab','cat'=>3, 'price'=>7],
        ['name'=>'Tab Losartan 50mg',             'generic'=>'Losartan',             'form'=>'Tab','cat'=>3, 'price'=>8],
        ['name'=>'Tab Losartan 100mg',            'generic'=>'Losartan',             'form'=>'Tab','cat'=>3, 'price'=>12],
        ['name'=>'Tab Telmisartan 40mg',          'generic'=>'Telmisartan',          'form'=>'Tab','cat'=>3, 'price'=>8],
        ['name'=>'Tab Telmisartan 80mg',          'generic'=>'Telmisartan',          'form'=>'Tab','cat'=>3, 'price'=>12],
        ['name'=>'Tab Hydrochlorothiazide 12.5mg','generic'=>'Hydrochlorothiazide',  'form'=>'Tab','cat'=>3, 'price'=>3],
        ['name'=>'Tab Chlorthalidone 12.5mg',     'generic'=>'Chlorthalidone',       'form'=>'Tab','cat'=>3, 'price'=>5],
        ['name'=>'Tab Ramipril 2.5mg',            'generic'=>'Ramipril',             'form'=>'Tab','cat'=>3, 'price'=>6],
        ['name'=>'Tab Ramipril 5mg',              'generic'=>'Ramipril',             'form'=>'Tab','cat'=>3, 'price'=>8],
        ['name'=>'Tab Enalapril 5mg',             'generic'=>'Enalapril',            'form'=>'Tab','cat'=>3, 'price'=>5],
        ['name'=>'Tab Enalapril 10mg',            'generic'=>'Enalapril',            'form'=>'Tab','cat'=>3, 'price'=>7],
        ['name'=>'Tab Metoprolol 25mg',           'generic'=>'Metoprolol',           'form'=>'Tab','cat'=>8, 'price'=>5],
        ['name'=>'Tab Metoprolol 50mg',           'generic'=>'Metoprolol',           'form'=>'Tab','cat'=>8, 'price'=>7],
        ['name'=>'Tab Carvedilol 3.125mg',        'generic'=>'Carvedilol',           'form'=>'Tab','cat'=>8, 'price'=>8],
        ['name'=>'Tab Carvedilol 6.25mg',         'generic'=>'Carvedilol',           'form'=>'Tab','cat'=>8, 'price'=>10],
        ['name'=>'Tab Atorvastatin 10mg',         'generic'=>'Atorvastatin',         'form'=>'Tab','cat'=>8, 'price'=>5],
        ['name'=>'Tab Atorvastatin 20mg',         'generic'=>'Atorvastatin',         'form'=>'Tab','cat'=>8, 'price'=>7],
        ['name'=>'Tab Atorvastatin 40mg',         'generic'=>'Atorvastatin',         'form'=>'Tab','cat'=>8, 'price'=>10],
        ['name'=>'Tab Atorvastatin 80mg',         'generic'=>'Atorvastatin',         'form'=>'Tab','cat'=>8, 'price'=>14],
        ['name'=>'Cap Fenofibrate 145mg',         'generic'=>'Fenofibrate',          'form'=>'Cap','cat'=>8, 'price'=>10],
        ['name'=>'Tab Clopidogrel 75mg',          'generic'=>'Clopidogrel',          'form'=>'Tab','cat'=>8, 'price'=>8],
        ['name'=>'Tab Isosorbide Mononitrate 20mg','generic'=>'Isosorbide Mononitrate','form'=>'Tab','cat'=>8, 'price'=>6],
        ['name'=>'Tab Isosorbide Mononitrate 30mg','generic'=>'Isosorbide Mononitrate','form'=>'Tab','cat'=>8, 'price'=>8],
        ['name'=>'Tab Nitroglycerine 0.5mg',      'generic'=>'Nitroglycerine',       'form'=>'Tab','cat'=>8, 'price'=>5],
        ['name'=>'Tab Furosemide 40mg',           'generic'=>'Furosemide',           'form'=>'Tab','cat'=>8, 'price'=>3],
        ['name'=>'Tab Furosemide 80mg',           'generic'=>'Furosemide',           'form'=>'Tab','cat'=>8, 'price'=>5],
        ['name'=>'Tab Spironolactone 25mg',       'generic'=>'Spironolactone',       'form'=>'Tab','cat'=>8, 'price'=>5],
        ['name'=>'Tab Digoxin 0.25mg',            'generic'=>'Digoxin',              'form'=>'Tab','cat'=>8, 'price'=>4],
        ['name'=>'Tab Warfarin 5mg',              'generic'=>'Warfarin',             'form'=>'Tab','cat'=>8, 'price'=>5],
        ['name'=>'Tab Amiodarone 200mg',          'generic'=>'Amiodarone',           'form'=>'Tab','cat'=>8, 'price'=>15],
        ['name'=>'Tab Potassium Chloride 600mg',  'generic'=>'Potassium Chloride',   'form'=>'Tab','cat'=>8, 'price'=>4],

        // ── Analgesics / Antipyretics ──────────────────────────────────────────
        ['name'=>'Tab Paracetamol 500mg',         'generic'=>'Paracetamol',          'form'=>'Tab','cat'=>2, 'price'=>2],
        ['name'=>'Tab Ibuprofen 400mg',           'generic'=>'Ibuprofen',            'form'=>'Tab','cat'=>2, 'price'=>4],

        // ── Antibiotics ───────────────────────────────────────────────────────
        ['name'=>'Cap Amoxicillin 500mg',         'generic'=>'Amoxicillin',          'form'=>'Cap','cat'=>1, 'price'=>8],
        ['name'=>'Tab Azithromycin 500mg',        'generic'=>'Azithromycin',         'form'=>'Tab','cat'=>1, 'price'=>25],
        ['name'=>'Cap Nitrofurantoin 100mg',      'generic'=>'Nitrofurantoin',       'form'=>'Cap','cat'=>1, 'price'=>12],

        // ── Antacids / GI ─────────────────────────────────────────────────────
        ['name'=>'Tab Pantoprazole 40mg',         'generic'=>'Pantoprazole',         'form'=>'Tab','cat'=>5, 'price'=>5],
        ['name'=>'Tab Domperidone 10mg',          'generic'=>'Domperidone',          'form'=>'Tab','cat'=>5, 'price'=>3],
        ['name'=>'Tab Sucralfate 1g',             'generic'=>'Sucralfate',           'form'=>'Tab','cat'=>5, 'price'=>5],
        ['name'=>'Tab Ranitidine 150mg',          'generic'=>'Ranitidine',           'form'=>'Tab','cat'=>5, 'price'=>4],

        // ── Antihistamines ────────────────────────────────────────────────────
        ['name'=>'Tab Cetirizine 10mg',           'generic'=>'Cetirizine',           'form'=>'Tab','cat'=>6, 'price'=>3],
        ['name'=>'Tab Levocetirizine 5mg',        'generic'=>'Levocetirizine',       'form'=>'Tab','cat'=>6, 'price'=>5],

        // ── Antidiabetics ─────────────────────────────────────────────────────
        ['name'=>'Tab Metformin 500mg',           'generic'=>'Metformin',            'form'=>'Tab','cat'=>4, 'price'=>4],
        ['name'=>'Tab Metformin 1000mg',          'generic'=>'Metformin',            'form'=>'Tab','cat'=>4, 'price'=>7],
        ['name'=>'Tab Glipizide 5mg',             'generic'=>'Glipizide',            'form'=>'Tab','cat'=>4, 'price'=>5],
        ['name'=>'Tab Glibenclamide 5mg',         'generic'=>'Glibenclamide',        'form'=>'Tab','cat'=>4, 'price'=>4],

        // ── Hormones / Thyroid ────────────────────────────────────────────────
        ['name'=>'Tab Levothyroxine 25mcg',       'generic'=>'Levothyroxine',        'form'=>'Tab','cat'=>11,'price'=>5],
        ['name'=>'Tab Levothyroxine 50mcg',       'generic'=>'Levothyroxine',        'form'=>'Tab','cat'=>11,'price'=>6],
        ['name'=>'Tab Levothyroxine 100mcg',      'generic'=>'Levothyroxine',        'form'=>'Tab','cat'=>11,'price'=>8],

        // ── Respiratory ───────────────────────────────────────────────────────
        ['name'=>'Tab Salbutamol 4mg',            'generic'=>'Salbutamol',           'form'=>'Tab','cat'=>9, 'price'=>3],
        ['name'=>'MDI Salbutamol 100mcg',         'generic'=>'Salbutamol',           'form'=>'Inhaler','cat'=>9,'price'=>120],
        ['name'=>'MDI Budesonide 200mcg',         'generic'=>'Budesonide',           'form'=>'Inhaler','cat'=>9,'price'=>180],
        ['name'=>'Tab Montelukast 10mg',          'generic'=>'Montelukast',          'form'=>'Tab','cat'=>9, 'price'=>12],
        ['name'=>'Tab Ambroxol 30mg',             'generic'=>'Ambroxol',             'form'=>'Tab','cat'=>9, 'price'=>4],

        // ── Vitamins & Supplements ────────────────────────────────────────────
        ['name'=>'Tab Vitamin C 500mg',           'generic'=>'Ascorbic Acid',        'form'=>'Tab','cat'=>7, 'price'=>3],
        ['name'=>'Tab Vitamin B12 500mcg',        'generic'=>'Cyanocobalamin',       'form'=>'Tab','cat'=>7, 'price'=>5],
        ['name'=>'Tab Ferrous Sulfate 200mg',     'generic'=>'Ferrous Sulfate',      'form'=>'Tab','cat'=>7, 'price'=>4],
        ['name'=>'Tab Folic Acid 5mg',            'generic'=>'Folic Acid',           'form'=>'Tab','cat'=>7, 'price'=>2],
        ['name'=>'Cap Cranberry 400mg',           'generic'=>'Cranberry Extract',    'form'=>'Cap','cat'=>7, 'price'=>15],
        ['name'=>'Tab Zinolvita',                 'generic'=>'Zinc + Multivitamin',  'form'=>'Tab','cat'=>7, 'price'=>15],
        ['name'=>'Cap Omegasoft',                 'generic'=>'Omega-3 Fatty Acids',  'form'=>'Cap','cat'=>7, 'price'=>20],
        ['name'=>'Tab Shelcal',                   'generic'=>'Calcium + Vitamin D3', 'form'=>'Tab','cat'=>7, 'price'=>10],

        // ── Neurological / Psychiatric ────────────────────────────────────────
        ['name'=>'Tab Clonazepam 0.25mg',         'generic'=>'Clonazepam',           'form'=>'Tab','cat'=>10,'price'=>5],
        ['name'=>'Tab Clonazepam 0.5mg',          'generic'=>'Clonazepam',           'form'=>'Tab','cat'=>10,'price'=>7],
        ['name'=>'Tab Escitalopram 10mg',         'generic'=>'Escitalopram',         'form'=>'Tab','cat'=>10,'price'=>10],
        ['name'=>'Tab Alprazolam 0.25mg',         'generic'=>'Alprazolam',           'form'=>'Tab','cat'=>10,'price'=>5],
        ['name'=>'Tab Alprazolam 0.5mg',          'generic'=>'Alprazolam',           'form'=>'Tab','cat'=>10,'price'=>7],
    ];

    public function run(): void
    {
        $added = 0;
        $stocked = 0;

        foreach ($this->medicines as $med) {
            $existing = Medicine::where('name', $med['name'])->first();

            if (!$existing) {
                $existing = Medicine::create([
                    'name'                 => $med['name'],
                    'generic_name'         => $med['generic'],
                    'medicine_category_id' => $med['cat'],
                    'dosage_form'          => $med['form'],
                    'strength'             => '',
                    'unit'                 => in_array($med['form'], ['Cap']) ? 'Capsule' : (in_array($med['form'], ['Inhaler']) ? 'Inhaler' : 'Tablet'),
                    'unit_price'           => $med['price'],
                    'reorder_level'        => 100,
                    'is_active'            => true,
                ]);
                $added++;
            }

            // Add stock if none exists
            if ($existing->total_stock === 0) {
                PharmacyStock::create([
                    'medicine_id'    => $existing->id,
                    'batch_number'   => 'TEMPLATE-STOCK',
                    'quantity'       => 10000,
                    'min_stock_level'=> 100,
                    'unit_cost'      => $med['price'],
                    'is_active'      => true,
                ]);
                $stocked++;
            }
        }

        $this->command->info("Added {$added} new medicines, stocked {$stocked} with 10,000 units.");
    }
}
