<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HSN/SAC + GST-rate columns on items the clinic sells/buys.
 *
 * Medicines use HSN (Harmonized System of Nomenclature); service_charges
 * use SAC (Services Accounting Code). The `gst_rate_id` lets the user pick
 * a default rate per item — Sales/Purchase vouchers fall back to this when
 * a line is created from an item.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $t) {
            $t->string('hsn_code', 16)->nullable()->after('manufacturer');
            $t->unsignedBigInteger('gst_rate_id')->nullable()->after('hsn_code');
            $t->foreign('gst_rate_id')->references('id')->on('gst_rates')->nullOnDelete();
        });

        Schema::table('service_charges', function (Blueprint $t) {
            $t->string('sac_code', 16)->nullable()->after('tax_percent');
            $t->unsignedBigInteger('gst_rate_id')->nullable()->after('sac_code');
            $t->foreign('gst_rate_id')->references('id')->on('gst_rates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $t) {
            $t->dropForeign(['gst_rate_id']);
            $t->dropColumn(['hsn_code', 'gst_rate_id']);
        });
        Schema::table('service_charges', function (Blueprint $t) {
            $t->dropForeign(['gst_rate_id']);
            $t->dropColumn(['sac_code', 'gst_rate_id']);
        });
    }
};
