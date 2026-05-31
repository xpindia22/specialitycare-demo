<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\EchoRecord;
use App\Models\Patient;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * High-level patient-facing notifier. Per-event entry points
 * (sendBillPaidReceipt, sendPrescription, etc.) consult
 * NotificationPreferenceService and then fan out to email + WhatsApp
 * as configured.
 *
 * Email gets the PDF as an attachment. WhatsApp gets a short text plus
 * a public storage URL to the PDF (hashed filename, so only people with
 * the link can read it).
 *
 * Each entry point dispatches its actual sending via dispatch()->afterResponse()
 * — the calling observer / controller doesn't block on SMTP or Twilio.
 */
class PatientNotifier
{
    public function __construct(
        private readonly NotificationPreferenceService $prefs,
        private readonly WhatsAppService $wa,
    ) {}

    // ───────────────────────── Teleconsult link ─────────────────────────

    /**
     * Send the Jitsi meeting link to the patient when a Teleconsult appointment
     * is created. Channels: patient_email, patient_whatsapp, owner_whatsapp CC.
     * Gated against notification_preferences[appointment_confirm] since we want
     * teleconsult links to ride the same on/off switch as appointment confirms
     * — no separate pref to forget to enable.
     */
    public function sendTeleconsultLink(Appointment $appt): void
    {
        $appt->loadMissing(['patient', 'doctor']);
        if (!$appt->patient || !$appt->meeting_url) return;

        $eventKey = 'appointment_confirm';
        $sendPatientEmail = $this->prefs->shouldSend($eventKey, 'patient', 'email');
        $sendPatientWA    = $this->prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $sendAdminWA      = $this->prefs->shouldSend($eventKey, 'admin',   'whatsapp');
        if (!($sendPatientEmail || $sendPatientWA || $sendAdminWA)) return;

        $self = $this;
        dispatch(function () use ($self, $appt, $sendPatientEmail, $sendPatientWA, $sendAdminWA) {
            $self->dispatchTeleconsultLink($appt, $sendPatientEmail, $sendPatientWA, $sendAdminWA);
        })->afterResponse();
    }

    public function dispatchTeleconsultLink(Appointment $appt, bool $patientEmail, bool $patientWA, bool $adminWA): void
    {
        $clinic    = setting('clinic_name', config('app.name', 'Demo Clinic'));
        $patient   = $appt->patient;
        $doctor    = $appt->doctor->full_name ?? 'Doctor';
        $date      = optional($appt->appointment_date)->format('d M Y') ?: '—';
        $time      = $appt->appointment_time ?: '';
        $when      = trim($date . ($time ? ' ' . $time : ''));
        $url       = $appt->meeting_url;

        $subject   = "Your video consultation — {$appt->appointment_no} · {$clinic}";

        $bodyEmail = "Dear " . ($patient->full_name ?: 'patient') . ",\n\n"
                   . "Your video consultation with {$doctor} is confirmed for {$when}.\n\n"
                   . "Join the call here at the appointment time:\n{$url}\n\n"
                   . "Tips: use a recent browser (Chrome/Edge/Safari), allow camera + microphone when prompted, and join 2-3 minutes early. If anything doesn't work, call the clinic.\n\n"
                   . "— {$clinic}";

        $bodyWA    = "Hi " . ($patient->full_name ?: 'there') . ",\n\n"
                   . "Your video consultation with {$doctor} is confirmed for {$when}.\n\n"
                   . "Join: {$url}\n\n"
                   . "— {$clinic}";

        // Patient channels
        if ($patientEmail && $patient->email) {
            $this->mailWithPdf($patient->email, $subject, $bodyEmail, null, null);
        }
        if ($patientWA) {
            // Template send path will use a Gupshup-approved teleconsult_link
            // template (pending) when available. Free-form fallback until then.
            if ($this->wa->hasTemplate('teleconsult_link')) {
                $this->wa->sendTemplateToPatient(
                    $patient,
                    'teleconsult_link',
                    [$patient->full_name ?: 'patient', $doctor, $when, $url, $clinic],
                    $bodyWA,
                    'teleconsult_link',
                );
            } else {
                $this->wa->sendToPatient($patient, $bodyWA, 'teleconsult_link');
            }
        }

        // Owner CC
        if ($adminWA) {
            $this->wa->sendOwnerAlert(
                "🎥 Teleconsult booked\nNo: {$appt->appointment_no}\nPatient: {$patient->full_name}\nDoctor: {$doctor}\nWhen: {$when}\nJoin URL: {$url}",
                'owner_teleconsult_booked',
                $patient
            );
        }
    }

