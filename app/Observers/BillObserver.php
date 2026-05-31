<?php

namespace App\Observers;

use App\Models\Bill;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;
use App\Services\PatientNotifier;

class BillObserver
{
    public function created(Bill $b): void
    {
        // Patient + owner channels (email + WhatsApp). PatientNotifier itself
        // checks each channel against notification_preferences[bill_created],
        // so we always call it and let it sort out who to notify.
        app(PatientNotifier::class)->sendBillCreated($b);

        // Admin email digest (separate from the patient flow above so the
        // owner can keep this short-form internal alert independent of the
        // full patient-facing send). Gated on its own preference flag.
        if (!app(NotificationPreferenceService::class)->shouldSend('bill_created', 'admin', 'email')) return;
        $b->loadMissing(['patient']);
        AdminNotifier::notify(
            'New bill — ' . ($b->bill_no ?? '#' . $b->id),
            AdminNotifier::fmt(array_merge([
                "New bill generated.",
                "Bill No: " . ($b->bill_no ?? '#' . $b->id),
                "Patient: " . ($b->patient->full_name ?? '—'),
                "Total: ₹" . number_format((float) $b->total_amount, 2),
                "Paid: ₹" . number_format((float) $b->paid_amount, 2),
                "Due: ₹" . number_format((float) $b->due_amount, 2),
                "Status: " . ($b->status ?? '—'),
                "Payment method: " . ($b->payment_method ?? '—'),
                "",
            ], AdminNotifier::actorContext(), [
                "Link: " . url('/billing/' . $b->id),
            ])),
            "bill_created_{$b->id}"
        );
    }

    public function updated(Bill $b): void
    {
        $changes = collect($b->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;
        // Only notify when status or amounts change (skip notes-only edits)
        $meaningful = ['status', 'paid_amount', 'due_amount', 'total_amount', 'discount_amount', 'insurance_amount'];
        if (!$changes->intersect($meaningful)->count()) return;

        // Bill just transitioned to Paid — fire the patient receipt event
        // (separate gating from bill_updated, so the owner can turn on Paid
        // receipts to patients without turning on every bill-edit email).
        if ($b->status === 'Paid' && $b->getOriginal('status') !== 'Paid') {
            app(PatientNotifier::class)->sendBillPaidReceipt($b);
        }

        if (!app(NotificationPreferenceService::class)->shouldSend('bill_updated', 'admin', 'email')) return;

        $diff = AdminNotifier::diff($b);
        $b->loadMissing(['patient']);
        AdminNotifier::notify(
            'Bill updated — ' . ($b->bill_no ?? '#' . $b->id) . ($b->status ? " ({$b->status})" : ''),
            AdminNotifier::fmt(array_merge([
                "Bill updated.",
                "Bill No: " . ($b->bill_no ?? '#' . $b->id),
                "Patient: " . ($b->patient->full_name ?? '—'),
                "Total: ₹" . number_format((float) $b->total_amount, 2),
                "Paid: ₹" . number_format((float) $b->paid_amount, 2),
                "Due: ₹" . number_format((float) $b->due_amount, 2),
                "Status: " . ($b->status ?? '—'),
                $diff,
                "",
            ], AdminNotifier::actorContext())),
            "bill_updated_{$b->id}_" . md5($changes->implode(','))
        );
    }
}
