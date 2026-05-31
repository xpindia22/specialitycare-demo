<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Per-event toggle matrix: who gets notified (patient / admin) on which
 * channel (email / WhatsApp) when each domain event fires. Observers and
 * notifiers consult NotificationPreferenceService::shouldSend() before
 * sending anything.
 *
 * Seed defaults are CONSERVATIVE — admin email ON across the board (free,
 * useful for audit), but every patient-facing channel and every WhatsApp
 * channel starts OFF for events that don't already send today. The two
 * exceptions are the events that ARE sending in production right now
 * (registration welcome + appointment confirm) — those keep their
 * current ON state so retrofitting the observers doesn't change behavior.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $t) {
            $t->id();
            $t->string('event_key', 64)->unique();
            $t->string('event_label', 160);
            $t->string('group_label', 60)->nullable();   // UI grouping: OPD / IPD / Billing / Reports / Patient lifecycle
            $t->boolean('is_enabled')->default(true);     // Master toggle per event
            $t->boolean('patient_email')->default(false);
            $t->boolean('patient_whatsapp')->default(false);
            $t->boolean('admin_email')->default(true);
            $t->boolean('admin_whatsapp')->default(false);
            $t->text('note')->nullable();                 // Hint shown in the UI
            $t->timestamps();
        });

        // Seed every event the codebase fires (and a handful of future ones
        // we'll wire up in later phases). Defaults preserve current behavior
        // for the events that are live today.
        $now = now();
        $rows = [
            // Patient lifecycle
            ['patient_registered',    'Patient registered (new pt walks in)',           'Patient',  true,  0,1, 1,0, 'Welcome WhatsApp to patient is live today; admin email is the audit trail.'],
            ['patient_updated',       'Patient record edited',                          'Patient',  true,  0,0, 1,0, null],
            ['patient_deleted',       'Patient record deleted',                         'Patient',  true,  0,0, 1,0, null],

            // OPD / Appointments
            ['appointment_created',   'New appointment / revisit booked',               'OPD',      true,  0,1, 1,1, 'Patient gets confirmation WA; admin gets the parallel WA + email.'],
            ['appointment_updated',   'Appointment changed (time, doctor, status)',     'OPD',      true,  0,0, 1,0, null],
            ['appointment_cancelled', 'Appointment cancelled / deleted',                'OPD',      true,  0,0, 1,0, null],
            ['encounter_created',     'New visit / encounter opened',                   'OPD',      true,  0,0, 1,0, null],
            ['encounter_updated',     'Encounter edited',                               'OPD',      true,  0,0, 1,0, null],

            // Clinical actions
            ['prescription_created',  'Prescription created',                           'Clinical', true,  0,0, 1,0, 'Phase-2 wires up patient PDF email + WhatsApp link.'],
            ['prescription_updated',  'Prescription edited',                            'Clinical', true,  0,0, 1,0, null],
            ['prescription_deleted',  'Prescription deleted',                           'Clinical', true,  0,0, 1,0, null],
            ['diagnosis_created',     'Diagnosis recorded',                             'Clinical', true,  0,0, 1,0, null],

            // Lab / Imaging
            ['lab_order_created',     'Lab tests ordered',                              'Lab',      true,  0,0, 1,0, null],
            ['lab_result_ready',      'Lab result ready (PDF)',                         'Lab',      true,  0,0, 1,0, 'Phase-2: patient PDF email + WA link when results signed off.'],
            ['ecg_report_ready',      'ECG report ready (PDF)',                         'Reports',  true,  0,0, 1,0, 'Phase-2.'],
            ['echo_report_ready',     'Echo report ready (PDF)',                        'Reports',  true,  0,0, 1,0, 'Phase-2.'],

            // Billing
            ['bill_created',          'New bill generated',                             'Billing',  true,  0,0, 1,0, null],
            ['bill_updated',          'Bill amount / status changed',                   'Billing',  true,  0,0, 1,0, null],
            ['bill_paid',             'Bill paid (confirmation receipt)',               'Billing',  true,  0,0, 1,0, 'Phase-2.'],
            ['bill_overdue',          'Bill overdue reminder',                          'Billing',  false, 0,0, 0,0, 'Disabled in 2026-05 after openemr import flagged false unpaid rows. Re-enable carefully.'],

            // IPD
            ['admission_created',     'New IPD admission',                              'IPD',      true,  0,0, 1,0, null],
            ['admission_updated',     'Admission status changed',                       'IPD',      true,  0,0, 1,0, null],
            ['discharge_summary_ready','Discharge summary signed (PDF)',                'IPD',      true,  0,0, 1,0, 'Phase-2.'],
        ];

        $seedRows = [];
        foreach ($rows as $r) {
            [$key,$label,$group,$enabled,$pe,$pw,$ae,$aw,$note] = $r;
            $seedRows[] = [
                'event_key'        => $key,
                'event_label'      => $label,
                'group_label'      => $group,
                'is_enabled'       => $enabled,
                'patient_email'    => $pe,
                'patient_whatsapp' => $pw,
                'admin_email'      => $ae,
                'admin_whatsapp'   => $aw,
                'note'             => $note,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }
        DB::table('notification_preferences')->insert($seedRows);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
