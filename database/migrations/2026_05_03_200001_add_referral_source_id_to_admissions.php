<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('admissions', function (Blueprint $table) {
            $table->foreignId('referral_source_id')->nullable()->after('referred_by')
                  ->constrained('referral_sources')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ReferralSource::class);
        });
    }
};
