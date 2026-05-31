<?php
namespace Database\Seeders;

use App\Models\DrugInteraction;
use Illuminate\Database\Seeder;

class DrugInteractionSeeder extends Seeder
{
    public function run(): void
    {
        DrugInteraction::truncate();

        $interactions = [
            // ── CONTRAINDICATED ──────────────────────────────────────────────
            ['drug1_name'=>'tramadol',     'drug2_name'=>'maoi',          'severity'=>'contraindicated',
             'description'=>'Life-threatening serotonin syndrome: hyperthermia, agitation, rigidity, seizures.',
             'management'=>'Do not combine. Avoid tramadol within 14 days of MAOI use.'],

            ['drug1_name'=>'ssri',         'drug2_name'=>'maoi',          'severity'=>'contraindicated',
             'description'=>'Fatal serotonin syndrome risk with SSRIs (fluoxetine, sertraline, paroxetine, escitalopram) and MAOIs.',
             'management'=>'Allow 14-day washout after stopping MAOI before starting SSRI; 5 weeks after fluoxetine before MAOI.'],

            ['drug1_name'=>'linezolid',    'drug2_name'=>'sertraline',    'severity'=>'contraindicated',
             'description'=>'Linezolid has MAOI activity; combination with sertraline causes serotonin syndrome.',
             'management'=>'Avoid. Use alternative antibiotic or wait 2 weeks after stopping sertraline.'],

            ['drug1_name'=>'linezolid',    'drug2_name'=>'fluoxetine',    'severity'=>'contraindicated',
             'description'=>'Linezolid + fluoxetine causes serotonin syndrome due to MAO inhibition.',
             'management'=>'Avoid. Wait at least 5 weeks after stopping fluoxetine before linezolid.'],

            ['drug1_name'=>'pethidine',    'drug2_name'=>'maoi',          'severity'=>'contraindicated',
             'description'=>'Serotonin syndrome, hyperpyrexia, coma, and death reported with pethidine/meperidine + MAOIs.',
             'management'=>'Absolutely contraindicated. Use alternative analgesics.'],

            ['drug1_name'=>'simvastatin',  'drug2_name'=>'itraconazole',  'severity'=>'contraindicated',
             'description'=>'Itraconazole markedly increases simvastatin levels → severe myopathy/rhabdomyolysis.',
             'management'=>'Contraindicated. Switch to pravastatin or rosuvastatin if antifungal needed.'],

            ['drug1_name'=>'simvastatin',  'drug2_name'=>'ketoconazole',  'severity'=>'contraindicated',
             'description'=>'Ketoconazole drastically increases simvastatin AUC → rhabdomyolysis.',
             'management'=>'Contraindicated. Use pravastatin or rosuvastatin.'],

            ['drug1_name'=>'methotrexate', 'drug2_name'=>'trimethoprim',  'severity'=>'contraindicated',
             'description'=>'Both inhibit folate metabolism; combined use causes severe pancytopenia.',
             'management'=>'Avoid. Monitor closely if unavoidable; supplement with folinic acid.'],

            // ── MAJOR ─────────────────────────────────────────────────────────
            ['drug1_name'=>'warfarin',     'drug2_name'=>'aspirin',       'severity'=>'major',
             'description'=>'Both inhibit clotting; aspirin also inhibits platelet function. Serious bleeding risk including GI haemorrhage.',
             'management'=>'Avoid combination if possible. If both needed, use minimum aspirin dose (75mg) and monitor INR closely.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'metronidazole', 'severity'=>'major',
             'description'=>'Metronidazole inhibits CYP2C9, markedly increasing warfarin levels and INR.',
             'management'=>'Reduce warfarin dose by 30–50% during metronidazole course. Monitor INR every 2–3 days.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'fluconazole',   'severity'=>'major',
             'description'=>'Fluconazole inhibits CYP2C9, significantly increasing warfarin effect and bleeding risk.',
             'management'=>'Reduce warfarin dose 25–50%. Monitor INR 2 days after starting fluconazole and 1 week after stopping.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'amiodarone',    'severity'=>'major',
             'description'=>'Amiodarone inhibits CYP2C9/CYP3A4, increasing warfarin levels 2–3 fold. Effect lasts months after stopping amiodarone.',
             'management'=>'Reduce warfarin by 30–50%, monitor INR weekly initially. Effect persists for weeks after stopping amiodarone.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'rifampicin',    'severity'=>'major',
             'description'=>'Rifampicin is a potent CYP inducer; dramatically reduces warfarin effect leading to sub-therapeutic anticoagulation.',
             'management'=>'Increase warfarin dose 2–5×. Monitor INR daily when starting/stopping rifampicin.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'carbamazepine', 'severity'=>'major',
             'description'=>'Carbamazepine induces CYP2C9, reducing warfarin levels and anticoagulation efficacy.',
             'management'=>'Increase warfarin dose; monitor INR closely when carbamazepine started or stopped.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'ciprofloxacin', 'severity'=>'major',
             'description'=>'Ciprofloxacin inhibits CYP1A2, reducing warfarin metabolism and increasing bleeding risk.',
             'management'=>'Monitor INR within 2 days of starting ciprofloxacin; anticipate 25–30% increase in INR.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'ibuprofen',     'severity'=>'major',
             'description'=>'NSAIDs inhibit platelet function and cause GI ulceration, dramatically increasing bleeding risk with warfarin.',
             'management'=>'Avoid. Use paracetamol for analgesia instead. If NSAID essential, add PPI and monitor INR closely.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'diclofenac',    'severity'=>'major',
             'description'=>'Diclofenac inhibits platelet aggregation and can cause GI bleeding, compounding warfarin anticoagulation.',
             'management'=>'Avoid. Use paracetamol. If diclofenac needed, co-prescribe PPI and monitor INR frequently.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'phenytoin',     'severity'=>'major',
             'description'=>'Complex bidirectional interaction: phenytoin can initially increase then decrease warfarin levels. Unpredictable INR changes.',
             'management'=>'Monitor INR very closely. Both phenytoin toxicity and anticoagulation changes can occur.'],

            ['drug1_name'=>'digoxin',      'drug2_name'=>'amiodarone',    'severity'=>'major',
             'description'=>'Amiodarone reduces digoxin clearance by 50%, causing digoxin toxicity (nausea, bradycardia, arrhythmias).',
             'management'=>'Reduce digoxin dose by 50% when starting amiodarone. Monitor serum digoxin levels and ECG.'],

            ['drug1_name'=>'digoxin',      'drug2_name'=>'verapamil',     'severity'=>'major',
             'description'=>'Verapamil reduces renal/extrarenal digoxin clearance, causing digoxin toxicity. Additive bradycardia risk.',
             'management'=>'Reduce digoxin dose by 30–50%. Monitor serum levels and heart rate. ECG monitoring recommended.'],

            ['drug1_name'=>'verapamil',    'drug2_name'=>'metoprolol',    'severity'=>'major',
             'description'=>'Both slow AV conduction; combination can cause complete heart block and severe bradycardia.',
             'management'=>'Avoid IV verapamil in patients on beta-blockers. If oral combination needed, monitor ECG and heart rate closely.'],

            ['drug1_name'=>'verapamil',    'drug2_name'=>'atenolol',      'severity'=>'major',
             'description'=>'Verapamil + beta-blocker (atenolol) can cause bradycardia, AV block, and cardiac arrest.',
             'management'=>'Avoid IV combination. Oral: use low doses with careful ECG monitoring.'],

            ['drug1_name'=>'simvastatin',  'drug2_name'=>'amiodarone',    'severity'=>'major',
             'description'=>'Amiodarone inhibits CYP3A4, increasing simvastatin levels 3–4 fold. Risk of myopathy/rhabdomyolysis.',
             'management'=>'Do not exceed simvastatin 20mg/day with amiodarone. Consider switching to pravastatin (not metabolised by CYP3A4).'],

            ['drug1_name'=>'atorvastatin', 'drug2_name'=>'clarithromycin','severity'=>'major',
             'description'=>'Clarithromycin inhibits CYP3A4, significantly raising atorvastatin levels → myopathy/rhabdomyolysis.',
             'management'=>'Temporarily stop atorvastatin during clarithromycin course, or use azithromycin instead.'],

            ['drug1_name'=>'lithium',      'drug2_name'=>'ibuprofen',     'severity'=>'major',
             'description'=>'NSAIDs reduce renal lithium clearance, causing lithium toxicity (tremor, confusion, renal failure).',
             'management'=>'Avoid NSAIDs with lithium. Use paracetamol for pain. If NSAID unavoidable, reduce lithium dose and monitor levels.'],

            ['drug1_name'=>'lithium',      'drug2_name'=>'diclofenac',    'severity'=>'major',
             'description'=>'Diclofenac increases lithium levels by reducing renal clearance, risking toxicity.',
             'management'=>'Avoid. Use paracetamol. Monitor lithium levels carefully if combination unavoidable.'],

            ['drug1_name'=>'lithium',      'drug2_name'=>'furosemide',    'severity'=>'major',
             'description'=>'Furosemide increases lithium reabsorption in proximal tubule, risking toxicity.',
             'management'=>'Monitor lithium levels regularly. Start with low lithium dose and titrate carefully.'],

            ['drug1_name'=>'theophylline', 'drug2_name'=>'ciprofloxacin', 'severity'=>'major',
             'description'=>'Ciprofloxacin inhibits CYP1A2, raising theophylline levels 2–3 fold. Risk of theophylline toxicity (arrhythmias, seizures).',
             'management'=>'Reduce theophylline dose by 30–50%. Monitor serum levels closely when adding ciprofloxacin.'],

            ['drug1_name'=>'theophylline', 'drug2_name'=>'erythromycin',  'severity'=>'major',
             'description'=>'Erythromycin inhibits CYP1A2/CYP3A4, increasing theophylline levels 30–40%. Toxicity risk.',
             'management'=>'Monitor theophylline levels. Reduce dose if levels rise above 15 mcg/mL. Consider azithromycin instead.'],

            ['drug1_name'=>'ssri',         'drug2_name'=>'tramadol',      'severity'=>'major',
             'description'=>'SSRIs (fluoxetine, paroxetine) + tramadol can cause serotonin syndrome: agitation, fever, clonus, tachycardia.',
             'management'=>'Monitor for signs of serotonin syndrome. Use alternative analgesic (e.g., opioids without serotonergic activity) if possible.'],

            ['drug1_name'=>'spironolactone','drug2_name'=>'enalapril',    'severity'=>'major',
             'description'=>'ACE inhibitor + spironolactone causes dangerous hyperkalaemia, especially in renal impairment.',
             'management'=>'Monitor serum potassium and renal function within 1 week of combination. Avoid if K+ >5.0 mEq/L.'],

            ['drug1_name'=>'spironolactone','drug2_name'=>'lisinopril',   'severity'=>'major',
             'description'=>'ACE inhibitor + spironolactone causes dangerous hyperkalaemia.',
             'management'=>'Monitor electrolytes and renal function closely. Restrict in diabetic nephropathy.'],

            ['drug1_name'=>'enalapril',    'drug2_name'=>'losartan',      'severity'=>'major',
             'description'=>'ACE inhibitor + ARB combination doubles risk of hypotension, hyperkalaemia, and acute kidney injury.',
             'management'=>'Avoid combination. Not recommended in diabetic nephropathy. Monitor closely if both prescribed.'],

            ['drug1_name'=>'metformin',    'drug2_name'=>'contrast',      'severity'=>'major',
             'description'=>'IV contrast media with metformin can cause lactic acidosis due to contrast-induced nephropathy impairing metformin clearance.',
             'management'=>'Stop metformin 48 hours before IV contrast. Restart only when creatinine is normal 48 hours post-procedure.'],

            ['drug1_name'=>'metronidazole','drug2_name'=>'alcohol',       'severity'=>'major',
             'description'=>'Disulfiram-like reaction: flushing, vomiting, tachycardia, hypotension — can be severe.',
             'management'=>'Avoid alcohol completely during metronidazole treatment and 48 hours after last dose.'],

            ['drug1_name'=>'rifampicin',   'drug2_name'=>'oral contraceptive', 'severity'=>'major',
             'description'=>'Rifampicin strongly induces CYP3A4, reducing plasma levels of oral contraceptives leading to contraceptive failure.',
             'management'=>'Use additional contraceptive method during rifampicin treatment and for 4 weeks after. Consider depot or IUD.'],

            ['drug1_name'=>'clopidogrel',  'drug2_name'=>'aspirin',       'severity'=>'major',
             'description'=>'Dual antiplatelet therapy: significantly increased GI and intracranial bleeding risk. If combined with an anticoagulant (warfarin/acenocoumarol) this becomes triple antithrombotic therapy — extremely high bleed risk.',
             'management'=>'Use only when specifically indicated (ACS within 12 months, recent coronary stent). Always add PPI. Limit triple therapy duration. Monitor closely for any bleeding.'],

            ['drug1_name'=>'methotrexate', 'drug2_name'=>'ibuprofen',     'severity'=>'major',
             'description'=>'NSAIDs reduce renal methotrexate clearance, leading to accumulation and toxicity (myelosuppression, mucositis).',
             'management'=>'Avoid NSAIDs with methotrexate. Use paracetamol. If NSAID unavoidable, hold methotrexate and monitor FBC/creatinine.'],

            ['drug1_name'=>'ketoconazole', 'drug2_name'=>'midazolam',     'severity'=>'major',
             'description'=>'Ketoconazole inhibits CYP3A4, dramatically increasing midazolam levels → prolonged sedation.',
             'management'=>'Avoid combination or reduce midazolam dose significantly with close monitoring.'],

            // ── MODERATE ─────────────────────────────────────────────────────
            ['drug1_name'=>'enalapril',    'drug2_name'=>'ibuprofen',     'severity'=>'moderate',
             'description'=>'NSAIDs reduce ACE inhibitor efficacy via prostaglandin inhibition, and increase risk of acute kidney injury.',
             'management'=>'Avoid concurrent use. Use paracetamol for analgesia. Monitor BP and renal function if combination necessary.'],

            ['drug1_name'=>'lisinopril',   'drug2_name'=>'ibuprofen',     'severity'=>'moderate',
             'description'=>'NSAIDs reduce ACE inhibitor antihypertensive effect and can precipitate renal failure.',
             'management'=>'Use paracetamol instead. Monitor renal function and blood pressure if both are needed.'],

            ['drug1_name'=>'metoprolol',   'drug2_name'=>'insulin',       'severity'=>'moderate',
             'description'=>'Beta-blockers mask tachycardia (an early warning of hypoglycaemia). Sweating may still occur.',
             'management'=>'Educate diabetic patients to monitor glucose regularly. Consider cardioselective beta-blockers (atenolol, bisoprolol) at low doses.'],

            ['drug1_name'=>'atenolol',     'drug2_name'=>'insulin',       'severity'=>'moderate',
             'description'=>'Beta-blockers mask sympathetic signs of hypoglycaemia in insulin-treated diabetics.',
             'management'=>'Monitor glucose more frequently. Prefer cardioselective agents. Educate patient on non-adrenergic hypoglycaemia symptoms.'],

            ['drug1_name'=>'digoxin',      'drug2_name'=>'furosemide',    'severity'=>'moderate',
             'description'=>'Furosemide causes hypokalaemia, sensitising the myocardium to digoxin toxicity.',
             'management'=>'Monitor serum potassium. Supplement potassium or use potassium-sparing diuretic. Check digoxin levels regularly.'],

            ['drug1_name'=>'amlodipine',   'drug2_name'=>'simvastatin',   'severity'=>'moderate',
             'description'=>'Amlodipine inhibits CYP3A4, increasing simvastatin exposure ~77%. Myopathy risk increases.',
             'management'=>'Do not exceed simvastatin 20mg/day when combined with amlodipine. Consider rosuvastatin or pravastatin.'],

            ['drug1_name'=>'ciprofloxacin','drug2_name'=>'antacid',       'severity'=>'moderate',
             'description'=>'Antacids containing aluminium/magnesium/calcium chelate ciprofloxacin, reducing absorption by up to 90%.',
             'management'=>'Administer ciprofloxacin 2 hours before or 6 hours after antacids.'],

            ['drug1_name'=>'doxycycline',  'drug2_name'=>'antacid',       'severity'=>'moderate',
             'description'=>'Antacids reduce doxycycline absorption by chelation. Also applies to calcium, iron, and dairy products.',
             'management'=>'Take doxycycline 2 hours before or 3 hours after antacids/dairy products.'],

            ['drug1_name'=>'tetracycline', 'drug2_name'=>'antacid',       'severity'=>'moderate',
             'description'=>'Tetracycline absorption markedly reduced by divalent/trivalent cations in antacids.',
             'management'=>'Take tetracycline on empty stomach; separate from antacids by at least 2 hours.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'paracetamol',   'severity'=>'moderate',
             'description'=>'High-dose paracetamol (>2g/day regularly) inhibits CYP2C9, modestly increasing INR.',
             'management'=>'Use lowest effective paracetamol dose. Monitor INR if patient takes paracetamol >2g/day regularly.'],

            ['drug1_name'=>'aspirin',      'drug2_name'=>'ibuprofen',     'severity'=>'moderate',
             'description'=>'Ibuprofen competitively inhibits COX-1, potentially reducing aspirin\'s cardioprotective antiplatelet effect.',
             'management'=>'Take aspirin at least 60 minutes before or 8 hours after ibuprofen if concurrent use necessary.'],

            ['drug1_name'=>'ssri',         'drug2_name'=>'ibuprofen',     'severity'=>'moderate',
             'description'=>'SSRIs inhibit platelet serotonin uptake; combined with NSAIDs significantly increases GI bleeding risk.',
             'management'=>'Add PPI (omeprazole, pantoprazole) as gastroprotection. Use paracetamol where possible.'],

            ['drug1_name'=>'ssri',         'drug2_name'=>'aspirin',       'severity'=>'moderate',
             'description'=>'Both affect platelet function via different mechanisms; combination increases bleeding risk.',
             'management'=>'Consider PPI cover. Monitor for unusual bruising or GI symptoms.'],

            ['drug1_name'=>'clonazepam',   'drug2_name'=>'alcohol',       'severity'=>'moderate',
             'description'=>'Additive CNS and respiratory depression; risk of coma and respiratory arrest.',
             'management'=>'Counsel patients to avoid alcohol with benzodiazepines. Monitor for oversedation.'],

            ['drug1_name'=>'diazepam',     'drug2_name'=>'alcohol',       'severity'=>'moderate',
             'description'=>'Alcohol potentiates CNS depression of benzodiazepines; increased sedation and respiratory depression.',
             'management'=>'Avoid alcohol during benzodiazepine treatment. Warn patients explicitly.'],

            ['drug1_name'=>'opioid',       'drug2_name'=>'benzodiazepine','severity'=>'moderate',
             'description'=>'Combined opioid + benzodiazepine significantly increases risk of respiratory depression and overdose death.',
             'management'=>'Avoid combination when possible. If both needed, use lowest effective doses, monitor respiratory rate.'],

            ['drug1_name'=>'morphine',     'drug2_name'=>'diazepam',      'severity'=>'moderate',
             'description'=>'Additive CNS and respiratory depression with morphine + diazepam.',
             'management'=>'Use minimal doses. Monitor closely for sedation and respiratory rate.'],

            ['drug1_name'=>'gabapentin',   'drug2_name'=>'opioid',        'severity'=>'moderate',
             'description'=>'Gabapentinoids + opioids synergistically increase respiratory depression risk.',
             'management'=>'Monitor sedation scores. Avoid in patients with sleep apnoea or respiratory compromise.'],

            ['drug1_name'=>'clopidogrel',  'drug2_name'=>'omeprazole',    'severity'=>'moderate',
             'description'=>'Omeprazole inhibits CYP2C19, reducing conversion of clopidogrel to active metabolite — reducing antiplatelet effect.',
             'management'=>'Prefer pantoprazole over omeprazole for gastroprotection in patients on clopidogrel.'],

            ['drug1_name'=>'phenytoin',    'drug2_name'=>'carbamazepine', 'severity'=>'moderate',
             'description'=>'Both induce hepatic enzymes and can alter each other\'s serum levels unpredictably.',
             'management'=>'Monitor drug levels and clinical response closely. Adjust doses based on serum levels.'],

            ['drug1_name'=>'clonidine',    'drug2_name'=>'metoprolol',    'severity'=>'moderate',
             'description'=>'Abrupt clonidine withdrawal with concurrent beta-blocker causes severe rebound hypertension.',
             'management'=>'Taper and stop beta-blocker several days before discontinuing clonidine. Never stop clonidine abruptly.'],

            ['drug1_name'=>'metformin',    'drug2_name'=>'alcohol',       'severity'=>'moderate',
             'description'=>'Alcohol potentiates metformin\'s risk of lactic acidosis, particularly in fasting patients.',
             'management'=>'Advise patients to avoid excessive alcohol. Binge drinking is especially dangerous.'],

            ['drug1_name'=>'iron',         'drug2_name'=>'antacid',       'severity'=>'moderate',
             'description'=>'Antacids reduce iron absorption by raising gastric pH and by chelation.',
             'management'=>'Take iron supplements 2 hours before or 4 hours after antacids. Take with vitamin C to enhance absorption.'],

            ['drug1_name'=>'iron',         'drug2_name'=>'calcium',       'severity'=>'moderate',
             'description'=>'Calcium salts reduce iron absorption. Also applies to calcium-rich foods (dairy).',
             'management'=>'Separate iron and calcium supplements by 2 hours. Do not take iron with milk.'],

            ['drug1_name'=>'fluoroquinolone','drug2_name'=>'iron',        'severity'=>'moderate',
             'description'=>'Iron chelates fluoroquinolones (ciprofloxacin, levofloxacin), reducing their absorption significantly.',
             'management'=>'Take fluoroquinolones 2 hours before or 4 hours after iron supplements.'],

            ['drug1_name'=>'levothyroxine','drug2_name'=>'calcium',       'severity'=>'moderate',
             'description'=>'Calcium impairs levothyroxine absorption, potentially causing hypothyroidism.',
             'management'=>'Take levothyroxine at least 4 hours apart from calcium supplements. Recheck TFTs after any change.'],

            ['drug1_name'=>'levothyroxine','drug2_name'=>'antacid',       'severity'=>'moderate',
             'description'=>'Antacids reduce levothyroxine absorption; this may cause inadequate thyroid replacement.',
             'management'=>'Take levothyroxine 30–60 minutes before breakfast; separate from antacids by 4 hours.'],

            ['drug1_name'=>'fluoxetine',   'drug2_name'=>'tamoxifen',     'severity'=>'moderate',
             'description'=>'Fluoxetine inhibits CYP2D6, reducing conversion of tamoxifen to its active metabolite (endoxifen), reducing efficacy.',
             'management'=>'Use paroxetine-free and fluoxetine-free antidepressants (e.g., venlafaxine, citalopram) with tamoxifen.'],

            ['drug1_name'=>'warfarin',     'drug2_name'=>'clarithromycin','severity'=>'moderate',
             'description'=>'Clarithromycin inhibits CYP3A4/CYP2C9 — can increase warfarin levels and INR.',
             'management'=>'Monitor INR during clarithromycin course and 1 week after stopping.'],

            ['drug1_name'=>'spironolactone','drug2_name'=>'potassium',    'severity'=>'moderate',
             'description'=>'Spironolactone retains potassium; additional potassium supplementation can cause hyperkalaemia.',
             'management'=>'Avoid routine potassium supplementation. Monitor serum potassium closely.'],

            ['drug1_name'=>'furosemide',   'drug2_name'=>'aminoglycoside','severity'=>'moderate',
             'description'=>'Both are ototoxic; combination increases risk of permanent hearing loss.',
             'management'=>'Monitor renal function and hearing. Use minimum effective doses and duration.'],

            ['drug1_name'=>'amiodarone',   'drug2_name'=>'metoprolol',    'severity'=>'moderate',
             'description'=>'Additive effect on cardiac conduction: bradycardia and AV block risk.',
             'management'=>'Monitor ECG and heart rate. Reduce beta-blocker dose if bradycardia occurs.'],

            ['drug1_name'=>'fluoxetine',   'drug2_name'=>'warfarin',      'severity'=>'moderate',
             'description'=>'Fluoxetine inhibits CYP2C9, can increase warfarin concentrations and INR.',
             'management'=>'Monitor INR when starting/stopping fluoxetine. Adjust warfarin dose accordingly.'],

            ['drug1_name'=>'dexamethasone','drug2_name'=>'ibuprofen',     'severity'=>'moderate',
             'description'=>'Corticosteroids + NSAIDs significantly increase risk of GI ulceration and bleeding.',
             'management'=>'Add PPI (omeprazole or pantoprazole) whenever both are prescribed. Monitor for GI symptoms.'],

            ['drug1_name'=>'prednisolone', 'drug2_name'=>'ibuprofen',     'severity'=>'moderate',
             'description'=>'Corticosteroid + NSAID combination increases GI bleed risk 15-fold.',
             'management'=>'Co-prescribe PPI. Use minimum effective doses. Advise patient on GI symptoms.'],

            ['drug1_name'=>'metformin',    'drug2_name'=>'furosemide',    'severity'=>'moderate',
             'description'=>'Furosemide increases metformin plasma levels by reducing renal clearance, increasing lactic acidosis risk.',
             'management'=>'Monitor renal function. If creatinine rises, suspend metformin.'],

            ['drug1_name'=>'allopurinol',  'drug2_name'=>'azathioprine',  'severity'=>'moderate',
             'description'=>'Allopurinol inhibits xanthine oxidase, markedly increasing azathioprine levels → bone marrow suppression.',
             'management'=>'Reduce azathioprine dose by 75% if allopurinol is essential. Monitor FBC closely.'],

            // ── MINOR ─────────────────────────────────────────────────────────
            ['drug1_name'=>'paracetamol',  'drug2_name'=>'metoclopramide','severity'=>'minor',
             'description'=>'Metoclopramide increases the rate of paracetamol absorption; useful therapeutically but can lead to faster peak levels.',
             'management'=>'No action needed — this is generally a beneficial interaction for pain management.'],

            ['drug1_name'=>'aspirin',      'drug2_name'=>'antacid',       'severity'=>'minor',
             'description'=>'Antacids reduce aspirin absorption modestly and may reduce its analgesic/antiplatelet effect.',
             'management'=>'Take aspirin 2 hours before antacid if analgesic effect is important.'],

            ['drug1_name'=>'iron',         'drug2_name'=>'ciprofloxacin', 'severity'=>'minor',
             'description'=>'Iron can chelate ciprofloxacin, slightly reducing its absorption.',
             'management'=>'Separate doses by at least 2 hours.'],

            ['drug1_name'=>'metformin',    'drug2_name'=>'vitamin b12',   'severity'=>'minor',
             'description'=>'Long-term metformin use reduces vitamin B12 absorption from the gut.',
             'management'=>'Monitor B12 levels annually in long-term metformin users. Supplement if deficient.'],

            ['drug1_name'=>'antacid',      'drug2_name'=>'levofloxacin',  'severity'=>'minor',
             'description'=>'Aluminium/magnesium antacids chelate levofloxacin, reducing its bioavailability.',
             'management'=>'Take levofloxacin 2 hours before or 2 hours after antacids.'],

            // ── ACENOCOUMAROL (Acitrom) — vitamin K antagonist like warfarin ─
            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'aspirin',       'severity'=>'major',
             'description'=>'Aspirin inhibits platelet function AND displaces acenocoumarol from plasma proteins, markedly increasing bleeding risk. Triple antithrombotic therapy (Acitrom + Ecosprin + Clopid) is extremely high risk.',
             'management'=>'Avoid unless specifically indicated (e.g. prosthetic valve + ACS). If unavoidable, use lowest aspirin dose (75mg), add PPI, and monitor INR every 3–5 days.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'clopidogrel',   'severity'=>'major',
             'description'=>'Acenocoumarol + clopidogrel dual/triple antithrombotic combination: major bleeding risk including intracranial and GI haemorrhage.',
             'management'=>'Avoid unless essential (mechanical valve + ACS/stent). If required, add PPI, restrict duration of clopidogrel, and monitor INR closely.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'metronidazole', 'severity'=>'major',
             'description'=>'Metronidazole inhibits CYP2C9, markedly increasing acenocoumarol levels and INR.',
             'management'=>'Reduce acenocoumarol dose by ~30–50% during metronidazole course. Monitor INR every 2–3 days.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'fluconazole',   'severity'=>'major',
             'description'=>'Fluconazole inhibits CYP2C9, significantly increasing acenocoumarol effect and bleeding risk.',
             'management'=>'Reduce acenocoumarol dose 25–50%. Monitor INR 2 days after starting and 1 week after stopping fluconazole.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'amiodarone',    'severity'=>'major',
             'description'=>'Amiodarone inhibits CYP2C9/CYP3A4, increasing acenocoumarol levels 2–3 fold. Effect lasts months.',
             'management'=>'Reduce acenocoumarol by 30–50%, monitor INR weekly initially. Effect persists for weeks after stopping amiodarone.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'rifampicin',    'severity'=>'major',
             'description'=>'Rifampicin strongly induces CYP2C9, dramatically reducing acenocoumarol effect.',
             'management'=>'Increase acenocoumarol dose; monitor INR daily when starting/stopping rifampicin.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'ibuprofen',     'severity'=>'major',
             'description'=>'NSAIDs inhibit platelet function and cause GI ulceration, dramatically increasing bleeding risk with acenocoumarol.',
             'management'=>'Avoid. Use paracetamol for analgesia. Add PPI if NSAID is essential. Monitor INR closely.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'diclofenac',    'severity'=>'major',
             'description'=>'Diclofenac + acenocoumarol: GI bleed risk and enhanced anticoagulation.',
             'management'=>'Avoid. Use paracetamol. If diclofenac needed, add PPI and monitor INR frequently.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'paracetamol',   'severity'=>'moderate',
             'description'=>'High-dose paracetamol (>2g/day) modestly inhibits acenocoumarol metabolism, increasing INR.',
             'management'=>'Use lowest effective paracetamol dose. Monitor INR if >2g/day regularly.'],

            ['drug1_name'=>'acenocoumarol', 'drug2_name'=>'ciprofloxacin', 'severity'=>'major',
             'description'=>'Ciprofloxacin inhibits CYP1A2, reducing acenocoumarol metabolism and increasing bleeding risk.',
             'management'=>'Monitor INR within 2 days of starting ciprofloxacin. Anticipate 25–30% rise in INR.'],

        ];

        foreach ($interactions as $data) {
            DrugInteraction::create($data);
        }
    }
}