    // ───────────────────────── Bill created ─────────────────────────

    /**
     * Patient-facing notification when a new bill is generated.
     * Channels: patient_email, patient_whatsapp, admin_email, admin_whatsapp.
     * Each channel is gated against notification_preferences[bill_created].
     * The PDF (billing.print) is attached to emails and linked from WhatsApp.
     */
    public function sendBillCreated(Bill $bill): void
    {
        $bill->loadMissing(['patient']);
        if (!$bill->patient) return;

        $eventKey = 'bill_created';
        $sendPatientEmail = $this->prefs->shouldSend($eventKey, 'patient', 'email');
        $sendPatientWA    = $this->prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $sendAdminEmail   = $this->prefs->shouldSend($eventKey, 'admin',   'email');
        $sendAdminWA      = $this->prefs->shouldSend($eventKey, 'admin',   'whatsapp');
        if (!($sendPatientEmail || $sendPatientWA || $sendAdminEmail || $sendAdminWA)) return;

        $self = $this;
        dispatch(function () use ($self, $bill, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA) {
            $self->dispatchBillCreated($bill, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA);
        })->afterResponse();
    }

    /** Heavy lifting for bill_created — runs in afterResponse(). */
    public function dispatchBillCreated(Bill $bill, bool $patientEmail, bool $patientWA, bool $adminEmail, bool $adminWA): void
    {
        $clinic  = setting('clinic_name', config('app.name', 'Demo Clinic'));
        $patient = $bill->patient;

        // Render the bill PDF once; reuse across channels.
        try {
            $bill->load(['items', 'payments', 'encounter']);
            $pdf = Pdf::loadView('billing.print', ['bill' => $bill]);
            $pdfBytes = $pdf->output();
        } catch (\Throwable $e) {
            Log::warning("PatientNotifier::bill_created PDF gen failed for bill #{$bill->id}: " . $e->getMessage());
            $pdfBytes = null;
        }

        $pdfUrl = ($pdfBytes && ($patientWA || $adminWA))
            ? $this->storeForLink($pdfBytes, 'bills', "bill-{$bill->bill_no}")
            : null;

        $total = '₹' . number_format((float) $bill->total_amount, 2);
        $due   = '₹' . number_format((float) $bill->due_amount, 2);
        $billDate = optional($bill->bill_date)->format('d M Y') ?: now()->format('d M Y');

        $subject = "Your bill — {$bill->bill_no} · {$clinic}";

        $bodyEmailPatient = "Dear " . ($patient->full_name ?: 'patient') . ",\n\n"
                          . "A bill has been generated for you.\n\n"
                          . "Bill No: {$bill->bill_no}\n"
                          . "Date: {$billDate}\n"
                          . "Total: {$total}\n"
                          . "Due: {$due}\n"
                          . "Status: " . ($bill->status ?: '—') . "\n\n"
                          . ($bill->due_amount > 0
                              ? "Please clear the dues at your next visit or via the payment options shared at the clinic.\n\n"
                              : "Thank you for your payment.\n\n")
                          . "— {$clinic}";

        $bodyWAPatient = "Hi " . ($patient->full_name ?: 'there') . ",\n\n"
                       . "Your bill {$bill->bill_no} for {$total}"
                       . ($bill->due_amount > 0 ? " ({$due} due)" : "")
                       . " has been generated."
                       . ($pdfUrl ? "\n\nBill: {$pdfUrl}" : '')
                       . "\n\n— {$clinic}";

        // Patient channels
        if ($patientEmail && $patient->email) {
            $this->mailWithPdf($patient->email, $subject, $bodyEmailPatient, $pdfBytes, "Bill-{$bill->bill_no}.pdf");
        }
        if ($patientWA) {
            // Prefer template send (delivers outside 24h session window); fall back
            // to free-form when no approved template is configured (won't deliver
            // outside session — but the rendered body is still logged for audit).
            // Template params: {{1}}=patient name, {{2}}=bill no, {{3}}=total,
            // {{4}}=due, {{5}}=clinic name. Match this ordering in the Gupshup/Meta
            // template body when you submit it for approval.
            if ($this->wa->hasTemplate('bill_created')) {
                $this->wa->sendTemplateToPatient(
                    $patient,
                    'bill_created',
                    [$patient->full_name ?: 'patient', $bill->bill_no, $total, $due, $clinic],
                    $bodyWAPatient,
                    'bill_created',
                );
            } else {
                $this->wa->sendToPatient($patient, $bodyWAPatient, 'bill_created');
            }
        }

        // Admin channels
        if ($adminEmail) {
            $to = config('admin_notify.email');
            if ($to) {
                $adminBody = "New bill generated:\n"
                           . "  No: {$bill->bill_no}\n"
                           . "  Patient: {$patient->full_name} (" . ($patient->jhc_id ?? '—') . ")\n"
                           . "  Total: {$total}\n"
                           . "  Due: {$due}\n"
                           . "  Status: " . ($bill->status ?: '—');
                $this->mailWithPdf($to, "[SC] New bill — {$bill->bill_no}", $adminBody, $pdfBytes, "Bill-{$bill->bill_no}.pdf");
            }
        }
        if ($adminWA) {
            // Owner template params: {{1}}=patient name, {{2}}=bill no, {{3}}=total, {{4}}=due.
            $renderedOwner = "🧾 New bill\nNo: {$bill->bill_no}\nPatient: {$patient->full_name}\nTotal: {$total}\nDue: {$due}\nStatus: " . ($bill->status ?: '—')
                           . ($pdfUrl ? "\nPDF: {$pdfUrl}" : '');
            if ($this->wa->hasTemplate('owner_bill_created')) {
                $this->wa->sendTemplateOwnerAlert(
                    'owner_bill_created',
                    [$patient->full_name ?: 'patient', $bill->bill_no, $total, $due],
                    $renderedOwner,
                    'owner_bill_created',
                    $patient,
                );
            } else {
                $this->wa->sendOwnerAlert($renderedOwner, 'owner_bill_created', $patient);
            }
        }
    }

