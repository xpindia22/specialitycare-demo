<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('notification_preferences')->upsert([[
            'event_key'    => 'refill_reminder',
            'event_label'  => 'Prescription refill due (3 days before running out)',
            'group_label'  => 'Clinical care',
            'is_enabled'   => true,
            'patient_email' => false, 'patient_whatsapp' => true,
            'admin_email'   => false, 'admin_whatsapp'   => false,
            'created_at'   => $now, 'updated_at' => $now,
        ]], ['event_key'], ['event_label', 'group_label', 'is_enabled', 'patient_email', 'patient_whatsapp', 'admin_email', 'admin_whatsapp', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('notification_preferences')->where('event_key', 'refill_reminder')->delete();
    }
};
