<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the "2 days before" email reminder separately from the 1-day one
 * (reminder_email_sent_at), so an appointment can get both without the
 * dedup blocking the second.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->timestamp('reminder_email_2d_at')->nullable()->after('reminder_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn('reminder_email_2d_at');
        });
    }
};
