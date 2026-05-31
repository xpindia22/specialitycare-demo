<?php

namespace App\Observers;

use App\Models\LabOrder;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;

class LabOrderObserver
{
    public function created(LabOrder $lo): void
    {
        if (!app(NotificationPreferenceService::class)->shouldSend('lab_order_created', 'admin', 'email')) return;
        $lo->loadMissing(['patient', 'doctor', 'items.test', 'items.panel']);
        $items = $lo->items->map(fn ($i) => $i->test->name ?? $i->panel->name ?? '—')->take(8)->implode(', ');
        AdminNotifier::notify(
            'New lab order — ' . ($lo->order_no ?? '#' . $lo->id),
            AdminNotifier::fmt(array_merge([
                "New lab order placed.",
                "Order No: " . ($lo->order_no ?? '#' . $lo->id),
                "Patient: " . ($lo->patient->full_name ?? '—'),
                "Doctor: " . ($lo->doctor->full_name ?? '—'),
                "Status: " . ($lo->status ?? '—'),
                "Total: ₹" . number_format((float) $lo->total_amount, 2),
                "Items (" . $lo->items->count() . "): " . ($items ?: '—'),
                "",
            ], AdminNotifier::actorContext(), [
                "Link: " . url('/lab/order/' . $lo->id),
            ])),
            "labord_created_{$lo->id}"
        );
    }
}
