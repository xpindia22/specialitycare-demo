<?php

/*
 | Speciality visit forms on the encounter page, keyed by department code.
 |
 | A form's tab shows when the visit belongs to that department (token,
 | appointment or the doctor's departments) or the form already has data; any
 | other form can be added from "+ Speciality form".
 |
 | Field types: text, number, select (options), checks (options, several),
 | textarea, date, and refraction (Sph / Cyl / Axis / Add / VA grid).
 | 'eyes' => true gives a Right and a Left value (keys key_r / key_l).
 */

$snellen = ['6/6', '6/9', '6/12', '6/18', '6/24', '6/36', '6/60', '5/60', '4/60', '3/60', '2/60', '1/60', 'CF', 'HM', 'PL+', 'PL−', '6/5', '6/7.5'];

return [

    'OPTH' => [
        'title' => 'Eye Examination', 'short' => 'Eye', 'icon' => 'fa-eye', 'print_label' => 'Print Glasses Rx',
        'sections' => [
            'va' => ['title' => 'Visual acuity', 'icon' => 'fa-glasses', 'fields' => [
                'va_unaided' => ['label' => 'Unaided (distance)', 'type' => 'select', 'options' => $snellen, 'eyes' => true],
                'va_pinhole' => ['label' => 'Pinhole', 'type' => 'select', 'options' => $snellen, 'eyes' => true],
                'va_aided'   => ['label' => 'With glasses', 'type' => 'select', 'options' => $snellen, 'eyes' => true],
                'va_near'    => ['label' => 'Near vision', 'type' => 'select', 'options' => ['N5', 'N6', 'N8', 'N10', 'N12', 'N18', 'N24', 'N36', '<N36'], 'eyes' => true],
            ]],
            'iop' => ['title' => 'Eye pressure (IOP)', 'icon' => 'fa-gauge', 'fields' => [
                'iop'        => ['label' => 'IOP', 'type' => 'number', 'unit' => 'mmHg', 'eyes' => true, 'min' => 0, 'max' => 80, 'warn_above' => 21],
                'iop_method' => ['label' => 'Method', 'type' => 'select', 'options' => ['Non-contact (air puff)', 'Applanation (Goldmann)', 'iCare rebound', 'Schiotz', 'Digital']],
                'iop_time'   => ['label' => 'Time', 'type' => 'text', 'placeholder' => 'e.g. 10:30 AM'],
            ]],
            'refraction' => ['title' => 'Refraction', 'icon' => 'fa-bullseye', 'fields' => [
                'auto_ref'   => ['label' => 'Auto-refractometer', 'type' => 'refraction', 'add' => false],
                'subjective' => ['label' => 'Subjective (accepted)', 'type' => 'refraction', 'add' => true],
                'cyclo'      => ['label' => 'Cycloplegic', 'type' => 'select', 'options' => ['Not done', 'Cyclopentolate 1%', 'Atropine 1%', 'Tropicamide']],
            ]],
            'glasses' => ['title' => 'Glasses prescription', 'icon' => 'fa-glasses', 'fields' => [
                'gl'           => ['label' => 'Spectacle Rx', 'type' => 'refraction', 'add' => true, 'copy_from' => 'subjective'],
                'pd'           => ['label' => 'PD', 'type' => 'text', 'unit' => 'mm', 'placeholder' => '62 or 31/31'],
                'lens_type'    => ['label' => 'Lens', 'type' => 'select', 'options' => ['Single vision — distance', 'Single vision — near', 'Bifocal', 'Progressive', 'Office / computer']],
                'lens_options' => ['label' => 'Coating / material', 'type' => 'checks', 'options' => ['Anti-glare (ARC)', 'Blue cut', 'Photochromic', 'Polycarbonate', 'High index', 'Tinted']],
                'gl_use'       => ['label' => 'Use', 'type' => 'select', 'options' => ['Constant wear', 'Distance only', 'Near / reading only', 'Computer work']],
                'gl_advice'    => ['label' => 'Advice', 'type' => 'textarea', 'placeholder' => 'e.g. Review after 1 year / contact lens trial'],
            ]],
            'anterior' => ['title' => 'Anterior segment (slit lamp)', 'icon' => 'fa-magnifying-glass', 'fields' => [
                'lids'        => ['label' => 'Lids / adnexa', 'type' => 'text', 'eyes' => true, 'placeholder' => 'NAD'],
                'conjunctiva' => ['label' => 'Conjunctiva', 'type' => 'text', 'eyes' => true, 'placeholder' => 'Quiet'],
                'cornea'      => ['label' => 'Cornea', 'type' => 'text', 'eyes' => true, 'placeholder' => 'Clear'],
                'ac'          => ['label' => 'Anterior chamber', 'type' => 'text', 'eyes' => true, 'placeholder' => 'Normal depth, quiet'],
                'iris'        => ['label' => 'Iris / pupil', 'type' => 'text', 'eyes' => true, 'placeholder' => 'RRR, no RAPD'],
                'lens'        => ['label' => 'Lens', 'type' => 'select', 'eyes' => true, 'options' => ['Clear', 'Early cataract', 'NS grade 1', 'NS grade 2', 'NS grade 3', 'NS grade 4', 'Cortical', 'PSC', 'Mature', 'Pseudophakia (IOL)', 'Aphakia']],
            ]],
            'fundus' => ['title' => 'Fundus', 'icon' => 'fa-circle-dot', 'fields' => [
                'dilated' => ['label' => 'Pupils', 'type' => 'select', 'options' => ['Dilated', 'Undilated']],
                'media'   => ['label' => 'Media', 'type' => 'text', 'eyes' => true, 'placeholder' => 'Clear'],
                'disc'    => ['label' => 'Optic disc', 'type' => 'text', 'eyes' => true, 'placeholder' => 'Pink, healthy margins'],
                'cdr'     => ['label' => 'Cup : disc', 'type' => 'number', 'eyes' => true, 'min' => 0, 'max' => 1, 'step' => 0.05, 'warn_above' => 0.6],
                'macula'  => ['label' => 'Macula', 'type' => 'text', 'eyes' => true, 'placeholder' => 'FR+'],
                'vessels' => ['label' => 'Vessels', 'type' => 'text', 'eyes' => true],
                'retinopathy' => ['label' => 'Retinopathy', 'type' => 'select', 'eyes' => true, 'options' => ['None', 'Mild NPDR', 'Moderate NPDR', 'Severe NPDR', 'PDR', 'Hypertensive gr 1', 'Hypertensive gr 2', 'Hypertensive gr 3', 'Hypertensive gr 4']],
            ]],
            'other' => ['title' => 'Other', 'icon' => 'fa-ellipsis', 'fields' => [
                'motility'     => ['label' => 'Ocular motility / squint', 'type' => 'text'],
                'colour'       => ['label' => 'Colour vision', 'type' => 'text', 'placeholder' => 'Ishihara 17/17'],
                'eye_impression' => ['label' => 'Eye impression', 'type' => 'textarea'],
            ]],
        ],
    ],

    'GYN' => [
        'title' => 'Gynae / Obstetric', 'short' => 'Gynae', 'icon' => 'fa-person-pregnant', 'print_label' => 'Print',
        'sections' => [
            'menstrual' => ['title' => 'Menstrual history', 'icon' => 'fa-calendar-days', 'fields' => [
                'lmp'          => ['label' => 'LMP', 'type' => 'date'],
                'cycle'        => ['label' => 'Cycle', 'type' => 'select', 'options' => ['Regular', 'Irregular', 'Amenorrhoea', 'Post-menopausal']],
                'cycle_len'    => ['label' => 'Cycle / flow days', 'type' => 'text', 'placeholder' => '28 / 4–5'],
                'flow'         => ['label' => 'Flow', 'type' => 'select', 'options' => ['Normal', 'Scanty', 'Heavy (menorrhagia)', 'With clots']],
                'dysmenorrhoea'=> ['label' => 'Dysmenorrhoea', 'type' => 'select', 'options' => ['None', 'Mild', 'Moderate', 'Severe']],
                'menarche'     => ['label' => 'Menarche age', 'type' => 'number', 'unit' => 'yrs', 'min' => 7, 'max' => 20],
                'menopause'    => ['label' => 'Menopause age', 'type' => 'number', 'unit' => 'yrs', 'min' => 30, 'max' => 65],
            ]],
            'obstetric' => ['title' => 'Obstetric', 'icon' => 'fa-baby', 'fields' => [
                'pregnant' => ['label' => 'Currently pregnant', 'type' => 'select', 'options' => ['No', 'Yes', 'Not sure']],
                'g'        => ['label' => 'Gravida (G)', 'type' => 'number', 'min' => 0, 'max' => 20],
                'p'        => ['label' => 'Para (P)', 'type' => 'number', 'min' => 0, 'max' => 20],
                'l'        => ['label' => 'Living (L)', 'type' => 'number', 'min' => 0, 'max' => 20],
                'a'        => ['label' => 'Abortions (A)', 'type' => 'number', 'min' => 0, 'max' => 20],
                'edd'      => ['label' => 'EDD', 'type' => 'date', 'hint' => 'Fills from LMP (+280 days); change it if dated by scan'],
                'ga'       => ['label' => 'Gestational age', 'type' => 'text', 'hint' => 'From LMP'],
                'prev_deliveries' => ['label' => 'Previous deliveries', 'type' => 'textarea', 'placeholder' => 'e.g. 2019 LSCS, male, 3.1 kg — breech'],
            ]],
            'anc' => ['title' => 'Antenatal examination', 'icon' => 'fa-heart-pulse', 'fields' => [
                'fundal_height' => ['label' => 'Fundal height', 'type' => 'number', 'unit' => 'cm', 'min' => 0, 'max' => 50],
                'presentation'  => ['label' => 'Presentation', 'type' => 'select', 'options' => ['Cephalic', 'Breech', 'Transverse', 'Oblique', 'Not palpable']],
                'fhr'           => ['label' => 'Fetal heart', 'type' => 'number', 'unit' => 'bpm', 'min' => 0, 'max' => 220, 'warn_below' => 110, 'warn_above' => 160],
                'fm'            => ['label' => 'Fetal movements', 'type' => 'select', 'options' => ['Felt well', 'Reduced', 'Not felt']],
                'oedema'        => ['label' => 'Oedema', 'type' => 'select', 'options' => ['None', 'Pedal', 'Generalised']],
                'urine_alb'     => ['label' => 'Urine albumin', 'type' => 'select', 'options' => ['Nil', 'Trace', '+', '++', '+++']],
                'urine_sugar'   => ['label' => 'Urine sugar', 'type' => 'select', 'options' => ['Nil', 'Trace', '+', '++', '+++']],
                'tt'            => ['label' => 'Td / TT', 'type' => 'select', 'options' => ['Not given', 'Dose 1', 'Dose 2', 'Booster']],
                'scan'          => ['label' => 'Latest scan', 'type' => 'textarea'],
            ]],
            'gyn_exam' => ['title' => 'Gynaecological examination', 'icon' => 'fa-stethoscope', 'fields' => [
                'breast'      => ['label' => 'Breasts', 'type' => 'text', 'placeholder' => 'NAD'],
                'pa'          => ['label' => 'Per abdomen', 'type' => 'text'],
                'ps'          => ['label' => 'Per speculum', 'type' => 'text'],
                'pv'          => ['label' => 'Per vaginum', 'type' => 'text'],
                'pap'         => ['label' => 'Pap smear', 'type' => 'select', 'options' => ['Not done', 'Taken today', 'Normal (date in notes)', 'Abnormal']],
                'contraception' => ['label' => 'Contraception', 'type' => 'select', 'options' => ['None', 'Condom', 'OCP', 'Cu-T / IUCD', 'Injectable', 'Tubectomy', 'Partner vasectomy']],
            ]],
        ],
    ],

    'ORTH' => [
        'title' => 'Orthopaedic', 'short' => 'Ortho', 'icon' => 'fa-bone', 'print_label' => 'Print',
        'sections' => [
            'site' => ['title' => 'Complaint', 'icon' => 'fa-location-dot', 'fields' => [
                'side'      => ['label' => 'Side', 'type' => 'select', 'options' => ['Right', 'Left', 'Bilateral', 'Midline']],
                'region'    => ['label' => 'Region', 'type' => 'select', 'options' => ['Shoulder', 'Arm', 'Elbow', 'Forearm', 'Wrist', 'Hand / fingers', 'Hip', 'Thigh', 'Knee', 'Leg', 'Ankle', 'Foot', 'Cervical spine', 'Thoracic spine', 'Lumbar spine', 'Pelvis', 'Multiple']],
                'injury'    => ['label' => 'Injury', 'type' => 'select', 'options' => ['No injury', 'Fall', 'Road accident', 'Sports', 'Work', 'Assault', 'Other']],
                'duration'  => ['label' => 'Duration', 'type' => 'text', 'placeholder' => 'e.g. 3 weeks'],
                'pain'      => ['label' => 'Pain score', 'type' => 'select', 'options' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], 'unit' => '/10'],
            ]],
            'exam' => ['title' => 'Examination', 'icon' => 'fa-hand', 'fields' => [
                'swelling'   => ['label' => 'Swelling', 'type' => 'text'],
                'tenderness' => ['label' => 'Tenderness', 'type' => 'text'],
                'deformity'  => ['label' => 'Deformity', 'type' => 'text'],
                'rom'        => ['label' => 'Range of movement', 'type' => 'textarea', 'placeholder' => 'e.g. Knee flexion 0–110°, extension lag 10°'],
                'power'      => ['label' => 'Power (MRC)', 'type' => 'select', 'options' => ['5/5', '4+/5', '4/5', '4−/5', '3/5', '2/5', '1/5', '0/5']],
                'nv'         => ['label' => 'Distal neurovascular', 'type' => 'select', 'options' => ['Intact', 'Impaired — see notes']],
                'special'    => ['label' => 'Special tests', 'type' => 'textarea', 'placeholder' => 'e.g. Lachman +, McMurray −, SLR 40° right'],
                'gait'       => ['label' => 'Gait', 'type' => 'select', 'options' => ['Normal', 'Antalgic', 'Trendelenburg', 'With support', 'Non-ambulatory']],
            ]],
            'imaging' => ['title' => 'Imaging', 'icon' => 'fa-x-ray', 'fields' => [
                'xray' => ['label' => 'X-ray', 'type' => 'textarea'],
                'mri'  => ['label' => 'MRI / CT', 'type' => 'textarea'],
            ]],
            'ortho_plan' => ['title' => 'Plan', 'icon' => 'fa-clipboard-list', 'fields' => [
                'immobilise' => ['label' => 'Immobilisation', 'type' => 'select', 'options' => ['None', 'Plaster cast', 'Slab', 'Brace', 'Splint', 'Cervical collar', 'Lumbar belt', 'Arm sling', 'Crepe bandage']],
                'weight_bearing' => ['label' => 'Weight bearing', 'type' => 'select', 'options' => ['Full', 'Partial', 'Toe touch', 'Non weight bearing']],
                'physio'     => ['label' => 'Physiotherapy', 'type' => 'select', 'options' => ['Not needed', 'Referred', 'Home exercises given']],
                'surgery'    => ['label' => 'Surgery', 'type' => 'text', 'placeholder' => 'e.g. Advised ORIF — counselled'],
            ]],
        ],
    ],

    'DERM' => [
        'title' => 'Skin (Dermatology)', 'short' => 'Skin', 'icon' => 'fa-hand-dots', 'print_label' => 'Print',
        'sections' => [
            'lesion' => ['title' => 'Lesions', 'icon' => 'fa-circle-dot', 'fields' => [
                'site'         => ['label' => 'Site(s)', 'type' => 'text', 'placeholder' => 'e.g. both forearms, trunk'],
                'morphology'   => ['label' => 'Morphology', 'type' => 'checks', 'options' => ['Macule', 'Patch', 'Papule', 'Plaque', 'Nodule', 'Vesicle', 'Bulla', 'Pustule', 'Wheal', 'Erosion', 'Ulcer', 'Scale', 'Crust', 'Lichenification', 'Excoriation']],
                'colour'       => ['label' => 'Colour', 'type' => 'select', 'options' => ['Erythematous', 'Hyperpigmented', 'Hypopigmented', 'Depigmented', 'Violaceous', 'Skin-coloured', 'Yellowish']],
                'distribution' => ['label' => 'Distribution', 'type' => 'select', 'options' => ['Localised', 'Generalised', 'Symmetrical', 'Dermatomal', 'Photo-exposed', 'Flexural', 'Extensor', 'Intertriginous', 'Acral']],
                'size'         => ['label' => 'Size / number', 'type' => 'text', 'placeholder' => 'e.g. 3–4 plaques, 2–5 cm'],
                'duration'     => ['label' => 'Duration', 'type' => 'text'],
                'itch'         => ['label' => 'Itching', 'type' => 'select', 'options' => ['None', 'Mild', 'Moderate', 'Severe', 'Worse at night']],
            ]],
            'appendages' => ['title' => 'Hair, nails, mucosa', 'icon' => 'fa-scissors', 'fields' => [
                'hair'   => ['label' => 'Hair / scalp', 'type' => 'text'],
                'nails'  => ['label' => 'Nails', 'type' => 'text'],
                'mucosa' => ['label' => 'Mucosa', 'type' => 'text'],
            ]],
            'scores' => ['title' => 'Severity', 'icon' => 'fa-chart-simple', 'fields' => [
                'bsa'    => ['label' => 'Body surface affected', 'type' => 'number', 'unit' => '%', 'min' => 0, 'max' => 100, 'warn_above' => 10],
                'pasi'   => ['label' => 'PASI', 'type' => 'number', 'min' => 0, 'max' => 72, 'step' => 0.1],
                'scorad' => ['label' => 'SCORAD', 'type' => 'number', 'min' => 0, 'max' => 103, 'step' => 0.1],
                'dlqi'   => ['label' => 'DLQI', 'type' => 'number', 'min' => 0, 'max' => 30],
            ]],
            'tests' => ['title' => 'Bedside tests', 'icon' => 'fa-vial', 'fields' => [
                'koh'    => ['label' => 'KOH mount', 'type' => 'select', 'options' => ['Not done', 'Positive (fungal)', 'Negative']],
                'woods'  => ['label' => "Wood's lamp", 'type' => 'text'],
                'dermoscopy' => ['label' => 'Dermoscopy', 'type' => 'textarea'],
                'biopsy' => ['label' => 'Biopsy', 'type' => 'select', 'options' => ['Not needed', 'Advised', 'Taken today', 'Report received']],
            ]],
        ],
    ],
];
