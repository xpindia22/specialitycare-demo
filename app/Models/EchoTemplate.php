<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EchoTemplate extends Model
{
    protected $fillable = ['name', 'category', 'description', 'fields', 'is_builtin', 'created_by'];

    protected $casts = [
        'fields'     => 'array',
        'is_builtin' => 'boolean',
    ];

    public const CATEGORIES = [
        'normal'    => 'Normal',
        'ihd'       => 'IHD / CAD',
        'lvh'       => 'LVH / Hypertension',
        'dcm'       => 'LV Dysfunction',
        'valvular'  => 'Valvular',
        'rv_pah'    => 'RV / Pulmonary HTN',
        'congenital'=> 'Congenital',
        'other'     => 'Other',
    ];

    /**
     * The echo-form field keys that a template can populate.
     * Mirrors EchoRecord::$fillable minus identity columns (record_no, patient_id,
     * encounter_id, doctor_id, record_date, performed_by, echo_file, price).
     */
    public const FIELD_KEYS = [
        'diagnosis', 'ref_by', 'height_cm', 'weight_kg', 'bsa',
        'rv_wall_thickness', 'rv_dimension', 'tapse', 'ivc_size',
        'ivs', 'lvpw', 'lv_edd', 'lv_esd', 'epss', 'mapse',
        'la_size', 'ao_root', 'ef_percent', 'fs_percent', 'ea_ratio', 'lap_ee', 'pap',
        'mitral_valve', 'aortic_valve', 'tricuspid_valve', 'pulmonary_valve',
        'lv_diastolic_function', 'lv_systolic_function', 'ivs_motion', 'pericardium_status',
        'rv_longitudinal_function', 'rv_transverse_function', 'rv_paradoxical_movement',
        'ias_status', 'pericardial_effusion',
        'anterior_motion', 'anteroseptal_motion', 'inferior_motion', 'inferoseptal_motion',
        'lateral_motion', 'posterior_motion', 'apex_motion', 'rwma_comments',
        'mv_velocity', 'mv_gradient', 'av_velocity', 'av_gradient',
        'tv_velocity', 'tv_gradient', 'pv_velocity', 'pv_gradient',
        'impression', 'recommendations',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
