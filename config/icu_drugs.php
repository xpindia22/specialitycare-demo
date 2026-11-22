<?php
/*
 | ICU drug library — used by the infusion calculator, the ICU chart and the
 | emergency dose sheet. Doses are standard adult critical-care references
 | (ACLS / common ICU protocols); every screen tells staff to verify against
 | the hospital protocol and the ampoule in hand.
 |
 | Infusions
 |   unit      dose unit: <mg|mcg|U>[/kg]/<min|h>
 |   amount    unit of the drug in the syringe/bag (mg, mcg or U)
 |   dilutions [amount, volume ml, label] — first is the default
 |   range     usual dose range; max = dose above which a warning shows
 |
 | Bolus / emergency
 |   dose      [low, high] per kg when per_kg, else fixed; strength = per ml
 */

return [

    'infusions' => [
        'noradrenaline' => [
            'name' => 'Noradrenaline', 'aka' => 'Norepinephrine, Norad', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[4, 50, '4 mg in 50 ml'], [8, 50, '8 mg in 50 ml (double strength)'], [16, 50, '16 mg in 50 ml (quad strength)'], [2, 50, '2 mg in 50 ml']],
            'range' => [0.05, 0.5], 'max' => 1, 'start' => 0.05, 'step' => 0.05,
            'note' => 'Target MAP ≥ 65. Central line preferred. Check the ampoule: 2 mg noradrenaline bitartrate = 1 mg base.',
        ],
        'adrenaline' => [
            'name' => 'Adrenaline', 'aka' => 'Epinephrine', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[4, 50, '4 mg in 50 ml'], [2, 50, '2 mg in 50 ml'], [1, 50, '1 mg in 50 ml']],
            'range' => [0.01, 0.3], 'max' => 0.5, 'start' => 0.02, 'step' => 0.02,
            'note' => 'Watch lactate, glucose and arrhythmias.',
        ],
        'dopamine' => [
            'name' => 'Dopamine', 'aka' => '', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[200, 50, '200 mg in 50 ml'], [400, 50, '400 mg in 50 ml'], [200, 100, '200 mg in 100 ml']],
            'range' => [5, 15], 'max' => 20, 'start' => 5, 'step' => 2.5,
            'note' => '5–10 mostly β (inotrope), >10 α (pressor). Tachyarrhythmia risk.',
        ],
        'dobutamine' => [
            'name' => 'Dobutamine', 'aka' => '', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[250, 50, '250 mg in 50 ml'], [500, 50, '500 mg in 50 ml'], [250, 250, '250 mg in 250 ml']],
            'range' => [2.5, 10], 'max' => 20, 'start' => 2.5, 'step' => 2.5,
            'note' => 'Inotrope; can drop BP (vasodilates). Often paired with noradrenaline.',
        ],
        'vasopressin' => [
            'name' => 'Vasopressin', 'aka' => '', 'group' => 'Vasopressor / inotrope',
            'unit' => 'U/min', 'amount' => 'U',
            'dilutions' => [[20, 50, '20 U in 50 ml'], [40, 100, '40 U in 100 ml']],
            'range' => [0.01, 0.03], 'max' => 0.04, 'start' => 0.03, 'step' => 0.01,
            'note' => 'Usually a fixed 0.03 U/min added to noradrenaline. Not titrated like a pressor.',
        ],
        'phenylephrine' => [
            'name' => 'Phenylephrine', 'aka' => '', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/min', 'amount' => 'mg',
            'dilutions' => [[10, 100, '10 mg in 100 ml'], [10, 50, '10 mg in 50 ml']],
            'range' => [20, 180], 'max' => 200, 'start' => 20, 'step' => 10,
            'note' => 'Pure α; reflex bradycardia.',
        ],
        'milrinone' => [
            'name' => 'Milrinone', 'aka' => '', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[10, 50, '10 mg in 50 ml'], [20, 100, '20 mg in 100 ml']],
            'range' => [0.25, 0.75], 'max' => 0.75, 'start' => 0.375, 'step' => 0.125,
            'note' => 'Reduce dose in renal failure. Hypotension.',
        ],
        'levosimendan' => [
            'name' => 'Levosimendan', 'aka' => '', 'group' => 'Vasopressor / inotrope',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[12.5, 250, '12.5 mg in 250 ml'], [12.5, 500, '12.5 mg in 500 ml']],
            'range' => [0.05, 0.2], 'max' => 0.2, 'start' => 0.1, 'step' => 0.05,
            'note' => 'Usually run for 24 h. Bolus often omitted (hypotension).',
        ],

        'gtn' => [
            'name' => 'Nitroglycerin', 'aka' => 'GTN, NTG, Glyceryl trinitrate', 'group' => 'Vasodilator / antihypertensive',
            'unit' => 'mcg/min', 'amount' => 'mg',
            'dilutions' => [[50, 50, '50 mg in 50 ml'], [25, 50, '25 mg in 50 ml'], [10, 50, '10 mg in 50 ml']],
            'range' => [5, 200], 'max' => 400, 'start' => 10, 'step' => 10,
            'note' => 'Start 5–10, increase by 5–10 every 3–5 min. Avoid with sildenafil / RV infarct.',
        ],
        'nitroprusside' => [
            'name' => 'Sodium nitroprusside', 'aka' => 'SNP', 'group' => 'Vasodilator / antihypertensive',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[50, 50, '50 mg in 50 ml'], [50, 250, '50 mg in 250 ml']],
            'range' => [0.3, 3], 'max' => 10, 'start' => 0.3, 'step' => 0.3,
            'note' => 'Protect from light. Cyanide toxicity: 10 mcg/kg/min for no more than 10 min.',
        ],
        'labetalol' => [
            'name' => 'Labetalol', 'aka' => '', 'group' => 'Vasodilator / antihypertensive',
            'unit' => 'mg/min', 'amount' => 'mg',
            'dilutions' => [[100, 20, '100 mg in 20 ml (undiluted 5 mg/ml)'], [200, 200, '200 mg in 200 ml']],
            'range' => [0.5, 2], 'max' => 2, 'start' => 0.5, 'step' => 0.5,
            'note' => 'Total usually ≤ 300 mg. Avoid in asthma, heart block, decompensated failure.',
        ],
        'nicardipine' => [
            'name' => 'Nicardipine', 'aka' => '', 'group' => 'Vasodilator / antihypertensive',
            'unit' => 'mg/h', 'amount' => 'mg',
            'dilutions' => [[25, 250, '25 mg in 250 ml'], [20, 200, '20 mg in 200 ml']],
            'range' => [5, 15], 'max' => 15, 'start' => 5, 'step' => 2.5,
            'note' => 'Increase 2.5 mg/h every 5–15 min.',
        ],

        'amiodarone' => [
            'name' => 'Amiodarone', 'aka' => 'Cordarone', 'group' => 'Antiarrhythmic / rate control',
            'unit' => 'mg/min', 'amount' => 'mg',
            'dilutions' => [[900, 500, '900 mg in 500 ml D5'], [450, 250, '450 mg in 250 ml D5'], [300, 50, '300 mg in 50 ml D5 (central only)']],
            'range' => [0.5, 1], 'max' => 1, 'start' => 1, 'step' => 0.5,
            'note' => 'Load 150 mg in 100 ml D5 over 10 min, then 1 mg/min × 6 h, then 0.5 mg/min × 18 h. Dilute in D5 only. Max 2.2 g / 24 h.',
            'protocol' => [['1 mg/min for 6 h (360 mg)', 1], ['0.5 mg/min for 18 h (540 mg)', 0.5]],
        ],
        'esmolol' => [
            'name' => 'Esmolol', 'aka' => '', 'group' => 'Antiarrhythmic / rate control',
            'unit' => 'mcg/kg/min', 'amount' => 'mg',
            'dilutions' => [[500, 50, '500 mg in 50 ml (10 mg/ml)'], [2500, 250, '2500 mg in 250 ml']],
            'range' => [50, 200], 'max' => 300, 'start' => 50, 'step' => 50,
            'note' => 'Load 500 mcg/kg over 1 min; increase by 50 every 4 min.',
        ],
        'diltiazem' => [
            'name' => 'Diltiazem', 'aka' => '', 'group' => 'Antiarrhythmic / rate control',
            'unit' => 'mg/h', 'amount' => 'mg',
            'dilutions' => [[125, 125, '125 mg in 125 ml'], [50, 50, '50 mg in 50 ml']],
            'range' => [5, 15], 'max' => 15, 'start' => 5, 'step' => 2.5,
            'note' => 'After bolus 0.25 mg/kg over 2 min. Avoid in heart failure with low EF.',
        ],
        'furosemide' => [
            'name' => 'Furosemide', 'aka' => 'Lasix', 'group' => 'Diuretic',
            'unit' => 'mg/h', 'amount' => 'mg',
            'dilutions' => [[100, 50, '100 mg in 50 ml'], [250, 50, '250 mg in 50 ml'], [200, 20, '200 mg in 20 ml (undiluted 10 mg/ml)']],
            'range' => [5, 20], 'max' => 40, 'start' => 5, 'step' => 5,
            'note' => 'Give a bolus before starting. Bolus no faster than 4 mg/min (ototoxicity). Watch K⁺ and Mg²⁺.',
        ],

        'heparin' => [
            'name' => 'Heparin (unfractionated)', 'aka' => 'UFH', 'group' => 'Anticoagulant / metabolic',
            'unit' => 'U/kg/h', 'amount' => 'U',
            'dilutions' => [[25000, 50, '25,000 U in 50 ml'], [25000, 250, '25,000 U in 250 ml'], [10000, 50, '10,000 U in 50 ml']],
            'range' => [12, 18], 'max' => 25, 'start' => 18, 'step' => 1,
            'note' => 'VTE: bolus 80 U/kg then 18 U/kg/h. ACS: 60 U/kg (max 4000) then 12 U/kg/h (max 1000 U/h). Titrate to aPTT.',
        ],
        'insulin' => [
            'name' => 'Insulin (regular)', 'aka' => 'Actrapid, Human Actrapid', 'group' => 'Anticoagulant / metabolic',
            'unit' => 'U/h', 'amount' => 'U',
            'dilutions' => [[50, 50, '50 U in 50 ml NS (1 U/ml)']],
            'range' => [1, 10], 'max' => 15, 'start' => 2, 'step' => 1,
            'note' => 'DKA: 0.1 U/kg/h (for 60 kg = 6 U/h). Check glucose hourly; add dextrose when < 250 mg/dl.',
        ],

        'midazolam' => [
            'name' => 'Midazolam', 'aka' => '', 'group' => 'Sedation / analgesia',
            'unit' => 'mg/kg/h', 'amount' => 'mg',
            'dilutions' => [[50, 50, '50 mg in 50 ml'], [100, 50, '100 mg in 50 ml']],
            'range' => [0.02, 0.1], 'max' => 0.2, 'start' => 0.02, 'step' => 0.01,
            'note' => 'Accumulates in renal / hepatic failure. Daily sedation hold.',
        ],
        'fentanyl' => [
            'name' => 'Fentanyl', 'aka' => '', 'group' => 'Sedation / analgesia',
            'unit' => 'mcg/kg/h', 'amount' => 'mcg',
            'dilutions' => [[500, 50, '500 mcg in 50 ml'], [1000, 50, '1000 mcg in 50 ml'], [1000, 100, '1000 mcg in 100 ml']],
            'range' => [0.5, 2], 'max' => 3, 'start' => 0.5, 'step' => 0.25,
            'note' => 'Respiratory depression; chest wall rigidity with fast boluses.',
        ],
        'morphine' => [
            'name' => 'Morphine', 'aka' => '', 'group' => 'Sedation / analgesia',
            'unit' => 'mg/h', 'amount' => 'mg',
            'dilutions' => [[50, 50, '50 mg in 50 ml'], [30, 30, '30 mg in 30 ml']],
            'range' => [1, 5], 'max' => 10, 'start' => 1, 'step' => 1,
            'note' => 'Accumulates in renal failure.',
        ],
        'propofol' => [
            'name' => 'Propofol 1%', 'aka' => '', 'group' => 'Sedation / analgesia',
            'unit' => 'mg/kg/h', 'amount' => 'mg',
            'dilutions' => [[500, 50, '500 mg in 50 ml (undiluted 1%)'], [1000, 50, '1000 mg in 50 ml (2%)']],
            'range' => [0.3, 3], 'max' => 4, 'start' => 0.5, 'step' => 0.25,
            'note' => 'Above 4 mg/kg/h for > 48 h: propofol infusion syndrome. Counts as fat calories (1.1 kcal/ml). Hypotension.',
        ],
        'dexmedetomidine' => [
            'name' => 'Dexmedetomidine', 'aka' => 'Dexmed', 'group' => 'Sedation / analgesia',
            'unit' => 'mcg/kg/h', 'amount' => 'mcg',
            'dilutions' => [[200, 50, '200 mcg in 50 ml'], [400, 50, '400 mcg in 50 ml']],
            'range' => [0.2, 0.7], 'max' => 1.4, 'start' => 0.2, 'step' => 0.1,
            'note' => 'Bradycardia and hypotension; usually no loading dose in ICU.',
        ],
        'atracurium' => [
            'name' => 'Atracurium', 'aka' => '', 'group' => 'Sedation / analgesia',
            'unit' => 'mg/kg/h', 'amount' => 'mg',
            'dilutions' => [[250, 25, '250 mg in 25 ml (undiluted 10 mg/ml)'], [250, 50, '250 mg in 50 ml']],
            'range' => [0.3, 0.6], 'max' => 0.9, 'start' => 0.3, 'step' => 0.1,
            'note' => 'Patient must be sedated. Monitor with train-of-four.',
        ],
    ],

    'bolus' => [
        'Cardiac arrest' => [
            ['name' => 'Adrenaline (1:10,000)', 'dose' => [1, 1], 'unit' => 'mg', 'per_kg' => false, 'strength' => 0.1,
             'how' => 'IV push every 3–5 min, flush 20 ml', 'paeds' => '0.01 mg/kg (0.1 ml/kg of 1:10,000), max 1 mg'],
            ['name' => 'Amiodarone (Cordarone)', 'dose' => [300, 300], 'unit' => 'mg', 'per_kg' => false, 'strength' => 50,
             'how' => 'After 3rd shock, IV push; second dose 150 mg', 'paeds' => '5 mg/kg, max 300 mg'],
            ['name' => 'Lignocaine 2%', 'dose' => [1, 1.5], 'unit' => 'mg', 'per_kg' => true, 'strength' => 20,
             'how' => 'If no amiodarone; repeat 0.5–0.75 mg/kg, max 3 mg/kg', 'paeds' => '1 mg/kg'],
            ['name' => 'Calcium chloride 10%', 'dose' => [10, 10], 'unit' => 'ml', 'per_kg' => false, 'strength' => null,
             'how' => 'Hyperkalaemia / Ca-channel blocker overdose; central line', 'paeds' => '0.2 ml/kg'],
            ['name' => 'Sodium bicarbonate 8.4%', 'dose' => [1, 1], 'unit' => 'mEq', 'per_kg' => true, 'strength' => 1,
             'how' => 'Hyperkalaemia, TCA overdose, severe acidosis', 'paeds' => '1 mEq/kg (use 4.2% in infants)'],
        ],
        'Arrhythmia / rate control' => [
            ['name' => 'Adenosine', 'dose' => [6, 6], 'unit' => 'mg', 'per_kg' => false, 'strength' => 3,
             'how' => 'Rapid IV push + 20 ml flush; then 12 mg, then 12 mg', 'paeds' => '0.1 mg/kg (max 6), then 0.2 mg/kg (max 12)'],
            ['name' => 'Amiodarone (Cordarone) loading', 'dose' => [150, 150], 'unit' => 'mg', 'per_kg' => false, 'strength' => 50,
             'how' => 'In 100 ml D5 over 10 min, then infusion 1 mg/min', 'paeds' => '5 mg/kg over 20–60 min'],
            ['name' => 'Metoprolol', 'dose' => [5, 5], 'unit' => 'mg', 'per_kg' => false, 'strength' => 1,
             'how' => 'Slow IV over 2 min, every 5 min, max 15 mg. Hold if HR < 60 or SBP < 100', 'paeds' => ''],
            ['name' => 'Esmolol loading', 'dose' => [0.5, 0.5], 'unit' => 'mg', 'per_kg' => true, 'strength' => 10,
             'how' => 'Over 1 min, then infusion 50 mcg/kg/min', 'paeds' => ''],
            ['name' => 'Diltiazem', 'dose' => [0.25, 0.25], 'unit' => 'mg', 'per_kg' => true, 'strength' => 5,
             'how' => 'Over 2 min; after 15 min 0.35 mg/kg if needed', 'paeds' => ''],
            ['name' => 'Digoxin', 'dose' => [0.25, 0.5], 'unit' => 'mg', 'per_kg' => false, 'strength' => 0.25,
             'how' => 'Slow IV; total load 0.75–1 mg in 24 h (less in renal failure)', 'paeds' => ''],
            ['name' => 'Atropine', 'dose' => [1, 1], 'unit' => 'mg', 'per_kg' => false, 'strength' => 0.6,
             'how' => 'Bradycardia: every 3–5 min, max 3 mg (ampoule 0.6 mg/ml)', 'paeds' => '0.02 mg/kg (min 0.1, max 0.5 mg)'],
            ['name' => 'Magnesium sulphate 50%', 'dose' => [2, 2], 'unit' => 'g', 'per_kg' => false, 'strength' => 0.5,
             'how' => 'Torsades: over 10–15 min, diluted', 'paeds' => '25–50 mg/kg, max 2 g'],
        ],
        'Blood pressure / heart failure' => [
            ['name' => 'Furosemide (Lasix)', 'dose' => [0.5, 1], 'unit' => 'mg', 'per_kg' => true, 'strength' => 10,
             'how' => 'Slow IV, not faster than 4 mg/min. Usual 20–80 mg; double if already on oral', 'paeds' => '1 mg/kg, max 40 mg'],
            ['name' => 'Labetalol', 'dose' => [20, 20], 'unit' => 'mg', 'per_kg' => false, 'strength' => 5,
             'how' => 'Over 2 min; then 40–80 mg every 10 min, max 300 mg', 'paeds' => ''],
            ['name' => 'Hydralazine', 'dose' => [5, 10], 'unit' => 'mg', 'per_kg' => false, 'strength' => 20,
             'how' => 'Slow IV every 20–30 min (pre-eclampsia)', 'paeds' => ''],
            ['name' => 'Phenylephrine', 'dose' => [100, 100], 'unit' => 'mcg', 'per_kg' => false, 'strength' => 100,
             'how' => 'Push dose (10 mg in 100 ml = 100 mcg/ml), repeat every 2–5 min', 'paeds' => ''],
        ],
        'Anaphylaxis / airway / metabolic' => [
            ['name' => 'Adrenaline IM (1:1000)', 'dose' => [0.01, 0.01], 'unit' => 'mg', 'per_kg' => true, 'strength' => 1, 'max' => 0.5,
             'how' => 'Anterolateral thigh; repeat every 5 min', 'paeds' => '0.01 mg/kg, max 0.5 mg (0.3 mg under 12 y)'],
            ['name' => 'Hydrocortisone', 'dose' => [100, 200], 'unit' => 'mg', 'per_kg' => false, 'strength' => 50,
             'how' => 'IV (vial 100 mg reconstituted in 2 ml)', 'paeds' => '2–4 mg/kg, max 100 mg'],
            ['name' => 'Dextrose 25%', 'dose' => [25, 25], 'unit' => 'g', 'per_kg' => false, 'strength' => 0.25,
             'how' => 'Hypoglycaemia: 100 ml IV, recheck in 15 min', 'paeds' => '0.5 g/kg as 10% (5 ml/kg)'],
            ['name' => 'Calcium gluconate 10%', 'dose' => [3, 3], 'unit' => 'g', 'per_kg' => false, 'strength' => 0.1,
             'how' => 'Hyperkalaemia: 30 ml over 5–10 min, repeat if ECG unchanged', 'paeds' => '0.5 ml/kg, max 20 ml'],
            ['name' => 'Insulin (regular) for K⁺', 'dose' => [10, 10], 'unit' => 'U', 'per_kg' => false, 'strength' => 100,
             'how' => 'With 25 g dextrose IV (100 ml of 25%); check glucose hourly × 4', 'paeds' => '0.1 U/kg with 0.5 g/kg dextrose'],
            ['name' => 'Naloxone', 'dose' => [0.04, 0.4], 'unit' => 'mg', 'per_kg' => false, 'strength' => 0.4,
             'how' => 'Titrate every 2–3 min to breathing (up to 2 mg; 10 mg total)', 'paeds' => '0.1 mg/kg, max 2 mg'],
            ['name' => 'Magnesium sulphate 50%', 'dose' => [2, 2], 'unit' => 'g', 'per_kg' => false, 'strength' => 0.5,
             'how' => 'Severe asthma / eclampsia load 4 g: over 15–20 min, diluted', 'paeds' => '40 mg/kg over 20 min, max 2 g'],
        ],
        'Intubation (RSI)' => [
            ['name' => 'Fentanyl', 'dose' => [1, 2], 'unit' => 'mcg', 'per_kg' => true, 'strength' => 50, 'how' => 'Pre-treatment, 3 min before induction', 'paeds' => '1–2 mcg/kg'],
            ['name' => 'Midazolam', 'dose' => [0.05, 0.1], 'unit' => 'mg', 'per_kg' => true, 'strength' => 1, 'how' => 'Induction / sedation (use 1 mg/ml)', 'paeds' => '0.1 mg/kg'],
            ['name' => 'Ketamine', 'dose' => [1, 2], 'unit' => 'mg', 'per_kg' => true, 'strength' => 50, 'how' => 'Induction; good in shock / asthma', 'paeds' => '1–2 mg/kg'],
            ['name' => 'Etomidate', 'dose' => [0.3, 0.3], 'unit' => 'mg', 'per_kg' => true, 'strength' => 2, 'how' => 'Induction; haemodynamically stable', 'paeds' => '0.3 mg/kg'],
            ['name' => 'Propofol 1%', 'dose' => [1, 2], 'unit' => 'mg', 'per_kg' => true, 'strength' => 10, 'how' => 'Induction; reduce in shock / elderly', 'paeds' => '2–3 mg/kg'],
            ['name' => 'Succinylcholine', 'dose' => [1.5, 1.5], 'unit' => 'mg', 'per_kg' => true, 'strength' => 50, 'how' => 'Paralysis; avoid in hyperkalaemia, burns > 24 h, crush', 'paeds' => '2 mg/kg (infants)'],
            ['name' => 'Rocuronium', 'dose' => [1, 1.2], 'unit' => 'mg', 'per_kg' => true, 'strength' => 10, 'how' => 'Paralysis (RSI dose)', 'paeds' => '1 mg/kg'],
            ['name' => 'Vecuronium', 'dose' => [0.1, 0.1], 'unit' => 'mg', 'per_kg' => true, 'strength' => 1, 'how' => '10 mg vial in 10 ml', 'paeds' => '0.1 mg/kg'],
        ],
    ],

    // Electrical therapy (joules) — shown on the emergency sheet
    'electrical' => [
        ['VF / pulseless VT (biphasic)', '200 J, then same or higher', '2 J/kg, then 4 J/kg (max 10 J/kg)'],
        ['Synchronised: narrow regular (SVT, flutter)', '50–100 J', '0.5–1 J/kg, then 2 J/kg'],
        ['Synchronised: atrial fibrillation', '120–200 J', '0.5–1 J/kg, then 2 J/kg'],
        ['Synchronised: monomorphic VT with pulse', '100 J', '0.5–1 J/kg, then 2 J/kg'],
    ],
];
