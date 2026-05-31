<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Patient-growth features rolled out 2026-05-23:
 *   - patients.is_chronic       — flag patients on chronic meds (HTN/T2DM/post-MI
 *                                  etc.) so the morning medication-reminder cron
 *                                  knows who to ping.
 *   - appointments.meeting_url  — teleconsult join URL (Meet / Zoom / WA video).
 *   - notification_preferences  — seed rows for the three new patient-facing
 *                                  events (review_request, birthday_greeting,
 *                                  medication_reminder) so the admin Notifications
 *                                  page lists them automatically and the
 *                                  WhatsApp toggle defaults to ON.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $t) {
            $t->boolean('is_chronic')->default(false)->after('is_active');
        });

        Schema::table('appointments', function (Blueprint $t) {
            $t->string('meeting_url')->nullable()->after('notes');
        });

        // Seed the three new event rows for the admin Notifications UI.
        // Idempotent: upsert on event_key.
        $now = now();
        DB::table('notification_preferences')->upsert([
            [
                'event_key'    => 'review_request',
                'event_label'  => 'Google review request (2 days post-visit)',
                'group_label'  => 'Patient growth',
                'is_enabled'   => true,
                'patient_email' => false, 'patient_whatsapp' => true,
                'admin_email'   => false, 'admin_whatsapp'   => false,
                'created_at'   => $now, 'updated_at' => $now,
            ],
            [
                'event_key'    => 'birthday_greeting',
                'event_label'  => 'Birthday greeting',
                'group_label'  => 'Patient growth',
                'is_enabled'   => true,
                'patient_email' => false, 'patient_whatsapp' => true,
                'admin_email'   => false, 'admin_whatsapp'   => false,
                'created_at'   => $now, 'updated_at' => $now,
            ],
            [
                'event_key'    => 'medication_reminder',
                'event_label'  => 'Daily medication reminder (chronic patients)',
                'group_label'  => 'Clinical care',
                'is_enabled'   => true,
                'patient_email' => false, 'patient_whatsapp' => true,
                'admin_email'   => false, 'admin_whatsapp'   => false,
                'created_at'   => $now, 'updated_at' => $now,
            ],
        ], ['event_key'], ['event_label', 'group_label', 'is_enabled', 'patient_email', 'patient_whatsapp', 'admin_email', 'admin_whatsapp', 'updated_at']);
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $t) {
            $t->dropColumn('is_chronic');
        });
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn('meeting_url');
        });
        DB::table('notification_preferences')
            ->whereIn('event_key', ['review_request', 'birthday_greeting', 'medication_reminder'])
            ->delete();
    }
};