    // ───────────────────────── Bill overdue ─────────────────────────

    /**
     * Reminder for an overdue (unpaid + N+ days old) bill. Channels:
     * patient_email, patient_whatsapp, admin_email, admin_whatsapp.
     * Each channel gated against notification_preferences[bill_overdue].
     *
     * Designed to be called from a scheduled command — but if invoked from
     * an HTTP request the dispatch is still deferred via afterResponse().
     * From CLI, call dispatchBillOverdueReminder() directly to avoid the
     * deferred path which won't fire without a terminating HTTP response.
     */
    public function sendBillOverdueReminder(Bill $bill): void
    {
        $bill->loadMissing(['patient']);
        if (!$bill->patient) return;

        $eventKey = 'bill_overdue';
        $sendPatientEmail = $this->prefs->shouldSend($eventKey, 'patient', 'email');
        $sendPatientWA    = $this->prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $sendAdminEmail   = $this->prefs->shouldSend($eventKey, 'admin',   'email');
        $sendAdminWA      = $this->prefs->shouldSend($eventKey, 'admin',   'whatsapp');
        if (!($sendPatientEmail || $sendPatientWA || $sendAdminEmail || $sendAdminWA)) return;

        $self = $this;
        dispatch(function () use ($self, $bill, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA) {
            $self->dispatchBillOverdueReminder($bill, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA);
        })->afterResponse();
    }

