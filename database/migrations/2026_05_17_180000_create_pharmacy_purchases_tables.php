<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_purchases', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('supplier_ledger_id');
            $t->string('invoice_no', 64)->nullable();
            $t->date('invoice_date');
            $t->string('payment_mode', 16)->default('Credit'); // Credit | Cash | Bank
            $t->decimal('subtotal',         12, 2)->default(0);
            $t->decimal('cgst_amount',      12, 2)->default(0);
            $t->decimal('sgst_amount',      12, 2)->default(0);
            $t->decimal('igst_amount',      12, 2)->default(0);
            $t->decimal('round_off',         8, 2)->default(0);
            $t->decimal('total_amount',     12, 2)->default(0);
            $t->string('narration', 500)->nullable();
            $t->unsignedBigInteger('voucher_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index('supplier_ledger_id');
            $t->index('invoice_date');
            $t->foreign('supplier_ledger_id')->references('id')->on('account_ledgers');
            $t->foreign('voucher_id')->references('id')->on('vouchers')->nullOnDelete();
        });

        Schema::create('pharmacy_purchase_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_id');
            $t->unsignedBigInteger('medicine_id');
            $t->string('batch_number', 64)->nullable();
            $t->date('expiry_date')->nullable();
            $t->decimal('quantity',     12, 2);
            $t->decimal('unit_cost',    12, 2);
            $t->string('hsn_code', 16)->nullable();
            $t->decimal('gst_rate', 5, 2)->default(0);
            $t->decimal('taxable_amount', 12, 2)->default(0);
            $t->decimal('cgst_amount',    12, 2)->default(0);
            $t->decimal('sgst_amount',    12, 2)->default(0);
            $t->decimal('igst_amount',    12, 2)->default(0);
            $t->decimal('line_total',     12, 2)->default(0);
            $t->unsignedBigInteger('stock_id')->nullable();
            $t->timestamps();
            $t->index('purchase_id');
            $t->index('medicine_id');
            $t->foreign('purchase_id')->references('id')->on('pharmacy_purchases')->cascadeOnDelete();
            $t->foreign('medicine_id')->references('id')->on('medicines');
            $t->foreign('stock_id')->references('id')->on('pharmacy_stock')->nullOnDelete();
        });

        Schema::table('pharmacy_stock', function (Blueprint $t) {
            $t->unsignedBigInteger('purchase_item_id')->nullable()->after('supplier');
            $t->foreign('purchase_item_id')->references('id')->on('pharmacy_purchase_items')->nullOnDelete();
        });

        // Soft-default HSN/GST on existing medicines so purchases can be recorded
        // immediately. Catch-all pharma HSN is 30049099; GST 0% is rate id=1.
        // The user can correct per-medicine later via the "fix missing" screen.
        $defaultGstId = DB::table('gst_rates')->where('rate', 0)->value('id') ?? 1;
        DB::table('medicines')->whereNull('hsn_code')->orWhere('hsn_code', '')->update(['hsn_code' => '30049099']);
        DB::table('medicines')->whereNull('gst_rate_id')->update(['gst_rate_id' => $defaultGstId]);
    }

    public function down(): void
    {
        Schema::table('pharmacy_stock', function (Blueprint $t) {
            $t->dropForeign(['purchase_item_id']);
            $t->dropColumn('purchase_item_id');
        });
        Schema::dropIfExists('pharmacy_purchase_items');
        Schema::dropIfExists('pharmacy_purchases');
    }
};
