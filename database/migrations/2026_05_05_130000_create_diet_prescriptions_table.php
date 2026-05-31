<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('diet_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('height_cm', 5, 1);
            $table->decimal('weight_kg', 5, 1);
            $table->decimal('bmi', 4, 1);
            $table->decimal('target_weight_kg', 5, 1)->nullable();
            $table->string('activity_level')->default('sedentary'); // sedentary/light/moderate/active
            $table->json('conditions');          // ['dm','htn','cad','cholesterol','uric_acid','hypothyroid','ckd']
            $table->integer('calorie_target');
            $table->text('diet_notes')->nullable();
            $table->foreignId('prescribed_by')->constrained('users');
            $table->timestamp('prescribed_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('diet_prescriptions'); }
};
