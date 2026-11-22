<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\EchoRecord;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Manual, staff-triggered "send via WhatsApp" actions — always available
 * regardless of the notification_preferences auto-send toggle, mirroring
 * EmailDocumentController. Defaults to the patient's own registered number,
 * shown editable in a "to" field (like email) so staff can redirect a send
 * to any number.
 */
class WhatsAppDocumentController extends Controller
{
    public function __construct(private readonly WhatsAppService $wa) {}

    public function sendBill(Request $request, Bill $bill)
    {
        $bill->load(['patient', 'items', 'payments', 'encounter']);
        if (!$bill->patient) return back()->with('error', 'No patient linked to this bill.');

        $clinic = setting('clinic_name', 'James Heart Care');
        $name   = $bill->patient->full_name ?: 'there';
        $paid   = $bill->status === 'Paid';
        $label  = $paid ? 'receipt' : 'bill';
        $total  = '₹' . number_format((float) $bill->total_amount, 2);
        $due    = '₹' . number_format((float) $bill->due_amount, 2);
        $amount = '₹' . number_format((float) $bill->paid_amount, 2);

        $pdfUrl = $this->renderAndStore('billing.print', ['bill' => $bill], 'bills', "bill-{$bill->bill_no}");

        $body = "Hi {$name},\n\n"
              . "Here is your {$label} {$bill->bill_no} for {$total}"
              . ($bill->due_amount > 0 ? " ({$due} due)" : "") . "."
              . ($pdfUrl ? "\n\nView: {$pdfUrl}" : '')
              . "\n\n— {$clinic}";

        // Prefer the approved bill_created/bill_paid template (delivers outside the
        // 24h session window); sendAndRespond falls back to the free-form $body
        // above when no template is configured/approved yet.
        [$templateKey, $templateParams] = $paid
            ? ['bill_paid', [$name, $bill->bill_no, $amount, $clinic]]
            : ['bill_created', [$name, $bill->bill_no, $total, $due, $clinic]];

        return $this->sendAndRespond($bill->patient, $body, 'bill_manual', ucfirst($label), $request->input('phone'), $templateKey, $templateParams);
    }

