<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track when each bill was last sent an overdue reminder so the same
 * bill isn't re-pinged in every scheduled run. Indexed because the
 * reminder command filters on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->timestamp('last_overdue_reminded_at')->nullable()->after('notes');
            $table->index('last_overdue_reminded_at', 'bills_last_overdue_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('bills_last_overdue_idx');
            $table->dropColumn('last_overdue_reminded_at');
        });
    }
};
