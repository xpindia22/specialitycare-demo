<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('vaccine_code', 60);     // matches Vaccination::SCHEDULE keys (BCG, HEPB_BIRTH, OPV0, etc.)
            $table->string('vaccine_name', 150);
            $table->string('dose_label', 60)->nullable();   // e.g. "Birth dose", "1st dose", "Booster"
            $table->date('given_date');
            $table->string('site', 80)->nullable();         // e.g. "Right deltoid"
            $table->string('batch_number', 80)->nullable();
            $table->string('manufacturer', 120)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_code']);
        });
    }

    public function down(): void { Schema::dropIfExists('vaccinations'); }
};