    /** Heavy lifting for bill_overdue. Called directly from CLI to bypass afterResponse. */
    public function dispatchBillOverdueReminder(Bill $bill, bool $patientEmail, bool $patientWA, bool $adminEmail, bool $adminWA): void
    {
        $clinic  = setting('clinic_name', config('app.name', 'Demo Clinic'));
        $patient = $bill->patient;

        $due       = '₹' . number_format((float) $bill->due_amount, 2);
        $amountRaw = number_format((float) $bill->due_amount, 2);
        $billDate  = optional($bill->bill_date)->format('d M Y') ?: '—';
        $daysOverdue = (int) optional($bill->bill_date)->diffInDays(now());

        $subject = "Payment reminder — Bill {$bill->bill_no} · {$clinic}";

        $bodyEmail = "Dear " . ($patient->full_name ?: 'patient') . ",\n\n"
                   . "This is a friendly reminder that bill {$bill->bill_no} for {$due} (dated {$billDate}) is still outstanding.\n\n"
                   . "Please clear the dues at your next visit or via the payment options shared at the clinic.\n\n"
                   . "If you've already paid, please ignore this message.\n\n"
                   . "Thank you,\n— {$clinic}";

        $bodyWA = "Hi " . ($patient->full_name ?: 'there') . ",\n\n"
                . "Reminder: bill {$bill->bill_no} of {$due} dated {$billDate} is still outstanding.\n\n"
                . "Please clear at your next visit or via the payment options shared at the clinic.\n\n"
                . "— {$clinic}";

        // Patient channels
        if ($patientEmail && $patient->email) {
            $this->mailWithPdf($patient->email, $subject, $bodyEmail, null, null);
        }
        if ($patientWA) {
            // Use the already-approved Gupshup 'billing' template (bill_overdue) —
            // delivers outside the 24h session window. Params order:
            // {{1}}=patient name, {{2}}=bill no, {{3}}=amount, {{4}}=bill date, {{5}}=clinic.
            if ($this->wa->hasTemplate('billing')) {
                $this->wa->sendTemplateToPatient(
                    $patient,
                    'billing',
                    [$patient->full_name ?: 'patient', (string) $bill->bill_no, $amountRaw, $billDate, $clinic],
                    $bodyWA,
                    'bill_overdue',
                );
            } else {
                $this->wa->sendToPatient($patient, $bodyWA, 'bill_overdue');
            }
        }

        // Admin channels
        if ($adminEmail) {
            $to = config('admin_notify.email');
            if ($to) {
                $adminBody = "Bill overdue reminder sent:\n"
                           . "  No: {$bill->bill_no}\n"
                           . "  Patient: {$patient->full_name} (" . ($patient->jhc_id ?? '—') . ")\n"
                           . "  Due: {$due}\n"
                           . "  Bill date: {$billDate}\n"
                           . "  Days outstanding: {$daysOverdue}";
                $this->mailWithPdf($to, "[SC] Overdue reminder — {$bill->bill_no}", $adminBody, null, null);
            }
        }
        if ($adminWA) {
            $renderedOwner = "⏰ Overdue bill\nNo: {$bill->bill_no}\nPatient: {$patient->full_name}\nDue: {$due}\nBill date: {$billDate}\nDays: {$daysOverdue}";
            // Owner alert is free-form for now (no dedicated owner_bill_overdue
            // template yet) — owner sees it inside the 24h session if active.
            $this->wa->sendOwnerAlert($renderedOwner, 'owner_bill_overdue', $patient);
        }
    }

    // ───────────────────────── Bill paid ─────────────────────────

    public function sendBillPaidReceipt(Bill $bill): void
    {
        $bill->loadMissing(['patient']);
        if (!$bill->patient) return;

        $eventKey = 'bill_paid';
        $sendPatientEmail = $this->prefs->shouldSend($eventKey, 'patient', 'email');
        $sendPatientWA    = $this->prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $sendAdminEmail   = $this->prefs->shouldSend($eventKey, 'admin',   'email');
        $sendAdminWA      = $this->prefs->shouldSend($eventKey, 'admin',   'whatsapp');
        if (!($sendPatientEmail || $sendPatientWA || $sendAdminEmail || $sendAdminWA)) return;

        $self = $this;
        dispatch(function () use ($self, $bill, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA) {
            $self->dispatchBillPaid($bill, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA);
        })->afterResponse();
    }

