<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase-2 hook: prescription_to_patient is the event that fires when
 * a prescription should be delivered to the patient (PDF email +
 * WhatsApp link). Distinct from prescription_created (admin audit
 * email), so the owner can independently turn patient delivery on
 * once they've reviewed the PDF format and content.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('notification_preferences')->insertOrIgnore([
            'event_key'        => 'prescription_to_patient',
            'event_label'      => 'Prescription delivered to patient (PDF email + WA link)',
            'group_label'      => 'Clinical',
            'is_enabled'       => true,
            'patient_email'    => false,
            'patient_whatsapp' => false,
            'admin_email'      => true,
            'admin_whatsapp'   => false,
            'note'             => 'Sends after prescription_created. Patient channels OFF by default — flip on when you are happy with the PDF format.',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('notification_preferences')->where('event_key', 'prescription_to_patient')->delete();
    }
};
