<?php

namespace App\Jobs;

use App\Services\LabReportIntake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Downloads a lab-report PDF that arrived on WhatsApp and files it in the
 * Pending Lab Reports queue (parsed, awaiting a human to pick the patient).
 * Dispatched afterResponse() from the webhook so the 200 ack is never delayed.
 */
class ProcessInboundLabReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $fromPhone,
        public string $url,
        public string $mime = '',
        public ?string $name = null,
    ) {}

    public function handle(LabReportIntake $intake): void
    {
        // Re-check the allowlist in the worker too (defence in depth).
        if (!LabReportIntake::senderAllowed($this->fromPhone)) {
            Log::info('Inbound lab report dropped: sender not allowlisted', ['from' => $this->fromPhone]);
            return;
        }
        if ($this->url === '') {
            Log::warning('Inbound lab report: no media URL (Meta media-id needs Graph resolution)', ['from' => $this->fromPhone]);
            return;
        }

        try {
            $resp = Http::timeout(30)->get($this->url);
            if (!$resp->ok()) {
                Log::warning('Inbound lab report: media download failed', ['from' => $this->fromPhone, 'status' => $resp->status()]);
                return;
            }
            $body = $resp->body();

            // Must look like a PDF (by mime or magic bytes).
            $isPdf = str_contains(strtolower($this->mime), 'pdf') || str_starts_with($body, '%PDF');
            if (!$isPdf) {
                Log::info('Inbound lab report ignored: not a PDF', ['from' => $this->fromPhone, 'mime' => $this->mime]);
                return;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'inlab_') . '.pdf';
            file_put_contents($tmp, $body);
            $intake->intake($tmp, $this->fromPhone, 'whatsapp');
            @unlink($tmp);
        } catch (\Throwable $e) {
            Log::error('ProcessInboundLabReport failed', ['err' => $e->getMessage(), 'from' => $this->fromPhone]);
        }
    }
}
