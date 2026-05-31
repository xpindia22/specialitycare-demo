<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrescriptionTemplate;

class PrescriptionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ─── CARDIAC ─────────────────────────────────────────────────────────────

            [
                'name' => 'Hypertension — Stage 1',
                'items' => [
                    ['medicine_name'=>'Tab Amlodipine 5mg',  'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Losartan 50mg',   'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Hypertension — Stage 2',
                'items' => [
                    ['medicine_name'=>'Tab Amlodipine 10mg',           'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Losartan 100mg',            'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Hydrochlorothiazide 12.5mg','dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Hypertension — Add-on (Triple)',
                'items' => [
                    ['medicine_name'=>'Tab Amlodipine 10mg',  'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Telmisartan 80mg', 'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Chlorthalidone 12.5mg','dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Atorvastatin 20mg','dosage'=>'1 Tab', 'frequency'=>'HS', 'duration'=>'30', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Stable Angina / CAD',
                'items' => [
                    ['medicine_name'=>'Tab Aspirin 75mg',               'dosage'=>'1 Tab',   'frequency'=>'OD',  'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Atorvastatin 40mg',          'dosage'=>'1 Tab',   'frequency'=>'HS',  'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Metoprolol 25mg',            'dosage'=>'1 Tab',   'frequency'=>'BD',  'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Isosorbide Mononitrate 20mg','dosage'=>'1 Tab',   'frequency'=>'BD',  'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Nitroglycerine 0.5mg',       'dosage'=>'1 Tab SL','frequency'=>'SOS', 'duration'=>'30', 'instructions'=>'Sublingual — for chest pain'],
                ],
            ],
            [
                'name' => 'Post-MI Discharge',
                'items' => [
                    ['medicine_name'=>'Tab Aspirin 75mg',      'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Clopidogrel 75mg',  'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Atorvastatin 80mg', 'dosage'=>'1 Tab', 'frequency'=>'HS', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Metoprolol 25mg',   'dosage'=>'1 Tab', 'frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Ramipril 2.5mg',    'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Pantoprazole 40mg', 'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'Before food'],
                ],
            ],
            [
                'name' => 'Heart Failure — HFrEF',
                'items' => [
                    ['medicine_name'=>'Tab Carvedilol 3.125mg', 'dosage'=>'1 Tab', 'frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Enalapril 5mg',      'dosage'=>'1 Tab', 'frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Furosemide 40mg',    'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Spironolactone 25mg','dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Atrial Fibrillation — Rate Control',
                'items' => [
                    ['medicine_name'=>'Tab Metoprolol 25mg', 'dosage'=>'1 Tab',   'frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Digoxin 0.25mg',  'dosage'=>'1 Tab',   'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Warfarin 5mg',    'dosage'=>'1 Tab',   'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food — dose per INR'],
                ],
            ],
            [
                'name' => 'Atrial Fibrillation — Rhythm Control',
                'items' => [
                    ['medicine_name'=>'Tab Amiodarone 200mg','dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Warfarin 5mg',   'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food — dose per INR'],
                ],
            ],
            [
                'name' => 'Dyslipidemia',
                'items' => [
                    ['medicine_name'=>'Tab Atorvastatin 20mg', 'dosage'=>'1 Tab', 'frequency'=>'HS', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Cap Fenofibrate 145mg', 'dosage'=>'1 Cap', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Cardiac Oedema / Volume Overload',
                'items' => [
                    ['medicine_name'=>'Tab Furosemide 40mg',        'dosage'=>'1 Tab', 'frequency'=>'BD', 'duration'=>'7',  'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Spironolactone 25mg',    'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Potassium Chloride 600mg','dosage'=>'1 Tab','frequency'=>'BD', 'duration'=>'7',  'instructions'=>'After food'],
                ],
            ],

            // ─── GENERAL MEDICINE ────────────────────────────────────────────────────

            [
                'name' => 'Viral Fever / URTI',
                'items' => [
                    ['medicine_name'=>'Tab Paracetamol 500mg','dosage'=>'1 Tab', 'frequency'=>'TDS', 'duration'=>'5',  'instructions'=>'After food — for fever > 38.5°C'],
                    ['medicine_name'=>'Tab Cetirizine 10mg',  'dosage'=>'1 Tab', 'frequency'=>'OD',  'duration'=>'5',  'instructions'=>'At night'],
                    ['medicine_name'=>'Tab Vitamin C 500mg',  'dosage'=>'1 Tab', 'frequency'=>'OD',  'duration'=>'10', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Gastritis / GERD / Acidity',
                'items' => [
                    ['medicine_name'=>'Tab Pantoprazole 40mg','dosage'=>'1 Tab', 'frequency'=>'OD',  'duration'=>'14', 'instructions'=>'Before food — empty stomach'],
                    ['medicine_name'=>'Tab Domperidone 10mg', 'dosage'=>'1 Tab', 'frequency'=>'TDS', 'duration'=>'7',  'instructions'=>'Before food'],
                    ['medicine_name'=>'Tab Sucralfate 1g',    'dosage'=>'1 Tab', 'frequency'=>'BD',  'duration'=>'14', 'instructions'=>'Before food'],
                ],
            ],
            [
                'name' => 'Diabetes Type 2 — Newly Diagnosed',
                'items' => [
                    ['medicine_name'=>'Tab Metformin 500mg',  'dosage'=>'1 Tab',   'frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Glipizide 5mg',    'dosage'=>'1 Tab',   'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'Before food'],
                    ['medicine_name'=>'Tab Vitamin B12 500mcg','dosage'=>'1 Tab',  'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Hypothyroidism',
                'items' => [
                    ['medicine_name'=>'Tab Levothyroxine 50mcg','dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'Empty stomach — 30 min before breakfast'],
                ],
            ],
            [
                'name' => 'UTI — Female',
                'items' => [
                    ['medicine_name'=>'Cap Nitrofurantoin 100mg','dosage'=>'1 Cap','frequency'=>'BD','duration'=>'7','instructions'=>'After food'],
                    ['medicine_name'=>'Cap Cranberry 400mg',     'dosage'=>'1 Cap','frequency'=>'BD','duration'=>'7','instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Respiratory Infection / LRTI',
                'items' => [
                    ['medicine_name'=>'Cap Amoxicillin 500mg',  'dosage'=>'1 Cap', 'frequency'=>'TDS', 'duration'=>'7', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Azithromycin 500mg', 'dosage'=>'1 Tab', 'frequency'=>'OD',  'duration'=>'5', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Salbutamol 4mg',     'dosage'=>'1 Tab', 'frequency'=>'TDS', 'duration'=>'5', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Ambroxol 30mg',      'dosage'=>'1 Tab', 'frequency'=>'TDS', 'duration'=>'5', 'instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Asthma — Mild Exacerbation',
                'items' => [
                    ['medicine_name'=>'MDI Salbutamol 100mcg', 'dosage'=>'2 puffs','frequency'=>'SOS','duration'=>'30','instructions'=>'Inhale — as needed for breathlessness'],
                    ['medicine_name'=>'MDI Budesonide 200mcg', 'dosage'=>'2 puffs','frequency'=>'BD', 'duration'=>'30','instructions'=>'Inhale — rinse mouth after use'],
                    ['medicine_name'=>'Tab Montelukast 10mg',  'dosage'=>'1 Tab',  'frequency'=>'HS', 'duration'=>'30','instructions'=>'At night'],
                ],
            ],
            [
                'name' => 'Iron Deficiency Anaemia',
                'items' => [
                    ['medicine_name'=>'Tab Ferrous Sulfate 200mg','dosage'=>'1 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Folic Acid 5mg',       'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Vitamin B12 500mcg',   'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Vitamin C 500mg',      'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food — aids iron absorption'],
                ],
            ],
            [
                'name' => 'Anxiety / Insomnia',
                'items' => [
                    ['medicine_name'=>'Tab Clonazepam 0.25mg', 'dosage'=>'1 Tab', 'frequency'=>'HS', 'duration'=>'14', 'instructions'=>'At night'],
                    ['medicine_name'=>'Tab Escitalopram 10mg', 'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food — morning'],
                ],
            ],
        ];

        $commonAddons = [
            ['medicine_name'=>'Tab Zinolvita',         'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
            ['medicine_name'=>'Cap Omegasoft',         'dosage'=>'1 Cap', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
            ['medicine_name'=>'Tab Shelcal',           'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After food'],
            ['medicine_name'=>'Tab Pantoprazole 40mg', 'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'Before food — empty stomach'],
        ];

        $upserted = 0;
        foreach ($templates as $tpl) {
            $existing = collect($tpl['items'])->pluck('medicine_name')->map(fn($n) => strtolower($n));
            foreach ($commonAddons as $addon) {
                if (!$existing->contains(strtolower($addon['medicine_name']))) {
                    $tpl['items'][] = $addon;
                }
            }
            PrescriptionTemplate::updateOrCreate(
                ['name' => $tpl['name']],
                ['doctor_id' => null, 'items' => $tpl['items'], 'created_by' => null]
            );
            $upserted++;
        }

        $this->command->info("Upserted {$upserted} prescription templates.");
    }
}
