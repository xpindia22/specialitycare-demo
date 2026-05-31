<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->string('diagnosis')->nullable()->after('record_date');
            $table->string('ref_by')->nullable()->after('diagnosis');
            $table->string('height_cm')->nullable()->after('ref_by');
            $table->string('weight_kg')->nullable()->after('height_cm');
            $table->string('bsa')->nullable()->after('weight_kg');
            $table->string('rv_wall_thickness')->nullable()->after('rv_dimension');
            $table->string('tapse')->nullable()->after('rv_wall_thickness');
            $table->string('ivc_size')->nullable()->after('tapse');
            $table->string('epss')->nullable()->after('lv_esd');
            $table->string('mapse')->nullable()->after('epss');
            $table->string('fs_percent')->nullable()->after('ef_percent');
            $table->string('ea_ratio')->nullable()->after('fs_percent');
            $table->string('lap_ee')->nullable()->after('ea_ratio');
            $table->string('pap')->nullable()->after('lap_ee');
            $table->string('lv_diastolic_function')->nullable();
            $table->string('lv_systolic_function')->nullable();
            $table->string('ivs_motion')->nullable();
            $table->string('pericardium_status')->nullable();
            $table->string('rv_longitudinal_function')->nullable();
            $table->string('rv_transverse_function')->nullable();
            $table->string('rv_paradoxical_movement')->nullable();
            $table->string('ias_status')->nullable();
            $table->string('pericardial_effusion')->nullable();
            $table->string('mv_velocity')->nullable();
            $table->string('mv_gradient')->nullable();
            $table->string('av_velocity')->nullable();
            $table->string('av_gradient')->nullable();
            $table->string('tv_velocity')->nullable();
            $table->string('tv_gradient')->nullable();
            $table->string('pv_velocity')->nullable();
            $table->string('pv_gradient')->nullable();
        });
    }

    public function down(): void {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->dropColumn([
                'diagnosis','ref_by','height_cm','weight_kg','bsa',
                'rv_wall_thickness','tapse','ivc_size','epss','mapse',
                'fs_percent','ea_ratio','lap_ee','pap',
                'lv_diastolic_function','lv_systolic_function','ivs_motion',
                'pericardium_status','rv_longitudinal_function','rv_transverse_function',
                'rv_paradoxical_movement','ias_status','pericardial_effusion',
                'mv_velocity','mv_gradient','av_velocity','av_gradient',
                'tv_velocity','tv_gradient','pv_velocity','pv_gradient',
            ]);
        });
    }
};
