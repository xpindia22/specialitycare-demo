<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;

class AppointmentObserver
{
    public function created(Appointment $a): void
    {
        $a->loadMissing(['patient', 'doctor']);
        $prefs = app(NotificationPreferenceService::class);

        if ($prefs->shouldSend('appointment_created', 'admin', 'email')) {
            AdminNotifier::notify(
                "New appointment — " . optional($a->patient)->full_name,
                AdminNotifier::fmt(array_merge([
                    "New appointment booked.",
                    "Patient: " . ($a->patient->full_name ?? '—') . " (" . ($a->patient->jhc_id ?? '—') . ")",
                    "Doctor: " . ($a->doctor->full_name ?? '—'),
                    "When: " . optional($a->appointment_date)->format('d M Y') . " " . ($a->appointment_time ?? ''),
                    "Type: " . ($a->type ?? '—'),
                    "Status: " . ($a->status ?? '—'),
                    "Reason: " . ($a->reason ?? '—'),
                    "",
                ], AdminNotifier::actorContext())),
                "appt_created_{$a->id}"
            );
        }

        // Defer Twilio calls until AFTER the HTTP response — keeps appointment
        // booking snappy even if Twilio is slow.
        $sendPatientWA = $prefs->shouldSend('appointment_created', 'patient', 'whatsapp');
        $sendAdminWA   = $prefs->shouldSend('appointment_created', 'admin',   'whatsapp');
        if ($sendPatientWA || $sendAdminWA) {
            $self = $this;
            dispatch(function () use ($a, $self, $sendPatientWA, $sendAdminWA) {
                $wa = app(WhatsAppService::class);
                if ($sendPatientWA) $self->sendWhatsAppConfirmation($a, $wa);
                if ($sendAdminWA)   $self->sendOwnerCopy($a, $wa);
            })->afterResponse();
        }
    }

    public function sendWhatsAppConfirmation(Appointment $a, WhatsAppService $wa): void
    {
        if (!$a->patient || empty($a->patient->phone)) return;

        $clinic = config('app.name', 'Speciality Care');
        $when = trim(
            (optional($a->appointment_date)->format('d M Y') ?: '') .
            ($a->appointment_time ? ' at ' . \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') : '')
        );
        $doctor = $a->doctor->full_name ?? null;

        // $doctor is Doctor::full_name which already includes the "Dr." prefix,
        // so the doctor clause is "with Dr. X" (never "with Dr. Dr. X").
        $name      = $a->patient->full_name ?: 'there';
        $docClause = $doctor ? "with {$doctor}" : 'with our doctor';
        $apptNo    = (string) ($a->appointment_no ?: $a->id);
        $body = "Hi {$name},\n\n"
              . "Your appointment {$docClause} is confirmed for {$when}.\n"
              . "Appointment no: {$apptNo}\n\n"
              . "Please arrive 10 minutes early. Reply to this message if you need to reschedule.\n\n"
              . "— {$clinic}";

        // Template appointment_confirm: {{1}} name, {{2}} "with Dr. X", {{3}} when, {{4}} appt no, {{5}} clinic.
        $wa->sendTemplateToPatient($a->patient, 'appointment_confirm', [$name, $docClause, $when, $apptNo, $clinic], $body, 'appointment_confirm');
    }

    public function sendOwnerCopy(Appointment $a, WhatsAppService $wa): void
    {
        $when = trim(
            (optional($a->appointment_date)->format('d M Y') ?: '') .
            ($a->appointment_time ? ' ' . \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') : '')
        );
        // appointments.type enum = New Consultation / Follow-up / Review /
        // Emergency / Teleconsult. Treat Follow-up and Review as revisits so
        // the owner can tell at a glance which flow this is.
        $kind = $a->type ?? '';
        $isRevisit  = $kind && (stripos($kind, 'follow') !== false || stripos($kind, 'review') !== false);
        $isTeleconsult = stripos($kind, 'tele') !== false;
        $emoji = $isTeleconsult ? '💻' : ($isRevisit ? '🔁' : '📅');
        $label = $isTeleconsult ? 'Teleconsult appointment' : ($isRevisit ? 'Revisit appointment' : 'New appointment');

        $patientLine = ($a->patient->full_name ?? '—') . " (" . ($a->patient->jhc_id ?? '—') . ")";
        $doctorLine  = $a->doctor->full_name ?? '—';
        $whenLine    = $when ?: '—';
        $apptNo      = (string) ($a->appointment_no ?: $a->id);
        $actor       = AdminNotifier::actor();

        $body = "{$emoji} {$label}\n"
              . "Patient: {$patientLine}\n"
              . "Doctor: {$doctorLine}\n"
              . "When: {$whenLine}\n"
              . "No: {$apptNo}\n"
              . ($a->meeting_url ? "Join: " . $a->meeting_url . "\n" : '')
              . "By: {$actor}";

        // Owner template owner_appointment_created: {{1}} type label, {{2}} patient
        // (name + JHC id), {{3}} doctor, {{4}} when, {{5}} appt no. Booked-by is
        // intentionally NOT a template var (Meta rejects too many vars for the
        // body length); it stays in the free-form fallback body below, which
        // also carries the teleconsult Join URL. Template delivers regardless of
        // the owner's 24h session window; falls back to free-form until approved.
        if ($wa->hasTemplate('owner_appointment_created')) {
            $wa->sendTemplateOwnerAlert(
                'owner_appointment_created',
                [$label, $patientLine, $doctorLine, $whenLine, $apptNo],
                $body,
                'owner_appointment_created',
                $a->patient,
            );
        } else {
            $wa->sendOwnerAlert($body, 'owner_appointment_created', $a->patient);
        }
    }

    public function updated(Appointment $a): void
    {
        $changes = collect($a->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;
        if (!app(NotificationPreferenceService::class)->shouldSend('appointment_updated', 'admin', 'email')) return;
        $diff = AdminNotifier::diff($a);
        $a->loadMissing(['patient', 'doctor']);
        AdminNotifier::notify(
            "Appointment updated — " . optional($a->patient)->full_name,
            AdminNotifier::fmt(array_merge([
                "Appointment updated.",
                "Patient: " . ($a->patient->full_name ?? '—') . " (" . ($a->patient->jhc_id ?? '—') . ")",
                "Doctor: " . ($a->doctor->full_name ?? '—'),
                "When: " . optional($a->appointment_date)->format('d M Y') . " " . ($a->appointment_time ?? ''),
                "Status: " . ($a->status ?? '—'),
                $diff,
                "",
            ], AdminNotifier::actorContext())),
            "appt_updated_{$a->id}_" . md5($changes->implode(','))
        );
    }

    public function deleted(Appointment $a): void
    {
        if (!app(NotificationPreferenceService::class)->shouldSend('appointment_cancelled', 'admin', 'email')) return;
        $a->loadMissing(['patient', 'doctor']);
        AdminNotifier::notify(
            "Appointment cancelled / deleted — " . optional($a->patient)->full_name,
            AdminNotifier::fmt(array_merge([
                "Appointment cancelled or deleted.",
                "Patient: " . ($a->patient->full_name ?? '—') . " (" . ($a->patient->jhc_id ?? '—') . ")",
                "Doctor: " . ($a->doctor->full_name ?? '—'),
                "Was scheduled: " . optional($a->appointment_date)->format('d M Y') . " " . ($a->appointment_time ?? ''),
                "",
            ], AdminNotifier::actorContext()))
        );
    }
}
