<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EncounterTemplate;

class EncounterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ─── GENERAL MEDICINE ────────────────────────────────────────────────────

            [
                'name'            => 'Hypertension — Follow-up',
                'specialization'  => 'General Medicine',
                'chief_complaint' => 'Routine BP check. No chest pain, no breathlessness, no palpitations.',
                'clinical_notes'  => "S: Known case of HTN on regular medication. Compliant. Denies headache, blurring of vision, chest pain.\nO: Conscious, oriented. CVS – S1 S2 normal, no murmur. RS – clear. Abdomen – soft.\nA: Hypertension — controlled / partially controlled.\nP: Continue current antihypertensives. Salt restriction (<5g/day), regular exercise. Review in 4 weeks with home BP log.",
                'diagnoses'       => ['Essential Hypertension (I10)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Amlodipine 5mg',  'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD', 'instructions'=>'After breakfast'],
                    ['drug_name'=>'Tab Telmisartan 40mg','dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD', 'instructions'=>'After breakfast'],
                ],
            ],

            [
                'name'            => 'Type 2 Diabetes — Follow-up',
                'specialization'  => 'General Medicine',
                'chief_complaint' => 'Routine diabetes review. No polyuria, polydipsia, or weight loss. No symptoms of hypoglycaemia.',
                'clinical_notes'  => "S: Known T2DM on OHA. Compliant with diet & medication. No hypoglycaemic episodes.\nO: Vitals stable. Feet – no ulcers, sensations intact. Fundus screen pending/normal.\nA: T2DM — glycaemic control adequate / suboptimal (per HbA1c).\nP: Continue current OHA. Reinforce diet, daily walk 30 min. Repeat FBS, PPBS, HbA1c in 3 months. Annual eye and foot exam.",
                'diagnoses'       => ['Type 2 Diabetes Mellitus (E11.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Metformin 500mg', 'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'BD',  'instructions'=>'After food'],
                    ['drug_name'=>'Tab Glimepiride 1mg', 'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'Before breakfast'],
                ],
            ],

            [
                'name'            => 'Viral Fever / URTI',
                'specialization'  => 'General Medicine',
                'chief_complaint' => 'Fever with body ache, sore throat, runny nose x 2-3 days.',
                'clinical_notes'  => "S: Low-to-moderate grade fever, myalgia, throat pain, nasal discharge. No breathing difficulty, no chest pain.\nO: Temp documented. Throat – mildly congested. CVS / RS – clear. No lymphadenopathy.\nA: Acute viral URTI.\nP: Symptomatic management. Adequate hydration & rest. Return if fever > 5 days, breathlessness, or worsening.",
                'diagnoses'       => ['Acute Upper Respiratory Tract Infection (J06.9)', 'Viral Fever (B34.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Paracetamol 500mg','dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'TDS', 'instructions'=>'After food — for fever > 38.5°C / body ache'],
                    ['drug_name'=>'Tab Cetirizine 10mg',  'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'HS',  'instructions'=>'At bedtime x 5 days'],
                    ['drug_name'=>'Tab Vitamin C 500mg',  'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'After food x 7 days'],
                ],
            ],

            [
                'name'            => 'Acute Gastritis / GERD',
                'specialization'  => 'General Medicine',
                'chief_complaint' => 'Burning epigastric pain, sour eructations, occasional nausea x few days.',
                'clinical_notes'  => "S: Epigastric burning, retrosternal discomfort, worse on empty stomach / lying down. No haematemesis, no melaena, no weight loss.\nO: Soft abdomen, mild epigastric tenderness. No guarding, no rigidity. Bowel sounds normal.\nA: Acute gastritis / GERD.\nP: PPI before food x 2 weeks. Avoid spicy/oily food, late meals, alcohol, smoking. Small frequent meals, head-end elevation. Endoscopy if symptoms persist > 4 weeks or alarm features.",
                'diagnoses'       => ['Gastro-Oesophageal Reflux Disease (K21.9)', 'Acute Gastritis (K29.7)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Pantoprazole 40mg','dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'30 min before breakfast x 14 days'],
                    ['drug_name'=>'Tab Domperidone 10mg', 'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'TDS', 'instructions'=>'Before food x 5 days'],
                    ['drug_name'=>'Syp Sucralfate',       'dose'=>'10 ml', 'route'=>'Oral', 'frequency'=>'TDS', 'instructions'=>'Before food x 14 days'],
                ],
            ],

            // ─── CARDIOLOGY ──────────────────────────────────────────────────────────

            [
                'name'            => 'Stable Angina / CAD — Review',
                'specialization'  => 'Cardiology',
                'chief_complaint' => 'Routine cardiac review. Occasional exertional chest discomfort relieved by rest.',
                'clinical_notes'  => "S: Known CAD. Currently NYHA Class I-II. No rest pain, no orthopnoea, no PND. Compliant with medications.\nO: BP / HR documented. CVS – S1 S2 normal, no murmur. RS – clear. Pedal pulses present.\nA: Stable Coronary Artery Disease.\nP: Continue DAPT / statin / beta-blocker / ACEi as below. Lifestyle measures, diet, walking. Repeat lipid profile in 3 months. Review with reports.",
                'diagnoses'       => ['Coronary Artery Disease (I25.10)', 'Stable Angina (I20.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Aspirin 75mg',                'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'After dinner'],
                    ['drug_name'=>'Tab Atorvastatin 40mg',           'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'HS',  'instructions'=>'At bedtime'],
                    ['drug_name'=>'Tab Metoprolol Succinate 25mg',   'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'After breakfast'],
                    ['drug_name'=>'Tab Ramipril 2.5mg',              'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'After breakfast'],
                    ['drug_name'=>'Tab Nitroglycerine 0.5mg',        'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'SOS', 'instructions'=>'Sublingual — for chest pain only'],
                ],
            ],

            // ─── RESPIRATORY ─────────────────────────────────────────────────────────

            [
                'name'            => 'Bronchial Asthma — Follow-up',
                'specialization'  => 'Pulmonology',
                'chief_complaint' => 'Episodic wheeze, cough, breathlessness — partly controlled.',
                'clinical_notes'  => "S: Known asthma. Frequency of symptoms / night awakenings / rescue inhaler use documented. No fever, no chest pain.\nO: SpO2 in air. RS – wheeze present / clear. PEFR documented if available.\nA: Bronchial Asthma — partly controlled.\nP: Step-up controller. Inhaler technique reinforced. Avoid triggers. Action plan given. Review in 4 weeks.",
                'diagnoses'       => ['Bronchial Asthma (J45.909)'],
                'prescription'    => [
                    ['drug_name'=>'MDI Budesonide + Formoterol 200/6mcg', 'dose'=>'2 puffs', 'route'=>'Inhaled', 'frequency'=>'BD',  'instructions'=>'Inhale — rinse mouth after use'],
                    ['drug_name'=>'MDI Salbutamol 100mcg',                'dose'=>'2 puffs', 'route'=>'Inhaled', 'frequency'=>'SOS', 'instructions'=>'For breathlessness — max 4 times/day'],
                    ['drug_name'=>'Tab Montelukast 10mg',                 'dose'=>'1 Tab',   'route'=>'Oral',    'frequency'=>'HS',  'instructions'=>'At bedtime'],
                ],
            ],

            // ─── GYNAECOLOGY ─────────────────────────────────────────────────────────

            [
                'name'            => 'Antenatal Checkup (Routine)',
                'specialization'  => 'Obstetrics & Gynaecology',
                'chief_complaint' => 'Routine antenatal visit. No bleeding PV, no leaking, foetal movements perceived well.',
                'clinical_notes'  => "S: G_P_L_, period of gestation _ weeks by LMP. No bleeding PV, no leaking, no abdominal pain. Foetal movements good.\nO: Wt / BP / Pallor / Pedal oedema documented. P/A – uterus per period of gestation, FHS heard / FM+. Symphyseal-fundal height noted.\nA: Antenatal — _ weeks, low-risk / high-risk pregnancy.\nP: Continue iron, calcium, folic acid. Routine ANC investigations as per gestational age. Tetanus toxoid as scheduled. Review in 4 weeks / earlier if any warning signs.",
                'diagnoses'       => ['Supervision of Normal Pregnancy (Z34.90)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Folic Acid 5mg',          'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD', 'instructions'=>'After food (1st trimester)'],
                    ['drug_name'=>'Tab Ferrous Ascorbate 100mg', 'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'OD', 'instructions'=>'After food (after 14 weeks)'],
                    ['drug_name'=>'Tab Calcium Carbonate 500mg + Vitamin D3 250 IU', 'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'BD', 'instructions'=>'After food'],
                ],
            ],

            // ─── NEUROLOGY ───────────────────────────────────────────────────────────

            [
                'name'            => 'Migraine — Acute',
                'specialization'  => 'General Medicine',
                'chief_complaint' => 'Recurrent throbbing unilateral headache with photophobia / phonophobia.',
                'clinical_notes'  => "S: Episodic unilateral throbbing headache, lasts hours, associated nausea, photo/phonophobia. Triggers — stress / sleep deprivation / fasting. No focal deficit, no fever, no neck stiffness.\nO: GCS 15. CNS – cranial nerves intact, no motor / sensory deficit, no meningeal signs. Fundus normal.\nA: Migraine without aura.\nP: Trigger avoidance, regular sleep & meals. Abortive Rx during attack. Headache diary. Consider prophylaxis if > 4 attacks/month. Red-flag review.",
                'diagnoses'       => ['Migraine without Aura (G43.009)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Naproxen 500mg',     'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'SOS', 'instructions'=>'After food — at onset of headache, max BD'],
                    ['drug_name'=>'Tab Sumatriptan 50mg',   'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'SOS', 'instructions'=>'At onset of migraine — may repeat after 2h, max 2/day'],
                    ['drug_name'=>'Tab Domperidone 10mg',   'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'SOS', 'instructions'=>'For nausea / vomiting'],
                ],
            ],

            // ─── UROLOGY ─────────────────────────────────────────────────────────────

            [
                'name'            => 'UTI — Uncomplicated (Female)',
                'specialization'  => 'General Medicine',
                'chief_complaint' => 'Burning micturition, urinary frequency, suprapubic discomfort x few days.',
                'clinical_notes'  => "S: Dysuria, frequency, urgency. No fever, no flank pain, no haematuria, no vaginal discharge. Sexually active / not.\nO: Afebrile. Abdomen soft, mild suprapubic tenderness. No renal angle tenderness.\nA: Acute uncomplicated lower UTI.\nP: Increase oral fluids. Empirical antibiotics x 5-7 days. Urine R/M & C/S sent. Review with reports / if fever, flank pain, or non-resolution at 48-72 h.",
                'diagnoses'       => ['Acute Cystitis (N30.0)', 'Urinary Tract Infection (N39.0)'],
                'prescription'    => [
                    ['drug_name'=>'Cap Nitrofurantoin 100mg', 'dose'=>'1 Cap', 'route'=>'Oral', 'frequency'=>'BD',  'instructions'=>'After food x 5 days'],
                    ['drug_name'=>'Tab Paracetamol 500mg',    'dose'=>'1 Tab', 'route'=>'Oral', 'frequency'=>'TDS', 'instructions'=>'After food — for pain / fever, SOS'],
                    ['drug_name'=>'Sachet Potassium Citrate', 'dose'=>'1 Sachet', 'route'=>'Oral', 'frequency'=>'BD', 'instructions'=>'In a glass of water — after food x 5 days'],
                ],
            ],

            // ─── AUTOIMMUNE / RHEUMATOLOGY ───────────────────────────────────────────

            [
                'name'            => 'Rheumatoid Arthritis — Follow-up',
                'specialization'  => 'Rheumatology',
                'chief_complaint' => 'Symmetric small joint pain & morning stiffness > 1 hour. Routine review on DMARD therapy.',
                'clinical_notes'  => "S: Symmetric polyarthralgia involving MCP, PIP, wrists. Morning stiffness duration noted. Compliance with MTX & folate documented. Asks about flu-like symptoms after MTX dose.\nO: Tender / swollen joint count documented. No active synovitis on examination today / tender joints at MCP & wrists. No new deformity. Skin – no rash. Chest – clear.\nA: Rheumatoid Arthritis — moderate activity / in remission on DMARDs.\nP: Continue MTX weekly + folic acid. Monitor CBC, LFT, RFT every 8-12 weeks. Steroid taper as below. Joint protection, physiotherapy. Influenza & pneumococcal vaccination if not received.",
                'diagnoses'       => ['Rheumatoid Arthritis (M05.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Methotrexate 7.5mg',     'dose'=>'2 Tab',   'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'Once weekly (e.g. every Sunday) after food'],
                    ['drug_name'=>'Tab Folic Acid 5mg',         'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'On non-MTX days (or daily)'],
                    ['drug_name'=>'Tab Hydroxychloroquine 200mg','dose'=>'1 Tab',  'route'=>'Oral', 'frequency'=>'BD',  'instructions'=>'After food — annual eye review'],
                    ['drug_name'=>'Tab Prednisolone 5mg',       'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'OD',  'instructions'=>'After breakfast — taper as advised'],
                    ['drug_name'=>'Tab Naproxen 500mg',         'dose'=>'1 Tab',   'route'=>'Oral', 'frequency'=>'BD',  'instructions'=>'After food x 7 days for flare — SOS'],
                ],
            ],

            [
                'name'            => 'Systemic Lupus Erythematosus — Maintenance',
                'specialization'  => 'Rheumatology',
                'chief_complaint' => 'Routine SLE follow-up. No new rash, oral ulcers, joint pain, or breathlessness.',
                'clinical_notes'  => "S: Known SLE on HCQ ± steroid ± MMF. Asks about photosensitivity, alopecia, joint pain, oral ulcers, Raynaud's. No haematuria, frothy urine, or facial swelling.\nO: BP, weight, urinalysis dipstick documented. Skin – malar / discoid lesions noted. Oral cavity – clear / ulcers. Joints – non-tender. CVS / RS – clear.\nA: SLE — clinically inactive / mildly active.\nP: Continue HCQ lifelong. Sun protection (SPF 50, hat). Strict BP and urine monitoring. Pneumococcal & influenza vaccination. CBC, RFT, LFT, urine R/M, complement, anti-dsDNA every 3-6 months. Avoid sulpha drugs.",
                'diagnoses'       => ['Systemic Lupus Erythematosus (M32.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Hydroxychloroquine 200mg','dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — annual eye exam mandatory'],
                    ['drug_name'=>'Tab Prednisolone 5mg',        'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — lowest effective dose'],
                    ['drug_name'=>'Tab Mycophenolate Mofetil 500mg','dose'=>'2 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food (if on MMF)'],
                    ['drug_name'=>'Tab Calcium Carbonate 500mg + Vitamin D3 250 IU','dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — for bone protection on steroids'],
                ],
            ],

            // ─── CARDIOLOGY ──────────────────────────────────────────────────────────

            [
                'name'            => 'Heart Failure (HFrEF) — Follow-up',
                'specialization'  => 'Cardiology',
                'chief_complaint' => 'Routine HF review. NYHA class assessment, weight log, fluid status review.',
                'clinical_notes'  => "S: Known HFrEF (LVEF documented). Current NYHA II / III. Orthopnoea / PND / pedal oedema queried. Fluid intake & daily weight reviewed. Compliance with GDMT documented.\nO: BP / HR / SpO2 documented. JVP not raised / raised. CVS – S1 S2, S3 gallop / no murmur. RS – bibasal crackles / clear. Pedal oedema – present / absent. Weight today documented.\nA: HFrEF on optimised guideline-directed medical therapy.\nP: Continue ARNI / ACEi, beta-blocker, MRA, SGLT2i quartet. Salt restriction (<2 g/day), fluid restriction (<1.5 L/day). Daily weight chart. Up-titrate as tolerated. Recheck RFT & K+ in 2 weeks after up-titration.",
                'diagnoses'       => ['Heart Failure with Reduced Ejection Fraction (I50.20)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Sacubitril + Valsartan 50mg','dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — monitor BP/K+/RFT'],
                    ['drug_name'=>'Tab Bisoprolol 2.5mg',           'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — uptitrate as tolerated'],
                    ['drug_name'=>'Tab Spironolactone 25mg',        'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — monitor K+'],
                    ['drug_name'=>'Tab Dapagliflozin 10mg',         'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast'],
                    ['drug_name'=>'Tab Furosemide 40mg',            'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Morning — adjust per weight & oedema'],
                ],
            ],

            [
                'name'            => 'Atrial Fibrillation — Anticoagulation Review',
                'specialization'  => 'Cardiology',
                'chief_complaint' => 'AF anticoagulation review. CHA₂DS₂-VASc & HAS-BLED reassessment.',
                'clinical_notes'  => "S: Known AF — paroxysmal / persistent. No new bleeding, no chest pain, no syncope, no neurological deficit. Compliance with anticoagulation reviewed.\nO: HR irregularly irregular / sinus today. BP documented. No bruising, no melaena. CVS – variable S1 intensity / no murmur. CHA₂DS₂-VASc = __ , HAS-BLED = __ .\nA: Atrial Fibrillation — rate / rhythm controlled, on appropriate anticoagulation.\nP: Continue DOAC + rate control as below. Patient education on missed-dose protocol & bleeding red flags. Avoid NSAIDs. Repeat ECG / Holter as needed.",
                'diagnoses'       => ['Atrial Fibrillation (I48.91)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Apixaban 5mg',         'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — do not skip / double dose'],
                    ['drug_name'=>'Tab Bisoprolol 5mg',       'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast'],
                    ['drug_name'=>'Tab Atorvastatin 40mg',    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime'],
                ],
            ],

            [
                'name'            => 'Dyslipidemia — Primary Prevention',
                'specialization'  => 'Cardiology',
                'chief_complaint' => 'Lipid review with abnormal lipid profile. No chest pain, no claudication.',
                'clinical_notes'  => "S: Family history of CAD / DM noted. Smoking, alcohol, exercise, dietary habits documented. No anginal symptoms. ASCVD risk discussed.\nO: BP, BMI, waist circumference documented. CVS / RS – clear. Carotid bruit – absent. Pedal pulses present.\nA: Dyslipidemia — on / requiring statin therapy.\nP: Lifestyle: TLC diet, 150 min/week aerobic exercise, weight reduction, smoking cessation. High-intensity statin as below. Repeat lipid profile 6-8 weeks after starting / dose change. LFT at baseline.",
                'diagnoses'       => ['Mixed Hyperlipidemia (E78.2)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Atorvastatin 40mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime — long term'],
                    ['drug_name'=>'Cap Omegasoft',         'dose'=>'1 Cap','route'=>'Oral','frequency'=>'OD','instructions'=>'After food'],
                ],
            ],

            // ─── LIVER ───────────────────────────────────────────────────────────────

            [
                'name'            => 'Chronic Liver Disease / Cirrhosis — Follow-up',
                'specialization'  => 'Gastroenterology / Hepatology',
                'chief_complaint' => 'Routine cirrhosis review. Encephalopathy / variceal screen / fluid status.',
                'clinical_notes'  => "S: Known CLD (etiology – ETOH / HBV / HCV / NASH). No haematemesis, melaena, abdominal distension, sleep reversal, or behavioural change. Diet & salt restriction reviewed.\nO: Icterus, palmar erythema, spider naevi, gynaecomastia documented. Abdomen – distension / ascites tap, hepatosplenomegaly. No flap. Child-Pugh / MELD documented.\nA: Cirrhosis — Child-Pugh A / B / C. Decompensation features – none / ascites / encephalopathy.\nP: Salt restriction <2g/day, alcohol abstinence, no NSAIDs. Beta-blocker for variceal prophylaxis. Lactulose for HE prevention. Diuretics for ascites. UGI endoscopy yearly. USG + AFP every 6 months for HCC surveillance.",
                'diagnoses'       => ['Cirrhosis of Liver (K74.60)', 'Portal Hypertension (K76.6)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Propranolol 40mg',         'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — titrate to HR ~55-60'],
                    ['drug_name'=>'Syp Lactulose 200ml',          'dose'=>'15 ml','route'=>'Oral','frequency'=>'TDS','instructions'=>'Aim for 2-3 soft stools/day'],
                    ['drug_name'=>'Tab Rifaximin 550mg',          'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — for HE prevention'],
                    ['drug_name'=>'Tab Spironolactone 25mg',      'dose'=>'4 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — adjust per ascites'],
                    ['drug_name'=>'Tab Furosemide 40mg',          'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Morning — monitor electrolytes'],
                ],
            ],

            [
                'name'            => 'Non-Alcoholic Fatty Liver Disease (NAFLD)',
                'specialization'  => 'Gastroenterology / Hepatology',
                'chief_complaint' => 'Incidentally detected fatty liver on USG / raised transaminases. No alcohol use.',
                'clinical_notes'  => "S: Asymptomatic / right upper quadrant heaviness. No jaundice. Diet, exercise, weight history reviewed. Alcohol denied.\nO: BMI, waist circumference documented. Abdomen – soft, tender RUQ / non-tender, no organomegaly. No stigmata of CLD.\nA: NAFLD with elevated transaminases. Other causes (viral, autoimmune, Wilson's) excluded.\nP: Lifestyle is the cornerstone — 7-10% weight loss, Mediterranean diet, no alcohol, 150 min/week aerobic exercise. Treat metabolic comorbidities. Vitamin E for biopsy-proven NASH. Repeat LFT in 12 weeks.",
                'diagnoses'       => ['Non-alcoholic Steatohepatitis (K75.81)', 'Fatty Liver (K76.0)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Vitamin E 400mg',                'dose'=>'1 Cap','route'=>'Oral','frequency'=>'OD','instructions'=>'After food'],
                    ['drug_name'=>'Tab Ursodeoxycholic Acid 300mg',     'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food'],
                    ['drug_name'=>'Tab Pioglitazone 15mg',              'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast (if biopsy-proven NASH and DM)'],
                ],
            ],

            // ─── KIDNEY ──────────────────────────────────────────────────────────────

            [
                'name'            => 'Chronic Kidney Disease (Stage 3-4)',
                'specialization'  => 'Nephrology',
                'chief_complaint' => 'CKD review with stable creatinine. BP, fluid, anaemia, mineral-bone disorder reassessment.',
                'clinical_notes'  => "S: Known CKD. No haematuria, frothy urine, or new oedema. Reviews salt/protein restriction & medication compliance. Asks about appetite, nausea, fatigue, leg cramps.\nO: BP / weight / pedal oedema documented. Pallor / no pallor. CVS / RS – clear. Recent labs – Hb, eGFR, K+, Ca, PO4, PTH, Vit D documented.\nA: CKD Stage 3a / 3b / 4 (eGFR __ ml/min) — etiology DM / HTN.\nP: BP target <130/80. ACEi or ARB if proteinuria. SGLT2i renoprotective. Statin. Salt <5g/day. Avoid nephrotoxins (NSAIDs, contrast, gentamicin). Iron / EPO if Hb low. Phosphate binders & active vit D as below. Refer dialysis planning if eGFR < 20.",
                'diagnoses'       => ['Chronic Kidney Disease, Stage 3 (N18.30)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Telmisartan 40mg',           'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — monitor K+'],
                    ['drug_name'=>'Tab Dapagliflozin 10mg',         'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast'],
                    ['drug_name'=>'Tab Atorvastatin 20mg',          'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime'],
                    ['drug_name'=>'Tab Calcitriol 0.25mcg',         'dose'=>'1 Cap','route'=>'Oral','frequency'=>'OD','instructions'=>'After food'],
                    ['drug_name'=>'Tab Sevelamer 800mg',            'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'With each meal — for hyperphosphatemia'],
                    ['drug_name'=>'Tab Ferrous Ascorbate 100mg',    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After food'],
                ],
            ],

            [
                'name'            => 'Renal Stone / Nephrolithiasis — Acute',
                'specialization'  => 'Urology',
                'chief_complaint' => 'Acute loin to groin colicky pain with vomiting. Haematuria queried.',
                'clinical_notes'  => "S: Sudden severe colicky loin pain radiating to groin / scrotum. Vomiting +/-. Haematuria +/-. No fever, no LUTS.\nO: Restless. Vitals stable. Renal angle tenderness present. Abdomen – soft, no peritonism. Genital exam normal.\nA: Renal / ureteric calculus — uncomplicated.\nP: Adequate hydration (2.5-3 L/day). Strain urine. NSAID for pain ± opioid. Alpha-blocker for medical expulsive therapy if stone <10 mm in distal ureter. NCCT KUB / USG to confirm. Refer urology if stone >10 mm, fever, obstruction, single kidney. Dietary advice (low salt, low oxalate).",
                'diagnoses'       => ['Calculus of Kidney and Ureter (N20.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Diclofenac 50mg',           'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food x 5 days — for pain'],
                    ['drug_name'=>'Tab Tamsulosin 0.4mg',          'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime x 4 weeks — caution postural drop'],
                    ['drug_name'=>'Tab Ondansetron 4mg',           'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'For nausea / vomiting — SOS'],
                    ['drug_name'=>'Sachet Potassium Citrate',      'dose'=>'1 Sachet','route'=>'Oral','frequency'=>'BD','instructions'=>'In a glass of water — for urine alkalinisation'],
                ],
            ],

            // ─── ENDOCRINE ───────────────────────────────────────────────────────────

            [
                'name'            => 'Hypothyroidism — Follow-up',
                'specialization'  => 'Endocrinology',
                'chief_complaint' => 'Routine thyroid review. No fatigue, weight gain, constipation, cold intolerance.',
                'clinical_notes'  => "S: Known hypothyroidism on levothyroxine. Compliance & fasting administration reviewed. Asks about fatigue, weight, mood, menstrual cycle, hair loss.\nO: Weight, HR, BP documented. Skin – dry / normal. Reflexes – normal / delayed. No goitre / diffuse goitre.\nA: Primary Hypothyroidism — euthyroid on replacement (per TSH).\nP: Continue levothyroxine. Take on empty stomach 30-60 min before food, separate from calcium / iron / soy. Repeat TSH every 6-8 weeks until stable then 6-12 monthly. Adjust dose by 12.5-25 mcg as needed.",
                'diagnoses'       => ['Hypothyroidism, unspecified (E03.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Levothyroxine 50mcg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Empty stomach — 30-60 min before breakfast (adjust per TSH)'],
                ],
            ],

            [
                'name'            => 'Hyperthyroidism / Graves\' Disease',
                'specialization'  => 'Endocrinology',
                'chief_complaint' => 'Palpitations, weight loss, heat intolerance, tremor, anxiety.',
                'clinical_notes'  => "S: Symptoms of thyrotoxicosis — palpitations, weight loss despite good appetite, sweating, anxiety, tremor, loose stools. Eye symptoms (diplopia, gritty eye) queried for Graves'. Menstrual irregularity in females.\nO: Tachycardia / AF, fine tremor of outstretched hands, warm sweaty palms, lid lag, lid retraction. Diffuse goitre with bruit / nodular. Eye signs (proptosis, chemosis) noted.\nA: Hyperthyroidism — likely Graves' disease.\nP: Anti-thyroid drug as below. Beta-blocker for symptomatic relief. Counsel about agranulocytosis (sore throat / fever – urgent CBC). Recheck TFT every 4-6 weeks. Anti-TPO / TRAb / Tc-99m scan as needed. Avoid pregnancy on carbimazole — switch to PTU in 1st trimester.",
                'diagnoses'       => ["Thyrotoxicosis with diffuse goitre / Graves' disease (E05.00)"],
                'prescription'    => [
                    ['drug_name'=>'Tab Carbimazole 10mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — taper per TFT'],
                    ['drug_name'=>'Tab Propranolol 40mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food — for symptoms; taper as euthyroid'],
                ],
            ],

            // ─── NEUROLOGY ───────────────────────────────────────────────────────────

            [
                'name'            => 'Epilepsy — Maintenance',
                'specialization'  => 'Neurology',
                'chief_complaint' => 'Routine epilepsy review. Seizure frequency, AED compliance, side effects.',
                'clinical_notes'  => "S: Seizure frequency since last visit, type, duration, post-ictal phase, triggers, sleep, alcohol, missed doses documented. Side effects of AEDs queried (sedation, mood, weight, cognition).\nO: BP, weight documented. Neuro – fully alert, no focal deficit. Skin – no rash (DRESS / SJS check on new AEDs).\nA: Epilepsy — controlled / partly controlled.\nP: Continue current AED. Maintain seizure diary. Adequate sleep, regular meals, alcohol moderation. Driving advice as per local rules. Folic acid in women of child-bearing age. Recheck levels if non-compliant or breakthrough.",
                'diagnoses'       => ['Epilepsy, unspecified (G40.909)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Levetiracetam 500mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — do not stop abruptly'],
                    ['drug_name'=>'Tab Folic Acid 5mg',      'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After food — esp women of child-bearing age'],
                ],
            ],

            [
                'name'            => 'Stroke — Secondary Prevention',
                'specialization'  => 'Neurology',
                'chief_complaint' => 'Post-ischaemic stroke / TIA review. BP, antiplatelet, lipid, lifestyle reassessment.',
                'clinical_notes'  => "S: Functional status (mRS), residual deficit, swallowing, speech, mood. Compliance with antiplatelet, statin, BP & DM medications. Smoking, alcohol, exercise documented.\nO: BP / HR / weight. CNS – residual hemiparesis / aphasia / facial droop. Carotid bruit – absent / present. Pulses bilateral.\nA: Ischaemic stroke / TIA — secondary prevention.\nP: BP target <130/80. High-intensity statin lifelong. Antiplatelet (single / dual short-term). Diabetic & lipid control. Smoking cessation, Mediterranean diet, 150 min/week exercise. Physiotherapy / SLT as needed. Carotid Doppler if not done.",
                'diagnoses'       => ['Cerebral Infarction (I63.9)', 'Personal history of TIA (Z86.73)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Aspirin 75mg',      'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After dinner'],
                    ['drug_name'=>'Tab Clopidogrel 75mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast (if dual / single agent)'],
                    ['drug_name'=>'Tab Atorvastatin 80mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime — high-intensity'],
                    ['drug_name'=>'Tab Telmisartan 40mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — for BP control'],
                ],
            ],

            // ─── RESPIRATORY ─────────────────────────────────────────────────────────

            [
                'name'            => 'COPD — Stable Follow-up',
                'specialization'  => 'Pulmonology',
                'chief_complaint' => 'Routine COPD review. Cough, sputum, dyspnoea (mMRC) and exacerbations reassessed.',
                'clinical_notes'  => "S: Smoking status & pack-years. Exacerbation frequency in past year. mMRC dyspnoea grade. CAT score documented. Inhaler technique reviewed.\nO: SpO2 in room air. RS – prolonged expiration, rhonchi, decreased air entry. CVS – clear. No pedal oedema (cor pulmonale check).\nA: COPD — GOLD A / B / E.\nP: Smoking cessation is non-negotiable. LAMA ± LABA ± ICS as below. Influenza & pneumococcal vaccination. Pulmonary rehabilitation. Nutrition. Action plan for exacerbation given.",
                'diagnoses'       => ['Chronic Obstructive Pulmonary Disease (J44.9)'],
                'prescription'    => [
                    ['drug_name'=>'MDI Tiotropium 18mcg',                  'dose'=>'1 puff','route'=>'Inhaled','frequency'=>'OD','instructions'=>'Once daily — same time daily'],
                    ['drug_name'=>'MDI Budesonide + Formoterol 200/6mcg',  'dose'=>'2 puffs','route'=>'Inhaled','frequency'=>'BD','instructions'=>'Inhale — rinse mouth after use'],
                    ['drug_name'=>'MDI Salbutamol 100mcg',                 'dose'=>'2 puffs','route'=>'Inhaled','frequency'=>'SOS','instructions'=>'For breathlessness — max 4 times/day'],
                ],
            ],

            [
                'name'            => 'Community-Acquired Pneumonia (Outpatient)',
                'specialization'  => 'Pulmonology',
                'chief_complaint' => 'Fever with productive cough, breathlessness, pleuritic chest pain x few days.',
                'clinical_notes'  => "S: Fever, productive cough (purulent / rusty sputum), breathlessness, pleuritic chest pain, myalgia. No haemoptysis, no weight loss. Comorbidities & smoking documented.\nO: Temp / RR / SpO2 / HR documented. RS – bronchial breathing, crackles over affected lobe. CRB-65 score = __.\nA: Community-Acquired Pneumonia — outpatient management (CRB-65 0-1).\nP: Empirical oral antibiotics x 5-7 days. Hydration, paracetamol for fever. CXR baseline. Review in 48-72 h or earlier if worsening. Admit if SpO2 < 92%, RR > 30, confusion, hypotension.",
                'diagnoses'       => ['Pneumonia, unspecified (J18.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Amoxicillin + Clavulanate 625mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food x 7 days'],
                    ['drug_name'=>'Tab Azithromycin 500mg',              'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After food x 5 days (for atypical cover)'],
                    ['drug_name'=>'Tab Paracetamol 500mg',               'dose'=>'1 Tab','route'=>'Oral','frequency'=>'QID','instructions'=>'For fever / pain — SOS, max 4 g/day'],
                    ['drug_name'=>'Tab Ambroxol 30mg',                   'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food — mucolytic'],
                ],
            ],

            // ─── DERMATOLOGY ─────────────────────────────────────────────────────────

            [
                'name'            => 'Acne Vulgaris — Moderate',
                'specialization'  => 'Dermatology',
                'chief_complaint' => 'Facial pimples with comedones and inflammatory papules.',
                'clinical_notes'  => "S: Duration of acne, prior treatments, OTC use. Female – menstrual irregularity, hirsutism (PCOS screen). Skin care routine, sunscreen use, occlusive cosmetics.\nO: Face / chest / back – open & closed comedones, papules, pustules, scarring noted. Severity – mild / moderate / severe.\nA: Acne Vulgaris — moderate, predominantly inflammatory.\nP: Gentle non-comedogenic cleanser BD. Topical retinoid + benzoyl peroxide combination. Systemic doxycycline 8-12 weeks for moderate. Sunscreen daily. Avoid picking. Photo at baseline & 12 weeks. Consider isotretinoin if scarring / failure (specialist).",
                'diagnoses'       => ['Acne Vulgaris (L70.0)'],
                'prescription'    => [
                    ['drug_name'=>'Gel Adapalene 0.1%',         'dose'=>'Pea size','route'=>'Topical','frequency'=>'HS','instructions'=>'At night to dry skin — avoid eyes'],
                    ['drug_name'=>'Gel Benzoyl Peroxide 2.5%',  'dose'=>'Pea size','route'=>'Topical','frequency'=>'OD','instructions'=>'Morning — may bleach clothing'],
                    ['drug_name'=>'Cap Doxycycline 100mg',      'dose'=>'1 Cap','route'=>'Oral','frequency'=>'OD','instructions'=>'After food x 8-12 weeks — avoid sun'],
                ],
            ],

            [
                'name'            => 'Atopic Dermatitis / Eczema',
                'specialization'  => 'Dermatology',
                'chief_complaint' => 'Itchy red rash in flexures, recurrent flares.',
                'clinical_notes'  => "S: Onset, distribution (flexures, face, hands), itch severity, sleep disturbance. Atopic background (asthma, allergic rhinitis), family history. Triggers — soaps, dust, sweat.\nO: Erythematous, scaly, lichenified plaques in antecubital / popliteal fossae. Excoriations, fissures. No secondary infection / pustules.\nA: Atopic dermatitis — moderate flare.\nP: Liberal emollients ≥ twice daily. Topical steroid as below — short pulses. Antihistamine for itch. Trigger avoidance, soft cotton clothing, cool baths. Topical antibiotic if super-infected. Counsel about steroid use & potency.",
                'diagnoses'       => ['Atopic Dermatitis (L20.9)'],
                'prescription'    => [
                    ['drug_name'=>'Cream Mometasone 0.1%',     'dose'=>'Thin layer','route'=>'Topical','frequency'=>'OD','instructions'=>'Apply to affected areas at night x 7-14 days'],
                    ['drug_name'=>'Cream Hydrocortisone 1%',   'dose'=>'Thin layer','route'=>'Topical','frequency'=>'BD','instructions'=>'For face / flexures — milder potency'],
                    ['drug_name'=>'Tab Levocetirizine 5mg',    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime — for itch'],
                ],
            ],

            [
                'name'            => 'Tinea Corporis / Fungal Skin Infection',
                'specialization'  => 'Dermatology',
                'chief_complaint' => 'Annular itchy rash on body, expanding over weeks.',
                'clinical_notes'  => "S: Duration, distribution, contact history, sharing of clothes / towels, OTC treatments tried (often topical steroid which worsens). Diabetes / immunosuppression queried.\nO: Annular erythematous plaques with central clearing and active scaly border. Single / multiple lesions. KOH if uncertain.\nA: Tinea Corporis — extensive / steroid-modified.\nP: Avoid sharing personal items, dry skin folds, loose cotton clothing. Topical antifungal x 4-6 weeks. Systemic antifungal x 2-4 weeks for extensive / steroid-modified disease. LFT before & during prolonged antifungal.",
                'diagnoses'       => ['Tinea Corporis (B35.4)'],
                'prescription'    => [
                    ['drug_name'=>'Cream Terbinafine 1%',  'dose'=>'Thin layer','route'=>'Topical','frequency'=>'BD','instructions'=>'For 4-6 weeks — continue 1 week after lesion clears'],
                    ['drug_name'=>'Tab Itraconazole 100mg','dose'=>'1 Cap','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 2-4 weeks (for extensive disease)'],
                    ['drug_name'=>'Tab Fexofenadine 180mg','dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'For itch'],
                ],
            ],

            // ─── ORTHOPEDICS ─────────────────────────────────────────────────────────

            [
                'name'            => 'Osteoarthritis — Knee',
                'specialization'  => 'Orthopaedics',
                'chief_complaint' => 'Bilateral knee pain on activity, morning stiffness < 30 min.',
                'clinical_notes'  => "S: Knee pain on stairs / walking / standing up. Stiffness < 30 min in morning. Buckling, locking. BMI, occupation, activity level documented.\nO: BMI documented. Knees – crepitus, mild effusion, varus deformity, restricted flexion, joint line tenderness. ROM noted.\nA: Osteoarthritis Knee — primary, bilateral.\nP: Weight reduction 5-10% if overweight. Quadriceps strengthening exercises. Avoid squatting & climbing stairs. Topical NSAID first line. Oral NSAID short course. Glucosamine + chondroitin (variable evidence). Knee brace / cane. Refer ortho for intra-articular steroid / surgery if severe.",
                'diagnoses'       => ['Osteoarthritis of Knee (M17.9)'],
                'prescription'    => [
                    ['drug_name'=>'Gel Diclofenac 1%',                            'dose'=>'2 g','route'=>'Topical','frequency'=>'TDS','instructions'=>'Apply over knee — gentle massage'],
                    ['drug_name'=>'Tab Diclofenac 50mg',                          'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 7 days — short course'],
                    ['drug_name'=>'Tab Pantoprazole 40mg',                        'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Before breakfast — gastroprotection while on NSAID'],
                    ['drug_name'=>'Tab Glucosamine 750mg + Chondroitin 600mg',    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 3 months trial'],
                ],
            ],

            [
                'name'            => 'Acute Low Back Pain / Lumbar Sprain',
                'specialization'  => 'Orthopaedics',
                'chief_complaint' => 'Acute lower back pain after lifting / awkward movement.',
                'clinical_notes'  => "S: Pain onset, character, radiation. Red flags – fever, weight loss, night pain, bowel/bladder, saddle anaesthesia, weakness, trauma – all denied.\nO: Mild paraspinal spasm, restricted lumbar flexion. SLR negative bilaterally. Neuro – power, sensations, reflexes intact. No saddle anaesthesia.\nA: Mechanical / muscular acute low back pain — no red flags.\nP: Stay active — avoid bed rest. Hot fomentation, NSAIDs short course, muscle relaxant. Posture correction, core strengthening once acute pain settles. Reassure good prognosis (>90% recover within 6 weeks). Imaging ONLY if red flags.",
                'diagnoses'       => ['Low Back Pain (M54.5)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Diclofenac 50mg',         'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 5 days'],
                    ['drug_name'=>'Tab Thiocolchicoside 4mg',    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 5 days — muscle relaxant'],
                    ['drug_name'=>'Tab Pantoprazole 40mg',       'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Before breakfast — while on NSAID'],
                    ['drug_name'=>'Gel Diclofenac 1%',           'dose'=>'2 g','route'=>'Topical','frequency'=>'TDS','instructions'=>'Apply locally over painful area'],
                ],
            ],

            [
                'name'            => 'Gout — Acute Flare',
                'specialization'  => 'Rheumatology',
                'chief_complaint' => 'Severe red, hot, swollen first MTP joint x 1-2 days.',
                'clinical_notes'  => "S: Acute onset (often overnight) severe monoarticular pain & swelling — typically 1st MTP. Triggers — alcohol, red meat, dehydration, diuretics. Past episodes documented.\nO: Affected joint hot, red, swollen, exquisitely tender. Tophi search at helix, olecranon, MTP. BP / weight documented.\nA: Acute gouty arthritis — likely.\nP: Treat flare — NSAID OR colchicine OR steroid. Do not start / stop urate-lowering during acute flare — continue if already on. Once flare settles, start allopurinol / febuxostat at low dose with concurrent colchicine prophylaxis x 6 months. Lifestyle — avoid alcohol, red meat, organ meats, sugary drinks; encourage hydration. Recheck uric acid in 4 weeks.",
                'diagnoses'       => ['Gout, unspecified (M10.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Colchicine 0.5mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 5-7 days — reduce in CKD'],
                    ['drug_name'=>'Tab Naproxen 500mg',    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food x 5 days'],
                    ['drug_name'=>'Tab Pantoprazole 40mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Before breakfast — gastroprotection'],
                    ['drug_name'=>'Tab Febuxostat 40mg',   'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After food — start ONLY after flare settles'],
                ],
            ],

            // ─── GYNAECOLOGY ─────────────────────────────────────────────────────────

            [
                'name'            => 'Vaginal Candidiasis',
                'specialization'  => 'Obstetrics & Gynaecology',
                'chief_complaint' => 'Vulval itching with thick white curdy vaginal discharge.',
                'clinical_notes'  => "S: Itch, discharge, dyspareunia, dysuria. Diabetes, antibiotics, pregnancy, OCP use, immunosuppression queried. Recurrence frequency.\nO: Vulval erythema, oedema. Speculum – thick curdy white discharge, vaginal mucosal erythema. No cervical motion tenderness, no foul odour.\nA: Uncomplicated Vulvovaginal Candidiasis.\nP: Single oral fluconazole + vaginal clotrimazole pessary. Treat partner only if symptomatic. Cotton underwear, avoid douching. If recurrent (>4/yr) — exclude DM, induction + suppression therapy.",
                'diagnoses'       => ['Vulvovaginal Candidiasis (B37.3)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Fluconazole 150mg',         'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Single dose — repeat after 72 h if recurrent'],
                    ['drug_name'=>'Pessary Clotrimazole 100mg',    'dose'=>'1 Pessary','route'=>'Oral','frequency'=>'HS','instructions'=>'Insert vaginally at bedtime x 6 nights'],
                    ['drug_name'=>'Cream Clotrimazole 1%',         'dose'=>'Thin layer','route'=>'Topical','frequency'=>'BD','instructions'=>'Apply to vulva x 7 days'],
                ],
            ],

            [
                'name'            => 'Polycystic Ovary Syndrome (PCOS)',
                'specialization'  => 'Obstetrics & Gynaecology',
                'chief_complaint' => 'Irregular menses, acne, hirsutism, weight gain.',
                'clinical_notes'  => "S: Cycle history (oligo / amenorrhoea), acne, hirsutism, weight gain, fertility concerns, family history of DM. Sleep apnoea & mood symptoms queried.\nO: BMI, waist circumference, BP. Acne, hirsutism (modified Ferriman-Gallwey score), acanthosis nigricans documented. Pelvic exam if appropriate.\nA: PCOS — Rotterdam criteria (2 of 3: oligo-anovulation / clinical or biochemical hyperandrogenism / polycystic ovaries).\nP: Lifestyle is first-line — 5-10% weight loss is hugely effective. Combined OCP for cycle regulation & androgen excess (if no contraindication). Metformin for insulin resistance / glucose intolerance. Topical retinoid for acne, eflornithine / hair removal for hirsutism. Annual screening for T2DM / dyslipidemia.",
                'diagnoses'       => ['Polycystic Ovary Syndrome (E28.2)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Ethinyl Estradiol 0.03mg + Drospirenone 3mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'Same time daily — 21 days then 7-day break'],
                    ['drug_name'=>'Tab Metformin 500mg',                              'dose'=>'1 Tab','route'=>'Oral','frequency'=>'BD','instructions'=>'After food — titrate up'],
                    ['drug_name'=>'Tab Folic Acid 5mg',                               'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After food — esp if planning pregnancy'],
                ],
            ],

            // ─── OBSTETRICS ──────────────────────────────────────────────────────────

            [
                'name'            => 'Nausea & Vomiting in Pregnancy',
                'specialization'  => 'Obstetrics & Gynaecology',
                'chief_complaint' => 'Persistent nausea & vomiting in early pregnancy.',
                'clinical_notes'  => "S: Pregnancy confirmed by USG / hCG. Frequency and severity of vomiting, oral intake, weight loss, dehydration. PUQE score documented. Red flags – ketones, hypotension, weight loss > 5% — for hyperemesis admission.\nO: Vitals, weight, hydration, urine ketones. P/A – soft, non-tender, gravid uterus per gestation. No pallor, no icterus.\nA: Nausea & vomiting of pregnancy — mild / moderate. No hyperemesis.\nP: Reassurance — usually settles by 14-16 weeks. Small frequent meals, ginger, avoid triggers. Vitamin B6 + doxylamine first-line. Add ondansetron if persistent (after 1st trimester ideally). IV fluids & admission if hyperemesis.",
                'diagnoses'       => ['Vomiting in Pregnancy (O21.0)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Doxylamine 10mg + Pyridoxine 10mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime initially — may add morning dose'],
                    ['drug_name'=>'Tab Ondansetron 4mg',                   'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'For breakthrough vomiting — SOS'],
                    ['drug_name'=>'Tab Folic Acid 5mg',                    'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After food — continue through 1st trimester'],
                ],
            ],

            // ─── DENTAL ──────────────────────────────────────────────────────────────

            [
                'name'            => 'Dental Caries / Abscess — Antibiotic Cover',
                'specialization'  => 'Dentistry',
                'chief_complaint' => 'Severe toothache with facial swelling x 2-3 days.',
                'clinical_notes'  => "S: Toothache (sharp / throbbing), facial / gum swelling, fever, difficulty chewing, sensitivity to hot/cold. Trauma, recent dental work, prior episodes documented.\nO: Localised swelling over offending tooth, fluctuance / firm. Extraoral – no facial cellulitis, no trismus / mild trismus. Lymph nodes — submandibular tender / not. Caries / mobility / percussion-tenderness of involved tooth.\nA: Acute apical / periapical abscess with localised swelling.\nP: Definitive Rx — drainage / RCT / extraction by dentist (refer / schedule). Antibiotics + analgesia bridging. Warm saline rinses, soft diet. Chlorhexidine mouthwash. Review in 3-5 days; if worsening swelling, trismus, dysphagia — admit (Ludwig's caution).",
                'diagnoses'       => ['Periapical Abscess without Sinus (K04.7)', 'Dental Caries (K02.9)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Amoxicillin + Clavulanate 625mg', 'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food x 5-7 days'],
                    ['drug_name'=>'Tab Metronidazole 400mg',             'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food x 5 days — for anaerobic cover'],
                    ['drug_name'=>'Tab Paracetamol 500mg',               'dose'=>'1 Tab','route'=>'Oral','frequency'=>'QID','instructions'=>'For pain — SOS, max 4 g/day'],
                    ['drug_name'=>'Mouthwash Chlorhexidine 0.2%',        'dose'=>'10 ml','route'=>'Oral','frequency'=>'BD','instructions'=>'Rinse for 30 sec, do not swallow, no food/water 30 min after'],
                ],
            ],

            // ─── PSYCHIATRY ──────────────────────────────────────────────────────────

            [
                'name'            => 'Depression — Major (Moderate)',
                'specialization'  => 'Psychiatry',
                'chief_complaint' => 'Persistent low mood, anhedonia, fatigue, sleep disturbance > 2 weeks.',
                'clinical_notes'  => "S: Low mood, loss of interest, fatigue, sleep / appetite / weight changes, guilt, poor concentration, suicidal ideation (active risk assessment). Onset, duration, triggers, prior episodes & treatments documented. Substance use, family history.\nO: Mental status — appearance, eye contact, speech, mood, affect, thought (form/content), perception, insight, judgement. PHQ-9 score = __. No active suicidal plan / has plan (escalate).\nA: Major Depressive Disorder — moderate severity, single / recurrent episode.\nP: SSRI + psychoeducation. Behavioural activation, sleep hygiene, regular exercise. CBT referral. Safety plan & follow-up in 1-2 weeks. Avoid alcohol. Counsel about onset of action (4-6 weeks), side effects, not to stop abruptly.",
                'diagnoses'       => ['Major Depressive Disorder, single episode, moderate (F32.1)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Sertraline 50mg',     'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — review in 2 weeks'],
                    ['drug_name'=>'Tab Clonazepam 0.25mg',   'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime x 2 weeks (short-term, sleep / anxiety)'],
                ],
            ],

            [
                'name'            => 'Generalised Anxiety Disorder',
                'specialization'  => 'Psychiatry',
                'chief_complaint' => 'Excessive worry, restlessness, sleep difficulty, somatic anxiety symptoms > 6 months.',
                'clinical_notes'  => "S: Excessive uncontrollable worry about multiple domains > 6 months. Restlessness, fatigue, irritability, muscle tension, sleep disturbance. Panic attacks / phobias / OCD differentiated. Caffeine, substance, thyroid screen. GAD-7 score = __.\nO: Anxious affect, restless, tachycardia. No tremor, no goitre. Mental status otherwise normal. No suicidal ideation.\nA: Generalised Anxiety Disorder — moderate.\nP: Psychoeducation. CBT first-line. SSRI for moderate-severe. Buspirone or short-term benzodiazepine adjunct. Reduce caffeine, regular exercise, sleep hygiene, mindfulness. Review in 2 weeks.",
                'diagnoses'       => ['Generalised Anxiety Disorder (F41.1)'],
                'prescription'    => [
                    ['drug_name'=>'Tab Escitalopram 10mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'OD','instructions'=>'After breakfast — onset 4-6 weeks'],
                    ['drug_name'=>'Tab Buspirone 5mg',      'dose'=>'1 Tab','route'=>'Oral','frequency'=>'TDS','instructions'=>'After food — for anxiety, non-sedating'],
                    ['drug_name'=>'Tab Clonazepam 0.25mg',  'dose'=>'1 Tab','route'=>'Oral','frequency'=>'HS','instructions'=>'At bedtime x 2 weeks (short-term only)'],
                ],
            ],

        ];

        $upserted = 0;
        foreach ($templates as $tpl) {
            EncounterTemplate::updateOrCreate(
                ['name' => $tpl['name']],
                [
                    'specialization'        => $tpl['specialization'],
                    'chief_complaint'       => $tpl['chief_complaint'],
                    'clinical_notes'        => $tpl['clinical_notes'],
                    'diagnoses'             => $tpl['diagnoses'],
                    'prescription_template' => $tpl['prescription'],
                    'is_active'             => true,
                    'created_by'            => null,
                ]
            );
            $upserted++;
        }

        $this->command->info("Upserted {$upserted} encounter templates.");
    }
}
