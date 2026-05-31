<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->string('operation_no')->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('surgeon_id')->constrained('doctors');
            $table->string('anesthetist_name')->nullable();
            $table->date('operation_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('procedure_name');
            $table->string('icd_code')->nullable();
            $table->string('diagnosis');
            $table->enum('anesthesia_type', ['general', 'spinal', 'epidural', 'local', 'regional', 'none'])->default('general');
            $table->text('pre_op_diagnosis')->nullable();
            $table->text('post_op_diagnosis')->nullable();
            $table->text('findings')->nullable();
            $table->text('complications')->nullable();
            $table->enum('outcome', ['successful', 'complicated', 'abandoned', 'patient_expired'])->default('successful');
            $table->string('scrub_nurse')->nullable();
            $table->string('circulating_nurse')->nullable();
            $table->boolean('blood_transfused')->default(false);
            $table->tinyInteger('blood_units')->default(0);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
