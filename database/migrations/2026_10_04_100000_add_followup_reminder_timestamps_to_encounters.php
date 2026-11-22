<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// When the follow-up tracker last sent this encounter's follow-up reminder
// manually (per channel), so staff can see it and bulk sends skip recent ones.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            if (!Schema::hasColumn('encounters', 'followup_whatsapp_sent_at')) {
                $table->timestamp('followup_whatsapp_sent_at')->nullable()->after('follow_up_date');
            }
            if (!Schema::hasColumn('encounters', 'followup_email_sent_at')) {
                $table->timestamp('followup_email_sent_at')->nullable()->after('followup_whatsapp_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['followup_whatsapp_sent_at', 'followup_email_sent_at']);
        });
    }
};
