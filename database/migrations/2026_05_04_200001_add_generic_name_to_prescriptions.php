<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('generic_name')->nullable()->after('medicine_id');
        });
        Schema::table('ipd_prescriptions', function (Blueprint $table) {
            $table->string('generic_name')->nullable()->after('drug_name');
        });
    }
    public function down(): void {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropColumn('generic_name');
        });
        Schema::table('ipd_prescriptions', function (Blueprint $table) {
            $table->dropColumn('generic_name');
        });
    }
};
