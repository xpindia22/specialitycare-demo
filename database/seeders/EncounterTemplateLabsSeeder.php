<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EncounterTemplate;

class EncounterTemplateLabsSeeder extends Seeder
{
    /**
     * Adds a `lab_orders` payload (tests + panels by name) to each existing
     * encounter template. Names are resolved to IDs at apply-time on the
     * encounter screen.
     */
    public function run(): void
    {
        $map = [

            // ─── original 9 ───────────────────────────────────────────────
            'Hypertension — Follow-up' => [
                'panels' => ['Renal Function Test', 'Lipid Profile'],
                'tests'  => ['Urine Routine', 'HbA1c'],
            ],
            'Type 2 Diabetes — Follow-up' => [
                'panels' => ['Diabetes Profile', 'Lipid Profile', 'Renal Function Test'],
                'tests'  => ['Urine Microalbumin'],
            ],
            'Viral Fever / URTI' => [
                'panels' => [],
                'tests'  => ['CBC', 'Dengue NS1 Antigen', 'Malaria Antigen', 'Urine Routine'],
            ],
            'Acute Gastritis / GERD' => [
                'panels' => [],
                'tests'  => ['CBC'],
            ],
            'Stable Angina / CAD — Review' => [
                'panels' => ['Lipid Profile', 'Renal Function Test'],
                'tests'  => ['HbA1c', 'Blood Glucose Fasting'],
            ],
            'Bronchial Asthma — Follow-up' => [
                'panels' => [],
                'tests'  => ['CBC', 'Total IgE'],
            ],
            'Antenatal Checkup (Routine)' => [
                'panels' => ['Antenatal Profile'],
                'tests'  => [],
            ],
            'Migraine — Acute' => [
                'panels' => [],
                'tests'  => [],
            ],
            'UTI — Uncomplicated (Female)' => [
                'panels' => [],
                'tests'  => ['Urine Routine', 'Urine Culture and Sensitivity'],
            ],

            // ─── autoimmune / rheumatology ────────────────────────────────
            'Rheumatoid Arthritis — Follow-up' => [
                'panels' => ['Rheumatoid Arthritis Panel', 'DMARD Monitoring Panel'],
                'tests'  => [],
            ],
            'Systemic Lupus Erythematosus — Maintenance' => [
                'panels' => ['Lupus / SLE Panel'],
                'tests'  => [],
            ],
            'Gout — Acute Flare' => [
                'panels' => ['Gout Workup'],
                'tests'  => [],
            ],

            // ─── cardiac ──────────────────────────────────────────────────
            'Heart Failure (HFrEF) — Follow-up' => [
                'panels' => ['Heart Failure Panel', 'Lipid Profile'],
                'tests'  => ['HbA1c'],
            ],
            'Atrial Fibrillation — Anticoagulation Review' => [
                'panels' => ['Renal Function Test', 'Coagulation Profile'],
                'tests'  => ['CBC', 'LFT'],
            ],
            'Dyslipidemia — Primary Prevention' => [
                'panels' => ['Lipid Profile'],
                'tests'  => ['HbA1c', 'Blood Glucose Fasting', 'LFT', 'TSH'],
            ],

            // ─── liver ────────────────────────────────────────────────────
            'Chronic Liver Disease / Cirrhosis — Follow-up' => [
                'panels' => ['Liver Function Test', 'Renal Function Test', 'Coagulation Profile'],
                'tests'  => ['CBC', 'Alpha-Fetoprotein (AFP)'],
            ],
            'Non-Alcoholic Fatty Liver Disease (NAFLD)' => [
                'panels' => ['Liver Function Test', 'Lipid Profile'],
                'tests'  => ['HbA1c', 'Blood Glucose Fasting'],
            ],

            // ─── kidney ───────────────────────────────────────────────────
            'Chronic Kidney Disease (Stage 3-4)' => [
                'panels' => ['CKD Workup Panel'],
                'tests'  => [],
            ],
            'Renal Stone / Nephrolithiasis — Acute' => [
                'panels' => ['Renal Function Test'],
                'tests'  => ['Urine Routine', 'Urine Culture and Sensitivity', 'Calcium Total', 'Uric Acid (Serum)'],
            ],

            // ─── endocrine ────────────────────────────────────────────────
            'Hypothyroidism — Follow-up' => [
                'panels' => [],
                'tests'  => ['TSH', 'Free T4'],
            ],
            "Hyperthyroidism / Graves' Disease" => [
                'panels' => [],
                'tests'  => ['TSH', 'Free T3', 'Free T4', 'Anti-TPO'],
            ],

            // ─── neurology ────────────────────────────────────────────────
            'Epilepsy — Maintenance' => [
                'panels' => [],
                'tests'  => ['CBC', 'LFT', 'RFT'],
            ],
            'Stroke — Secondary Prevention' => [
                'panels' => ['Lipid Profile', 'Renal Function Test'],
                'tests'  => ['HbA1c', 'Carotid Doppler'],
            ],

            // ─── respiratory ──────────────────────────────────────────────
            'COPD — Stable Follow-up' => [
                'panels' => [],
                'tests'  => ['CBC'],
            ],
            'Community-Acquired Pneumonia (Outpatient)' => [
                'panels' => [],
                'tests'  => ['CBC', 'CRP', 'Sputum Gram Stain', 'Sputum Culture and Sensitivity'],
            ],

            // ─── dermatology ──────────────────────────────────────────────
            'Acne Vulgaris — Moderate' => [
                'panels' => [],
                'tests'  => [],
            ],
            'Atopic Dermatitis / Eczema' => [
                'panels' => [],
                'tests'  => ['Total IgE'],
            ],
            'Tinea Corporis / Fungal Skin Infection' => [
                'panels' => [],
                'tests'  => ['KOH Mount'],
            ],

            // ─── orthopedics ──────────────────────────────────────────────
            'Osteoarthritis — Knee' => [
                'panels' => [],
                'tests'  => ['Renal Function Test'],
            ],
            'Acute Low Back Pain / Lumbar Sprain' => [
                'panels' => [],
                'tests'  => [],
            ],

            // ─── gynae / obs ──────────────────────────────────────────────
            'Vaginal Candidiasis' => [
                'panels' => [],
                'tests'  => ['High Vaginal Swab Culture', 'KOH Mount'],
            ],
            'Polycystic Ovary Syndrome (PCOS)' => [
                'panels' => ['PCOS Hormonal Profile'],
                'tests'  => [],
            ],
            'Nausea & Vomiting in Pregnancy' => [
                'panels' => [],
                'tests'  => ['CBC', 'Urine Routine', 'TSH', 'Electrolytes'],
            ],

            // ─── dental ───────────────────────────────────────────────────
            'Dental Caries / Abscess — Antibiotic Cover' => [
                'panels' => [],
                'tests'  => ['CBC', 'Pus Culture'],
            ],

            // ─── psychiatry ───────────────────────────────────────────────
            'Depression — Major (Moderate)' => [
                'panels' => [],
                'tests'  => ['TSH', 'Vitamin B12', 'Vitamin D 25-OH', 'Blood Glucose Fasting'],
            ],
            'Generalised Anxiety Disorder' => [
                'panels' => [],
                'tests'  => ['TSH', 'Blood Glucose Fasting'],
            ],
        ];

        $updated = 0;
        $missing = [];
        foreach ($map as $name => $payload) {
            $tpl = EncounterTemplate::where('name', $name)->first();
            if (!$tpl) { $missing[] = $name; continue; }
            $tpl->lab_orders = [
                'tests'  => array_values(array_filter($payload['tests'])),
                'panels' => array_values(array_filter($payload['panels'])),
            ];
            $tpl->save();
            $updated++;
        }

        $this->command->info("Updated lab_orders on {$updated} encounter templates.");
        if (!empty($missing)) {
            $this->command->warn('Skipped (template not found): ' . implode(', ', $missing));
        }
    }
}
