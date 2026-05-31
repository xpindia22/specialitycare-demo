<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ipd_prescriptions', function (Blueprint $table) {
            $table->foreignId('medicine_id')->nullable()->after('drug_name')
                  ->constrained('medicines')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('ipd_prescriptions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Medicine::class);
            $table->dropColumn('medicine_id');
        });
    }
};
