<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecg_records', function (Blueprint $table) {
            if (!Schema::hasColumn('ecg_records', 'referral_source_id')) {
                $table->foreignId('referral_source_id')->nullable()->after('ref_by')
                    ->constrained('referral_sources')->nullOnDelete();
            }
        });
        Schema::table('echo_records', function (Blueprint $table) {
            if (!Schema::hasColumn('echo_records', 'referral_source_id')) {
                $table->foreignId('referral_source_id')->nullable()->after('ref_by')
                    ->constrained('referral_sources')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecg_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_source_id');
        });
        Schema::table('echo_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_source_id');
        });
    }
};
