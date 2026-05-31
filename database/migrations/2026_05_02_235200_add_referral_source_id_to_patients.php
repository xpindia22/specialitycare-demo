<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('patients', 'referral_source_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->foreignId('referral_source_id')->nullable()->constrained('referral_sources')->nullOnDelete()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['referral_source_id']);
            $table->dropColumn('referral_source_id');
        });
    }
};
