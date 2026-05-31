<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Built-in echo templates for Ischemic Heart Disease / Coronary Artery Disease.
 * Each row sets the secondary echo findings (EF, chamber size, secondary MR, etc.)
 * and spells out the regional wall motion abnormality in the impression text
 * (the form has no per-segment WMA dropdown — and clinicians read the impression
 * anyway).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            // 1. CAD, well-controlled, preserved LV
            [
                'name' => 'CAD with Preserved LV Function (post-revascularization)',
                'category' => 'ihd',
                'description' => 'Known CAD, post-PCI / post-CABG, no resting WMA, preserved EF.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '20', 'tapse' => '20', 'ivc_size' => '17',
                    'ivs' => '10', 'lvpw' => '10', 'lv_edd' => '48', 'lv_esd' => '32',
                    'epss' => '5', 'mapse' => '14', 'la_size' => '38', 'ao_root' => '32',
                    'ef_percent' => '55%', 'fs_percent' => '32%', 'lap_ee' => '9', 'pap' => '20',
                    'lv_diastolic_function' => 'Grade I - Impaired Relaxation',
                    'lv_systolic_function' => 'Normal',
                    'ea_ratio' => 'E/A &lt;1.0 - Grade I',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'None',
                    'mv_velocity' => '80', 'mv_gradient' => '2', 'mitral_valve' => 'Mild MR',
                    'av_velocity' => '160', 'av_gradient' => '10', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '80', 'tv_gradient' => '2', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Normal - no PR',
                    'impression' => "Known coronary artery disease, status post revascularization.\n\n"
                                  . "1. Normal LV size and wall thickness.\n"
                                  . "2. Preserved LV systolic function. EF = 55%. No resting regional wall motion abnormality.\n"
                                  . "3. Grade I diastolic dysfunction (impaired relaxation).\n"
                                  . "4. Normal RV size and function.\n"
                                  . "5. Mild functional MR, mild TR. Other valves normal.\n"
                                  . "6. No pericardial effusion.\n\n"
                                  . "CONCLUSION: Stable CAD with preserved LV function and Grade I LV diastolic dysfunction.",
                    'recommendations' => "Continue DAPT / antiplatelet as advised.\nHigh-intensity statin.\nOptimise BP, glycemic control, lipid profile.\nRepeat echo in 12 months or sooner if new symptoms.",
                ],
            ],

            // 2. Acute / Recent Anterior STEMI (LAD)
            [
                'name' => 'Acute / Recent Anterior MI (LAD territory)',
                'category' => 'ihd',
                'description' => 'Recent anterior STEMI, akinetic anterior wall + apex, moderately reduced EF.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '22', 'tapse' => '18', 'ivc_size' => '18',
                    'ivs' => '10', 'lvpw' => '9', 'lv_edd' => '54', 'lv_esd' => '42',
                    'epss' => '9', 'mapse' => '9', 'la_size' => '40', 'ao_root' => '32',
                    'ef_percent' => '38%', 'fs_percent' => '22%', 'lap_ee' => '13', 'pap' => '32',
                    'lv_diastolic_function' => 'Grade II - Pseudonormal',
                    'lv_systolic_function' => 'Moderately reduced (EF 35-44%)',
                    'ea_ratio' => 'E/A 1.0-2.0 - Grade II Pseudonormal',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '95', 'mv_gradient' => '3', 'mitral_valve' => 'Mild MR',
                    'av_velocity' => '140', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '230', 'tv_gradient' => '3', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Normal - no PR',
                    'impression' => "Recent anterior wall myocardial infarction (LAD territory).\n\n"
                                  . "1. Mildly dilated LV (LVIDD 54 mm).\n"
                                  . "2. Regional wall motion abnormality: akinesia of mid-to-distal anterior wall, anterior septum and apex. Hyperkinetic basal inferior and posterior walls.\n"
                                  . "3. Moderately reduced LV systolic function. EF = 38%.\n"
                                  . "4. Grade II diastolic dysfunction (pseudonormal pattern).\n"
                                  . "5. Mild functional MR, mild TR.\n"
                                  . "6. Mild pulmonary hypertension (PAP 32 mmHg).\n"
                                  . "7. No LV thrombus, no pericardial effusion.\n\n"
                                  . "CONCLUSION: Recent anterior MI with moderately reduced LV systolic function and Grade II diastolic dysfunction.",
                    'recommendations' => "Dual antiplatelet therapy (aspirin + P2Y12 inhibitor) per ACS protocol.\nHigh-intensity statin, ACEi/ARB, beta-blocker, MRA.\nCoronary angiography / revascularization assessment.\nMonitor for LV thrombus on serial echo at 1, 3 and 6 months.\nCardiac rehab referral.",
                ],
            ],

            // 3. Old Anterior MI (healed)
            [
                'name' => 'Old Anterior MI (healed)',
                'category' => 'ihd',
                'description' => 'Healed anterior MI, thinned anterior wall, mildly-moderately reduced EF.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '22', 'tapse' => '19', 'ivc_size' => '18',
                    'ivs' => '7', 'lvpw' => '10', 'lv_edd' => '56', 'lv_esd' => '44',
                    'epss' => '8', 'mapse' => '10', 'la_size' => '42', 'ao_root' => '32',
                    'ef_percent' => '42%', 'fs_percent' => '22%', 'lap_ee' => '12', 'pap' => '28',
                    'lv_diastolic_function' => 'Grade II - Pseudonormal',
                    'lv_systolic_function' => 'Moderately reduced (EF 35-44%)',
                    'ea_ratio' => 'E/A 1.0-2.0 - Grade II Pseudonormal',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'None',
                    'mv_velocity' => '90', 'mv_gradient' => '2', 'mitral_valve' => 'Mild MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '210', 'tv_gradient' => '3', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Normal - no PR',
                    'impression' => "Old anterior wall myocardial infarction (healed).\n\n"
                                  . "1. Mildly dilated LV (LVIDD 56 mm).\n"
                                  . "2. Thinned and akinetic mid-distal anterior wall, anterior septum and apex (IVS 7 mm — regional scarring).\n"
                                  . "3. Moderately reduced LV systolic function. EF = 42%.\n"
                                  . "4. Grade II diastolic dysfunction.\n"
                                  . "5. Mildly dilated LA (42 mm).\n"
                                  . "6. Mild functional MR, mild TR.\n"
                                  . "7. No LV thrombus, no aneurysm seen, no pericardial effusion.\n\n"
                                  . "CONCLUSION: Old anterior MI with mildly dilated LV and moderately reduced systolic function.",
                    'recommendations' => "Optimise GDMT: beta-blocker, ACEi/ARBi/ARNi, MRA, SGLT2i.\nAntiplatelet + high-intensity statin.\nAssess viability if revascularization being considered.\nRepeat echo in 6 months. ICD assessment if EF persistently <35%.",
                ],
            ],

            // 4. Old Anterior MI with Apical Aneurysm + LV thrombus
            [
                'name' => 'Old Anterior MI with Apical Aneurysm + LV Thrombus',
                'category' => 'ihd',
                'description' => 'Healed anterior MI with apical aneurysm and mural thrombus — high embolic risk.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '22', 'tapse' => '18', 'ivc_size' => '20',
                    'ivs' => '7', 'lvpw' => '10', 'lv_edd' => '60', 'lv_esd' => '50',
                    'epss' => '12', 'mapse' => '8', 'la_size' => '44', 'ao_root' => '32',
                    'ef_percent' => '30%', 'fs_percent' => '17%', 'lap_ee' => '16', 'pap' => '36',
                    'lv_diastolic_function' => 'Grade III - Restrictive',
                    'lv_systolic_function' => 'Severely reduced (EF &lt;35%)',
                    'ea_ratio' => 'E/A &gt;2.0 - Grade III Restrictive',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Mildly reduced',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '110', 'mv_gradient' => '3', 'mitral_valve' => 'Moderate MR',
                    'av_velocity' => '130', 'av_gradient' => '7', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '290', 'tv_gradient' => '4', 'tricuspid_valve' => 'Moderate TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Mild PR',
                    'impression' => "Old anterior wall MI with apical aneurysm and LV thrombus.\n\n"
                                  . "1. Dilated LV (LVIDD 60 mm) with eccentric remodeling.\n"
                                  . "2. Thinned and dyskinetic mid-distal anterior septum and apex with a discrete apical aneurysm.\n"
                                  . "3. Echogenic mass in the apex consistent with LV mural thrombus.\n"
                                  . "4. Severely reduced LV systolic function. EF = 30%.\n"
                                  . "5. Grade III (restrictive) diastolic dysfunction.\n"
                                  . "6. Moderate functional MR (tethering), moderate TR.\n"
                                  . "7. Moderate pulmonary hypertension (PAP 36 mmHg).\n\n"
                                  . "CONCLUSION: Old anterior MI with apical aneurysm and LV thrombus. Severely reduced systolic function. High embolic risk.",
                    'recommendations' => "URGENT cardiology referral.\nTherapeutic anticoagulation (warfarin / DOAC) for LV thrombus — minimum 3 months.\nGDMT: beta-blocker, ACEi/ARBi/ARNi, MRA, SGLT2i.\nICD / CRT assessment.\nRepeat echo at 3 months to assess thrombus resolution.",
                ],
            ],

            // 5. Acute / Recent Inferior MI (RCA)
            [
                'name' => 'Acute / Recent Inferior MI (RCA territory)',
                'category' => 'ihd',
                'description' => 'Recent inferior STEMI, inferior wall akinesia, preserved overall EF.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '22', 'tapse' => '17', 'ivc_size' => '18',
                    'ivs' => '10', 'lvpw' => '10', 'lv_edd' => '50', 'lv_esd' => '36',
                    'epss' => '6', 'mapse' => '12', 'la_size' => '38', 'ao_root' => '32',
                    'ef_percent' => '50%', 'fs_percent' => '28%', 'lap_ee' => '11', 'pap' => '24',
                    'lv_diastolic_function' => 'Grade I - Impaired Relaxation',
                    'lv_systolic_function' => 'Mildly reduced (EF 45-54%)',
                    'ea_ratio' => 'E/A &lt;1.0 - Grade I',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '90', 'mv_gradient' => '2', 'mitral_valve' => 'Mild MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '200', 'tv_gradient' => '3', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Normal - no PR',
                    'impression' => "Recent inferior wall myocardial infarction (RCA territory).\n\n"
                                  . "1. Normal LV size.\n"
                                  . "2. Regional wall motion abnormality: akinesia of basal-to-mid inferior and infero-septal walls. Hyperkinetic anterior wall.\n"
                                  . "3. Mildly reduced LV systolic function. EF = 50%.\n"
                                  . "4. Grade I diastolic dysfunction.\n"
                                  . "5. Mild functional MR (possible posteromedial papillary muscle ischemia), mild TR.\n"
                                  . "6. No RV involvement on this study. No pericardial effusion.\n\n"
                                  . "CONCLUSION: Recent inferior wall MI with mildly reduced LV systolic function.",
                    'recommendations' => "DAPT (aspirin + P2Y12) per ACS protocol.\nHigh-intensity statin, ACEi/ARB, beta-blocker (cautious if bradycardia/AV block).\nCoronary angiography for revascularization.\nCardiac rehab referral.\nRepeat echo at 1 month and 6 months.",
                ],
            ],

            // 6. Old Inferior MI
            [
                'name' => 'Old Inferior MI (healed)',
                'category' => 'ihd',
                'description' => 'Healed inferior MI, thinned inferior wall, mostly preserved EF.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '22', 'tapse' => '18', 'ivc_size' => '18',
                    'ivs' => '10', 'lvpw' => '7', 'lv_edd' => '52', 'lv_esd' => '38',
                    'epss' => '6', 'mapse' => '11', 'la_size' => '40', 'ao_root' => '32',
                    'ef_percent' => '52%', 'fs_percent' => '27%', 'lap_ee' => '10', 'pap' => '22',
                    'lv_diastolic_function' => 'Grade I - Impaired Relaxation',
                    'lv_systolic_function' => 'Mildly reduced (EF 45-54%)',
                    'ea_ratio' => 'E/A &lt;1.0 - Grade I',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'None',
                    'mv_velocity' => '90', 'mv_gradient' => '2', 'mitral_valve' => 'Mild MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '210', 'tv_gradient' => '3', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Normal - no PR',
                    'impression' => "Old inferior wall myocardial infarction (healed).\n\n"
                                  . "1. Normal LV size.\n"
                                  . "2. Thinned and hypokinetic basal-to-mid inferior and infero-septal walls (regional scar).\n"
                                  . "3. Mildly reduced LV systolic function. EF = 52%.\n"
                                  . "4. Grade I diastolic dysfunction.\n"
                                  . "5. Mild functional MR, mild TR.\n"
                                  . "6. No RV involvement. No pericardial effusion.\n\n"
                                  . "CONCLUSION: Old inferior MI with mildly reduced LV systolic function.",
                    'recommendations' => "Antiplatelet + high-intensity statin.\nBeta-blocker, ACEi/ARB.\nOptimise risk factors (BP, lipids, glycemic control, smoking cessation).\nRepeat echo annually.",
                ],
            ],

            // 7. Inferior MI with RV Involvement
            [
                'name' => 'Inferior MI with RV Involvement',
                'category' => 'ihd',
                'description' => 'Inferior MI extending to RV — dilated RV with reduced TAPSE.',
                'fields' => [
                    'rv_wall_thickness' => '5', 'rv_dimension' => '38', 'tapse' => '13', 'ivc_size' => '24',
                    'ivs' => '10', 'lvpw' => '10', 'lv_edd' => '50', 'lv_esd' => '38',
                    'epss' => '8', 'mapse' => '11', 'la_size' => '40', 'ao_root' => '32',
                    'ef_percent' => '48%', 'fs_percent' => '24%', 'lap_ee' => '11', 'pap' => '30',
                    'lv_diastolic_function' => 'Grade I - Impaired Relaxation',
                    'lv_systolic_function' => 'Mildly reduced (EF 45-54%)',
                    'ea_ratio' => 'E/A &lt;1.0 - Grade I',
                    'ivs_motion' => 'Paradoxical Motion',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Severely reduced',
                    'rv_transverse_function' => 'Moderately reduced',
                    'rv_paradoxical_movement' => 'Present',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '90', 'mv_gradient' => '2', 'mitral_valve' => 'Mild MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '270', 'tv_gradient' => '4', 'tricuspid_valve' => 'Moderate TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Mild PR',
                    'impression' => "Inferior wall myocardial infarction with RV involvement.\n\n"
                                  . "1. Normal LV size with regional akinesia of inferior and infero-septal walls.\n"
                                  . "2. Mildly reduced LV systolic function. EF = 48%.\n"
                                  . "3. Moderately dilated RV (RVID 38 mm).\n"
                                  . "4. Severely reduced RV systolic function (TAPSE 13 mm).\n"
                                  . "5. Paradoxical septal motion.\n"
                                  . "6. Dilated IVC (24 mm) with reduced respiratory collapse — elevated RA pressure.\n"
                                  . "7. Moderate TR. Mild pulmonary hypertension.\n"
                                  . "8. No pericardial effusion.\n\n"
                                  . "CONCLUSION: Inferior MI with significant RV involvement and RV dysfunction.",
                    'recommendations' => "Pre-load dependent — AVOID nitrates and diuretics in the acute phase.\nIV fluid loading guided by clinical response.\nDAPT, statin, beta-blocker (cautious).\nUrgent coronary angiography / revascularization.\nMonitor for AV block (RCA territory).",
                ],
            ],

            // 8. Extensive Anterior + Inferior MI
            [
                'name' => 'Extensive Anterior + Inferior MI (severe LV dysfunction)',
                'category' => 'ihd',
                'description' => 'Multi-territory MI — extensive WMA, severely reduced EF, eccentric remodeling.',
                'fields' => [
                    'rv_wall_thickness' => '5', 'rv_dimension' => '26', 'tapse' => '15', 'ivc_size' => '22',
                    'ivs' => '8', 'lvpw' => '8', 'lv_edd' => '66', 'lv_esd' => '56',
                    'epss' => '14', 'mapse' => '7', 'la_size' => '46', 'ao_root' => '32',
                    'ef_percent' => '25%', 'fs_percent' => '15%', 'lap_ee' => '18', 'pap' => '44',
                    'lv_diastolic_function' => 'Grade III - Restrictive',
                    'lv_systolic_function' => 'Severely reduced (EF &lt;35%)',
                    'ea_ratio' => 'E/A &gt;2.0 - Grade III Restrictive',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Mildly reduced',
                    'rv_transverse_function' => 'Mildly reduced',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '110', 'mv_gradient' => '3', 'mitral_valve' => 'Moderate MR',
                    'av_velocity' => '130', 'av_gradient' => '7', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '310', 'tv_gradient' => '5', 'tricuspid_valve' => 'Moderate TR',
                    'pv_velocity' => '110', 'pv_gradient' => '12', 'pulmonary_valve' => 'Moderate PR',
                    'impression' => "Extensive multi-territory myocardial infarction (anterior + inferior).\n\n"
                                  . "1. Moderately dilated LV (LVIDD 66 mm) with eccentric remodeling.\n"
                                  . "2. Akinesia of anterior wall, anterior septum, apex, inferior and infero-septal walls.\n"
                                  . "3. Severely reduced LV systolic function. EF = 25%.\n"
                                  . "4. Grade III (restrictive) diastolic dysfunction.\n"
                                  . "5. Moderately dilated LA (46 mm).\n"
                                  . "6. Moderate functional MR (tethering), moderate TR.\n"
                                  . "7. Moderate pulmonary hypertension (PAP 44 mmHg).\n"
                                  . "8. No LV thrombus seen, no pericardial effusion.\n\n"
                                  . "CONCLUSION: Multi-territory ischemic cardiomyopathy with severely reduced EF and moderate functional MR.",
                    'recommendations' => "URGENT cardiology referral.\nGDMT: beta-blocker, ACEi/ARBi/ARNi, MRA, SGLT2i.\nDiuretics for volume overload. Salt and fluid restriction.\nViability study before any revascularization decision.\nICD primary prevention + CRT assessment if QRS wide.\nConsider advanced HF therapies (LVAD, transplant) if refractory.",
                ],
            ],

            // 9. Ischemic Cardiomyopathy (global hypokinesia)
            [
                'name' => 'Ischemic Cardiomyopathy (multivessel CAD, global hypokinesia)',
                'category' => 'ihd',
                'description' => 'Multivessel CAD producing global hypokinesia — distinguished from DCM by ischemic etiology.',
                'fields' => [
                    'rv_wall_thickness' => '5', 'rv_dimension' => '26', 'tapse' => '16', 'ivc_size' => '22',
                    'ivs' => '9', 'lvpw' => '9', 'lv_edd' => '62', 'lv_esd' => '52',
                    'epss' => '12', 'mapse' => '8', 'la_size' => '44', 'ao_root' => '32',
                    'ef_percent' => '30%', 'fs_percent' => '16%', 'lap_ee' => '16', 'pap' => '38',
                    'lv_diastolic_function' => 'Grade III - Restrictive',
                    'lv_systolic_function' => 'Severely reduced (EF &lt;35%)',
                    'ea_ratio' => 'E/A &gt;2.0 - Grade III Restrictive',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Mildly reduced',
                    'rv_transverse_function' => 'Mildly reduced',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '110', 'mv_gradient' => '3', 'mitral_valve' => 'Moderate MR',
                    'av_velocity' => '130', 'av_gradient' => '7', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '290', 'tv_gradient' => '4', 'tricuspid_valve' => 'Moderate TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Mild PR',
                    'impression' => "Ischemic cardiomyopathy.\n\n"
                                  . "1. Dilated LV (LVIDD 62 mm).\n"
                                  . "2. Global LV hypokinesia, worse in territories of prior infarction.\n"
                                  . "3. Severely reduced LV systolic function. EF = 30%.\n"
                                  . "4. Grade III (restrictive) diastolic dysfunction. Elevated LV filling pressure (E/e' 16).\n"
                                  . "5. Moderately dilated LA (44 mm).\n"
                                  . "6. Moderate functional (tethering) MR, moderate TR.\n"
                                  . "7. Moderate pulmonary hypertension (PAP 38 mmHg).\n"
                                  . "8. No LV thrombus. No pericardial effusion.\n\n"
                                  . "CONCLUSION: Ischemic cardiomyopathy with severely reduced EF, restrictive filling and secondary functional MR.",
                    'recommendations' => "GDMT: beta-blocker, ACEi/ARBi/ARNi, MRA, SGLT2i.\nDiuretics for volume overload.\nCoronary angiography + viability assessment for revascularization.\nICD primary prevention. CRT if QRS >150 ms and LBBB.\nConsider Mitraclip / TEER for severe functional MR if EF persistently low.\nCardiac rehab and salt/fluid restriction.",
                ],
            ],

            // 10. Old Anterior MI with Concentric LVH (HTN + CAD overlap)
            [
                'name' => 'Old Anterior MI with Concentric LVH (HTN + CAD)',
                'category' => 'ihd',
                'description' => 'Hypertensive heart + old anterior MI — septal thinning vs LVH overlap.',
                'fields' => [
                    'rv_wall_thickness' => '4', 'rv_dimension' => '22', 'tapse' => '18', 'ivc_size' => '20',
                    'ivs' => '8', 'lvpw' => '14', 'lv_edd' => '54', 'lv_esd' => '42',
                    'epss' => '9', 'mapse' => '10', 'la_size' => '44', 'ao_root' => '34',
                    'ef_percent' => '40%', 'fs_percent' => '22%', 'lap_ee' => '14', 'pap' => '32',
                    'lv_diastolic_function' => 'Grade II - Pseudonormal',
                    'lv_systolic_function' => 'Moderately reduced (EF 35-44%)',
                    'ea_ratio' => 'E/A 1.0-2.0 - Grade II Pseudonormal',
                    'ivs_motion' => 'Hypertrophied',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'None',
                    'mv_velocity' => '100', 'mv_gradient' => '3', 'mitral_valve' => 'Moderate MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '250', 'tv_gradient' => '4', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Normal - no PR',
                    'impression' => "Hypertensive heart disease with superimposed old anterior MI.\n\n"
                                  . "1. Mildly dilated LV (LVIDD 54 mm).\n"
                                  . "2. Asymmetric wall thickness: thinned anterior septum (IVS 8 mm — regional scar) and concentric thickening of posterior and lateral walls (LVPW 14 mm — hypertensive LVH).\n"
                                  . "3. Akinetic mid-distal anterior wall, anterior septum and apex (old MI).\n"
                                  . "4. Moderately reduced LV systolic function. EF = 40%.\n"
                                  . "5. Grade II diastolic dysfunction (pseudonormal).\n"
                                  . "6. Mildly dilated LA (44 mm).\n"
                                  . "7. Moderate functional MR, mild TR.\n"
                                  . "8. Mild pulmonary hypertension (PAP 32 mmHg).\n\n"
                                  . "CONCLUSION: Old anterior MI on a background of hypertensive heart disease. Moderately reduced LV systolic function and Grade II diastolic dysfunction.",
                    'recommendations' => "Strict BP control (target <130/80) — ACEi/ARB preferred.\nGDMT: beta-blocker, MRA, SGLT2i.\nHigh-intensity statin + antiplatelet.\nAddress lipid, glycemic and renal status.\nCoronary angiography / viability assessment if not done.\nRepeat echo in 6 months. ICD assessment if EF persistently <35%.",
                ],
            ],
        ];

        foreach ($rows as $r) {
            $exists = DB::table('echo_templates')->where('name', $r['name'])->where('is_builtin', true)->exists();
            if ($exists) continue;
            DB::table('echo_templates')->insert([
                'name'        => $r['name'],
                'category'    => $r['category'],
                'description' => $r['description'],
                'fields'      => json_encode($r['fields']),
                'is_builtin'  => true,
                'created_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('echo_templates')->where('is_builtin', true)->where('category', 'ihd')->delete();
    }
};
