<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Table may already exist from the base migration — create only if absent
        if (!Schema::hasTable('icd_codes')) {
            Schema::create('icd_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('description');
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        $now = now();

        $codes = [
            // Infectious & Parasitic
            ['code' => 'A09',  'description' => 'Diarrhoea and gastroenteritis of infectious origin',          'category' => 'Infectious & Parasitic'],
            ['code' => 'A15',  'description' => 'Respiratory tuberculosis',                                    'category' => 'Infectious & Parasitic'],
            ['code' => 'A16',  'description' => 'Respiratory tuberculosis, not confirmed bacteriologically',   'category' => 'Infectious & Parasitic'],
            ['code' => 'A36',  'description' => 'Diphtheria',                                                  'category' => 'Infectious & Parasitic'],
            ['code' => 'A37',  'description' => 'Whooping cough',                                              'category' => 'Infectious & Parasitic'],
            ['code' => 'A90',  'description' => 'Dengue fever',                                                'category' => 'Infectious & Parasitic'],
            ['code' => 'A91',  'description' => 'Dengue haemorrhagic fever',                                   'category' => 'Infectious & Parasitic'],
            ['code' => 'B01',  'description' => 'Varicella (chickenpox)',                                      'category' => 'Infectious & Parasitic'],
            ['code' => 'B05',  'description' => 'Measles',                                                     'category' => 'Infectious & Parasitic'],
            ['code' => 'B06',  'description' => 'Rubella',                                                     'category' => 'Infectious & Parasitic'],
            ['code' => 'B15',  'description' => 'Acute hepatitis A',                                           'category' => 'Infectious & Parasitic'],
            ['code' => 'B16',  'description' => 'Acute hepatitis B',                                           'category' => 'Infectious & Parasitic'],
            ['code' => 'B17',  'description' => 'Other acute viral hepatitis',                                 'category' => 'Infectious & Parasitic'],
            ['code' => 'B50',  'description' => 'Plasmodium falciparum malaria',                               'category' => 'Infectious & Parasitic'],
            ['code' => 'B54',  'description' => 'Unspecified malaria',                                         'category' => 'Infectious & Parasitic'],

            // Endocrine & Metabolic
            ['code' => 'E10',  'description' => 'Type 1 diabetes mellitus',                                    'category' => 'Endocrine & Metabolic'],
            ['code' => 'E11',  'description' => 'Type 2 diabetes mellitus',                                    'category' => 'Endocrine & Metabolic'],
            ['code' => 'E14',  'description' => 'Unspecified diabetes mellitus',                               'category' => 'Endocrine & Metabolic'],
            ['code' => 'E03',  'description' => 'Other hypothyroidism',                                        'category' => 'Endocrine & Metabolic'],
            ['code' => 'E05',  'description' => 'Thyrotoxicosis (hyperthyroidism)',                            'category' => 'Endocrine & Metabolic'],
            ['code' => 'E11.5', 'description' => 'Type 2 diabetes mellitus with peripheral circulatory complications', 'category' => 'Endocrine & Metabolic'],
            ['code' => 'E78',  'description' => 'Disorders of lipoprotein metabolism and other lipidaemias',   'category' => 'Endocrine & Metabolic'],
            ['code' => 'E86',  'description' => 'Volume depletion / dehydration',                              'category' => 'Endocrine & Metabolic'],

            // Circulatory System
            ['code' => 'I10',  'description' => 'Essential (primary) hypertension',                            'category' => 'Circulatory System'],
            ['code' => 'I20',  'description' => 'Angina pectoris',                                             'category' => 'Circulatory System'],
            ['code' => 'I21',  'description' => 'Acute myocardial infarction (Heart attack)',                  'category' => 'Circulatory System'],
            ['code' => 'I25',  'description' => 'Chronic ischaemic heart disease',                             'category' => 'Circulatory System'],
            ['code' => 'I48',  'description' => 'Atrial fibrillation and flutter',                             'category' => 'Circulatory System'],
            ['code' => 'I50',  'description' => 'Heart failure',                                               'category' => 'Circulatory System'],
            ['code' => 'I63',  'description' => 'Cerebral infarction (Ischaemic stroke)',                      'category' => 'Circulatory System'],
            ['code' => 'I64',  'description' => 'Stroke, not specified as haemorrhage or infarction',          'category' => 'Circulatory System'],

            // Respiratory System
            ['code' => 'J00',  'description' => 'Acute nasopharyngitis (common cold)',                         'category' => 'Respiratory System'],
            ['code' => 'J02',  'description' => 'Acute pharyngitis',                                           'category' => 'Respiratory System'],
            ['code' => 'J03',  'description' => 'Acute tonsillitis',                                           'category' => 'Respiratory System'],
            ['code' => 'J06',  'description' => 'Acute upper respiratory infection, unspecified',              'category' => 'Respiratory System'],
            ['code' => 'J18',  'description' => 'Pneumonia, unspecified',                                      'category' => 'Respiratory System'],
            ['code' => 'J20',  'description' => 'Acute bronchitis',                                            'category' => 'Respiratory System'],
            ['code' => 'J22',  'description' => 'Unspecified acute lower respiratory infection',               'category' => 'Respiratory System'],
            ['code' => 'J40',  'description' => 'Bronchitis, not specified as acute or chronic',               'category' => 'Respiratory System'],
            ['code' => 'J44',  'description' => 'Other chronic obstructive pulmonary disease (COPD)',          'category' => 'Respiratory System'],
            ['code' => 'J45',  'description' => 'Asthma',                                                      'category' => 'Respiratory System'],

            // Digestive System
            ['code' => 'K05',  'description' => 'Gingivitis and periodontal diseases',                         'category' => 'Digestive System'],
            ['code' => 'K21',  'description' => 'Gastro-oesophageal reflux disease (GERD)',                   'category' => 'Digestive System'],
            ['code' => 'K25',  'description' => 'Gastric ulcer',                                               'category' => 'Digestive System'],
            ['code' => 'K26',  'description' => 'Duodenal ulcer',                                              'category' => 'Digestive System'],
            ['code' => 'K29',  'description' => 'Gastritis and duodenitis',                                    'category' => 'Digestive System'],
            ['code' => 'K35',  'description' => 'Acute appendicitis',                                          'category' => 'Digestive System'],
            ['code' => 'K57',  'description' => 'Diverticular disease of intestine',                           'category' => 'Digestive System'],
            ['code' => 'K70',  'description' => 'Alcoholic liver disease',                                     'category' => 'Digestive System'],
            ['code' => 'K74',  'description' => 'Fibrosis and cirrhosis of liver',                             'category' => 'Digestive System'],
            ['code' => 'K80',  'description' => 'Cholelithiasis (gallstones)',                                  'category' => 'Digestive System'],
            ['code' => 'K81',  'description' => 'Cholecystitis',                                               'category' => 'Digestive System'],
            ['code' => 'K85',  'description' => 'Acute pancreatitis',                                          'category' => 'Digestive System'],

            // Genitourinary System
            ['code' => 'N10',  'description' => 'Acute tubulo-interstitial nephritis (pyelonephritis)',        'category' => 'Genitourinary System'],
            ['code' => 'N18',  'description' => 'Chronic kidney disease (CKD)',                                'category' => 'Genitourinary System'],
            ['code' => 'N20',  'description' => 'Calculus of kidney (renal stone)',                            'category' => 'Genitourinary System'],
            ['code' => 'N39',  'description' => 'Urinary tract infection, site not specified (UTI)',           'category' => 'Genitourinary System'],
            ['code' => 'N40',  'description' => 'Benign prostatic hyperplasia (BPH)',                          'category' => 'Genitourinary System'],

            // Obstetrics & Gynaecology
            ['code' => 'O80',  'description' => 'Single spontaneous delivery (normal delivery)',               'category' => 'Obstetrics & Gynaecology'],
            ['code' => 'O82',  'description' => 'Single delivery by caesarean section',                        'category' => 'Obstetrics & Gynaecology'],
            ['code' => 'O03',  'description' => 'Spontaneous abortion (miscarriage)',                          'category' => 'Obstetrics & Gynaecology'],
            ['code' => 'O14',  'description' => 'Gestational hypertension with proteinuria (pre-eclampsia)',   'category' => 'Obstetrics & Gynaecology'],
            ['code' => 'N91',  'description' => 'Absent, scanty and rare menstruation',                        'category' => 'Obstetrics & Gynaecology'],
            ['code' => 'N94',  'description' => 'Pain and other conditions associated with female genital organs', 'category' => 'Obstetrics & Gynaecology'],

            // Musculoskeletal & Connective Tissue
            ['code' => 'M06',  'description' => 'Other rheumatoid arthritis',                                  'category' => 'Musculoskeletal'],
            ['code' => 'M10',  'description' => 'Gout',                                                        'category' => 'Musculoskeletal'],
            ['code' => 'M17',  'description' => 'Gonarthrosis (knee osteoarthritis)',                          'category' => 'Musculoskeletal'],
            ['code' => 'M54',  'description' => 'Dorsalgia (back pain)',                                       'category' => 'Musculoskeletal'],
            ['code' => 'M79',  'description' => 'Other and unspecified soft-tissue disorders',                 'category' => 'Musculoskeletal'],

            // Injuries & External Causes
            ['code' => 'S00',  'description' => 'Superficial injury of head',                                  'category' => 'Injuries & External Causes'],
            ['code' => 'S06',  'description' => 'Intracranial injury',                                         'category' => 'Injuries & External Causes'],
            ['code' => 'S22',  'description' => 'Fracture of rib, sternum and thoracic spine',                 'category' => 'Injuries & External Causes'],
            ['code' => 'S52',  'description' => 'Fracture of forearm',                                         'category' => 'Injuries & External Causes'],
            ['code' => 'S62',  'description' => 'Fracture at wrist and hand level',                            'category' => 'Injuries & External Causes'],
            ['code' => 'S72',  'description' => 'Fracture of femur (hip fracture)',                            'category' => 'Injuries & External Causes'],
            ['code' => 'S82',  'description' => 'Fracture of lower leg, including ankle',                      'category' => 'Injuries & External Causes'],
            ['code' => 'T08',  'description' => 'Fracture of spine, level unspecified',                        'category' => 'Injuries & External Causes'],
            ['code' => 'T14',  'description' => 'Injury of unspecified body region',                           'category' => 'Injuries & External Causes'],
            ['code' => 'T30',  'description' => 'Burn, unspecified body region',                               'category' => 'Injuries & External Causes'],
            ['code' => 'T31',  'description' => 'Burns classified according to extent of body surface involved', 'category' => 'Injuries & External Causes'],

            // Nervous System
            ['code' => 'G35',  'description' => 'Multiple sclerosis',                                          'category' => 'Nervous System'],
            ['code' => 'G40',  'description' => 'Epilepsy',                                                    'category' => 'Nervous System'],
            ['code' => 'G43',  'description' => 'Migraine',                                                    'category' => 'Nervous System'],

            // Mental & Behavioural
            ['code' => 'F10',  'description' => 'Mental and behavioural disorders due to alcohol use',         'category' => 'Mental & Behavioural'],
            ['code' => 'F32',  'description' => 'Depressive episode',                                          'category' => 'Mental & Behavioural'],
            ['code' => 'F41',  'description' => 'Other anxiety disorders',                                     'category' => 'Mental & Behavioural'],

            // Factors influencing health / Preventive
            ['code' => 'Z00',  'description' => 'Encounter for general examination without complaint',         'category' => 'Preventive / Administrative'],
            ['code' => 'Z23',  'description' => 'Encounter for immunization / vaccination',                    'category' => 'Preventive / Administrative'],
            ['code' => 'Z30',  'description' => 'Encounter for contraceptive management',                      'category' => 'Preventive / Administrative'],
            ['code' => 'Z51',  'description' => 'Encounter for other aftercare',                               'category' => 'Preventive / Administrative'],
        ];

        foreach ($codes as $code) {
            DB::table('icd_codes')->insert([
                'code'        => $code['code'],
                'description' => $code['description'],
                'category'    => $code['category'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void {
        Schema::dropIfExists('icd_codes');
    }
};
