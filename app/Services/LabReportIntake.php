<?php

namespace App\Services;

use App\Models\PendingLabReport;
use Illuminate\Support\Facades\Storage;

/**
 * Takes a lab-report PDF that arrived out-of-band (WhatsApp/Gupshup, or a manual
 * drop), parses it, and files it in the Pending Lab Reports queue for a human to
 * match to a patient and confirm. Never writes to an encounter by itself.
 */
class LabReportIntake
{
    public function __construct(private LabReportParser $parser) {}

    /** Is this WhatsApp sender allowed to submit reports? */
    public static function senderAllowed(?string $sender): bool
    {
        $digits = preg_replace('/\D/', '', (string) $sender);
        $allow  = (array) config('labreports.allowed_senders', []);
        return $digits !== '' && in_array($digits, $allow, true);
    }

    /**
     * @param string $localPdfPath absolute path to a PDF already on disk
     * @param string $channel      whatsapp|upload|manual
     */
    public function intake(string $localPdfPath, ?string $sender, string $channel = 'whatsapp'): PendingLabReport
    {
        // Keep a copy on the app's storage disk.
        $stored = 'lab-reports/' . date('Y/m') . '/' . uniqid('rep_') . '.pdf';
        Storage::put($stored, file_get_contents($localPdfPath));

        $parsed = $this->parser->parse(Storage::path($stored));

        return PendingLabReport::create([
            'sender'        => $sender ? preg_replace('/\D/', '', $sender) : null,
            'channel'       => $channel,
            'report_path'   => $stored,
            'source'        => $parsed['source'] ?? 'text',
            'report_name'   => $parsed['header']['name'] ?? null,
            'reg_id'        => $parsed['header']['reg_id'] ?? null,
            'sample_date'   => $parsed['header']['sample_date'] ?? null,
            'parsed'        => $parsed,
            'analyte_count' => count($parsed['analytes'] ?? []),
            'status'        => 'Pending',
        ]);
    }
}
