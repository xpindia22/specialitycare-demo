<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundVitals;
use App\Jobs\ProcessInboundLabReport;
use App\Services\LabReportIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Inbound WhatsApp webhook (Gupshup BSP). Patients message their vitals to the
 * clinic number; Gupshup POSTs the message here. We acknowledge with 200
 * immediately (so Gupshup doesn't retry) and do the parse/save/reply work after
 * the response via ProcessInboundVitals.
 *
 * Public route, CSRF-exempt. Guarded by a ?token= that must match
 * services.whatsapp.gupshup.inbound_token (set it in the Gupshup callback URL).
 */
class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, ?string $token = null)
    {
        // A provider may GET the URL to validate it when you save the callback.
        if ($request->isMethod('get')) {
            return response('ok', 200);
        }

        // Feature switches (both off by default until configured).
        $vitalsOn = (bool) config('services.whatsapp.gupshup.inbound_vitals', false);
        $labOn    = (bool) config('services.whatsapp.gupshup.inbound_lab_reports', false);
        if (!$vitalsOn && !$labOn) {
            return response('disabled', 200);
        }

        // Token guard — accepted as a path segment (/inbound/<token>) or ?token=.
        // Only enforced if a token is configured.
        $expected = (string) config('services.whatsapp.gupshup.inbound_token', '');
        $provided = $token ?: (string) $request->query('token', '');
        if ($expected !== '' && $provided !== $expected) {
            Log::warning('Inbound WhatsApp webhook: bad token', ['ip' => $request->ip()]);
            return response('forbidden', 403);
        }

        $data = $request->all();

        // Text messages -> vitals (unchanged behaviour).
        if ($vitalsOn) {
            foreach ($this->extractMessages($data) as [$from, $text]) {
                ProcessInboundVitals::dispatchAfterResponse((string) $from, (string) $text);
            }
        }

        // Document (PDF) messages -> lab-report intake, only from allowlisted staff.
        if ($labOn) {
            foreach ($this->extractDocuments($data) as $doc) {
                if (!LabReportIntake::senderAllowed($doc['from'])) {
                    Log::info('Inbound lab report ignored: sender not allowlisted', ['from' => $doc['from']]);
                    continue;
                }
                ProcessInboundLabReport::dispatchAfterResponse(
                    (string) $doc['from'], (string) ($doc['url'] ?? ''), (string) ($doc['mime'] ?? ''), $doc['name'] ?? null
                );
            }
        }

        return response('ok', 200);
    }

    /**
     * Pull inbound document/file attachments out of the payload.
     * Returns list of ['from','url','mime','name'].  Handles Gupshup native
     * (direct media url) and Meta passthrough (media id — url resolved later).
     */
    private function extractDocuments(array $data): array
    {
        $out = [];

        // (a) Meta / WhatsApp Cloud API shape
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $m) {
                    if (($m['type'] ?? '') !== 'document') continue;
                    $out[] = [
                        // Gupshup forwards the Meta shape but enriches document with a
                        // direct, ready-to-download filemanager.gupshup.io URL. Fall back
                        // to the media id only if no URL is present (pure Meta Cloud API).
                        'from' => $m['from'] ?? null,
                        'url'  => $m['document']['url'] ?? null,
                        'id'   => $m['document']['id'] ?? null,
                        'mime' => $m['document']['mime_type'] ?? '',
                        'name' => $m['document']['filename'] ?? null,
                    ];
                }
            }
        }

        // (b) Gupshup native shape (direct downloadable url)
        if (($data['type'] ?? null) === 'message') {
            $p = $data['payload'] ?? [];
            if (in_array($p['type'] ?? '', ['file', 'document'], true)) {
                $out[] = [
                    'from' => $p['sender']['phone'] ?? ($p['source'] ?? null),
                    'url'  => $p['payload']['url'] ?? null,
                    'mime' => $p['payload']['contentType'] ?? '',
                    'name' => $p['payload']['name'] ?? null,
                ];
            }
        }

        return array_values(array_filter($out, fn ($d) => !empty($d['from'])));
    }

    /**
     * Pull [from, text] pairs out of the webhook payload. Handles:
     *  (a) Meta/Cloud passthrough (what Gupshup forwards here):
     *      object=whatsapp_business_account, entry[].changes[].value.messages[]
     *  (b) Gupshup native v2: type=message, payload{type:text, sender.phone, payload.text}
     */
    private function extractMessages(array $data): array
    {
        $out = [];

        // (a) Meta / WhatsApp Cloud API shape
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $m) {
                    if (($m['type'] ?? '') !== 'text') continue;
                    $from = $m['from'] ?? null;
                    $text = $m['text']['body'] ?? '';
                    if ($from && trim($text) !== '') $out[] = [$from, $text];
                }
            }
        }

        // (b) Gupshup native shape
        if (empty($out) && ($data['type'] ?? null) === 'message') {
            $p = $data['payload'] ?? [];
            if (($p['type'] ?? '') === 'text') {
                $from = $p['sender']['phone'] ?? ($p['source'] ?? null);
                $text = $p['payload']['text'] ?? '';
                if ($from && trim($text) !== '') $out[] = [$from, $text];
            }
        }

        return $out;
    }
}
