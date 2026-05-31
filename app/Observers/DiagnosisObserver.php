<?php

namespace App\Observers;

use App\Models\Diagnosis;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;

class DiagnosisObserver
{
    public function created(Diagnosis $d): void
    {
        if (!app(NotificationPreferenceService::class)->shouldSend('diagnosis_created', 'admin', 'email')) return;
        $d->loadMissing(['encounter.patient']);
        AdminNotifier::notify(
            'Diagnosis added — ' . ($d->diagnosis_name ?: '?'),
            AdminNotifier::fmt(array_merge([
                "Diagnosis added.",
                "Name: " . ($d->diagnosis_name ?? '—') . ($d->icd_code ? " ({$d->icd_code})" : ''),
                "Type: " . ($d->type ?? '—'),
                "Status: " . ($d->status ?? '—'),
                "Encounter: " . ($d->encounter->encounter_no ?? '#' . $d->encounter_id),
                "Patient: " . ($d->encounter->patient->full_name ?? '—'),
                "",
            ], AdminNotifier::actorContext())),
            "dx_created_{$d->id}"
        );
    }
}
