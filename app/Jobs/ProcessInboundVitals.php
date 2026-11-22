<?php

namespace App\Jobs;

use App\Services\InboundVitalsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Parses an inbound WhatsApp message into the patient's diaries and sends the
 * confirmation reply. Dispatched with dispatchAfterResponse() from the webhook,
 * so it runs in-process right after the 200 is returned to Gupshup — no queue
 * worker required, and the (a few seconds) Claude parse never delays the ack.
 */
class ProcessInboundVitals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $fromPhone,
        public string $text,
    ) {}

    public function handle(InboundVitalsService $service): void
    {
        if (trim($this->text) === '') {
            // Empty / non-text message: nudge the sender toward text.
            $service->process($this->fromPhone, 'help');
            return;
        }
        try {
            $service->process($this->fromPhone, $this->text);
        } catch (\Throwable $e) {
            Log::error('ProcessInboundVitals failed', ['err' => $e->getMessage(), 'from' => $this->fromPhone]);
        }
    }
}
