<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Idempotent: on consumer servers the table/data may already have
        // arrived via the specialitycare DB sync from nvme. Only create &
        // seed what is missing, so `migrate --force` is safe everywhere.
        if (Schema::hasTable('clinical_rules')) {
            if (DB::table('clinical_rules')->count() === 0) $this->seed();
            return;
        }
        Schema::create('clinical_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140);                 // short label shown as the suggestion heading
            $table->string('category', 20)->default('symptom'); // counseling|screening|vaccine|vitals|lab|symptom
            $table->string('severity', 10)->default('info');    // info|warning|urgent
            $table->string('match_type', 20);            // vital | demographic | keyword
            // vital / demographic condition
            $table->string('metric', 30)->nullable();    // bmi,bp_systolic,bp_diastolic,blood_glucose,temperature,spo2,pulse,respiratory_rate,weight (vital) | age (demographic)
            $table->string('comparator', 4)->nullable(); // gte,lte,gt,lt,eq
            $table->decimal('value_num', 8, 2)->nullable();
            // keyword condition
            $table->string('keywords', 500)->nullable();        // comma-separated; any match triggers
            $table->string('keyword_fields', 255)->nullable();  // comma-separated field keys to scan; null = default set
            // optional extra scoping filters (ALL must also hold)
            $table->string('gender', 10)->nullable();    // male|female (null = any)
            $table->unsignedSmallInteger('age_min')->nullable();
            $table->unsignedSmallInteger('age_max')->nullable();
            // output
            $table->string('suggestion', 600);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('name');
            $table->index(['is_active', 'sort_order']);
        });

        $this->seed();
    }

    private function seed(): void {
        // ---- Starter rule set (India-context primary care; advisory only) ----
        $r = function (array $a) {
            return array_merge([
                'category' => 'symptom', 'severity' => 'info',
                'metric' => null, 'comparator' => null, 'value_num' => null,
                'keywords' => null, 'keyword_fields' => null,
                'gender' => null, 'age_min' => null, 'age_max' => null,
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ], $a);
        };

        $seed = [
            // ----- Vitals -----
            $r(['name' => 'Overweight (BMI ≥ 25)', 'category' => 'vitals', 'severity' => 'warning',
                'match_type' => 'vital', 'metric' => 'bmi', 'comparator' => 'gte', 'value_num' => 25,
                'suggestion' => 'Overweight: order fasting lipid profile, fasting glucose/HbA1c and TSH; measure waist circumference & BP; counsel diet + 150 min/week activity; set a weight target and review in 3 months.']),
            $r(['name' => 'Obese (BMI ≥ 30)', 'category' => 'vitals', 'severity' => 'warning',
                'match_type' => 'vital', 'metric' => 'bmi', 'comparator' => 'gte', 'value_num' => 30,
                'suggestion' => 'Obese: order HbA1c, fasting lipid profile, LFT (fatty liver) and TSH; check BP & waist circumference; counsel structured weight management; consider specialist referral if comorbidities.']),
            $r(['name' => 'High systolic BP (≥ 140)', 'category' => 'vitals', 'severity' => 'warning',
                'match_type' => 'vital', 'metric' => 'bp_systolic', 'comparator' => 'gte', 'value_num' => 140,
                'suggestion' => 'Systolic BP ≥ 140: repeat after 5 min rest in both arms. If persistent order ECG, fasting glucose, lipid profile, serum creatinine/electrolytes and urine routine; do fundus exam; recheck BP in 1–2 weeks before diagnosing hypertension.']),
            $r(['name' => 'High diastolic BP (≥ 90)', 'category' => 'vitals', 'severity' => 'warning',
                'match_type' => 'vital', 'metric' => 'bp_diastolic', 'comparator' => 'gte', 'value_num' => 90,
                'suggestion' => 'Diastolic BP ≥ 90: repeat seated after rest. If persistent order ECG, renal profile, urine routine and lipids; assess target-organ damage; review in 1–2 weeks.']),
            $r(['name' => 'High blood glucose (≥ 200)', 'category' => 'lab', 'severity' => 'warning',
                'match_type' => 'vital', 'metric' => 'blood_glucose', 'comparator' => 'gte', 'value_num' => 200,
                'suggestion' => 'Random glucose ≥ 200 mg/dL: confirm with fasting glucose + HbA1c; order lipid profile, serum creatinine and urine microalbumin; check urine ketones if symptomatic or very high.']),
            $r(['name' => 'Low SpO₂ (≤ 94%)', 'category' => 'vitals', 'severity' => 'urgent',
                'match_type' => 'vital', 'metric' => 'spo2', 'comparator' => 'lte', 'value_num' => 94,
                'suggestion' => 'SpO₂ ≤ 94%: recheck on room air; start O₂ to target ≥ 94%; order chest X-ray, CBC and ECG; consider ABG; look for respiratory distress and escalate/refer if not improving.']),
            $r(['name' => 'Fever (temp ≥ 100.4°F)', 'category' => 'symptom', 'severity' => 'info',
                'match_type' => 'vital', 'metric' => 'temperature', 'comparator' => 'gte', 'value_num' => 100.4,
                'suggestion' => 'Fever ≥ 100.4°F: order CBC with peripheral smear; in endemic areas send malaria antigen/smear, dengue NS1/IgM and Widal/typhoid; urine routine; add CRP if indicated; review hydration.']),
            $r(['name' => 'Tachycardia (pulse ≥ 100)', 'category' => 'vitals', 'severity' => 'info',
                'match_type' => 'vital', 'metric' => 'pulse', 'comparator' => 'gte', 'value_num' => 100,
                'suggestion' => 'Pulse ≥ 100: recheck after rest; correlate with fever/pain/anxiety. If unexplained order ECG, CBC (anaemia) and TSH; check BP & SpO₂.']),

            // ----- Demographic screening & vaccines -----
            $r(['name' => 'Diabetes screening (age ≥ 45)', 'category' => 'screening', 'severity' => 'info',
                'match_type' => 'demographic', 'metric' => 'age', 'comparator' => 'gte', 'value_num' => 45,
                'suggestion' => 'Age ≥ 45: screen with fasting glucose or HbA1c; if normal repeat every 3 years (yearly if overweight or family history); also check BP and lipid profile.']),
            $r(['name' => 'Colorectal screening (age ≥ 50)', 'category' => 'screening', 'severity' => 'info',
                'match_type' => 'demographic', 'metric' => 'age', 'comparator' => 'gte', 'value_num' => 50,
                'suggestion' => 'Age ≥ 50: offer colorectal cancer screening — FIT/FOBT every year, OR colonoscopy every 10 years (stool-DNA or CT colonography where available); start earlier if family history.']),
            $r(['name' => 'Cervical screening (women ≥ 30)', 'category' => 'screening', 'severity' => 'info',
                'match_type' => 'demographic', 'metric' => 'age', 'comparator' => 'gte', 'value_num' => 30, 'gender' => 'female',
                'suggestion' => 'Women ≥ 30: HPV test every 5 years OR Pap smear every 3 years (co-testing where available); continue to age 65.']),
            $r(['name' => 'Breast screening (women ≥ 40)', 'category' => 'screening', 'severity' => 'info',
                'match_type' => 'demographic', 'metric' => 'age', 'comparator' => 'gte', 'value_num' => 40, 'gender' => 'female',
                'suggestion' => 'Women ≥ 40: clinical breast exam + mammography every 1–2 years; teach breast self-awareness; earlier screening/MRI if strong family history.']),
            $r(['name' => 'Elderly vaccines & fall risk (age ≥ 65)', 'category' => 'vaccine', 'severity' => 'info',
                'match_type' => 'demographic', 'metric' => 'age', 'comparator' => 'gte', 'value_num' => 65,
                'suggestion' => 'Age ≥ 65: give annual influenza + pneumococcal (PCV then PPSV23) vaccines; check Td/Tdap booster; assess fall risk, vision and bone density (DEXA); review polypharmacy.']),

            // ----- Keyword (symptom / history) -----
            $r(['name' => 'Tobacco use', 'category' => 'counseling', 'severity' => 'warning', 'match_type' => 'keyword',
                'keywords' => 'tobacco,smoking,smoke,smoker,cigarette,bidi,beedi,gutka,khaini,pan masala,zarda',
                'suggestion' => 'Tobacco use: advise quit, offer nicotine replacement/counselling; examine oral cavity for premalignant lesions; if smoker with chronic cough order chest X-ray and consider spirometry (COPD); check baseline BP & lipids.']),
            $r(['name' => 'Alcohol use', 'category' => 'counseling', 'severity' => 'info', 'match_type' => 'keyword',
                'keywords' => 'alcohol,drinking,liquor,ethanol,whisky,beer',
                'suggestion' => 'Alcohol use: quantify units/week; screen with AUDIT or CAGE; order LFT, GGT and CBC (MCV); counsel; if dependent plan supervised withdrawal + thiamine.']),
            $r(['name' => 'Chest pain', 'category' => 'symptom', 'severity' => 'urgent', 'match_type' => 'keyword',
                'keywords' => 'chest pain,chest tightness,chest discomfort,retrosternal',
                'suggestion' => 'Chest pain: do ECG now and BP in both arms; send troponin (serial if ongoing), CBC and blood glucose; give aspirin if cardiac suspected; chest X-ray; refer/admit urgently if high-risk features (radiation, sweating, breathlessness).']),
            $r(['name' => 'Breathlessness', 'category' => 'symptom', 'severity' => 'warning', 'match_type' => 'keyword',
                'keywords' => 'breathless,breathlessness,dyspnea,dyspnoea,short of breath,sob,wheez',
                'suggestion' => 'Breathlessness: check SpO₂ and respiratory rate now; order chest X-ray, ECG and CBC; consider spirometry/PEFR (asthma/COPD) and BNP/echo if cardiac; nebulise if wheeze.']),
            $r(['name' => 'Persistent cough (TB screen)', 'category' => 'symptom', 'severity' => 'warning', 'match_type' => 'keyword',
                'keywords' => 'chronic cough,cough >2 weeks,cough since,persistent cough,productive cough,cough 2 week,cough 3 week',
                'suggestion' => 'Cough > 2 weeks: send sputum for AFB/CBNAAT (GeneXpert) ×2 and chest X-ray; ask about weight loss/night sweats; HIV test if risk; advise cough etiquette/isolation if TB suspected.']),
            $r(['name' => 'Unintentional weight loss', 'category' => 'symptom', 'severity' => 'warning', 'match_type' => 'keyword',
                'keywords' => 'weight loss,losing weight,lost weight,wt loss',
                'suggestion' => 'Unintentional weight loss: order CBC, ESR, blood glucose/HbA1c, TSH, chest X-ray and sputum AFB (TB); stool occult blood; HIV if risk; arrange age-appropriate malignancy screen.']),
            $r(['name' => 'Headache', 'category' => 'symptom', 'severity' => 'info', 'match_type' => 'keyword',
                'keywords' => 'headache,migraine,head ache',
                'suggestion' => 'Headache: measure BP; ask red flags (sudden worst-ever, fever + neck stiffness, new neuro deficit, early-morning vomiting, new onset age > 50). If any red flag → urgent CT/MRI + referral; otherwise treat and review.']),
            $r(['name' => 'Polyuria / polydipsia (diabetes)', 'category' => 'symptom', 'severity' => 'warning', 'match_type' => 'keyword',
                'keywords' => 'polyuria,polydipsia,excessive thirst,frequent urination,increased thirst,increased urination',
                'suggestion' => 'Polyuria/polydipsia: send fasting glucose + HbA1c (or random glucose); urine routine for sugar/ketones; serum creatinine and electrolytes; review for weight loss.']),
            $r(['name' => 'Pedal oedema', 'category' => 'symptom', 'severity' => 'info', 'match_type' => 'keyword',
                'keywords' => 'pedal edema,pedal oedema,leg swelling,ankle swelling,swelling of feet,feet swelling',
                'suggestion' => 'Pedal oedema: measure BP; order urine routine (proteinuria), serum creatinine, albumin and LFT; ECG/echo if cardiac cause; TSH; if unilateral examine for DVT.']),
            $r(['name' => 'Possible pregnancy', 'category' => 'symptom', 'severity' => 'info', 'match_type' => 'keyword', 'gender' => 'female',
                'keywords' => 'pregnan,amenorrhea,amenorrhoea,missed period,lmp',
                'suggestion' => 'Possible pregnancy: confirm with urine β-hCG (serum if unclear); review all current drugs for teratogenicity before prescribing; start folic acid; arrange antenatal booking if confirmed.']),
        ];

        foreach ($seed as $i => $row) {
            $seed[$i]['sort_order'] = $i;
        }
        DB::table('clinical_rules')->insertOrIgnore($seed);
    }

    public function down(): void { Schema::dropIfExists('clinical_rules'); }
};
