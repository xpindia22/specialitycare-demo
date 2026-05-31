<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // ALTER enum to add 'nurse'
        DB::statement("ALTER TABLE referral_sources MODIFY type ENUM('doctor','hospital','clinic','nurse','other') NOT NULL DEFAULT 'doctor'");
        // Add column for free-text when type = other
        Schema::table('referral_sources', function (Blueprint $table) {
            $table->string('type_other')->nullable()->after('type');
        });
    }
    public function down(): void {
        Schema::table('referral_sources', function (Blueprint $table) {
            $table->dropColumn('type_other');
        });
        DB::statement("ALTER TABLE referral_sources MODIFY type ENUM('doctor','hospital','clinic','other') NOT NULL DEFAULT 'doctor'");
    }
};
