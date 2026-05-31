<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payees: named people the clinic pays a recurring amount to.
 *
 * Each payee gets an auto-created expense ledger so the P&L shows per-person
 * lines (e.g., "Salary - Sample Physician ₹2,16,000") instead of one rolled-up
 * "Staff Salaries" line. The auto-creation happens in the Payee model's
 * `creating` hook — see App\Models\Payee.
 *
 * Includes a new 'Salaries & Wages' (SALWAGES) sub-group under Indirect
 * Expenses for per-payee ledgers to live under. The existing EXP-SAL
 * ledger stays as a generic catch-all for un-named salary payments.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('payees', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('role', 64)->nullable();        // Doctor / Nurse / Maid / Cleaner / Other
            $t->decimal('monthly_amount', 14, 2);
            $t->unsignedBigInteger('ledger_id');                  // Dr side (auto-created per payee)
            $t->unsignedBigInteger('pay_from_ledger_id');         // Cr side (Cash / Bank)
            $t->unsignedBigInteger('hr_staff_id')->nullable();    // optional link to HR staff record
            $t->string('contact_phone', 32)->nullable();
            $t->boolean('is_active')->default(true);
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->foreign('ledger_id')->references('id')->on('account_ledgers')->restrictOnDelete();
            $t->foreign('pay_from_ledger_id')->references('id')->on('account_ledgers')->restrictOnDelete();
            $t->foreign('hr_staff_id')->references('id')->on('staff')->nullOnDelete();
            $t->index(['is_active']);
        });

        // Seed the 'Salaries & Wages' sub-group under Indirect Expenses, idempotent.
        $parentId = DB::table('account_groups')->where('code', 'IXP')->value('id');
        DB::table('account_groups')->updateOrInsert(
            ['code' => 'SALWAGES'],
            [
                'code'        => 'SALWAGES',
                'name'        => 'Salaries & Wages',
                'parent_id'   => $parentId,
                'nature'      => 'Expenses',
                'is_system'   => 1,
                'sort_order'  => 131,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('payees');
        // Don't drop the SALWAGES group on rollback — if any per-payee
        // ledgers were created under it, dropping would cascade their entries.
    }
};
