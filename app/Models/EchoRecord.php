<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EchoRecord extends Model {
    protected $fillable = [
        'record_no','patient_id','encounter_id','doctor_id','record_date',
        'diagnosis','ref_by','referral_source_id','height_cm','weight_kg','bsa',
        'rv_wall_thickness','rv_dimension','tapse','ivc_size',
        'ivs','lvpw','lv_edd','lv_esd','epss','mapse',
        'la_size','ao_root','ef_percent','fs_percent','ea_ratio','lap_ee','pap',
        'mitral_valve','mva','mr_vena_contracta','mr_regurgitant_vol',
        'aortic_valve','ava','ar_vena_contracta','ar_regurgitant_vol',
        'tricuspid_valve','tr_vena_contracta','tr_regurgitant_vol','pulmonary_valve',
        'lv_diastolic_function','lv_systolic_function','ivs_motion','pericardium_status',
        'rv_longitudinal_function','rv_transverse_function','rv_paradoxical_movement',
        'ias_status','pericardial_effusion',
        'anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion',
        'lateral_motion','posterior_motion','apex_motion','rwma_comments',
        'la_clot','lv_clot',
        'mv_velocity','mv_gradient','av_velocity','av_gradient',
        'tv_velocity','tv_gradient','pv_velocity','pv_gradient',
        'impression','recommendations','echo_file','price','performed_by',
    ];
    protected $casts = ['record_date'=>'date','price'=>'decimal:2'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function referralSource() { return $this->belongsTo(ReferralSource::class); }
    public static function generateNo(): string {
        $prefix = 'ECHO/'.date('Ym').'/';
        $last = static::where('record_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->record_no,-4)+1 : 1;
        return $prefix.str_pad($num,4,'0',STR_PAD_LEFT);
    }
}
