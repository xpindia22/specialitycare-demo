<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payees', function (Blueprint $table) {
            // Mirror of hr_staff_id but pointing at the doctors table.
            // Used by HR payroll to find the per-doctor expense ledger
            // when auto-posting a Payment voucher on markPaid.
            $table->foreignId('hr_doctor_id')->nullable()->after('hr_staff_id')
                ->constrained('doctors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hr_doctor_id');
        });
    }
};
