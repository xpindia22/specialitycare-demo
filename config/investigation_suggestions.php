<?php

// Deterministic diagnosis -> suggested investigations rule table, used by
// App\Services\InvestigationSuggester as the fast/free baseline layer before
// the AI layer adds anything extra (e.g. medication-driven monitoring tests).
//
// Each rule's `keywords` are matched (case-insensitive, whole word/phrase —
// e.g. "dm" won't false-match inside an unrelated word) against the
// encounter's diagnosis name, OR as an ICD-10 code prefix match against the
// diagnosis's icd_code.
//
// `panels`/`tests` must be exact LabPanel/LabTest names (resolved the same
// way Lab orders already resolve panel_names[]/test_names[] — see
// LabController::resolveSelectedItems()). Panels are additionally filtered
// by LabPanel::matchesPatient() for gender/age scoping.
//
// `procedures` are NOT lab tests — they're separate modules (ECG, Echo,
// Chest X-ray via Radiology) with their own create forms. Only these three
// exact names are recognised (see InvestigationSuggester::PROCEDURE_ROUTES);
// each renders as a link straight to that module's create-record page for
// this patient, not as an item on the lab order form.
//
// Starting set seeded from this clinic's existing EncounterTemplate lab_orders
// mappings (database/seeders/EncounterTemplateLabsSeeder.php) plus common
// general-medicine presentations. Extend this array to add more diagnoses —
// no code changes needed elsewhere.

return [

    ['keywords' => ['hypertension', 'hypertensive', 'htn', 'high blood pressure', 'i10', 'i11', 'i12', 'i13', 'i15'],
        'panels' => ['Renal Function Test', 'Lipid Profile'], 'tests' => ['Urine Routine', 'HbA1c'], 'procedures' => ['ECG']],

    ['keywords' => ['diabetes', 'diabetic', 't2dm', 't1dm', 'dm', 'e10', 'e11', 'e13'],
        'panels' => ['Diabetes Profile', 'Lipid Profile', 'Renal Function Test'], 'tests' => ['Urine Microalbumin']],

    ['keywords' => ['coronary artery disease', 'cad', 'angina', 'ischemic heart disease', 'ihd', 'i20', 'i25'],
        'panels' => ['Lipid Profile', 'Renal Function Test'], 'tests' => ['HbA1c', 'Blood Glucose Fasting', 'Troponin I'], 'procedures' => ['ECG', 'Echo']],

    ['keywords' => ['myocardial infarction', 'acute coronary syndrome', 'acs', 'i21', 'i22'],
        'panels' => ['Cardiac Marker Panel', 'Lipid Profile', 'Renal Function Test', 'Coagulation Profile'], 'tests' => ['Troponin I', 'D-Dimer'], 'procedures' => ['ECG', 'Echo']],

    ['keywords' => ['heart failure', 'cardiac failure', 'chf', 'i50'],
        'panels' => ['Heart Failure Panel', 'Renal Function Test', 'Electrolyte Panel', 'Liver Function Test'], 'tests' => ['NT-proBNP'], 'procedures' => ['ECG', 'Echo', 'Chest X-ray']],

    ['keywords' => ['atrial fibrillation', 'afib', 'arrhythmia', 'i48'],
        'panels' => ['Renal Function Test', 'Coagulation Profile'], 'tests' => ['CBC', 'LFT'], 'procedures' => ['ECG', 'Echo']],

    ['keywords' => ['chronic kidney disease', 'ckd', 'renal failure', 'n18'],
        'panels' => ['CKD Workup Panel', 'Renal Function Test', 'Electrolyte Panel'], 'tests' => ['Urine Routine']],

    ['keywords' => ['hypothyroidism', 'hyperthyroidism', 'thyroid', 'e03', 'e05'],
        'panels' => ['Thyroid Profile Total'], 'tests' => []],

    ['keywords' => ['viral fever', 'fever', 'urti', 'upper respiratory', 'r50'],
        'panels' => ['Fever Panel Basic'], 'tests' => ['CBC', 'Dengue NS1 Antigen', 'Malaria Antigen', 'Urine Routine']],

    ['keywords' => ['anemia', 'anaemia', 'd50', 'd64'],
        'panels' => ['CBC with ESR and Peripheral Smear', 'Iron Studies'], 'tests' => ['Vitamin B12', 'Reticulocyte Count']],

    ['keywords' => ['rheumatoid arthritis', 'm05', 'm06'],
        'panels' => ['Rheumatoid Arthritis Panel', 'DMARD Monitoring Panel'], 'tests' => []],

    ['keywords' => ['lupus', 'sle', 'm32'],
        'panels' => ['Lupus / SLE Panel', 'Renal Function Test'], 'tests' => ['Urine Routine']],

    ['keywords' => ['gout', 'm10'],
        'panels' => ['Gout Workup'], 'tests' => []],

    ['keywords' => ['asthma', 'copd', 'chronic obstructive', 'j44', 'j45'],
        'panels' => ['Arterial Blood Gas Panel'], 'tests' => ['CBC'], 'procedures' => ['Chest X-ray']],

    ['keywords' => ['pregnancy', 'antenatal', 'anc', 'z34'],
        'panels' => ['Antenatal Profile'], 'tests' => []],

    ['keywords' => ['urinary tract infection', 'uti', 'n39'],
        'panels' => [], 'tests' => ['Urine Routine', 'Urine Culture and Sensitivity']],

    ['keywords' => ['pancreatitis', 'k85'],
        'panels' => ['Pancreatitis Profile'], 'tests' => []],

    ['keywords' => ['hepatitis', 'jaundice', 'k70', 'k71', 'k72', 'k73', 'k74', 'k75', 'k76'],
        'panels' => ['Liver Function Test', 'Hepatitis Screen'], 'tests' => []],

    ['keywords' => ['pcos', 'polycystic ovar'],
        'panels' => ['PCOS Hormonal Profile'], 'tests' => []],

    ['keywords' => ['dyslipidemia', 'hyperlipidemia', 'hypercholesterolemia', 'e78'],
        'panels' => ['Lipid Profile'], 'tests' => []],

    ['keywords' => ['stroke', 'cva', 'cerebrovascular accident', 'tia', 'i63', 'i64', 'g45'],
        'panels' => ['Lipid Profile', 'Coagulation Profile', 'Renal Function Test'], 'tests' => ['Blood Glucose Fasting'], 'procedures' => ['ECG', 'Echo']],

    ['keywords' => ['chest pain', 'r07'],
        'panels' => ['Cardiac Marker Panel'], 'tests' => ['Troponin I'], 'procedures' => ['ECG', 'Echo']],

];
