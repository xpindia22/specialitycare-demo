<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            [
                'name' => 'RHD - Mitral Stenosis with Mitral Regurgitation',
                'category' => 'rhd',
                'description' => 'Combined rheumatic MS-MR, dilated LA, mild-moderate PAH.',
                'fields' => [
                    'rv_wall_thickness' => '5', 'rv_dimension' => '24', 'tapse' => '18', 'ivc_size' => '19',
                    'ivs' => '9', 'lvpw' => '9', 'lv_edd' => '50', 'lv_esd' => '32',
                    'epss' => '4', 'mapse' => '14', 'la_size' => '52', 'ao_root' => '30',
                    'ef_percent' => '60%', 'fs_percent' => '34%', 'lap_ee' => '13', 'pap' => '38',
                    'lv_diastolic_function' => 'Indeterminate',
                    'lv_systolic_function' => 'Normal',
                    'ea_ratio' => 'Normal (E/A &gt;1.0)',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Normal',
                    'rv_transverse_function' => 'Normal',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'None',
                    'mv_velocity' => '185', 'mv_gradient' => '12', 'mitral_valve' => 'Rheumatic MS with moderate MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '230', 'tv_gradient' => '3', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '100', 'pv_gradient' => '8', 'pulmonary_valve' => 'Normal - no PR',
                    'recommendations' => "Cardiology review. Wilkins score for PTMC / consider MVR given combined MS-MR.\nPenicillin prophylaxis (RHD).\nAnticoagulation if AF or LA thrombus.\nRepeat echo 6-monthly.",
                ],
            ],
            [
                'name' => 'RHD - Severe MS with Severe MR',
                'category' => 'rhd',
                'description' => 'Advanced combined mitral stenosis and regurgitation, severely dilated LA, surgical candidate.',
                'fields' => [
                    'rv_wall_thickness' => '6', 'rv_dimension' => '30', 'tapse' => '15', 'ivc_size' => '23',
                    'ivs' => '9', 'lvpw' => '9', 'lv_edd' => '56', 'lv_esd' => '36',
                    'epss' => '6', 'mapse' => '12', 'la_size' => '60', 'ao_root' => '30',
                    'ef_percent' => '55%', 'fs_percent' => '30%', 'lap_ee' => '17', 'pap' => '55',
                    'lv_diastolic_function' => 'Grade III - Restrictive',
                    'lv_systolic_function' => 'Normal',
                    'ea_ratio' => 'E/A &gt;2.0 - Grade III Restrictive',
                    'ivs_motion' => 'Normal / Intact',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Mildly reduced',
                    'rv_transverse_function' => 'Mildly reduced',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '250', 'mv_gradient' => '18', 'mitral_valve' => 'Rheumatic MS with severe MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '310', 'tv_gradient' => '5', 'tricuspid_valve' => 'Moderate TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Mild PR',
                    'recommendations' => "Cardiac surgery referral for combined MVR (repair unlikely feasible given mixed stenosis-regurgitation).\nPenicillin prophylaxis (RHD). Anticoagulation if AF.\nDiuretics for congestion. Serial echo 3-6 monthly pending surgery.",
                ],
            ],
            [
                'name' => 'RHD - Multivalvular (MS + MR + TR + PAH)',
                'category' => 'rhd',
                'description' => 'Combined mitral stenosis-regurgitation with severe TR and severe pulmonary hypertension.',
                'fields' => [
                    'rv_wall_thickness' => '7', 'rv_dimension' => '38', 'tapse' => '14', 'ivc_size' => '25',
                    'ivs' => '9', 'lvpw' => '9', 'lv_edd' => '48', 'lv_esd' => '30',
                    'epss' => '5', 'mapse' => '13', 'la_size' => '58', 'ao_root' => '30',
                    'ef_percent' => '58%', 'fs_percent' => '33%', 'lap_ee' => '15', 'pap' => '65',
                    'lv_diastolic_function' => 'Grade II - Pseudonormal',
                    'lv_systolic_function' => 'Normal',
                    'ea_ratio' => 'E/A 1.0-2.0 - Grade II Pseudonormal',
                    'ivs_motion' => 'Paradoxical Motion',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Moderately reduced',
                    'rv_transverse_function' => 'Moderately reduced',
                    'rv_paradoxical_movement' => 'Present',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '200', 'mv_gradient' => '13', 'mitral_valve' => 'Rheumatic MS with moderate MR',
                    'av_velocity' => '150', 'av_gradient' => '8', 'aortic_valve' => 'Normal - no AS, no AR',
                    'tv_velocity' => '420', 'tv_gradient' => '8', 'tricuspid_valve' => 'Severe TR',
                    'pv_velocity' => '120', 'pv_gradient' => '10', 'pulmonary_valve' => 'Moderate PR',
                    'recommendations' => "Urgent cardiology referral - combined valve disease with severe PAH.\nWilkins score / surgical (MVR + TV repair) assessment.\nPenicillin prophylaxis (RHD). Diuretics for congestion.\nPAH workup if RV dysfunction progresses.",
                ],
            ],
            [
                'name' => 'RHD - Multivalvular (MS + MR + TR + AS + AR)',
                'category' => 'rhd',
                'description' => 'Advanced/burnt-out RHD with mitral, tricuspid and aortic valve involvement.',
                'fields' => [
                    'rv_wall_thickness' => '6', 'rv_dimension' => '32', 'tapse' => '15', 'ivc_size' => '23',
                    'ivs' => '11', 'lvpw' => '11', 'lv_edd' => '50', 'lv_esd' => '32',
                    'epss' => '6', 'mapse' => '12', 'la_size' => '56', 'ao_root' => '33',
                    'ef_percent' => '52%', 'fs_percent' => '28%', 'lap_ee' => '16', 'pap' => '50',
                    'lv_diastolic_function' => 'Grade II - Pseudonormal',
                    'lv_systolic_function' => 'Mildly reduced (EF 45-54%)',
                    'ea_ratio' => 'E/A 1.0-2.0 - Grade II Pseudonormal',
                    'ivs_motion' => 'Hypertrophied',
                    'pericardium_status' => 'Normal',
                    'rv_longitudinal_function' => 'Mildly reduced',
                    'rv_transverse_function' => 'Mildly reduced',
                    'rv_paradoxical_movement' => 'Absent',
                    'ias_status' => 'Intact',
                    'pericardial_effusion' => 'Trivial',
                    'mv_velocity' => '210', 'mv_gradient' => '14', 'mitral_valve' => 'Rheumatic MS with moderate MR',
                    'av_velocity' => '320', 'av_gradient' => '30', 'aortic_valve' => 'Moderate AS with Mild AR',
                    'tv_velocity' => '310', 'tv_gradient' => '5', 'tricuspid_valve' => 'Moderate TR',
                    'pv_velocity' => '110', 'pv_gradient' => '10', 'pulmonary_valve' => 'Mild PR',
                    'recommendations' => "Cardiac surgery referral for multivalvular assessment (MVR + AVR +/- TV repair).\nPenicillin prophylaxis (RHD). Anticoagulation if AF.\nOptimise heart failure therapy pending surgery. Serial echo 3-6 monthly.",
                ],
            ],
            [
                'name' => 'RHD - Mixed Mitral-Aortic (MS + AR)',
                'category' => 'rhd',
                'description' => 'Rheumatic mitral stenosis with aortic regurgitation, no significant MR or TR.',
                'fields' => [
                    'rv_wall_thickness' => '5', 'rv_dimension' => '24', 'tapse' => '18', 'ivc_size' => '19',
                    'ivs' => '10', 'lvpw' => '10', 'lv_edd' => '54', 'lv_esd' => '34',
                    'epss' => '6', 'mapse' => '13', 'la_size' => '50', 'ao_root' => '31',
                    'ef_percent' => '56%', 'fs_percent' => '31%', 'lap_ee' => '12', 'pap' => '32',
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
                    'mv_velocity' => '165', 'mv_gradient' => '9', 'mitral_valve' => 'Rheumatic MS with mild MR',
                    'av_velocity' => '260', 'av_gradient' => '22', 'aortic_valve' => 'Severe AR',
                    'tv_velocity' => '200', 'tv_gradient' => '3', 'tricuspid_valve' => 'Mild TR',
                    'pv_velocity' => '100', 'pv_gradient' => '8', 'pulmonary_valve' => 'Normal - no PR',
                    'recommendations' => "Cardiology/cardiac surgery review - combined mitral stenosis and aortic regurgitation.\nPenicillin prophylaxis (RHD). Serial echo for AR progression and LV dimensions.\nSurgical intervention (MVR +/- AVR) if symptomatic or LV dilates further.",
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
        DB::table('echo_templates')->where('is_builtin', true)->where('category', 'rhd')->delete();
    }
};
