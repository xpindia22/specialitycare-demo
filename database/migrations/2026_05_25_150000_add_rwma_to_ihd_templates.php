<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the new per-segment WMA values into the IHD built-in templates
 * so that applying a template populates the new Regional Wall Motion card.
 * Idempotent — re-running just re-asserts the same values.
 */
return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            // 1. CAD with Preserved LV (no WMA — all normal)
            'CAD with Preserved LV Function (post-revascularization)' => [
                'anterior_motion'     => 'Normal',
                'anteroseptal_motion' => 'Normal',
                'inferior_motion'     => 'Normal',
                'inferoseptal_motion' => 'Normal',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Normal',
                'apex_motion'         => 'Normal',
                'rwma_comments'       => '',
            ],
            // 2. Acute / Recent Anterior MI — LAD distal: anterior, anteroseptal, apex akinetic
            'Acute / Recent Anterior MI (LAD territory)' => [
                'anterior_motion'     => 'Akinetic',
                'anteroseptal_motion' => 'Akinetic',
                'inferior_motion'     => 'Normal',
                'inferoseptal_motion' => 'Normal',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Normal',
                'apex_motion'         => 'Akinetic',
                'rwma_comments'       => 'Compensatory hyperkinesia of basal inferior and posterior walls. No LV thrombus seen at the time of study.',
            ],
            // 3. Old Anterior MI (healed) — same territory, hypokinetic (scar)
            'Old Anterior MI (healed)' => [
                'anterior_motion'     => 'Akinetic',
                'anteroseptal_motion' => 'Akinetic',
                'inferior_motion'     => 'Normal',
                'inferoseptal_motion' => 'Normal',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Normal',
                'apex_motion'         => 'Akinetic',
                'rwma_comments'       => 'Thinned anterior wall and apex consistent with prior infarction / scar. No aneurysm, no LV thrombus.',
            ],
            // 4. Old Anterior MI with Apical Aneurysm + LV Thrombus
            'Old Anterior MI with Apical Aneurysm + LV Thrombus' => [
                'anterior_motion'     => 'Akinetic',
                'anteroseptal_motion' => 'Akinetic',
                'inferior_motion'     => 'Normal',
                'inferoseptal_motion' => 'Normal',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Normal',
                'apex_motion'         => 'Aneurysmal',
                'rwma_comments'       => 'Discrete apical aneurysm with echogenic mass adherent to the apical endocardium — consistent with LV mural thrombus. High embolic risk.',
            ],
            // 5. Acute / Recent Inferior MI (RCA) — inferior + inferoseptal akinetic
            'Acute / Recent Inferior MI (RCA territory)' => [
                'anterior_motion'     => 'Normal',
                'anteroseptal_motion' => 'Normal',
                'inferior_motion'     => 'Akinetic',
                'inferoseptal_motion' => 'Akinetic',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Hypokinetic',
                'apex_motion'         => 'Normal',
                'rwma_comments'       => 'Compensatory hyperkinesia of anterior wall. Posteromedial papillary muscle ischemia → mild functional MR.',
            ],
            // 6. Old Inferior MI (healed) — hypokinetic scar
            'Old Inferior MI (healed)' => [
                'anterior_motion'     => 'Normal',
                'anteroseptal_motion' => 'Normal',
                'inferior_motion'     => 'Hypokinetic',
                'inferoseptal_motion' => 'Hypokinetic',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Hypokinetic',
                'apex_motion'         => 'Normal',
                'rwma_comments'       => 'Thinned inferior wall consistent with prior infarction / scar.',
            ],
            // 7. Inferior MI with RV Involvement
            'Inferior MI with RV Involvement' => [
                'anterior_motion'     => 'Normal',
                'anteroseptal_motion' => 'Normal',
                'inferior_motion'     => 'Akinetic',
                'inferoseptal_motion' => 'Akinetic',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Hypokinetic',
                'apex_motion'         => 'Normal',
                'rwma_comments'       => 'RV free-wall hypokinesia consistent with RV infarction. Paradoxical septal motion. Dilated IVC.',
            ],
            // 8. Extensive Anterior + Inferior MI
            'Extensive Anterior + Inferior MI (severe LV dysfunction)' => [
                'anterior_motion'     => 'Akinetic',
                'anteroseptal_motion' => 'Akinetic',
                'inferior_motion'     => 'Akinetic',
                'inferoseptal_motion' => 'Akinetic',
                'lateral_motion'      => 'Hypokinetic',
                'posterior_motion'    => 'Hypokinetic',
                'apex_motion'         => 'Akinetic',
                'rwma_comments'       => 'Extensive multi-territory wall motion abnormalities. Tethered mitral leaflets — moderate functional MR. No discrete aneurysm, no thrombus seen at time of study.',
            ],
            // 9. Ischemic Cardiomyopathy — global hypokinesia
            'Ischemic Cardiomyopathy (multivessel CAD, global hypokinesia)' => [
                'anterior_motion'     => 'Hypokinetic',
                'anteroseptal_motion' => 'Hypokinetic',
                'inferior_motion'     => 'Hypokinetic',
                'inferoseptal_motion' => 'Hypokinetic',
                'lateral_motion'      => 'Hypokinetic',
                'posterior_motion'    => 'Hypokinetic',
                'apex_motion'         => 'Hypokinetic',
                'rwma_comments'       => 'Global LV hypokinesia, worse in territories of prior infarction. Tethered mitral leaflets → moderate functional MR.',
            ],
            // 10. Old Anterior MI with Concentric LVH (HTN + CAD)
            'Old Anterior MI with Concentric LVH (HTN + CAD)' => [
                'anterior_motion'     => 'Akinetic',
                'anteroseptal_motion' => 'Akinetic',
                'inferior_motion'     => 'Normal',
                'inferoseptal_motion' => 'Normal',
                'lateral_motion'      => 'Normal',
                'posterior_motion'    => 'Normal',
                'apex_motion'         => 'Akinetic',
                'rwma_comments'       => 'Thinned anterior wall and apex (old infarct) against a background of concentric thickening of posterior / lateral walls (hypertensive LVH).',
            ],
        ];

        foreach ($updates as $name => $additions) {
            $row = DB::table('echo_templates')
                ->where('name', $name)
                ->where('is_builtin', true)
                ->first();
            if (!$row) continue;
            $fields = json_decode($row->fields, true) ?: [];
            $fields = array_merge($fields, $additions);
            DB::table('echo_templates')->where('id', $row->id)->update([
                'fields'     => json_encode($fields),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Strip the WMA keys back out of the IHD templates.
        $keys = ['anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion',
                 'lateral_motion','posterior_motion','apex_motion','rwma_comments'];
        DB::table('echo_templates')->where('category','ihd')->orderBy('id')->each(function ($row) use ($keys) {
            $fields = json_decode($row->fields, true) ?: [];
            foreach ($keys as $k) unset($fields[$k]);
            DB::table('echo_templates')->where('id', $row->id)->update([
                'fields'     => json_encode($fields),
                'updated_at' => now(),
            ]);
        });
    }
};
