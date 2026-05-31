<?php

namespace App\Observers;

use App\Models\Patient;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;

class PatientObserver
{
    public function created(Patient $p): void
    {
        $prefs = app(NotificationPreferenceService::class);

        if ($prefs->shouldSend('patient_registered', 'admin', 'email')) {
            AdminNotifier::notify(
                'New patient registered — ' . $p->full_name,
                AdminNotifier::fmt(array_merge([
                    "New patient registered.",
                    "JHC ID: " . ($p->jhc_id ?? '—'),
                    "Name: " . $p->full_name,
                    "Sex / Age: " . ($p->gender ?? '—') . " / " . ($p->age ?? '—'),
                    "Phone: " . ($p->phone ?? '—'),
                    "DOB: " . optional($p->date_of_birth)->format('d M Y'),
                    "",
                ], AdminNotifier::actorContext(), [
                    "Link: " . url('/patients/' . $p->id),
                ])),
                "patient_created_{$p->id}"
            );
        }

        // Defer Twilio calls until AFTER the HTTP response — keeps patient
        // registration snappy even if Twilio is slow / rate-limited.
        $self  = $this;
        $sendPatientWA = $prefs->shouldSend('patient_registered', 'patient', 'whatsapp');
        if ($sendPatientWA) {
            dispatch(function () use ($p, $self) {
                $self->sendWelcomeWhatsApp($p, app(WhatsAppService::class));
            })->afterResponse();
        }
    }

    public function updated(Patient $p): void
    {
        $changes = collect($p->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;

        if (!app(NotificationPreferenceService::class)->shouldSend('patient_updated', 'admin', 'email')) return;

        AdminNotifier::notify(
            'Patient edited — ' . $p->full_name,
            AdminNotifier::fmt(array_merge([
                "Patient record edited.",
                "JHC ID: " . ($p->jhc_id ?? '—'),
                "Name: " . $p->full_name,
                AdminNotifier::diff($p),
                "",
            ], AdminNotifier::actorContext(), [
                "Link: " . url('/patients/' . $p->id),
            ])),
            "patient_updated_{$p->id}_" . md5($changes->implode(','))
        );
    }

    public function deleted(Patient $p): void
    {
        if (!app(NotificationPreferenceService::class)->shouldSend('patient_deleted', 'admin', 'email')) return;

        AdminNotifier::notify(
            'Patient deleted — ' . $p->full_name,
            AdminNotifier::fmt(array_merge([
                "Patient record deleted.",
                "JHC ID: " . ($p->jhc_id ?? '—'),
                "Name: " . $p->full_name,
                "Phone: " . ($p->phone ?? '—'),
                "",
            ], AdminNotifier::actorContext()))
        );
    }

    public function sendWelcomeWhatsApp(Patient $p, WhatsAppService $wa): void
    {
        if (empty($p->phone) && empty($p->alt_phone)) return;

        $clinic = config('app.name', 'Speciality Care');
        $name = $p->full_name ?: 'there';
        $id   = $p->jhc_id ?: '—';
        $body = "Hi {$name},\n\n"
              . "Welcome to {$clinic}. You've been registered with patient ID: {$id}.\n"
              . "Please save this number — we'll use it for appointment confirmations, reminders and reports.\n\n"
              . "— {$clinic}";

        // Template patient_welcome: {{1}} name, {{2}} clinic, {{3}} patient ID.
        $wa->sendTemplateToPatient($p, 'patient_welcome', [$name, $clinic, $id], $body, 'patient_welcome');
    }
}
