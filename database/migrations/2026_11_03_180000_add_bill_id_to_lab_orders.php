<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            // Links a lab order to the Bill that already settled it. For uploaded
            // reports this is the auto-created "already paid" bill, so the billing
            // screen can skip these orders and never double-bill them.
            if (!Schema::hasColumn('lab_orders', 'bill_id')) {
                $table->unsignedBigInteger('bill_id')->nullable()->after('encounter_id');
                $table->index('bill_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            if (Schema::hasColumn('lab_orders', 'bill_id')) {
                $table->dropIndex(['bill_id']);
                $table->dropColumn('bill_id');
            }
        });
    }
};
