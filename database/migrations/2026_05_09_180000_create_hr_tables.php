<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present','half_day','absent','paid_leave','unpaid_leave','week_off','holiday'])->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('hours', 5, 2)->nullable();
            $table->string('notes', 255)->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->timestamps();
            $table->unique(['staff_id','date']);
            $table->index('date');
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['overtime','bonus','allowance','advance','deduction']);
            $table->decimal('amount', 10, 2);
            $table->string('notes', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['staff_id','date']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('days_in_month');
            $table->decimal('days_present', 5, 2)->default(0); // present + half_day*0.5
            $table->decimal('days_paid_leave', 5, 2)->default(0);
            $table->decimal('days_off', 5, 2)->default(0);     // week_off + holiday
            $table->decimal('days_unpaid_leave', 5, 2)->default(0);
            $table->decimal('days_absent', 5, 2)->default(0);
            $table->decimal('days_payable', 5, 2)->default(0); // present + paid_leave + off
            $table->decimal('base_salary', 10, 2);
            $table->decimal('base_pay_prorated', 10, 2);
            $table->decimal('credits', 10, 2)->default(0);     // overtime + bonus + allowance
            $table->decimal('debits', 10, 2)->default(0);      // advance + deduction
            $table->decimal('net_payable', 10, 2);
            $table->enum('status', ['pending','paid'])->default('pending');
            $table->string('payment_method', 30)->nullable();  // cash, bank_transfer, upi, cheque
            $table->date('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['staff_id','year','month']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('attendances');
    }
};
