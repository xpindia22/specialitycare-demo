<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\EchoRecord;
use App\Models\LabOrder;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Manual, staff-triggered "send via email" actions — always available
 * regardless of the notification_preferences auto-send toggle, so staff
 * can still hand a document to a patient on demand even when automatic
 * delivery for that document type is switched off at Admin → Notification
 * Preferences.
 */
class EmailDocumentController extends Controller
{
    public function emailPrescription(Request $request, Prescription $prescription)
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'nullable|string|max:200',
            'message' => 'nullable|string|max:1000',
        ]);

        $prescription->load(['patient','doctor','items.medicine','encounter']);
        $pdf = Pdf::loadView('encounters.prescription-print', compact('prescription'));
        $filename = "Prescription-{$prescription->prescription_no}.pdf";
        $clinic   = setting('clinic_name', 'James Heart Care');

        $subject = $data['subject'] ?: "Prescription from {$clinic} — {$prescription->prescription_no}";
        $body    = $data['message'] ?: "Dear {$prescription->patient->full_name},\n\nPlease find your prescription attached. Take medications as prescribed and return to clinic if you have any concerns.\n\n— {$clinic}";

        try {
            Mail::raw($body, function ($m) use ($data, $subject, $pdf, $filename) {
                $m->to($data['to'])->subject($subject);
                $m->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
        return back()->with('success', "Prescription emailed to {$data['to']}.");
    }

    public function emailBill(Request $request, Bill $bill)
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'nullable|string|max:200',
            'message' => 'nullable|string|max:1000',
        ]);

        $bill->load(['patient','items','payments','encounter']);
        $pdf = Pdf::loadView('billing.print', ['bill' => $bill]);
        $filename = "Bill-{$bill->bill_no}.pdf";
        $clinic   = setting('clinic_name', 'James Heart Care');

        $subject = $data['subject'] ?: "Bill from {$clinic} — {$bill->bill_no}";
        $body    = $data['message'] ?: "Dear {$bill->patient->full_name},\n\nPlease find your bill attached.\n\nTotal: ₹" . number_format($bill->total_amount, 2) .
                                       "\nPaid: ₹" . number_format($bill->paid_amount, 2) .
                                       "\nDue: ₹" . number_format($bill->due_amount, 2) .
                                       "\n\n— {$clinic}";

        try {
            Mail::raw($body, function ($m) use ($data, $subject, $pdf, $filename) {
                $m->to($data['to'])->subject($subject);
                $m->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
        return back()->with('success', "Bill emailed to {$data['to']}.");
    }

    public function emailLabReport(Request $request, LabOrder $order)
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'nullable|string|max:200',
            'message' => 'nullable|string|max:1000',
        ]);

        $order->load(['patient', 'doctor', 'items.test', 'items.results']);
        $pdf = Pdf::loadView('lab.report-print', compact('order'));
        $filename = "Lab-Report-{$order->order_no}.pdf";
        $clinic   = setting('clinic_name', 'James Heart Care');

        $subject = $data['subject'] ?: "Lab report from {$clinic} — {$order->order_no}";
        $body    = $data['message'] ?: "Dear {$order->patient->full_name},\n\nPlease find your lab report attached.\n\n— {$clinic}";

        try {
            Mail::raw($body, function ($m) use ($data, $subject, $pdf, $filename) {
                $m->to($data['to'])->subject($subject);
                $m->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
        return back()->with('success', "Lab report emailed to {$data['to']}.");
    }

    public function emailEchoReport(Request $request, EchoRecord $echo)
    {
        $data = $request->validate([
            'to'      => 'required|email',
            'subject' => 'nullable|string|max:200',
            'message' => 'nullable|string|max:1000',
        ]);

        $echo->load(['patient', 'doctor']);
        $pdf = Pdf::loadView('echo.print-pdf', compact('echo'));
        $filename = "Echo-{$echo->record_no}.pdf";
        $clinic   = setting('clinic_name', 'James Heart Care');

        $subject = $data['subject'] ?: "Echo report from {$clinic} — {$echo->record_no}";
        $body    = $data['message'] ?: "Dear {$echo->patient->full_name},\n\nPlease find your 2D Echocardiography report attached.\n\n— {$clinic}";

        try {
            Mail::raw($body, function ($m) use ($data, $subject, $pdf, $filename) {
                $m->to($data['to'])->subject($subject);
                $m->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
        return back()->with('success', "Echo report emailed to {$data['to']}.");
    }
}