    /** Heavy lifting for bill_paid — runs in afterResponse(). */
    public function dispatchBillPaid(Bill $bill, bool $patientEmail, bool $patientWA, bool $adminEmail, bool $adminWA): void
    {
        $clinic = setting('clinic_name', config('app.name', 'Demo Clinic'));
        $patient = $bill->patient;

        // Build the receipt PDF once; reuse for email + WhatsApp link.
        try {
            $bill->load(['items', 'payments', 'encounter']);
            $pdf = Pdf::loadView('billing.print', ['bill' => $bill]);
            $pdfBytes = $pdf->output();
        } catch (\Throwable $e) {
            Log::warning("PatientNotifier::bill_paid PDF gen failed for bill #{$bill->id}: " . $e->getMessage());
            $pdfBytes = null;
        }

        $pdfUrl = null;
        if ($pdfBytes && ($patientWA || $adminWA)) {
            $pdfUrl = $this->storeForLink($pdfBytes, 'bills', "receipt-{$bill->bill_no}");
        }

        $subject = "Payment received — Bill {$bill->bill_no} · {$clinic}";
        $amount  = '₹' . number_format((float) $bill->paid_amount, 2);
        $bodyEmail = "Dear " . ($patient->full_name ?: 'patient') . ",\n\n"
                   . "We've received your payment of {$amount} for bill {$bill->bill_no}.\n"
                   . "Receipt attached.\n\nThank you,\n— {$clinic}";

        $bodyWA = "Hi " . ($patient->full_name ?: 'there') . ",\n\n"
                . "Payment of {$amount} received for bill {$bill->bill_no}. Thank you!"
                . ($pdfUrl ? "\n\nReceipt: {$pdfUrl}" : '')
                . "\n\n— {$clinic}";

        // Patient channels
        if ($patientEmail && $patient->email) {
            $this->mailWithPdf($patient->email, $subject, $bodyEmail, $pdfBytes, "Receipt-{$bill->bill_no}.pdf");
        }
        if ($patientWA) {
            // Template params: {{1}}=patient name, {{2}}=bill no, {{3}}=amount, {{4}}=clinic name.
            // Match this ordering in the Gupshup/Meta template body when you submit it.
            if ($this->wa->hasTemplate('bill_paid')) {
                $this->wa->sendTemplateToPatient(
                    $patient,
                    'bill_paid',
                    [$patient->full_name ?: 'patient', $bill->bill_no, $amount, $clinic],
                    $bodyWA,
                    'bill_paid',
                );
            } else {
                $this->wa->sendToPatient($patient, $bodyWA, 'bill_paid');
            }
        }

        // Admin channels
        if ($adminEmail) {
            $to = config('admin_notify.email');
            if ($to) {
                $adminBody = "Bill paid:\n  No: {$bill->bill_no}\n  Patient: {$patient->full_name} (" . ($patient->jhc_id ?? '—') . ")\n  Amount: {$amount}\n  Method: " . ($bill->payment_method ?? '—');
                $this->mailWithPdf($to, "[SC] Bill paid — {$bill->bill_no}", $adminBody, $pdfBytes, "Receipt-{$bill->bill_no}.pdf");
            }
        }
        if ($adminWA) {
            // Owner template params: {{1}}=patient name, {{2}}=bill no, {{3}}=amount, {{4}}=method.
            $renderedOwner = "💰 Bill paid\nNo: {$bill->bill_no}\nPatient: {$patient->full_name}\nAmount: {$amount}\nMethod: " . ($bill->payment_method ?? '—');
            if ($this->wa->hasTemplate('owner_bill_paid')) {
                $this->wa->sendTemplateOwnerAlert(
                    'owner_bill_paid',
                    [$patient->full_name ?: 'patient', $bill->bill_no, $amount, (string)($bill->payment_method ?? '—')],
                    $renderedOwner,
                    'owner_bill_paid',
                    $patient,
                );
            } else {
                $this->wa->sendOwnerAlert($renderedOwner, 'owner_bill_paid', $patient);
            }
        }
    }

    // ───────────────────────── Prescription ─────────────────────────

    public function sendPrescription(Prescription $rx): void
    {
        $rx->loadMissing(['patient', 'doctor', 'items.medicine', 'encounter']);
        if (!$rx->patient) return;

        $eventKey = 'prescription_to_patient';
        $sendPatientEmail = $this->prefs->shouldSend($eventKey, 'patient', 'email');
        $sendPatientWA    = $this->prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $sendAdminEmail   = $this->prefs->shouldSend($eventKey, 'admin',   'email');
        $sendAdminWA      = $this->prefs->shouldSend($eventKey, 'admin',   'whatsapp');
        if (!($sendPatientEmail || $sendPatientWA || $sendAdminEmail || $sendAdminWA)) return;

        $self = $this;
        dispatch(function () use ($self, $rx, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA) {
            $self->dispatchPrescription($rx, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA);
        })->afterResponse();
    }