    public function sendPrescription(Request $request, Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'items.medicine', 'encounter']);
        if (!$prescription->patient) return back()->with('error', 'No patient linked to this prescription.');

        $clinic = setting('clinic_name', 'James Heart Care');
        $name   = $prescription->patient->full_name ?: 'there';
        $doctor = $prescription->doctor->full_name ?? 'Doctor';

        $pdfUrl = $this->renderAndStore('encounters.prescription-print', compact('prescription'), 'prescriptions', "rx-{$prescription->prescription_no}");

        $body = "Hi {$name},\n\n"
              . "Your prescription from {$doctor} is ready ({$prescription->prescription_no})."
              . ($pdfUrl ? "\n\nView: {$pdfUrl}" : '')
              . "\n\n— {$clinic}";

        // Template params: {{1}}=name, {{2}}=prescription no, {{3}}=doctor, {{4}}=clinic.
        return $this->sendAndRespond($prescription->patient, $body, 'prescription_manual', 'Prescription', $request->input('phone'),
            'prescription_ready', [$name, $prescription->prescription_no, $doctor, $clinic]);
    }

    public function sendLabReport(Request $request, LabOrder $order)
    {
        $order->load(['patient', 'doctor', 'items.test', 'items.results']);
        if (!$order->patient) return back()->with('error', 'No patient linked to this lab order.');

        $clinic = setting('clinic_name', 'James Heart Care');
        $name   = $order->patient->full_name ?: 'there';
        $doctor = $order->doctor->full_name ?? 'Doctor';

        $pdfUrl = $this->renderAndStore('lab.report-print', compact('order'), 'lab', "lab-{$order->order_no}");

        $body = "Hi {$name},\n\n"
              . "Your lab report ({$order->order_no}) ordered by {$doctor} is ready."
              . ($pdfUrl ? "\n\nView: {$pdfUrl}" : '')
              . "\n\n— {$clinic}";

        // Template params: {{1}}=name, {{2}}=order no, {{3}}=doctor, {{4}}=clinic.
        return $this->sendAndRespond($order->patient, $body, 'lab_result_manual', 'Lab report', $request->input('phone'),
            'lab_report_ready', [$name, $order->order_no, $doctor, $clinic]);
    }

    public function sendEchoReport(Request $request, EchoRecord $echo)
    {
        $echo->load(['patient', 'doctor']);
        if (!$echo->patient) return back()->with('error', 'No patient linked to this echo record.');

        $clinic = setting('clinic_name', 'James Heart Care');
        $name   = $echo->patient->full_name ?: 'there';
        $doctor = $echo->doctor->full_name ?? 'Cardiologist';
        $ef     = $echo->ef_percent ? rtrim((string) $echo->ef_percent, '%') : '—';

        $pdfUrl = $this->renderAndStore('echo.print-pdf', compact('echo'), 'echo', "echo-{$echo->record_no}");

        $body = "Hi {$name},\n\n"
              . "Your Echo report from {$doctor} is ready ({$echo->record_no})."
              . ($pdfUrl ? "\n\nView report: {$pdfUrl}" : '')
              . "\n\n— {$clinic}";

        // Template params: {{1}}=name, {{2}}=record no, {{3}}=doctor, {{4}}=EF, {{5}}=clinic.
        // echo_report_pdf (same params, DOCUMENT header) delivers the PDF itself in the chat;
        // echo_report_ready has no link in its approved text, so the patient only sees the EF.
        if ($pdfUrl && $this->wa->hasTemplate('echo_report_pdf')) {
            $patient = $echo->patient;
            $file = trim(preg_replace('/[^A-Za-z0-9]+/', '-', trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))), '-') ?: 'patient';
            $file .= '-Echo-' . str_replace(['/', '\\'], '-', $echo->record_no) . '-' . (optional($echo->record_date)->format('d-m-Y') ?: date('d-m-Y')) . '.pdf';
            return $this->sendAndRespond($patient, $body, 'echo_report_manual', 'Echo report (PDF)', $request->input('phone'),
                'echo_report_pdf', [$name, $echo->record_no, $doctor, $ef, $clinic], ['link' => $pdfUrl, 'filename' => $file]);
        }
        return $this->sendAndRespond($echo->patient, $body, 'echo_report_manual', 'Echo report', $request->input('phone'),
            'echo_report_ready', [$name, $echo->record_no, $doctor, $ef, $clinic]);
    }

    /** Render a PDF view and store it publicly, returning a shareable URL. */
    private function renderAndStore(string $view, array $data, string $type, string $slug): ?string
    {
        try {
            $bytes = Pdf::loadView($view, $data)->output();
            $path  = "notifications/{$type}/" . Str::slug($slug) . '-' . Str::random(16) . '.pdf';
            Storage::disk('public')->put($path, $bytes);
            return asset('storage/' . $path);
        } catch (\Throwable $e) {
            Log::warning("WhatsAppDocumentController::renderAndStore failed for {$view}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @param string|null $templateKey    when given and approved (WhatsAppService::hasTemplate),
     *                                    sends via this template instead of free-form $body --
     *                                    delivers outside the 24h session window. Falls back to
     *                                    free-form $body when not configured/approved.
     * @param array       $templateParams positional params matching the template's {{1}}, {{2}}, ...
     * @param array|null  $document       ['link','filename'] for a DOCUMENT-header template
     */
    private function sendAndRespond($patient, string $body, string $kind, string $label, ?string $phone = null, ?string $templateKey = null, array $templateParams = [], ?array $document = null)
    {
        $phone = trim((string) $phone);
        $target = $phone !== '' ? $phone : ($patient->phone ?? $patient->alt_phone);
        if (!$target) {
            return back()->with('error', "{$label} not sent: no phone number given and patient has none on file.");
        }
        $phoneOverride = $phone !== '' ? $phone : null;

        if ($templateKey !== null && $this->wa->hasTemplate($templateKey)) {
            $result = $this->wa->sendTemplateToPatient($patient, $templateKey, $templateParams, $body, $kind, null, $phoneOverride, $document);
        } else {
            $result = $this->wa->sendToPatient($patient, $body, $kind, null, $phoneOverride);
        }
        if ($result['ok'] ?? false) {
            return back()->with('success', "{$label} sent via WhatsApp to {$target}.");
        }

        $reason = $result['skipped'] ?? $result['reason'] ?? 'unknown error';
        return back()->with('error', "{$label} WhatsApp send failed: {$reason}");
    }
}
