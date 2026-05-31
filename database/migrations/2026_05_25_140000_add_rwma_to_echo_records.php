<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regional Wall Motion Abnormality (RWMA) fields on echo_records.
 * Seven LV wall segments + a free-text comments field. Each segment carries
 * one of: Normal | Hypokinetic | Akinetic | Dyskinetic | Aneurysmal (nullable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->string('anterior_motion', 40)->nullable()->after('pericardial_effusion');
            $table->string('anteroseptal_motion', 40)->nullable()->after('anterior_motion');
            $table->string('inferior_motion', 40)->nullable()->after('anteroseptal_motion');
            $table->string('inferoseptal_motion', 40)->nullable()->after('inferior_motion');
            $table->string('lateral_motion', 40)->nullable()->after('inferoseptal_motion');
            $table->string('posterior_motion', 40)->nullable()->after('lateral_motion');
            $table->string('apex_motion', 40)->nullable()->after('posterior_motion');
            $table->text('rwma_comments')->nullable()->after('apex_motion');
        });
    }

    public function down(): void
    {
        Schema::table('echo_records', function (Blueprint $table) {
            $table->dropColumn([
                'anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion',
                'lateral_motion','posterior_motion','apex_motion','rwma_comments',
            ]);
        });
    }
};
