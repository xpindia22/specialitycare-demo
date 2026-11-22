<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// lab_tests.profit: the clinic's actual profit (₹) out of the test's price.
// lab_order_items.profit: snapshot of that profit when the test was ordered, so
// later profit edits don't rewrite past revenue. NULL on items ordered before
// this existed; reports fall back to the test's current profit for those.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_tests', 'profit')) {
                $table->decimal('profit', 10, 2)->nullable()->after('price');
            }
        });
        Schema::table('lab_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_order_items', 'profit')) {
                $table->decimal('profit', 10, 2)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropColumn('profit');
        });
        Schema::table('lab_order_items', function (Blueprint $table) {
            $table->dropColumn('profit');
        });
    }
};
