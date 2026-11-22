<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Updates the RHD/rheumatic-MS built-in templates seeded in
// 2026_05_25_120100_seed_echo_templates.php and 2026_07_25_180000_seed_echo_templates_rhd.php
// so they populate the new mitral valve detailed-grading fields (mva, mr_vena_contracta,
// mr_regurgitant_vol) and use the "<Severity> Rheumatic MS with <severity> MR" wording that
// the grading panel in the echo form now composes and parses.
return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'Rheumatic Mitral Stenosis (Severe)' => [
                'mitral_valve' => 'Severe Rheumatic MS',
                'mva' => '0.8',
            ],
            'RHD - Mitral Stenosis with Mitral Regurgitation' => [
                'mitral_valve' => 'Moderate Rheumatic MS with moderate MR',
                'mva' => '1.2', 'mr_vena_contracta' => '0.5', 'mr_regurgitant_vol' => '45',
            ],
            'RHD - Severe MS with Severe MR' => [
                'mitral_valve' => 'Severe Rheumatic MS with severe MR',
                'mva' => '0.8', 'mr_vena_contracta' => '0.8', 'mr_regurgitant_vol' => '75',
            ],
            'RHD - Multivalvular (MS + MR + TR + PAH)' => [
                'mitral_valve' => 'Moderate Rheumatic MS with moderate MR',
                'mva' => '1.2', 'mr_vena_contracta' => '0.5', 'mr_regurgitant_vol' => '45',
            ],
            'RHD - Multivalvular (MS + MR + TR + AS + AR)' => [
                'mitral_valve' => 'Moderate Rheumatic MS with moderate MR',
                'mva' => '1.2', 'mr_vena_contracta' => '0.5', 'mr_regurgitant_vol' => '45',
            ],
            'RHD - Mixed Mitral-Aortic (MS + AR)' => [
                'mitral_valve' => 'Mild Rheumatic MS with mild MR',
                'mva' => '2.0', 'mr_vena_contracta' => '0.25', 'mr_regurgitant_vol' => '20',
            ],
        ];

        foreach ($updates as $name => $overrides) {
            $row = DB::table('echo_templates')->where('name', $name)->where('is_builtin', true)->first();
            if (!$row) continue;
            $fields = array_merge(json_decode($row->fields, true) ?: [], $overrides);
            DB::table('echo_templates')->where('id', $row->id)->update([
                'fields' => json_encode($fields),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Data-only tweak to existing rows; no reliable inverse without storing
        // the prior fields snapshot, so this migration is not reversed.
    }
};
