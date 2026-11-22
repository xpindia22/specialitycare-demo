<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OPD referral from one department / doctor to another. "Today" referrals get a token in
// the other department's queue straight away; "later" ones wait for reception to book an
// appointment or issue a token when the patient comes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->foreignId('from_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('to_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->enum('when', ['today', 'later'])->default('today');
            $table->date('preferred_date')->nullable();
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->string('reason', 255);
            $table->text('notes')->nullable();                 // clinical summary for the other doctor
            $table->enum('status', ['pending', 'queued', 'booked', 'seen', 'cancelled'])->default('pending');
            $table->foreignId('token_id')->nullable()->constrained('opd_tokens')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('to_encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'to_department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_referrals');
    }
};
