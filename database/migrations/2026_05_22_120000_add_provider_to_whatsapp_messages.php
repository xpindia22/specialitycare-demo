<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generalise the WhatsApp message log so it's no longer Twilio-specific.
 * `provider` records which backend sent the row; `provider_message_id` holds
 * the provider's own id (Twilio SID or Meta wamid). The legacy `twilio_sid`
 * column is kept for old rows and still populated when provider=twilio.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('provider', 16)->default('twilio')->after('kind');
            $table->string('provider_message_id', 128)->nullable()->after('twilio_sid');
        });

        // Backfill: existing rows were all Twilio; mirror the SID forward.
        Schema::hasColumn('whatsapp_messages', 'twilio_sid')
            && \Illuminate\Support\Facades\DB::table('whatsapp_messages')
                ->whereNotNull('twilio_sid')
                ->update(['provider_message_id' => \Illuminate\Support\Facades\DB::raw('twilio_sid')]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_message_id']);
        });
    }
};
