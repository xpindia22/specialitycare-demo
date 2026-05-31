<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medicine;
use App\Models\MedicineCategory;

class AdditionalMedicinesSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure we have a few extra categories
        $extraCats = ['Immunomodulators', 'Anticoagulants', 'Gastrointestinal', 'Topicals', 'Renal'];
        $catIds = [];
        foreach ($extraCats as $name) {
            $cat = MedicineCategory::firstOrCreate(['name' => $name]);
            $catIds[$name] = $cat->id;
        }
        // Resolve existing categories for reuse
        $existing = MedicineCategory::pluck('id', 'name')->toArray();
        $cat = array_merge($existing, $catIds);

        $g = function (string $name) use ($cat) { return $cat[$name] ?? null; };

        $medicines = [
            // ─── DMARDs / Immunomodulators ──────────────────────────────────────
            ['name'=>'Tab Methotrexate 7.5mg',          'generic_name'=>'methotrexate',        'medicine_category_id'=>$g('Immunomodulators'), 'dosage_form'=>'Tablet', 'strength'=>'7.5mg', 'unit'=>'Tab'],
            ['name'=>'Tab Methotrexate 10mg',           'generic_name'=>'methotrexate',        'medicine_category_id'=>$g('Immunomodulators'), 'dosage_form'=>'Tablet', 'strength'=>'10mg',  'unit'=>'Tab'],
            ['name'=>'Tab Hydroxychloroquine 200mg',    'generic_name'=>'hydroxychloroquine',  'medicine_category_id'=>$g('Immunomodulators'), 'dosage_form'=>'Tablet', 'strength'=>'200mg', 'unit'=>'Tab'],
            ['name'=>'Tab Leflunomide 10mg',            'generic_name'=>'leflunomide',         'medicine_category_id'=>$g('Immunomodulators'), 'dosage_form'=>'Tablet', 'strength'=>'10mg',  'unit'=>'Tab'],
            ['name'=>'Tab Leflunomide 20mg',            'generic_name'=>'leflunomide',         'medicine_category_id'=>$g('Immunomodulators'), 'dosage_form'=>'Tablet', 'strength'=>'20mg',  'unit'=>'Tab'],
            ['name'=>'Tab Mycophenolate Mofetil 500mg', 'generic_name'=>'mycophenolate mofetil','medicine_category_id'=>$g('Immunomodulators'),'dosage_form'=>'Tablet', 'strength'=>'500mg', 'unit'=>'Tab'],
            ['name'=>'Tab Sulfasalazine 500mg',         'generic_name'=>'sulfasalazine',       'medicine_category_id'=>$g('Immunomodulators'), 'dosage_form'=>'Tablet', 'strength'=>'500mg', 'unit'=>'Tab'],
            ['name'=>'Tab Prednisolone 5mg',            'generic_name'=>'prednisolone',        'medicine_category_id'=>$g('Hormones'),         'dosage_form'=>'Tablet', 'strength'=>'5mg',   'unit'=>'Tab'],
            ['name'=>'Tab Prednisolone 10mg',           'generic_name'=>'prednisolone',        'medicine_category_id'=>$g('Hormones'),         'dosage_form'=>'Tablet', 'strength'=>'10mg',  'unit'=>'Tab'],
            ['name'=>'Tab Colchicine 0.5mg',            'generic_name'=>'colchicine',          'medicine_category_id'=>$g('Analgesics'),       'dosage_form'=>'Tablet', 'strength'=>'0.5mg', 'unit'=>'Tab'],
            ['name'=>'Tab Allopurinol 100mg',           'generic_name'=>'allopurinol',         'medicine_category_id'=>$g('Analgesics'),       'dosage_form'=>'Tablet', 'strength'=>'100mg', 'unit'=>'Tab'],
            ['name'=>'Tab Febuxostat 40mg',             'generic_name'=>'febuxostat',          'medicine_category_id'=>$g('Analgesics'),       'dosage_form'=>'Tablet', 'strength'=>'40mg',  'unit'=>'Tab'],

            // ─── Cardiology / Heart Failure / Anticoagulation ──────────────────
            ['name'=>'Tab Bisoprolol 2.5mg',                  'generic_name'=>'bisoprolol',                  'medicine_category_id'=>$g('Cardiovascular'),   'dosage_form'=>'Tablet', 'strength'=>'2.5mg', 'unit'=>'Tab'],
            ['name'=>'Tab Bisoprolol 5mg',                    'generic_name'=>'bisoprolol',                  'medicine_category_id'=>$g('Cardiovascular'),   'dosage_form'=>'Tablet', 'strength'=>'5mg',   'unit'=>'Tab'],
            ['name'=>'Tab Eplerenone 25mg',                   'generic_name'=>'eplerenone',                  'medicine_category_id'=>$g('Cardiovascular'),   'dosage_form'=>'Tablet', 'strength'=>'25mg',  'unit'=>'Tab'],
            ['name'=>'Tab Sacubitril + Valsartan 50mg',       'generic_name'=>'sacubitril/valsartan',        'medicine_category_id'=>$g('Cardiovascular'),   'dosage_form'=>'Tablet', 'strength'=>'24/26mg','unit'=>'Tab'],
            ['name'=>'Tab Sacubitril + Valsartan 100mg',      'generic_name'=>'sacubitril/valsartan',        'medicine_category_id'=>$g('Cardiovascular'),   'dosage_form'=>'Tablet', 'strength'=>'49/51mg','unit'=>'Tab'],
            ['name'=>'Tab Dapagliflozin 10mg',                'generic_name'=>'dapagliflozin',               'medicine_category_id'=>$g('Antidiabetics'),    'dosage_form'=>'Tablet', 'strength'=>'10mg',  'unit'=>'Tab'],
            ['name'=>'Tab Empagliflozin 10mg',                'generic_name'=>'empagliflozin',               'medicine_category_id'=>$g('Antidiabetics'),    'dosage_form'=>'Tablet', 'strength'=>'10mg',  'unit'=>'Tab'],
            ['name'=>'Tab Apixaban 5mg',                      'generic_name'=>'apixaban',                    'medicine_category_id'=>$g('Anticoagulants'),   'dosage_form'=>'Tablet', 'strength'=>'5mg',   'unit'=>'Tab'],
            ['name'=>'Tab Rivaroxaban 20mg',                  'generic_name'=>'rivaroxaban',                 'medicine_category_id'=>$g('Anticoagulants'),   'dosage_form'=>'Tablet', 'strength'=>'20mg',  'unit'=>'Tab'],

            // ─── Endocrine ──────────────────────────────────────────────────────
            ['name'=>'Tab Carbimazole 5mg',           'generic_name'=>'carbimazole',     'medicine_category_id'=>$g('Hormones'),      'dosage_form'=>'Tablet', 'strength'=>'5mg',   'unit'=>'Tab'],
            ['name'=>'Tab Carbimazole 10mg',          'generic_name'=>'carbimazole',     'medicine_category_id'=>$g('Hormones'),      'dosage_form'=>'Tablet', 'strength'=>'10mg',  'unit'=>'Tab'],
            ['name'=>'Tab Pioglitazone 15mg',         'generic_name'=>'pioglitazone',    'medicine_category_id'=>$g('Antidiabetics'), 'dosage_form'=>'Tablet', 'strength'=>'15mg',  'unit'=>'Tab'],
            ['name'=>'Tab Pioglitazone 30mg',         'generic_name'=>'pioglitazone',    'medicine_category_id'=>$g('Antidiabetics'), 'dosage_form'=>'Tablet', 'strength'=>'30mg',  'unit'=>'Tab'],
            ['name'=>'Tab Vitamin E 400mg',           'generic_name'=>'tocopherol',      'medicine_category_id'=>$g('Vitamins & Supplements'),'dosage_form'=>'Capsule','strength'=>'400mg','unit'=>'Cap'],

            // ─── Liver ──────────────────────────────────────────────────────────
            ['name'=>'Tab Ursodeoxycholic Acid 300mg', 'generic_name'=>'ursodeoxycholic acid', 'medicine_category_id'=>$g('Gastrointestinal'),'dosage_form'=>'Tablet','strength'=>'300mg','unit'=>'Tab'],
            ['name'=>'Tab Rifaximin 550mg',            'generic_name'=>'rifaximin',            'medicine_category_id'=>$g('Antibiotics'),     'dosage_form'=>'Tablet','strength'=>'550mg','unit'=>'Tab'],
            ['name'=>'Syp Lactulose 200ml',            'generic_name'=>'lactulose',            'medicine_category_id'=>$g('Gastrointestinal'),'dosage_form'=>'Syrup', 'strength'=>'10g/15ml','unit'=>'ml'],
            ['name'=>'Tab Propranolol 40mg',           'generic_name'=>'propranolol',          'medicine_category_id'=>$g('Cardiovascular'),  'dosage_form'=>'Tablet','strength'=>'40mg','unit'=>'Tab'],

            // ─── Kidney ─────────────────────────────────────────────────────────
            ['name'=>'Tab Cholecalciferol 60K IU',  'generic_name'=>'vitamin d3',     'medicine_category_id'=>$g('Vitamins & Supplements'),'dosage_form'=>'Sachet','strength'=>'60000 IU','unit'=>'Sachet'],
            ['name'=>'Tab Calcitriol 0.25mcg',      'generic_name'=>'calcitriol',     'medicine_category_id'=>$g('Renal'),                 'dosage_form'=>'Capsule','strength'=>'0.25mcg','unit'=>'Cap'],
            ['name'=>'Tab Sevelamer 800mg',         'generic_name'=>'sevelamer',      'medicine_category_id'=>$g('Renal'),                 'dosage_form'=>'Tablet','strength'=>'800mg','unit'=>'Tab'],
            ['name'=>'Tab Calcium Acetate 667mg',   'generic_name'=>'calcium acetate','medicine_category_id'=>$g('Renal'),                 'dosage_form'=>'Tablet','strength'=>'667mg','unit'=>'Tab'],
            ['name'=>'Tab Tamsulosin 0.4mg',        'generic_name'=>'tamsulosin',     'medicine_category_id'=>$g('Renal'),                 'dosage_form'=>'Tablet','strength'=>'0.4mg','unit'=>'Tab'],
            ['name'=>'Tab Diclofenac 50mg',         'generic_name'=>'diclofenac',     'medicine_category_id'=>$g('Analgesics'),            'dosage_form'=>'Tablet','strength'=>'50mg','unit'=>'Tab'],

            // ─── Neurological ───────────────────────────────────────────────────
            ['name'=>'Tab Levetiracetam 500mg',    'generic_name'=>'levetiracetam',     'medicine_category_id'=>$g('Neurological'), 'dosage_form'=>'Tablet','strength'=>'500mg','unit'=>'Tab'],
            ['name'=>'Tab Sodium Valproate 500mg', 'generic_name'=>'sodium valproate',  'medicine_category_id'=>$g('Neurological'), 'dosage_form'=>'Tablet','strength'=>'500mg','unit'=>'Tab'],
            ['name'=>'Tab Carbamazepine 200mg',    'generic_name'=>'carbamazepine',     'medicine_category_id'=>$g('Neurological'), 'dosage_form'=>'Tablet','strength'=>'200mg','unit'=>'Tab'],

            // ─── Respiratory / Antibiotics ──────────────────────────────────────
            ['name'=>'MDI Tiotropium 18mcg',                  'generic_name'=>'tiotropium',               'medicine_category_id'=>$g('Respiratory'), 'dosage_form'=>'Inhaler','strength'=>'18mcg','unit'=>'puff'],
            ['name'=>'Cap Doxycycline 100mg',                 'generic_name'=>'doxycycline',              'medicine_category_id'=>$g('Antibiotics'), 'dosage_form'=>'Capsule','strength'=>'100mg','unit'=>'Cap'],
            ['name'=>'Tab Amoxicillin + Clavulanate 625mg',   'generic_name'=>'amoxicillin/clavulanate',  'medicine_category_id'=>$g('Antibiotics'), 'dosage_form'=>'Tablet','strength'=>'625mg','unit'=>'Tab'],
            ['name'=>'Tab Clindamycin 300mg',                 'generic_name'=>'clindamycin',              'medicine_category_id'=>$g('Antibiotics'), 'dosage_form'=>'Capsule','strength'=>'300mg','unit'=>'Cap'],
            ['name'=>'MDI Budesonide + Formoterol 200/6mcg',  'generic_name'=>'budesonide/formoterol',    'medicine_category_id'=>$g('Respiratory'), 'dosage_form'=>'Inhaler','strength'=>'200/6mcg','unit'=>'puff'],

            // ─── Dermatology / Topicals ─────────────────────────────────────────
            ['name'=>'Gel Adapalene 0.1%',          'generic_name'=>'adapalene',         'medicine_category_id'=>$g('Dermatological'), 'dosage_form'=>'Gel','strength'=>'0.1%','unit'=>'g'],
            ['name'=>'Gel Benzoyl Peroxide 2.5%',   'generic_name'=>'benzoyl peroxide',  'medicine_category_id'=>$g('Dermatological'), 'dosage_form'=>'Gel','strength'=>'2.5%','unit'=>'g'],
            ['name'=>'Cream Mometasone 0.1%',       'generic_name'=>'mometasone',        'medicine_category_id'=>$g('Dermatological'), 'dosage_form'=>'Cream','strength'=>'0.1%','unit'=>'g'],
            ['name'=>'Cream Hydrocortisone 1%',     'generic_name'=>'hydrocortisone',    'medicine_category_id'=>$g('Dermatological'), 'dosage_form'=>'Cream','strength'=>'1%','unit'=>'g'],
            ['name'=>'Cream Clotrimazole 1%',       'generic_name'=>'clotrimazole',      'medicine_category_id'=>$g('Dermatological'), 'dosage_form'=>'Cream','strength'=>'1%','unit'=>'g'],
            ['name'=>'Cream Terbinafine 1%',        'generic_name'=>'terbinafine',       'medicine_category_id'=>$g('Dermatological'), 'dosage_form'=>'Cream','strength'=>'1%','unit'=>'g'],
            ['name'=>'Tab Itraconazole 100mg',      'generic_name'=>'itraconazole',      'medicine_category_id'=>$g('Antifungals'),    'dosage_form'=>'Capsule','strength'=>'100mg','unit'=>'Cap'],
            ['name'=>'Tab Fluconazole 150mg',       'generic_name'=>'fluconazole',       'medicine_category_id'=>$g('Antifungals'),    'dosage_form'=>'Tablet','strength'=>'150mg','unit'=>'Tab'],
            ['name'=>'Tab Fexofenadine 180mg',      'generic_name'=>'fexofenadine',      'medicine_category_id'=>$g('Antihistamines'), 'dosage_form'=>'Tablet','strength'=>'180mg','unit'=>'Tab'],

            // ─── Orthopedic / Pain ──────────────────────────────────────────────
            ['name'=>'Gel Diclofenac 1%',                       'generic_name'=>'diclofenac',     'medicine_category_id'=>$g('Topicals'),  'dosage_form'=>'Gel','strength'=>'1%','unit'=>'g'],
            ['name'=>'Tab Tramadol 50mg',                       'generic_name'=>'tramadol',       'medicine_category_id'=>$g('Analgesics'),'dosage_form'=>'Tablet','strength'=>'50mg','unit'=>'Tab'],
            ['name'=>'Tab Thiocolchicoside 4mg',                'generic_name'=>'thiocolchicoside','medicine_category_id'=>$g('Analgesics'),'dosage_form'=>'Tablet','strength'=>'4mg','unit'=>'Tab'],
            ['name'=>'Tab Eperisone 50mg',                      'generic_name'=>'eperisone',      'medicine_category_id'=>$g('Analgesics'),'dosage_form'=>'Tablet','strength'=>'50mg','unit'=>'Tab'],
            ['name'=>'Tab Glucosamine 750mg + Chondroitin 600mg','generic_name'=>'glucosamine + chondroitin','medicine_category_id'=>$g('Vitamins & Supplements'),'dosage_form'=>'Tablet','strength'=>'750/600mg','unit'=>'Tab'],

            // ─── Gynaecology / Obstetrics ───────────────────────────────────────
            ['name'=>'Pessary Clotrimazole 100mg',                          'generic_name'=>'clotrimazole',                'medicine_category_id'=>$g('Antifungals'),    'dosage_form'=>'Pessary','strength'=>'100mg','unit'=>'Pessary'],
            ['name'=>'Tab Ethinyl Estradiol 0.03mg + Drospirenone 3mg',     'generic_name'=>'ethinyl estradiol/drospirenone','medicine_category_id'=>$g('Hormones'),     'dosage_form'=>'Tablet','strength'=>'0.03/3mg','unit'=>'Tab'],
            ['name'=>'Tab Doxylamine 10mg + Pyridoxine 10mg',               'generic_name'=>'doxylamine/pyridoxine',       'medicine_category_id'=>$g('Antihistamines'), 'dosage_form'=>'Tablet','strength'=>'10/10mg','unit'=>'Tab'],
            ['name'=>'Tab Ondansetron 4mg',                                 'generic_name'=>'ondansetron',                 'medicine_category_id'=>$g('Antihistamines'), 'dosage_form'=>'Tablet','strength'=>'4mg','unit'=>'Tab'],

            // ─── Dental ─────────────────────────────────────────────────────────
            ['name'=>'Mouthwash Chlorhexidine 0.2%',         'generic_name'=>'chlorhexidine',   'medicine_category_id'=>$g('Antibiotics'),  'dosage_form'=>'Mouthwash','strength'=>'0.2%','unit'=>'ml'],
            ['name'=>'Tab Metronidazole 400mg',              'generic_name'=>'metronidazole',   'medicine_category_id'=>$g('Antibiotics'),  'dosage_form'=>'Tablet','strength'=>'400mg','unit'=>'Tab'],

            // ─── Psychiatry ─────────────────────────────────────────────────────
            ['name'=>'Tab Sertraline 50mg',  'generic_name'=>'sertraline',  'medicine_category_id'=>$g('Neurological'), 'dosage_form'=>'Tablet','strength'=>'50mg','unit'=>'Tab'],
            ['name'=>'Tab Buspirone 5mg',    'generic_name'=>'buspirone',   'medicine_category_id'=>$g('Neurological'), 'dosage_form'=>'Tablet','strength'=>'5mg','unit'=>'Tab'],

            // ─── Top-up: useful basics ──────────────────────────────────────────
            ['name'=>'Sachet Potassium Citrate', 'generic_name'=>'potassium citrate','medicine_category_id'=>$g('Renal'),'dosage_form'=>'Sachet','strength'=>'1g','unit'=>'Sachet'],
            ['name'=>'Tab Ferrous Ascorbate 100mg','generic_name'=>'ferrous ascorbate','medicine_category_id'=>$g('Vitamins & Supplements'),'dosage_form'=>'Tablet','strength'=>'100mg','unit'=>'Tab'],
            ['name'=>'Tab Calcium Carbonate 500mg + Vitamin D3 250 IU','generic_name'=>'calcium carbonate + vitamin d3','medicine_category_id'=>$g('Vitamins & Supplements'),'dosage_form'=>'Tablet','strength'=>'500mg + 250IU','unit'=>'Tab'],
            ['name'=>'Tab Sumatriptan 50mg','generic_name'=>'sumatriptan','medicine_category_id'=>$g('Neurological'),'dosage_form'=>'Tablet','strength'=>'50mg','unit'=>'Tab'],
            ['name'=>'Tab Naproxen 500mg','generic_name'=>'naproxen','medicine_category_id'=>$g('Analgesics'),'dosage_form'=>'Tablet','strength'=>'500mg','unit'=>'Tab'],
        ];

        $upserted = 0;
        foreach ($medicines as $m) {
            $m['is_active']     = true;
            $m['unit_price']    = $m['unit_price']    ?? 0;
            $m['reorder_level'] = $m['reorder_level'] ?? 10;
            Medicine::updateOrCreate(['name' => $m['name']], $m);
            $upserted++;
        }

        $this->command->info("Upserted {$upserted} medicines (and ensured 5 extra categories).");
    }
}
