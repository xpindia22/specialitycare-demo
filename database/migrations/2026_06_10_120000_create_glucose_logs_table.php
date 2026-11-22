<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glucose_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('logged_at')->index();
            $table->string('reading_type', 20)->nullable();   // fasting, pre_meal, post_prandial, random, bedtime, hba1c
            $table->decimal('value', 6, 1)->nullable();        // mg/dL for glucose, % for HbA1c
            $table->string('medications', 255)->nullable();    // diabetes meds taken (free text)
            $table->decimal('insulin_units', 5, 1)->nullable();
            $table->string('symptoms', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glucose_logs');
    }
};
