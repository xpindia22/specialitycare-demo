<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core Accounts & GST module schema.
 *
 * Double-entry bookkeeping: groups → ledgers → vouchers → entries.
 * Σdr == Σcr enforced at the service layer (DB only validates non-null amounts).
 *
 * Tables created:
 *   - account_groups            Primary + sub groups (Tally-style)
 *   - account_ledgers           Individual ledgers under groups
 *   - voucher_types             Payment, Receipt, Journal, Sales, Purchase, Contra, CN, DN
 *   - vouchers                  Voucher header (one per transaction)
 *   - voucher_entries           Line items (the double-entry rows)
 *   - gst_rates                 0/5/12/18/28 + custom
 *   - recurring_expenses        Monthly auto-post jobs (rent, salary, etc.)
 *
 * HSN/SAC fields are added to medicines and service_charges in a separate
 * migration so this one stays focused on net-new structures.
 */
return new class extends Migration {

    public function up(): void
    {
        // ── Account groups ────────────────────────────────────────────────
        // Self-referencing parent_id for sub-groups (e.g., "Duties & Taxes"
        // under "Current Liabilities"). `nature` controls which side of the
        // P&L / Balance Sheet the group lives on.
        Schema::create('account_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 32)->unique();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->enum('nature', ['Assets', 'Liabilities', 'Income', 'Expenses']);
            $t->boolean('affects_gross_profit')->default(false); // Direct income/expense
            $t->boolean('is_system')->default(false);            // can't be deleted
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();

            $t->foreign('parent_id')->references('id')->on('account_groups')->nullOnDelete();
            $t->index(['nature']);
        });

        // ── Account ledgers ───────────────────────────────────────────────
        // Party ledgers (Sundry Debtors / Creditors children) carry GSTIN +
        // state_code so Sales/Purchase vouchers can auto-split CGST+SGST vs IGST.
        Schema::create('account_ledgers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 64)->unique();
            $t->unsignedBigInteger('group_id');
            $t->decimal('opening_balance', 14, 2)->default(0);
            $t->enum('opening_balance_dr_cr', ['Dr', 'Cr'])->default('Dr');
            $t->string('gstin', 20)->nullable();
            $t->string('state_code', 4)->nullable();                          // GST state code (e.g., 29 = Karnataka)
            $t->enum('party_type', ['Customer', 'Supplier', 'Employee', 'Bank', 'Cash', 'Tax', 'Other'])->nullable();
            $t->string('contact_phone', 32)->nullable();
            $t->string('contact_email', 191)->nullable();
            $t->text('address')->nullable();
            $t->boolean('is_system')->default(false);
            $t->boolean('is_active')->default(true);
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->foreign('group_id')->references('id')->on('account_groups')->restrictOnDelete();
            $t->index(['group_id', 'is_active']);
            $t->index(['party_type']);
        });

        // ── Voucher types ─────────────────────────────────────────────────
        // Seeded once; user can add custom types via UI later if needed.
        Schema::create('voucher_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 16)->unique();          // PAY, RCT, JV, SAL, PUR, CTR, CN, DN
            $t->string('numbering_prefix', 16);        // shown in voucher_no
            $t->enum('affects', ['Cash', 'Bank', 'Stock', 'Both', 'None'])->default('None');
            $t->boolean('is_system')->default(true);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        // ── Vouchers (header) ─────────────────────────────────────────────
        // source_type/source_id make auto-posted vouchers idempotent
        // (a bill that's already produced a Sales voucher won't double-post).
        Schema::create('vouchers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('voucher_type_id');
            $t->string('voucher_no', 32);
            $t->unsignedSmallInteger('fy_year');           // 2026 = FY 2026-27
            $t->date('voucher_date');
            $t->unsignedBigInteger('party_ledger_id')->nullable();
            $t->string('reference_no', 64)->nullable();    // external ref (e.g., supplier invoice no)
            $t->date('reference_date')->nullable();
            $t->text('narration')->nullable();
            $t->decimal('total_amount', 14, 2)->default(0); // Σdr (== Σcr); for display + reporting
            $t->string('source_type', 64)->nullable();     // e.g., App\Models\Bill
            $t->unsignedBigInteger('source_id')->nullable();
            $t->enum('status', ['Draft', 'Posted', 'Cancelled'])->default('Posted');
            $t->unsignedBigInteger('created_by');
            $t->timestamps();

            $t->foreign('voucher_type_id')->references('id')->on('voucher_types')->restrictOnDelete();
            $t->foreign('party_ledger_id')->references('id')->on('account_ledgers')->nullOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['voucher_type_id', 'fy_year', 'voucher_no']);
            $t->index(['voucher_date']);
            $t->index(['source_type', 'source_id']);
        });

        // ── Voucher entries (line items) ──────────────────────────────────
        // Exactly one of dr_amount/cr_amount > 0 per row. GST split fields
        // are populated on Sales/Purchase entries that carry tax.
        Schema::create('voucher_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('voucher_id');
            $t->unsignedBigInteger('ledger_id');
            $t->decimal('dr_amount', 14, 2)->default(0);
            $t->decimal('cr_amount', 14, 2)->default(0);
            $t->text('narration')->nullable();

            // GST breakdown (populated only on Sales/Purchase taxable rows)
            $t->string('hsn_sac', 16)->nullable();
            $t->decimal('taxable_amount', 14, 2)->nullable();
            $t->decimal('gst_rate', 5, 2)->nullable();        // total rate, e.g., 18.00
            $t->decimal('cgst_amount', 14, 2)->nullable();
            $t->decimal('sgst_amount', 14, 2)->nullable();
            $t->decimal('igst_amount', 14, 2)->nullable();
            $t->decimal('cess_amount', 14, 2)->nullable();

            $t->unsignedInteger('line_no')->default(0);
            $t->timestamps();

            $t->foreign('voucher_id')->references('id')->on('vouchers')->cascadeOnDelete();
            $t->foreign('ledger_id')->references('id')->on('account_ledgers')->restrictOnDelete();
            $t->index(['ledger_id']);
            $t->index(['voucher_id', 'line_no']);
        });

        // ── GST rate master ──────────────────────────────────────────────
        Schema::create('gst_rates', function (Blueprint $t) {
            $t->id();
            $t->string('name', 32);              // e.g., "GST 18%"
            $t->decimal('rate', 5, 2);           // 18.00 (total — split equally to CGST+SGST intra-state)
            $t->decimal('cess_rate', 5, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['rate', 'cess_rate']);
        });

        // ── Recurring expenses ───────────────────────────────────────────
        // Daily cron walks this table; on `day_of_month` posts a Payment
        // voucher (Dr expense_ledger, Cr counter_ledger) once that month.
        Schema::create('recurring_expenses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('expense_ledger_id');     // Dr side (e.g., Rent)
            $t->unsignedBigInteger('counter_ledger_id');     // Cr side (e.g., Cash / Bank)
            $t->decimal('amount', 14, 2);
            $t->unsignedTinyInteger('day_of_month')->default(1); // 1..28
            $t->text('narration')->nullable();
            $t->boolean('is_active')->default(true);
            $t->date('last_posted_on')->nullable();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->timestamps();

            $t->foreign('expense_ledger_id')->references('id')->on('account_ledgers')->restrictOnDelete();
            $t->foreign('counter_ledger_id')->references('id')->on('account_ledgers')->restrictOnDelete();
            $t->index(['is_active', 'day_of_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
        Schema::dropIfExists('gst_rates');
        Schema::dropIfExists('voucher_entries');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('voucher_types');
        Schema::dropIfExists('account_ledgers');
        Schema::dropIfExists('account_groups');
    }
};
