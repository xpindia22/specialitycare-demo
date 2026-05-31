<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ipd_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->enum('note_type', ['doctor_round','nursing','observation','procedure','dietary','physiotherapy']);
            $table->dateTime('noted_at');
            $table->text('note');
            $table->foreignId('written_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('ipd_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->decimal('temperature', 5, 1)->nullable();
            $table->unsignedTinyInteger('pulse')->nullable();
            $table->unsignedTinyInteger('respiration_rate')->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->decimal('weight', 5, 1)->nullable();
            $table->decimal('blood_glucose', 6, 1)->nullable();
            $table->string('urine_output')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ipd_vitals');
        Schema::dropIfExists('ipd_notes');
    }
};
