<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->change();
            $table->foreignId('doctor_id')->nullable()->after('staff_id')
                ->constrained('doctors')->cascadeOnDelete();
            $table->unique(['doctor_id', 'year', 'month']);
        });

        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->change();
            $table->foreignId('doctor_id')->nullable()->after('staff_id')
                ->constrained('doctors')->cascadeOnDelete();
            $table->index(['doctor_id', 'date']);
        });

        Schema::table('doctors', function (Blueprint $table) {
            // Monthly base salary (separate from consultation_fee, which is
            // per-consultation). Used by the HR payroll engine.
            $table->decimal('salary', 12, 2)->default(0)->after('consultation_fee');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('salary');
        });
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropIndex(['doctor_id', 'date']);
            $table->dropConstrainedForeignId('doctor_id');
            $table->foreignId('staff_id')->nullable(false)->change();
        });
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropUnique(['doctor_id', 'year', 'month']);
            $table->dropConstrainedForeignId('doctor_id');
            $table->foreignId('staff_id')->nullable(false)->change();
        });
    }
};
