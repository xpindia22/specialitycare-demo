<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * lab_result_ready now actually fires (LabController::updateStatus →
 * PatientNotifier::sendLabResult on first transition to Completed) — the
 * seed migration's "Phase-2" note is stale. Text-only update; does not
 * touch is_enabled/patient_email/patient_whatsapp so it can't change
 * behavior on any environment that already has this row.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('notification_preferences')
            ->where('event_key', 'lab_result_ready')
            ->update(['note' => 'Fires when a lab order is marked Completed. Manual re-send (any time) is available from the lab order page via Email/WhatsApp buttons, independent of this toggle.']);
    }

    public function down(): void
    {
        DB::table('notification_preferences')
            ->where('event_key', 'lab_result_ready')
            ->update(['note' => 'Phase-2: patient PDF email + WA link when results signed off.']);
    }
};
