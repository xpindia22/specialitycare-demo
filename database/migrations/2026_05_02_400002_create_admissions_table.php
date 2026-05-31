<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('ipd_no')->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained('beds');
            $table->foreignId('ward_id')->constrained('wards');
            $table->foreignId('admitting_doctor_id')->constrained('doctors');
            $table->enum('admission_type', ['emergency','elective','referral'])->default('elective');
            $table->dateTime('admission_date');
            $table->string('provisional_diagnosis')->nullable();
            $table->string('referred_by')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['admitted','discharged','transferred','absconded'])->default('admitted');
            $table->dateTime('discharge_date')->nullable();
            $table->enum('discharge_condition', ['improved','recovered','lama','expired','transferred'])->nullable();
            $table->text('discharge_notes')->nullable();
            $table->text('discharge_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('admissions');
    }
};
