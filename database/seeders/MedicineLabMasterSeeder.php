<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicineLabMasterSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Haematology', 'code' => 'HAEM'],
            ['name' => 'Biochemistry', 'code' => 'BIO'],
            ['name' => 'Clinical Pathology', 'code' => 'CLINPATH'],
            ['name' => 'Coagulation', 'code' => 'COAG'],
            ['name' => 'Immunology', 'code' => 'IMMUNO'],
            ['name' => 'Hormones', 'code' => 'HORM'],
            ['name' => 'Cardiac Markers', 'code' => 'CARD'],
            ['name' => 'Microbiology', 'code' => 'MICRO'],
            ['name' => 'Serology', 'code' => 'SERO'],
            ['name' => 'Arterial Blood Gas', 'code' => 'ABG'],
        ];

        foreach ($categories as $category) {
            DB::table('lab_categories')->updateOrInsert(
                ['code' => $category['code']],
                $category + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $categoryIds = DB::table('lab_categories')->pluck('id', 'code')->all();

        $tests = [
            // Complete blood count and peripheral blood indices.
            ['HAEM', 'Haemoglobin', 'CBC_HB', 'Whole blood EDTA', 'g/dL', 'Male 13.0-17.0; Female 12.0-15.0', 80],
            ['HAEM', 'RBC Count', 'CBC_RBC', 'Whole blood EDTA', 'million/uL', 'Male 4.5-5.9; Female 4.0-5.2', 80],
            ['HAEM', 'Packed Cell Volume / Haematocrit', 'CBC_PCV', 'Whole blood EDTA', '%', 'Male 40-50; Female 36-46', 80],
            ['HAEM', 'MCV', 'CBC_MCV', 'Whole blood EDTA', 'fL', '80-100', 80],
            ['HAEM', 'MCH', 'CBC_MCH', 'Whole blood EDTA', 'pg', '27-32', 80],
            ['HAEM', 'MCHC', 'CBC_MCHC', 'Whole blood EDTA', 'g/dL', '32-36', 80],
            ['HAEM', 'RDW-CV', 'CBC_RDWCV', 'Whole blood EDTA', '%', '11.5-14.5', 80],
            ['HAEM', 'Total Leucocyte Count', 'CBC_TLC', 'Whole blood EDTA', '/uL', '4000-11000', 80],
            ['HAEM', 'Neutrophils', 'CBC_NEUT_PCT', 'Whole blood EDTA', '%', '40-75', 80],
            ['HAEM', 'Lymphocytes', 'CBC_LYMPH_PCT', 'Whole blood EDTA', '%', '20-45', 80],
            ['HAEM', 'Monocytes', 'CBC_MONO_PCT', 'Whole blood EDTA', '%', '2-10', 80],
            ['HAEM', 'Eosinophils', 'CBC_EOS_PCT', 'Whole blood EDTA', '%', '1-6', 80],
            ['HAEM', 'Basophils', 'CBC_BASO_PCT', 'Whole blood EDTA', '%', '0-1', 80],
            ['HAEM', 'Absolute Neutrophil Count', 'CBC_ANC', 'Whole blood EDTA', '/uL', '2000-7500', 80],
            ['HAEM', 'Absolute Lymphocyte Count', 'CBC_ALC', 'Whole blood EDTA', '/uL', '1000-4000', 80],
            ['HAEM', 'Absolute Monocyte Count', 'CBC_AMC', 'Whole blood EDTA', '/uL', '200-1000', 80],
            ['HAEM', 'Absolute Eosinophil Count', 'CBC_AEC', 'Whole blood EDTA', '/uL', '40-440', 80],
            ['HAEM', 'Platelet Count', 'CBC_PLT', 'Whole blood EDTA', 'lakh/uL', '1.5-4.5', 80],
            ['HAEM', 'Mean Platelet Volume', 'CBC_MPV', 'Whole blood EDTA', 'fL', '7.5-11.5', 80],
            ['HAEM', 'Plateletcrit', 'CBC_PCT', 'Whole blood EDTA', '%', '0.20-0.50', 80],
            ['HAEM', 'Peripheral Smear', 'PS', 'Whole blood EDTA', null, 'RBC, WBC and platelet morphology: no abnormal cells seen', 150],
            ['HAEM', 'ESR', 'ESR', 'Whole blood EDTA', 'mm/hr', 'Male 0-15; Female 0-20', 120],
            ['HAEM', 'Reticulocyte Count', 'RETIC', 'Whole blood EDTA', '%', '0.5-2.5', 200],

            // Diabetes and glucose monitoring.
            ['BIO', 'Blood Glucose Fasting', 'FBS', 'Fluoride plasma', 'mg/dL', '70-99', 80],
            ['BIO', 'Blood Glucose Post Prandial', 'PPBS', 'Fluoride plasma', 'mg/dL', '<140', 80],
            ['BIO', 'Random Blood Glucose', 'RBS', 'Fluoride plasma', 'mg/dL', '70-140', 80],
            ['BIO', 'HbA1c', 'HBA1C', 'Whole blood EDTA', '%', 'Non-diabetic <5.7; Prediabetes 5.7-6.4; Diabetes >=6.5', 400],
            ['BIO', 'Estimated Average Glucose', 'EAG', 'Whole blood EDTA', 'mg/dL', 'Derived from HbA1c', 0],
            ['BIO', 'Urine Microalbumin', 'UMA', 'Spot urine', 'mg/L', '<20', 450],
            ['BIO', 'Urine Albumin Creatinine Ratio', 'UACR', 'Spot urine', 'mg/g creatinine', '<30', 500],

            // Renal function and electrolytes.
            ['BIO', 'Urea', 'UREA', 'Serum', 'mg/dL', '15-40', 120],
            ['BIO', 'Blood Urea Nitrogen', 'BUN', 'Serum', 'mg/dL', '7-20', 120],
            ['BIO', 'Creatinine', 'CREAT', 'Serum', 'mg/dL', 'Male 0.7-1.3; Female 0.6-1.1', 150],
            ['BIO', 'Uric Acid', 'URIC', 'Serum', 'mg/dL', 'Male 3.5-7.2; Female 2.6-6.0', 150],
            ['BIO', 'eGFR', 'EGFR', 'Serum', 'mL/min/1.73m2', '>=90 normal; <60 reduced', 0],
            ['BIO', 'Sodium', 'NA', 'Serum', 'mmol/L', '135-145', 150],
            ['BIO', 'Potassium', 'K', 'Serum', 'mmol/L', '3.5-5.1', 150],
            ['BIO', 'Chloride', 'CL', 'Serum', 'mmol/L', '98-107', 150],
            ['BIO', 'Bicarbonate', 'HCO3', 'Serum', 'mmol/L', '22-29', 150],
            ['BIO', 'Calcium Total', 'CA', 'Serum', 'mg/dL', '8.6-10.2', 180],
            ['BIO', 'Ionized Calcium', 'ICA', 'Serum', 'mmol/L', '1.12-1.32', 250],
            ['BIO', 'Phosphorus', 'PHOS', 'Serum', 'mg/dL', '2.5-4.5', 180],
            ['BIO', 'Magnesium', 'MG', 'Serum', 'mg/dL', '1.7-2.2', 250],

            // Liver function.
            ['BIO', 'Total Bilirubin', 'TBIL', 'Serum', 'mg/dL', '0.2-1.2', 120],
            ['BIO', 'Direct Bilirubin', 'DBIL', 'Serum', 'mg/dL', '0.0-0.3', 120],
            ['BIO', 'Indirect Bilirubin', 'IBIL', 'Serum', 'mg/dL', '0.2-0.9', 0],
            ['BIO', 'SGOT / AST', 'AST', 'Serum', 'U/L', '<40', 150],
            ['BIO', 'SGPT / ALT', 'ALT', 'Serum', 'U/L', '<41', 150],
            ['BIO', 'Alkaline Phosphatase', 'ALP', 'Serum', 'U/L', '40-129', 150],
            ['BIO', 'Gamma GT', 'GGT', 'Serum', 'U/L', 'Male 8-61; Female 5-36', 250],
            ['BIO', 'Total Protein', 'TPROT', 'Serum', 'g/dL', '6.4-8.3', 120],
            ['BIO', 'Albumin', 'ALB', 'Serum', 'g/dL', '3.5-5.2', 120],
            ['BIO', 'Globulin', 'GLOB', 'Serum', 'g/dL', '2.0-3.5', 0],
            ['BIO', 'A/G Ratio', 'AGR', 'Serum', 'ratio', '1.0-2.2', 0],

            // Lipids.
            ['BIO', 'Total Cholesterol', 'CHOL', 'Serum', 'mg/dL', '<200 desirable', 150],
            ['BIO', 'Triglycerides', 'TG', 'Serum', 'mg/dL', '<150', 150],
            ['BIO', 'HDL Cholesterol', 'HDL', 'Serum', 'mg/dL', 'Male >40; Female >50', 150],
            ['BIO', 'LDL Cholesterol', 'LDL', 'Serum', 'mg/dL', '<100 optimal', 150],
            ['BIO', 'VLDL Cholesterol', 'VLDL', 'Serum', 'mg/dL', '5-40', 0],
            ['BIO', 'Non-HDL Cholesterol', 'NONHDL', 'Serum', 'mg/dL', '<130 optimal', 0],
            ['BIO', 'Total Cholesterol / HDL Ratio', 'CHOLHDL', 'Serum', 'ratio', '<5.0', 0],

            // Thyroid and endocrine.
            ['HORM', 'TSH', 'TSH', 'Serum', 'uIU/mL', '0.4-4.0', 300],
            ['HORM', 'Total T3', 'TT3', 'Serum', 'ng/dL', '80-200', 300],
            ['HORM', 'Total T4', 'TT4', 'Serum', 'ug/dL', '5.0-12.0', 300],
            ['HORM', 'Free T3', 'FT3', 'Serum', 'pg/mL', '2.3-4.2', 350],
            ['HORM', 'Free T4', 'FT4', 'Serum', 'ng/dL', '0.8-1.8', 350],
            ['HORM', 'Vitamin D 25-OH', 'VITD', 'Serum', 'ng/mL', 'Deficient <20; Sufficient 30-100', 900],
            ['HORM', 'Vitamin B12', 'B12', 'Serum', 'pg/mL', '200-900', 700],
            ['HORM', 'Ferritin', 'FERR', 'Serum', 'ng/mL', 'Male 30-400; Female 15-150', 600],
            ['HORM', 'Cortisol 8 AM', 'CORT8', 'Serum', 'ug/dL', '5-25', 750],

            // Iron studies.
            ['BIO', 'Serum Iron', 'IRON', 'Serum', 'ug/dL', 'Male 65-175; Female 50-170', 300],
            ['BIO', 'TIBC', 'TIBC', 'Serum', 'ug/dL', '250-450', 300],
            ['BIO', 'Transferrin Saturation', 'TSAT', 'Serum', '%', '20-50', 0],

            // Cardiac and muscle markers.
            ['CARD', 'Troponin I', 'TROPI', 'Serum', 'ng/mL', '<0.04', 900],
            ['CARD', 'Troponin T', 'TROPT', 'Serum', 'ng/L', '<14', 900],
            ['CARD', 'CK-MB', 'CKMB', 'Serum', 'ng/mL', '<5', 500],
            ['CARD', 'CPK Total', 'CPK', 'Serum', 'U/L', 'Male 39-308; Female 26-192', 350],
            ['CARD', 'LDH', 'LDH', 'Serum', 'U/L', '135-225', 300],
            ['CARD', 'NT-proBNP', 'NTPROBNP', 'Serum', 'pg/mL', '<125 if age <75; <450 if age >=75', 1400],

            // Inflammation and infection.
            ['IMMUNO', 'CRP Quantitative', 'CRP', 'Serum', 'mg/L', '<5', 350],
            ['IMMUNO', 'High Sensitivity CRP', 'HSCRP', 'Serum', 'mg/L', 'Low risk <1; Average 1-3; High >3', 650],
            ['IMMUNO', 'Procalcitonin', 'PCTN', 'Serum', 'ng/mL', '<0.05', 1800],
            ['IMMUNO', 'D-Dimer', 'DDIMER', 'Citrated plasma', 'ng/mL FEU', '<500', 900],
            ['IMMUNO', 'Rheumatoid Factor', 'RF', 'Serum', 'IU/mL', '<14', 450],
            ['IMMUNO', 'Anti-CCP', 'ANTICCP', 'Serum', 'U/mL', '<20', 1200],
            ['IMMUNO', 'ANA by IFA', 'ANA', 'Serum', 'titre', 'Negative', 1200],
            ['IMMUNO', 'ASO Titre', 'ASO', 'Serum', 'IU/mL', '<200', 500],

            // Coagulation.
            ['COAG', 'Bleeding Time', 'BT', 'Whole blood', 'min', '2-7', 100],
            ['COAG', 'Clotting Time', 'CT', 'Whole blood', 'min', '5-11', 100],
            ['COAG', 'Prothrombin Time', 'PT', 'Citrated plasma', 'sec', '11-13.5', 350],
            ['COAG', 'INR', 'INR', 'Citrated plasma', 'ratio', '0.8-1.2', 0],
            ['COAG', 'aPTT', 'APTT', 'Citrated plasma', 'sec', '25-35', 400],
            ['COAG', 'Fibrinogen', 'FIB', 'Citrated plasma', 'mg/dL', '200-400', 700],

            // Pancreas and enzymes.
            ['BIO', 'Amylase', 'AMYL', 'Serum', 'U/L', '28-100', 350],
            ['BIO', 'Lipase', 'LIPASE', 'Serum', 'U/L', '13-60', 450],

            // Urine routine and microscopy.
            ['CLINPATH', 'Urine Colour', 'UR_COLOR', 'Urine', null, 'Pale yellow to amber', 80],
            ['CLINPATH', 'Urine Appearance', 'UR_APP', 'Urine', null, 'Clear', 80],
            ['CLINPATH', 'Urine Specific Gravity', 'UR_SG', 'Urine', null, '1.005-1.030', 80],
            ['CLINPATH', 'Urine pH', 'UR_PH', 'Urine', null, '5.0-8.0', 80],
            ['CLINPATH', 'Urine Protein', 'UR_PROT', 'Urine', null, 'Negative', 80],
            ['CLINPATH', 'Urine Glucose', 'UR_GLU', 'Urine', null, 'Negative', 80],
            ['CLINPATH', 'Urine Ketones', 'UR_KET', 'Urine', null, 'Negative', 80],
            ['CLINPATH', 'Urine Bilirubin', 'UR_BIL', 'Urine', null, 'Negative', 80],
            ['CLINPATH', 'Urine Urobilinogen', 'UR_URO', 'Urine', 'EU/dL', '0.2-1.0', 80],
            ['CLINPATH', 'Urine Nitrite', 'UR_NIT', 'Urine', null, 'Negative', 80],
            ['CLINPATH', 'Urine Leucocyte Esterase', 'UR_LE', 'Urine', null, 'Negative', 80],
            ['CLINPATH', 'Urine RBC', 'UR_RBC', 'Urine microscopy', '/HPF', '0-2', 80],
            ['CLINPATH', 'Urine Pus Cells', 'UR_PUS', 'Urine microscopy', '/HPF', '0-5', 80],
            ['CLINPATH', 'Urine Epithelial Cells', 'UR_EPI', 'Urine microscopy', '/HPF', '0-5', 80],
            ['CLINPATH', 'Urine Casts', 'UR_CASTS', 'Urine microscopy', '/LPF', 'Absent', 80],
            ['CLINPATH', 'Urine Crystals', 'UR_CRYST', 'Urine microscopy', null, 'Absent', 80],
            ['CLINPATH', 'Urine Bacteria', 'UR_BACT', 'Urine microscopy', null, 'Absent', 80],

            // Stool routine.
            ['CLINPATH', 'Stool Colour', 'ST_COLOR', 'Stool', null, 'Brown', 100],
            ['CLINPATH', 'Stool Consistency', 'ST_CONS', 'Stool', null, 'Formed', 100],
            ['CLINPATH', 'Stool Mucus', 'ST_MUCUS', 'Stool', null, 'Absent', 100],
            ['CLINPATH', 'Stool Blood', 'ST_BLOOD', 'Stool', null, 'Absent', 100],
            ['CLINPATH', 'Stool Occult Blood', 'ST_OB', 'Stool', null, 'Negative', 200],
            ['CLINPATH', 'Stool Ova/Cyst', 'ST_OVA', 'Stool microscopy', null, 'Not seen', 150],
            ['CLINPATH', 'Stool Pus Cells', 'ST_PUS', 'Stool microscopy', '/HPF', '0-5', 150],
            ['CLINPATH', 'Stool RBC', 'ST_RBC', 'Stool microscopy', '/HPF', 'Absent', 150],

            // Serology and infectious medicine.
            ['SERO', 'Malaria Parasite Smear', 'MP_SMEAR', 'Peripheral smear', null, 'Not seen', 200],
            ['SERO', 'Malaria Antigen', 'MP_AG', 'Whole blood EDTA', null, 'Negative', 500],
            ['SERO', 'Dengue NS1 Antigen', 'DENGUE_NS1', 'Serum', null, 'Negative', 700],
            ['SERO', 'Dengue IgM', 'DENGUE_IGM', 'Serum', null, 'Negative', 700],
            ['SERO', 'Dengue IgG', 'DENGUE_IGG', 'Serum', null, 'Negative', 700],
            ['SERO', 'Widal Test O Antigen', 'WIDAL_O', 'Serum', 'titre', '<1:80', 350],
            ['SERO', 'Widal Test H Antigen', 'WIDAL_H', 'Serum', 'titre', '<1:80', 350],
            ['SERO', 'Typhi IgM', 'TYPHI_IGM', 'Serum', null, 'Negative', 500],
            ['SERO', 'HIV 1 and 2', 'HIV', 'Serum', null, 'Non-reactive', 450],
            ['SERO', 'HBsAg', 'HBSAG', 'Serum', null, 'Non-reactive', 450],
            ['SERO', 'Anti-HCV', 'HCV', 'Serum', null, 'Non-reactive', 600],
            ['SERO', 'VDRL / RPR', 'VDRL', 'Serum', null, 'Non-reactive', 350],
            ['SERO', 'COVID-19 RAT', 'COVID_RAT', 'Nasopharyngeal swab', null, 'Negative', 500],

            // Cultures.
            ['MICRO', 'Urine Culture and Sensitivity', 'UR_CULTURE', 'Urine', null, 'No significant growth', 800],
            ['MICRO', 'Blood Culture and Sensitivity', 'BLD_CULTURE', 'Blood', null, 'No growth', 1500],
            ['MICRO', 'Sputum Gram Stain', 'SP_GRAM', 'Sputum', null, 'No pathogenic organism seen', 300],
            ['MICRO', 'Sputum AFB Smear', 'SP_AFB', 'Sputum', null, 'AFB not seen', 400],
            ['MICRO', 'Sputum Culture and Sensitivity', 'SP_CULTURE', 'Sputum', null, 'No pathogenic growth', 900],

            // ABG.
            ['ABG', 'ABG pH', 'ABG_PH', 'Arterial blood heparin', null, '7.35-7.45', 600],
            ['ABG', 'ABG pCO2', 'ABG_PCO2', 'Arterial blood heparin', 'mmHg', '35-45', 600],
            ['ABG', 'ABG pO2', 'ABG_PO2', 'Arterial blood heparin', 'mmHg', '80-100', 600],
            ['ABG', 'ABG HCO3', 'ABG_HCO3', 'Arterial blood heparin', 'mmol/L', '22-26', 600],
            ['ABG', 'ABG Base Excess', 'ABG_BE', 'Arterial blood heparin', 'mmol/L', '-2 to +2', 600],
            ['ABG', 'ABG Oxygen Saturation', 'ABG_SAO2', 'Arterial blood heparin', '%', '95-100', 600],
            ['ABG', 'ABG Lactate', 'ABG_LAC', 'Arterial blood heparin', 'mmol/L', '0.5-2.2', 600],
        ];

        foreach ($tests as $test) {
            [$categoryCode, $name, $shortCode, $sampleType, $unit, $normalRange, $price] = $test;

            DB::table('lab_tests')->updateOrInsert(
                ['short_code' => $shortCode],
                [
                    'lab_category_id' => $categoryIds[$categoryCode],
                    'name' => $name,
                    'short_code' => $shortCode,
                    'description' => $name . ' - medicine lab master',
                    'sample_type' => $sampleType,
                    'normal_range' => $normalRange,
                    'unit' => $unit,
                    'price' => $price,
                    'turnaround_hours' => in_array($categoryCode, ['MICRO'], true) ? 72 : 24,
                    'instructions' => 'Reference ranges are adult ranges and may vary by age, sex, pregnancy status, method, and local laboratory calibration.',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $testIds = DB::table('lab_tests')->pluck('id', 'short_code')->all();

        $panels = [
            ['Complete Blood Count', 'CBC', 350, ['CBC_HB', 'CBC_RBC', 'CBC_PCV', 'CBC_MCV', 'CBC_MCH', 'CBC_MCHC', 'CBC_RDWCV', 'CBC_TLC', 'CBC_NEUT_PCT', 'CBC_LYMPH_PCT', 'CBC_MONO_PCT', 'CBC_EOS_PCT', 'CBC_BASO_PCT', 'CBC_ANC', 'CBC_ALC', 'CBC_AMC', 'CBC_AEC', 'CBC_PLT', 'CBC_MPV', 'CBC_PCT']],
            ['CBC with ESR and Peripheral Smear', 'CBC_ESR_PS', 600, ['CBC_HB', 'CBC_RBC', 'CBC_PCV', 'CBC_MCV', 'CBC_MCH', 'CBC_MCHC', 'CBC_RDWCV', 'CBC_TLC', 'CBC_NEUT_PCT', 'CBC_LYMPH_PCT', 'CBC_MONO_PCT', 'CBC_EOS_PCT', 'CBC_BASO_PCT', 'CBC_ANC', 'CBC_ALC', 'CBC_AMC', 'CBC_AEC', 'CBC_PLT', 'CBC_MPV', 'CBC_PCT', 'ESR', 'PS']],
            ['Renal Function Test', 'RFT', 750, ['UREA', 'BUN', 'CREAT', 'URIC', 'EGFR', 'NA', 'K', 'CL', 'CA', 'PHOS']],
            ['Electrolyte Panel', 'ELEC', 450, ['NA', 'K', 'CL', 'HCO3', 'CA', 'MG']],
            ['Liver Function Test', 'LFT', 750, ['TBIL', 'DBIL', 'IBIL', 'AST', 'ALT', 'ALP', 'GGT', 'TPROT', 'ALB', 'GLOB', 'AGR']],
            ['Lipid Profile', 'LIPID', 650, ['CHOL', 'TG', 'HDL', 'LDL', 'VLDL', 'NONHDL', 'CHOLHDL']],
            ['Thyroid Profile Total', 'TFT_TOTAL', 750, ['TT3', 'TT4', 'TSH']],
            ['Thyroid Profile Free', 'TFT_FREE', 900, ['FT3', 'FT4', 'TSH']],
            ['Diabetes Profile', 'DIAB', 900, ['FBS', 'PPBS', 'HBA1C', 'EAG', 'UMA', 'UACR']],
            ['Iron Studies', 'IRON_STUDY', 900, ['CBC_HB', 'IRON', 'TIBC', 'TSAT', 'FERR']],
            ['Cardiac Marker Panel', 'CARD_MARK', 2200, ['TROPI', 'CKMB', 'CPK', 'LDH', 'NTPROBNP']],
            ['Coagulation Profile', 'COAG_PROFILE', 900, ['BT', 'CT', 'PT', 'INR', 'APTT', 'FIB']],
            ['Urine Routine and Microscopy', 'URINE_RM', 250, ['UR_COLOR', 'UR_APP', 'UR_SG', 'UR_PH', 'UR_PROT', 'UR_GLU', 'UR_KET', 'UR_BIL', 'UR_URO', 'UR_NIT', 'UR_LE', 'UR_RBC', 'UR_PUS', 'UR_EPI', 'UR_CASTS', 'UR_CRYST', 'UR_BACT']],
            ['Stool Routine and Microscopy', 'STOOL_RM', 300, ['ST_COLOR', 'ST_CONS', 'ST_MUCUS', 'ST_BLOOD', 'ST_OB', 'ST_OVA', 'ST_PUS', 'ST_RBC']],
            ['Fever Panel Basic', 'FEVER_BASIC', 1600, ['CBC_HB', 'CBC_TLC', 'CBC_NEUT_PCT', 'CBC_LYMPH_PCT', 'CBC_PLT', 'ESR', 'CRP', 'MP_AG', 'DENGUE_NS1', 'WIDAL_O', 'WIDAL_H', 'UR_RBC', 'UR_PUS']],
            ['Inflammatory and Autoimmune Screen', 'INFL_AUTO', 2800, ['ESR', 'CRP', 'HSCRP', 'RF', 'ANTICCP', 'ANA', 'ASO']],
            ['Pancreatitis Profile', 'PANCREAS', 1000, ['AMYL', 'LIPASE', 'CBC_TLC', 'CA', 'TBIL', 'AST', 'ALT', 'ALP']],
            ['Arterial Blood Gas Panel', 'ABG_PANEL', 900, ['ABG_PH', 'ABG_PCO2', 'ABG_PO2', 'ABG_HCO3', 'ABG_BE', 'ABG_SAO2', 'ABG_LAC']],
        ];

        foreach ($panels as [$name, $shortCode, $price, $shortCodes]) {
            DB::table('lab_panels')->updateOrInsert(
                ['short_code' => $shortCode],
                [
                    'name' => $name,
                    'short_code' => $shortCode,
                    'description' => 'Includes: ' . implode(', ', $shortCodes),
                    'price' => $price,
                    'turnaround_hours' => $shortCode === 'ABG_PANEL' ? 2 : 24,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $panelId = DB::table('lab_panels')->where('short_code', $shortCode)->value('id');
            $ids = collect($shortCodes)
                ->map(fn (string $code) => $testIds[$code] ?? null)
                ->filter()
                ->values()
                ->all();

            DB::table('lab_panel_tests')->where('lab_panel_id', $panelId)->delete();
            foreach ($ids as $testId) {
                DB::table('lab_panel_tests')->insert([
                    'lab_panel_id' => $panelId,
                    'lab_test_id' => $testId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