    public function dispatchPrescription(Prescription $rx, bool $patientEmail, bool $patientWA, bool $adminEmail, bool $adminWA): void
    {
        $clinic = setting('clinic_name', config('app.name', 'Demo Clinic'));
        $patient = $rx->patient;
        $doctor  = $rx->doctor->full_name ?? 'Doctor';

        try {
            $pdf = Pdf::loadView('encounters.prescription-print', ['prescription' => $rx]);
            $pdfBytes = $pdf->output();
        } catch (\Throwable $e) {
            Log::warning("PatientNotifier::prescription PDF gen failed for rx #{$rx->id}: " . $e->getMessage());
            $pdfBytes = null;
        }

        $pdfUrl = ($pdfBytes && ($patientWA || $adminWA))
            ? $this->storeForLink($pdfBytes, 'prescriptions', "rx-{$rx->prescription_no}")
            : null;

        $subject = "Your prescription — {$rx->prescription_no} · {$clinic}";
        $bodyEmail = "Dear " . ($patient->full_name ?: 'patient') . ",\n\n"
                   . "Your prescription from {$doctor} is attached.\nPrescription No: {$rx->prescription_no}\n"
                   . "Please follow the directions and return to clinic if you have any questions.\n\n"
                   . "— {$clinic}";

        $bodyWA = "Hi " . ($patient->full_name ?: 'there') . ",\n\n"
                . "Your prescription from {$doctor} is ready ({$rx->prescription_no})."
                . ($pdfUrl ? "\n\nView: {$pdfUrl}" : '')
                . "\n\n— {$clinic}";

        if ($patientEmail && $patient->email) {
            $this->mailWithPdf($patient->email, $subject, $bodyEmail, $pdfBytes, "Prescription-{$rx->prescription_no}.pdf");
        }
        if ($patientWA) {
            $this->wa->sendToPatient($patient, $bodyWA, 'prescription_to_patient');
        }
        if ($adminEmail) {
            $to = config('admin_notify.email');
            if ($to) {
                $this->mailWithPdf($to, "[SC] Rx sent to patient — {$rx->prescription_no}",
                    "Prescription {$rx->prescription_no} delivered to {$patient->full_name}.",
                    $pdfBytes, "Prescription-{$rx->prescription_no}.pdf");
            }
        }
        if ($adminWA) {
            $this->wa->sendOwnerAlert(
                "📋 Rx sent\nNo: {$rx->prescription_no}\nPatient: {$patient->full_name}\nDoctor: {$doctor}",
                'owner_prescription_sent', $patient
            );
        }
    }

    // ───────────────────────── Echo report ─────────────────────────

    /**
     * Owner-triggered send (button on echo.show). Generates PDF report,
     * emails patient + admin with attachment, WhatsApps patient + admin
     * with a link. Each channel gated by the echo_report_ready prefs.
     */
    public function sendEchoReport(EchoRecord $echo): array
    {
        $echo->loadMissing(['patient', 'doctor']);
        if (!$echo->patient) return ['ok' => false, 'reason' => 'no_patient'];

        $eventKey = 'echo_report_ready';
        $sendPatientEmail = $this->prefs->shouldSend($eventKey, 'patient', 'email');
        $sendPatientWA    = $this->prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $sendAdminEmail   = $this->prefs->shouldSend($eventKey, 'admin',   'email');
        $sendAdminWA      = $this->prefs->shouldSend($eventKey, 'admin',   'whatsapp');
        if (!($sendPatientEmail || $sendPatientWA || $sendAdminEmail || $sendAdminWA)) {
            return ['ok' => false, 'reason' => 'all_channels_off_in_prefs'];
        }

        $self = $this;
        dispatch(function () use ($self, $echo, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA) {
            $self->dispatchEchoReport($echo, $sendPatientEmail, $sendPatientWA, $sendAdminEmail, $sendAdminWA);
        })->afterResponse();

        return ['ok' => true, 'channels' => array_filter([
            $sendPatientEmail ? 'patient_email' : null,
            $sendPatientWA    ? 'patient_whatsapp' : null,
            $sendAdminEmail   ? 'admin_email' : null,
            $sendAdminWA      ? 'admin_whatsapp' : null,
        ])];
    }

