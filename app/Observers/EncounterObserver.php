<?php

namespace App\Observers;

use App\Models\Encounter;
use App\Models\ReferralPayment;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;

class EncounterObserver
{
    public function created(Encounter $e): void
    {
        $this->maybeCreateFirstConsultationReferralIncentive($e);

        if (!app(NotificationPreferenceService::class)->shouldSend('encounter_created', 'admin', 'email')) return;
        $e->loadMissing(['patient', 'doctor']);
        AdminNotifier::notify(
            'New visit — ' . ($e->encounter_no ?? '#' . $e->id),
            AdminNotifier::fmt(array_merge([
                "New encounter (visit) opened.",
                "Encounter: " . ($e->encounter_no ?? '#' . $e->id),
                "Patient: " . ($e->patient->full_name ?? '—') . " (" . ($e->patient->jhc_id ?? '—') . ")",
                "Doctor: " . ($e->doctor->full_name ?? '—'),
                "Date: " . optional($e->encounter_date)->format('d M Y'),
                "Visit type: " . ($e->visit_type ?? '—'),
                "Chief complaint: " . trim(strip_tags((string) $e->chief_complaint)),
                "",
            ], AdminNotifier::actorContext(), [
                "Link: " . url('/encounters/' . $e->id),
            ])),
            "encounter_created_{$e->id}"
        );
    }

    public function updated(Encounter $e): void
    {
        $changes = collect($e->getChanges())->except(['updated_at'])->keys();
        if ($changes->isEmpty()) return;
        if (!app(NotificationPreferenceService::class)->shouldSend('encounter_updated', 'admin', 'email')) return;
        $diff = AdminNotifier::diff($e);
        $e->loadMissing(['patient', 'doctor']);
        AdminNotifier::notify(
            'Encounter updated — ' . ($e->encounter_no ?? '#' . $e->id),
            AdminNotifier::fmt(array_merge([
                "Encounter updated: " . ($e->encounter_no ?? '#' . $e->id),
                "Patient: " . ($e->patient->full_name ?? '—') . " (" . ($e->patient->jhc_id ?? '—') . ")",
                "Doctor: " . ($e->doctor->full_name ?? '—'),
                $diff,
                "",
            ], AdminNotifier::actorContext(), [
                "Link: " . url('/encounters/' . $e->id),
            ])),
            "encounter_updated_{$e->id}"
        );
    }

    /**
     * One-time referral bonus (rate configurable in Settings, default ₹100) —
     * paid only on a patient's very first-ever consultation, system-wide.
     * Later visits by the same patient (even via a different referral) never re-trigger this.
     */
    private function maybeCreateFirstConsultationReferralIncentive(Encounter $e): void
    {
        $patient = $e->patient ?? \App\Models\Patient::find($e->patient_id);
        if (!$patient || !$patient->referral_source_id) return;
        if (Encounter::where('patient_id', $e->patient_id)->count() > 1) return;

        $rate = (float) setting('referral_rate_consultation_first', 100);
        if ($rate <= 0) return;

        ReferralPayment::create([
            'referral_source_id' => $patient->referral_source_id,
            'patient_id'         => $patient->id,
            'calculated_amount'  => $rate,
            'paid_amount'        => 0,
            'status'             => 'pending',
            'notes'              => "Auto first-consultation referral incentive (encounter {$e->encounter_no})",
            'created_by'         => $e->created_by,
        ]);
    }
}
