<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
        $clinic   = setting('clinic_name', 'Demo Clinic');

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
        $clinic   = setting('clinic_name', 'Demo Clinic');

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
}
