<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('patient_vitals')) {
            Schema::create('patient_vitals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
                $table->dateTime('recorded_at');
                $table->smallInteger('bp_systolic')->unsigned()->nullable();
                $table->smallInteger('bp_diastolic')->unsigned()->nullable();
                $table->smallInteger('pulse')->unsigned()->nullable();
                $table->decimal('temperature', 5, 2)->nullable();
                $table->decimal('spo2', 5, 2)->nullable();
                $table->decimal('weight', 6, 2)->nullable();
                $table->decimal('height', 6, 2)->nullable();
                $table->smallInteger('respiration')->unsigned()->nullable();
                $table->decimal('bmi', 5, 2)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unsignedBigInteger('openemr_id')->nullable()->unique('uq_openemr_id');
                $table->index('patient_id', 'idx_patient_id');
                $table->index('encounter_id', 'idx_encounter_id');
            });
        }

        if (!Schema::hasTable('patient_allergies')) {
            Schema::create('patient_allergies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->string('allergen');
                $table->string('reaction')->nullable();
                $table->enum('severity', ['mild','moderate','severe','fatal','unknown'])->default('unknown');
                $table->enum('status', ['active','inactive','confirmed','suspected'])->default('confirmed');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('openemr_id')->nullable()->unique('uq_openemr_id');
                $table->index('patient_id', 'idx_patient_id');
            });
        }

        if (!Schema::hasTable('patient_insurance')) {
            Schema::create('patient_insurance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['primary','secondary','tertiary'])->default('primary');
                $table->string('provider')->nullable();
                $table->string('plan_name')->nullable();
                $table->string('policy_number')->nullable();
                $table->string('group_number')->nullable();
                $table->string('subscriber_name')->nullable();
                $table->date('subscriber_dob')->nullable();
                $table->string('subscriber_relationship', 100)->nullable();
                $table->string('copay', 50)->nullable();
                $table->date('effective_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('openemr_id')->nullable()->unique('uq_openemr_id');
                $table->index('patient_id', 'idx_patient_id');
            });
        }

        if (!Schema::hasTable('patient_notes')) {
            Schema::create('patient_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->string('author')->nullable();
                $table->longText('body')->nullable();
                $table->dateTime('noted_at')->nullable();
                $table->timestamps();
                $table->unsignedBigInteger('openemr_id')->nullable()->unique('uq_openemr_id');
                $table->index('patient_id', 'idx_patient_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_notes');
        Schema::dropIfExists('patient_insurance');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_vitals');
    }
};
