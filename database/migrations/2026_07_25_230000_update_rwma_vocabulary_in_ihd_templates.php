<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The RWMA segment dropdowns (anterior_motion, etc.) changed their option set from
// ['Normal','Hypokinetic','Akinetic','Dyskinetic','Aneurysmal'] to
// ['Mild/Moderate/Severe Hypokinesia','Akinesia','Dyskinesia','Aneurysmal'] as part of
// switching the Regional Wall Motion card to a checkbox-driven Lesion Builder (segments
// with no finding are now simply unchecked/blank instead of stored as the literal string
// "Normal"). Existing built-in IHD templates still carry the old option text, which no
// longer matches any <option value="..."> in the new dropdown — remap them so applying
// these templates continues to select a real option instead of silently landing on blank.
return new class extends Migration
{
    private array $segmentKeys = [
        'anterior_motion', 'anteroseptal_motion', 'inferior_motion', 'inferoseptal_motion',
        'lateral_motion', 'posterior_motion', 'apex_motion',
    ];

    private array $valueMap = [
        'Normal' => '',
        'Hypokinetic' => 'Moderate Hypokinesia',
        'Akinetic' => 'Akinesia',
        'Dyskinetic' => 'Dyskinesia',
        'Aneurysmal' => 'Aneurysmal',
    ];

    public function up(): void
    {
        $rows = DB::table('echo_templates')->where('is_builtin', true)->get();
        foreach ($rows as $row) {
            $fields = json_decode($row->fields, true) ?: [];
            $changed = false;
            foreach ($this->segmentKeys as $key) {
                if (array_key_exists($key, $fields) && array_key_exists($fields[$key], $this->valueMap)) {
                    $fields[$key] = $this->valueMap[$fields[$key]];
                    $changed = true;
                }
            }
            if ($changed) {
                DB::table('echo_templates')->where('id', $row->id)->update([
                    'fields' => json_encode($fields),
                    'updated_at' => now(),
                ]);
            }
        }

        // This template's name and RWMA comments already describe an apical LV mural
        // thrombus — populate the new structured lv_clot field to match.
        $row = DB::table('echo_templates')->where('name', 'Old Anterior MI with Apical Aneurysm + LV Thrombus')->where('is_builtin', true)->first();
        if ($row) {
            $fields = json_decode($row->fields, true) ?: [];
            $fields['lv_clot'] = '1.8 cm, apical, non-mobile';
            DB::table('echo_templates')->where('id', $row->id)->update([
                'fields' => json_encode($fields),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Data-only vocabulary remap; no reliable inverse without a prior snapshot.
    }
};
