<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ipd_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->string('drug_name');
            $table->string('dose');
            $table->string('route');                          // oral, IV, IM, SC, topical, etc.
            $table->string('frequency');                      // OD, BD, TDS, QID, SOS, stat, etc.
            $table->json('schedule_times')->nullable();       // ["06:00","14:00","22:00"]
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('prescribed_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('ipd_med_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('ipd_prescriptions')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->date('log_date');
            $table->time('scheduled_time');
            $table->enum('status', ['given','missed','held','refused'])->default('given');
            $table->time('actual_time')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ipd_med_logs');
        Schema::dropIfExists('ipd_prescriptions');
    }
};
