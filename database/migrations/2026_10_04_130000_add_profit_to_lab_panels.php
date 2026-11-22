<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// lab_panels.profit: the clinic's actual profit (₹) out of the panel's price.
// When a panel is ordered it expands into one ₹0 lab_order_items line per test;
// the panel's profit is snapshotted once, on the first of those lines.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_panels', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_panels', 'profit')) {
                $table->decimal('profit', 10, 2)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_panels', function (Blueprint $table) {
            $table->dropColumn('profit');
        });
    }
};
