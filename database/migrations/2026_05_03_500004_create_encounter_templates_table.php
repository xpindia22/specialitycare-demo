<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('encounter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('clinical_notes')->nullable();
            // JSON array of diagnosis strings
            $table->json('diagnoses')->nullable();
            // JSON array of {drug_name, dose, route, frequency, instructions}
            $table->json('prescription_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('encounter_templates');
    }
};
