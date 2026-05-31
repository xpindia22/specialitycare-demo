<?php

namespace App\Observers;

use App\Models\PrescriptionItem;
use App\Services\AdminNotifier;

class PrescriptionItemObserver
{
    public function created(PrescriptionItem $i): void
    {
        $i->loadMissing(['medicine', 'prescription.patient']);
        AdminNotifier::notify(
            'Rx item added — ' . ($i->medicine->name ?? '(unknown drug)'),
            AdminNotifier::fmt(array_merge([
                "Drug added to prescription.",
                "Drug: " . ($i->medicine->name ?? '—') . " — " . trim(implode(' / ', array_filter([$i->dose, $i->frequency, $i->route]))),
                "Prescription: " . ($i->prescription->prescription_no ?? '#' . $i->prescription_id),
                "Patient: " . ($i->prescription->patient->full_name ?? '—'),
            ], AdminNotifier::actorContext())),
            "rx_item_created_{$i->id}"
        );
    }

    public function updated(PrescriptionItem $i): void
    {
        $changes = collect($i->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;
        $diff = AdminNotifier::diff($i);
        $i->loadMissing(['medicine', 'prescription.patient']);
        AdminNotifier::notify(
            'Rx item edited — ' . ($i->medicine->name ?? '(unknown drug)'),
            AdminNotifier::fmt(array_merge([
                "Drug entry edited.",
                "Drug: " . ($i->medicine->name ?? '—'),
                "Now: " . trim(implode(' / ', array_filter([$i->dose, $i->frequency, $i->route, $i->duration_days ? "{$i->duration_days}d" : null]))),
                $diff,
                "Prescription: " . ($i->prescription->prescription_no ?? '#' . $i->prescription_id),
                "Patient: " . ($i->prescription->patient->full_name ?? '—'),
            ], AdminNotifier::actorContext())),
            "rx_item_updated_{$i->id}_" . md5($changes->implode(','))
        );
    }

    public function deleted(PrescriptionItem $i): void
    {
        $i->loadMissing(['medicine', 'prescription.patient']);
        AdminNotifier::notify(
            'Rx item removed — ' . ($i->medicine->name ?? '(unknown drug)'),
            AdminNotifier::fmt(array_merge([
                "Drug removed from prescription.",
                "Drug: " . ($i->medicine->name ?? '—'),
                "Prescription: " . ($i->prescription->prescription_no ?? '#' . $i->prescription_id),
                "Patient: " . ($i->prescription->patient->full_name ?? '—'),
            ], AdminNotifier::actorContext()))
        );
    }
}
