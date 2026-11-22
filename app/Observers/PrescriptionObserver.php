<?php

namespace App\Observers;

use App\Models\Prescription;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;

class PrescriptionObserver
{
    public function created(Prescription $rx): void
    {
        // Patient-facing auto-send REMOVED 2026-08-26: no more auto WhatsApp/
        // email to the patient on prescription creation -- staff must click
        // Send/Email/WhatsApp on the prescription to send it.

        if (!app(NotificationPreferenceService::class)->shouldSend('prescription_created', 'admin', 'email')) return;
        $rx->loadMissing(['patient', 'doctor', 'items.medicine']);
        AdminNotifier::notify(
            'New prescription — ' . ($rx->prescription_no ?? '#' . $rx->id),
            AdminNotifier::fmt(array_merge([
                "New prescription created.",
                "No: " . ($rx->prescription_no ?? '#' . $rx->id),
                "Patient: " . ($rx->patient->full_name ?? '—') . " (" . ($rx->patient->jhc_id ?? '—') . ")",
                "Doctor: " . ($rx->doctor->full_name ?? '—'),
                "Status: " . ($rx->status ?? '—'),
                "Items: " . $rx->items->count(),
                $this->itemList($rx),
                "",
            ], AdminNotifier::actorContext())),
            "rx_created_{$rx->id}"
        );
    }

    public function updated(Prescription $rx): void
    {
        $changes = collect($rx->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;
        if (!app(NotificationPreferenceService::class)->shouldSend('prescription_updated', 'admin', 'email')) return;
        $diff = AdminNotifier::diff($rx);
        $rx->loadMissing(['patient', 'doctor']);
        AdminNotifier::notify(
            'Prescription edited — ' . ($rx->prescription_no ?? '#' . $rx->id),
            AdminNotifier::fmt(array_merge([
                "Prescription edited.",
                "No: " . ($rx->prescription_no ?? '#' . $rx->id),
                "Patient: " . ($rx->patient->full_name ?? '—'),
                $diff,
                "",
            ], AdminNotifier::actorContext())),
            "rx_updated_{$rx->id}_" . md5($changes->implode(','))
        );
    }

    public function deleted(Prescription $rx): void
    {
        if (!app(NotificationPreferenceService::class)->shouldSend('prescription_deleted', 'admin', 'email')) return;
        $rx->loadMissing(['patient']);
        AdminNotifier::notify(
            'Prescription deleted — ' . ($rx->prescription_no ?? '#' . $rx->id),
            AdminNotifier::fmt(array_merge([
                "Prescription deleted.",
                "No: " . ($rx->prescription_no ?? '#' . $rx->id),
                "Patient: " . ($rx->patient->full_name ?? '—'),
            ], AdminNotifier::actorContext()))
        );
    }

    private function itemList(Prescription $rx): string
    {
        if ($rx->items->isEmpty()) return '';
        return "Drugs:\n" . $rx->items->map(function ($i) {
            $name = $i->medicine->name ?? '(unknown)';
            $bits = array_filter([$i->dose, $i->frequency, $i->route, $i->duration_days ? "{$i->duration_days}d" : null]);
            return "  • {$name}" . ($bits ? ' — ' . implode(' / ', $bits) : '');
        })->implode("\n");
    }
}
