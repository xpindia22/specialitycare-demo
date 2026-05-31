<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrescriptionTemplate;

class AdditionalPrescriptionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ─── AUTOIMMUNE / RHEUMATOLOGY ─────────────────────────────────────

            [
                'name' => 'Rheumatoid Arthritis — Maintenance',
                'items' => [
                    ['medicine_name'=>'Tab Methotrexate 7.5mg',     'dosage'=>'2 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'Once weekly only — same day each week, after food'],
                    ['medicine_name'=>'Tab Folic Acid 5mg',         'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'On non-MTX days'],
                    ['medicine_name'=>'Tab Hydroxychloroquine 200mg','dosage'=>'1 Tab','frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Prednisolone 5mg',       'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'14', 'instructions'=>'After breakfast — taper as advised'],
                ],
            ],
            [
                'name' => 'SLE — Maintenance',
                'items' => [
                    ['medicine_name'=>'Tab Hydroxychloroquine 200mg','dosage'=>'1 Tab','frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food — annual eye exam'],
                    ['medicine_name'=>'Tab Prednisolone 5mg',       'dosage'=>'1 Tab', 'frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After breakfast'],
                    ['medicine_name'=>'Tab Mycophenolate Mofetil 500mg','dosage'=>'2 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Calcium Carbonate 500mg + Vitamin D3 250 IU','dosage'=>'1 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Gout — Acute Flare',
                'items' => [
                    ['medicine_name'=>'Tab Colchicine 0.5mg',  'dosage'=>'1 Tab', 'frequency'=>'BD',  'duration'=>'7',  'instructions'=>'After food — reduce in CKD'],
                    ['medicine_name'=>'Tab Naproxen 500mg',    'dosage'=>'1 Tab', 'frequency'=>'BD',  'duration'=>'5',  'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Pantoprazole 40mg', 'dosage'=>'1 Tab', 'frequency'=>'OD',  'duration'=>'7',  'instructions'=>'Before breakfast'],
                    ['medicine_name'=>'Tab Febuxostat 40mg',   'dosage'=>'1 Tab', 'frequency'=>'OD',  'duration'=>'30', 'instructions'=>'After food — START ONLY after flare settles'],
                ],
            ],

            // ─── CARDIAC ───────────────────────────────────────────────────────

            [
                'name' => 'Heart Failure — HFrEF GDMT',
                'items' => [
                    ['medicine_name'=>'Tab Sacubitril + Valsartan 50mg', 'dosage'=>'1 Tab','frequency'=>'BD', 'duration'=>'30', 'instructions'=>'After food — monitor BP/K+/RFT'],
                    ['medicine_name'=>'Tab Bisoprolol 2.5mg',           'dosage'=>'1 Tab','frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After breakfast'],
                    ['medicine_name'=>'Tab Spironolactone 25mg',        'dosage'=>'1 Tab','frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After breakfast — monitor K+'],
                    ['medicine_name'=>'Tab Dapagliflozin 10mg',         'dosage'=>'1 Tab','frequency'=>'OD', 'duration'=>'30', 'instructions'=>'After breakfast'],
                    ['medicine_name'=>'Tab Furosemide 40mg',            'dosage'=>'1 Tab','frequency'=>'OD', 'duration'=>'30', 'instructions'=>'Morning — adjust per weight'],
                ],
            ],
            [
                'name' => 'Atrial Fibrillation — Anticoagulation',
                'items' => [
                    ['medicine_name'=>'Tab Apixaban 5mg',         'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food — do not skip / double dose'],
                    ['medicine_name'=>'Tab Bisoprolol 5mg',       'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast'],
                    ['medicine_name'=>'Tab Atorvastatin 40mg',    'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'30','instructions'=>'At bedtime'],
                ],
            ],

            // ─── LIVER ─────────────────────────────────────────────────────────

            [
                'name' => 'Cirrhosis — Maintenance',
                'items' => [
                    ['medicine_name'=>'Tab Propranolol 40mg',      'dosage'=>'1 Tab','frequency'=>'BD', 'duration'=>'30','instructions'=>'After food — titrate to HR ~55-60'],
                    ['medicine_name'=>'Syp Lactulose 200ml',       'dosage'=>'15 ml','frequency'=>'TDS','duration'=>'30','instructions'=>'Aim for 2-3 soft stools/day'],
                    ['medicine_name'=>'Tab Rifaximin 550mg',       'dosage'=>'1 Tab','frequency'=>'BD', 'duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Spironolactone 25mg',   'dosage'=>'4 Tab','frequency'=>'OD', 'duration'=>'30','instructions'=>'After breakfast'],
                    ['medicine_name'=>'Tab Furosemide 40mg',       'dosage'=>'1 Tab','frequency'=>'OD', 'duration'=>'30','instructions'=>'Morning'],
                ],
            ],
            [
                'name' => 'NAFLD',
                'items' => [
                    ['medicine_name'=>'Tab Vitamin E 400mg',                'dosage'=>'1 Cap','frequency'=>'OD','duration'=>'90','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Ursodeoxycholic Acid 300mg',     'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'90','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Pioglitazone 15mg',              'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'90','instructions'=>'After breakfast (if biopsy-proven NASH + DM)'],
                ],
            ],

            // ─── KIDNEY ────────────────────────────────────────────────────────

            [
                'name' => 'CKD — Stage 3-4 Maintenance',
                'items' => [
                    ['medicine_name'=>'Tab Telmisartan 40mg',       'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast — monitor K+'],
                    ['medicine_name'=>'Tab Dapagliflozin 10mg',     'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast'],
                    ['medicine_name'=>'Tab Atorvastatin 20mg',      'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'30','instructions'=>'At bedtime'],
                    ['medicine_name'=>'Tab Calcitriol 0.25mcg',     'dosage'=>'1 Cap','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Sevelamer 800mg',        'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'30','instructions'=>'With each meal'],
                    ['medicine_name'=>'Tab Ferrous Ascorbate 100mg','dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Renal Colic — Acute',
                'items' => [
                    ['medicine_name'=>'Tab Diclofenac 50mg',     'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'5', 'instructions'=>'After food'],
                    ['medicine_name'=>'Tab Tamsulosin 0.4mg',    'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'30','instructions'=>'At bedtime — postural drop caution'],
                    ['medicine_name'=>'Tab Ondansetron 4mg',     'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'3','instructions'=>'For nausea — SOS'],
                    ['medicine_name'=>'Sachet Potassium Citrate','dosage'=>'1 Sachet','frequency'=>'BD','duration'=>'30','instructions'=>'In a glass of water'],
                ],
            ],

            // ─── ENDOCRINE ─────────────────────────────────────────────────────

            [
                'name' => 'Hypothyroidism — Replacement',
                'items' => [
                    ['medicine_name'=>'Tab Levothyroxine 50mcg', 'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'Empty stomach — 30-60 min before breakfast'],
                ],
            ],
            [
                'name' => "Hyperthyroidism — Graves'",
                'items' => [
                    ['medicine_name'=>'Tab Carbimazole 10mg',  'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food — taper per TFT'],
                    ['medicine_name'=>'Tab Propranolol 40mg',  'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'14','instructions'=>'After food — for symptoms'],
                ],
            ],

            // ─── NEUROLOGY ─────────────────────────────────────────────────────

            [
                'name' => 'Epilepsy — Maintenance',
                'items' => [
                    ['medicine_name'=>'Tab Levetiracetam 500mg', 'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food — do not stop abruptly'],
                    ['medicine_name'=>'Tab Folic Acid 5mg',      'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Stroke — Secondary Prevention',
                'items' => [
                    ['medicine_name'=>'Tab Aspirin 75mg',      'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After dinner'],
                    ['medicine_name'=>'Tab Clopidogrel 75mg',  'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast (if dual)'],
                    ['medicine_name'=>'Tab Atorvastatin 80mg', 'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'30','instructions'=>'At bedtime'],
                    ['medicine_name'=>'Tab Telmisartan 40mg',  'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast'],
                ],
            ],

            // ─── RESPIRATORY ───────────────────────────────────────────────────

            [
                'name' => 'COPD — Stable',
                'items' => [
                    ['medicine_name'=>'MDI Tiotropium 18mcg',                  'dosage'=>'1 puff', 'frequency'=>'OD', 'duration'=>'30','instructions'=>'Once daily — same time'],
                    ['medicine_name'=>'MDI Budesonide + Formoterol 200/6mcg',  'dosage'=>'2 puffs','frequency'=>'BD', 'duration'=>'30','instructions'=>'Inhale — rinse mouth after'],
                    ['medicine_name'=>'MDI Salbutamol 100mcg',                 'dosage'=>'2 puffs','frequency'=>'SOS','duration'=>'30','instructions'=>'For breathlessness'],
                ],
            ],
            [
                'name' => 'Community-Acquired Pneumonia — Outpatient',
                'items' => [
                    ['medicine_name'=>'Tab Amoxicillin + Clavulanate 625mg', 'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'7','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Azithromycin 500mg',              'dosage'=>'1 Tab','frequency'=>'OD', 'duration'=>'5','instructions'=>'After food — for atypical cover'],
                    ['medicine_name'=>'Tab Paracetamol 500mg',               'dosage'=>'1 Tab','frequency'=>'QID','duration'=>'5','instructions'=>'For fever — SOS'],
                    ['medicine_name'=>'Tab Ambroxol 30mg',                   'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'5','instructions'=>'After food'],
                ],
            ],

            // ─── DERMATOLOGY ───────────────────────────────────────────────────

            [
                'name' => 'Acne Vulgaris — Moderate',
                'items' => [
                    ['medicine_name'=>'Gel Adapalene 0.1%',         'dosage'=>'Pea size','frequency'=>'HS','duration'=>'90','instructions'=>'At night to dry skin'],
                    ['medicine_name'=>'Gel Benzoyl Peroxide 2.5%',  'dosage'=>'Pea size','frequency'=>'OD','duration'=>'90','instructions'=>'Morning — may bleach clothing'],
                    ['medicine_name'=>'Cap Doxycycline 100mg',      'dosage'=>'1 Cap',   'frequency'=>'OD','duration'=>'60','instructions'=>'After food — avoid sun'],
                ],
            ],
            [
                'name' => 'Atopic Dermatitis / Eczema',
                'items' => [
                    ['medicine_name'=>'Cream Mometasone 0.1%',    'dosage'=>'Thin layer','frequency'=>'OD','duration'=>'14','instructions'=>'At night to affected areas'],
                    ['medicine_name'=>'Cream Hydrocortisone 1%',  'dosage'=>'Thin layer','frequency'=>'BD','duration'=>'14','instructions'=>'For face / flexures'],
                    ['medicine_name'=>'Tab Levocetirizine 5mg',   'dosage'=>'1 Tab',     'frequency'=>'HS','duration'=>'14','instructions'=>'At bedtime'],
                ],
            ],
            [
                'name' => 'Tinea Corporis',
                'items' => [
                    ['medicine_name'=>'Cream Terbinafine 1%',  'dosage'=>'Thin layer','frequency'=>'BD','duration'=>'30','instructions'=>'Continue 1 week after lesion clears'],
                    ['medicine_name'=>'Tab Itraconazole 100mg','dosage'=>'1 Cap',     'frequency'=>'BD','duration'=>'21','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Fexofenadine 180mg','dosage'=>'1 Tab',     'frequency'=>'OD','duration'=>'14','instructions'=>'For itch'],
                ],
            ],

            // ─── ORTHOPEDICS ───────────────────────────────────────────────────

            [
                'name' => 'Osteoarthritis — Knee',
                'items' => [
                    ['medicine_name'=>'Gel Diclofenac 1%',                            'dosage'=>'2 g','frequency'=>'TDS','duration'=>'14','instructions'=>'Apply over knee'],
                    ['medicine_name'=>'Tab Diclofenac 50mg',                          'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'7','instructions'=>'After food — short course'],
                    ['medicine_name'=>'Tab Pantoprazole 40mg',                        'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'7','instructions'=>'Before breakfast'],
                    ['medicine_name'=>'Tab Glucosamine 750mg + Chondroitin 600mg',    'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'90','instructions'=>'After food — 3 month trial'],
                ],
            ],
            [
                'name' => 'Acute Low Back Pain',
                'items' => [
                    ['medicine_name'=>'Tab Diclofenac 50mg',         'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'5','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Thiocolchicoside 4mg',    'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'5','instructions'=>'After food — muscle relaxant'],
                    ['medicine_name'=>'Tab Pantoprazole 40mg',       'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'5','instructions'=>'Before breakfast'],
                    ['medicine_name'=>'Gel Diclofenac 1%',           'dosage'=>'2 g','frequency'=>'TDS','duration'=>'7','instructions'=>'Apply locally'],
                ],
            ],

            // ─── GYNAECOLOGY / OBSTETRICS ──────────────────────────────────────

            [
                'name' => 'Vaginal Candidiasis',
                'items' => [
                    ['medicine_name'=>'Tab Fluconazole 150mg',      'dosage'=>'1 Tab',    'frequency'=>'OD','duration'=>'1','instructions'=>'Single dose'],
                    ['medicine_name'=>'Pessary Clotrimazole 100mg', 'dosage'=>'1 Pessary','frequency'=>'HS','duration'=>'6','instructions'=>'Insert vaginally at bedtime'],
                    ['medicine_name'=>'Cream Clotrimazole 1%',      'dosage'=>'Thin layer','frequency'=>'BD','duration'=>'7','instructions'=>'Apply to vulva'],
                ],
            ],
            [
                'name' => 'PCOS',
                'items' => [
                    ['medicine_name'=>'Tab Ethinyl Estradiol 0.03mg + Drospirenone 3mg', 'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'21','instructions'=>'Same time daily — 21 on 7 off'],
                    ['medicine_name'=>'Tab Metformin 500mg',                              'dosage'=>'1 Tab','frequency'=>'BD','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Folic Acid 5mg',                               'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                ],
            ],
            [
                'name' => 'Nausea & Vomiting in Pregnancy',
                'items' => [
                    ['medicine_name'=>'Tab Doxylamine 10mg + Pyridoxine 10mg', 'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'14','instructions'=>'At bedtime'],
                    ['medicine_name'=>'Tab Ondansetron 4mg',                   'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'7','instructions'=>'For breakthrough vomiting — SOS'],
                    ['medicine_name'=>'Tab Folic Acid 5mg',                    'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After food'],
                ],
            ],

            // ─── DENTAL ────────────────────────────────────────────────────────

            [
                'name' => 'Dental Abscess — Antibiotic Cover',
                'items' => [
                    ['medicine_name'=>'Tab Amoxicillin + Clavulanate 625mg', 'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'5','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Metronidazole 400mg',             'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'5','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Paracetamol 500mg',               'dosage'=>'1 Tab','frequency'=>'QID','duration'=>'5','instructions'=>'For pain — SOS'],
                    ['medicine_name'=>'Mouthwash Chlorhexidine 0.2%',        'dosage'=>'10 ml','frequency'=>'BD','duration'=>'7','instructions'=>'Rinse 30 sec — do not swallow'],
                ],
            ],

            // ─── PSYCHIATRY ────────────────────────────────────────────────────

            [
                'name' => 'Depression — Moderate',
                'items' => [
                    ['medicine_name'=>'Tab Sertraline 50mg',     'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast — onset 4-6 weeks'],
                    ['medicine_name'=>'Tab Clonazepam 0.25mg',   'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'14','instructions'=>'At bedtime — short term only'],
                ],
            ],
            [
                'name' => 'Generalised Anxiety Disorder',
                'items' => [
                    ['medicine_name'=>'Tab Escitalopram 10mg',  'dosage'=>'1 Tab','frequency'=>'OD','duration'=>'30','instructions'=>'After breakfast — onset 4-6 weeks'],
                    ['medicine_name'=>'Tab Buspirone 5mg',      'dosage'=>'1 Tab','frequency'=>'TDS','duration'=>'30','instructions'=>'After food'],
                    ['medicine_name'=>'Tab Clonazepam 0.25mg',  'dosage'=>'1 Tab','frequency'=>'HS','duration'=>'14','instructions'=>'At bedtime — short term only'],
                ],
            ],

        ];

        $upserted = 0;
        foreach ($templates as $tpl) {
            PrescriptionTemplate::updateOrCreate(
                ['name' => $tpl['name']],
                ['doctor_id' => null, 'items' => $tpl['items'], 'created_by' => null]
            );
            $upserted++;
        }

        $this->command->info("Upserted {$upserted} additional prescription templates.");
    }
}