    public function dispatchEchoReport(EchoRecord $echo, bool $patientEmail, bool $patientWA, bool $adminEmail, bool $adminWA): void
    {
        $clinic  = setting('clinic_name', config('app.name', 'Demo Clinic'));
        $patient = $echo->patient;
        $doctor  = $echo->doctor->full_name ?? 'Cardiologist';
        $ef      = $echo->ef_percent ? (str_contains((string) $echo->ef_percent, '%') ? $echo->ef_percent : $echo->ef_percent . '%') : '—';
        $impression = trim((string) $echo->impression) ?: '—';

        // Render the PDF once; reuse across channels.
        try {
            $pdf = Pdf::loadView('echo.print-pdf', ['echo' => $echo]);
            $pdfBytes = $pdf->output();
        } catch (\Throwable $e) {
            Log::warning("PatientNotifier::echo PDF gen failed for echo #{$echo->id}: " . $e->getMessage());
            $pdfBytes = null;
        }

        $pdfUrl = ($pdfBytes && ($patientWA || $adminWA))
            ? $this->storeForLink($pdfBytes, 'echo', "echo-{$echo->record_no}")
            : null;

        $subject = "Echo report — {$echo->record_no} · {$clinic}";

        // ───── Patient channels ─────
        $bodyEmailPatient = "Dear " . ($patient->full_name ?: 'patient') . ",\n\n"
                          . "Your 2D Echocardiography report from {$doctor} is attached.\n"
                          . "Report No: {$echo->record_no}\n"
                          . "Date: " . optional($echo->record_date)->format('d M Y') . "\n"
                          . "EF: {$ef}\n\n"
                          . "Impression: {$impression}\n\n"
                          . "Please contact us if you have any questions.\n\n— {$clinic}";

        $bodyWAPatient = "Hi " . ($patient->full_name ?: 'there') . ",\n\n"
                       . "Your Echo report from {$doctor} is ready ({$echo->record_no}).\nEF: {$ef}"
                       . ($pdfUrl ? "\n\nView report: {$pdfUrl}" : '')
                       . "\n\n— {$clinic}";

        if ($patientEmail && $patient->email) {
            $this->mailWithPdf($patient->email, $subject, $bodyEmailPatient, $pdfBytes, "Echo-{$echo->record_no}.pdf");
        }
        if ($patientWA) {
            $this->wa->sendToPatient($patient, $bodyWAPatient, 'echo_report');
        }

        // ───── Admin channels ─────
        $bodyAdmin = "Echo report sent:\n"
                   . "  No: {$echo->record_no}\n"
                   . "  Patient: {$patient->full_name} (" . ($patient->jhc_id ?? '—') . ")\n"
                   . "  Doctor: {$doctor}\n"
                   . "  EF: {$ef}\n"
                   . "  Impression: {$impression}";

        if ($adminEmail) {
            $to = config('admin_notify.email');
            if ($to) {
                $this->mailWithPdf($to, "[SC] Echo report — {$echo->record_no}", $bodyAdmin, $pdfBytes, "Echo-{$echo->record_no}.pdf");
            }
        }
        if ($adminWA) {
            $waAdmin = "🫀 Echo report\n"
                     . "Patient: {$patient->full_name}\n"
                     . "No: {$echo->record_no}\n"
                     . "EF: {$ef}\n"
                     . "Impression: {$impression}"
                     . ($pdfUrl ? "\nPDF: {$pdfUrl}" : '');
            $this->wa->sendOwnerAlert($waAdmin, 'owner_echo_report', $patient);
        }
    }

    // ───────────────────────── helpers ─────────────────────────

    /**
     * Save a PDF under storage/app/public/notifications/{type}/{slug}-{rand}.pdf
     * so it can be served via the public storage symlink. Returns the absolute URL.
     * The random suffix makes URLs unguessable — only the link recipient can fetch.
     */
    private function storeForLink(string $bytes, string $type, string $slug): ?string
    {
        try {
            $rand = Str::random(16);
            $path = "notifications/{$type}/" . Str::slug($slug) . "-{$rand}.pdf";
            Storage::disk('public')->put($path, $bytes);
            return asset('storage/' . $path);
        } catch (\Throwable $e) {
            Log::warning("PatientNotifier::storeForLink failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a plain-text email with an optional PDF attachment. Errors logged but
     * never rethrown — a mail failure must not break a save.
     */
    private function mailWithPdf(string $to, string $subject, string $body, ?string $pdfBytes, ?string $pdfName): void
    {
        try {
            Mail::raw($body, function ($m) use ($to, $subject, $pdfBytes, $pdfName) {
                $m->to($to)->subject($subject);
                if ($pdfBytes && $pdfName) {
                    $m->attachData($pdfBytes, $pdfName, ['mime' => 'application/pdf']);
                }
            });
        } catch (\Throwable $e) {
            Log::warning("PatientNotifier::mailWithPdf failed to={$to} subject={$subject}: " . $e->getMessage());
        }
    }
}
