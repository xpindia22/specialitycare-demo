<?php

namespace App\Observers;

use App\Models\Admission;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;

class AdmissionObserver
{
    public function created(Admission $a): void
    {
        if (!app(NotificationPreferenceService::class)->shouldSend('admission_created', 'admin', 'email')) return;
        $a->loadMissing(['patient', 'admittingDoctor', 'ward', 'bed']);
        AdminNotifier::notify(
            'New admission — ' . ($a->ipd_no ?? '#' . $a->id),
            AdminNotifier::fmt(array_merge([
                "New IPD admission.",
                "IPD No: " . ($a->ipd_no ?? '#' . $a->id),
                "Patient: " . ($a->patient->full_name ?? '—'),
                "Admitting doctor: " . ($a->admittingDoctor->full_name ?? '—'),
                "Ward / Bed: " . ($a->ward->name ?? '—') . " / " . ($a->bed->bed_number ?? '—'),
                "Provisional Dx: " . ($a->provisional_diagnosis ?? '—'),
                "",
            ], AdminNotifier::actorContext(), [
                "Link: " . url('/admissions/' . $a->id),
            ])),
            "adm_created_{$a->id}"
        );
    }

    public function updated(Admission $a): void
    {
        $changes = collect($a->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;
        // Only notify when something operationally meaningful changed
        $meaningful = ['status', 'ward_id', 'bed_id', 'discharge_date', 'discharge_condition', 'provisional_diagnosis'];
        if (!$changes->intersect($meaningful)->count()) return;
        if (!app(NotificationPreferenceService::class)->shouldSend('admission_updated', 'admin', 'email')) return;

        $diff = AdminNotifier::diff($a);
        $a->loadMissing(['patient', 'ward', 'bed']);
        AdminNotifier::notify(
            'Admission updated — ' . ($a->ipd_no ?? '#' . $a->id),
            AdminNotifier::fmt(array_merge([
                "Admission updated.",
                "IPD No: " . ($a->ipd_no ?? '#' . $a->id),
                "Patient: " . ($a->patient->full_name ?? '—'),
                "Status: " . ($a->status ?? '—'),
                "Ward / Bed: " . ($a->ward->name ?? '—') . " / " . ($a->bed->bed_number ?? '—'),
                $diff,
                "",
            ], AdminNotifier::actorContext())),
            "adm_updated_{$a->id}_" . md5($changes->implode(','))
        );
    }
}
