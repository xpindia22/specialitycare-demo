<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp messages and audit-logs every send to the whatsapp_messages
 * table. Every entry point goes through send()/sendTemplate() — that's where
 * the module gate, phone normalisation, daily limit, and logging live.
 *
 * Two backends, selected by config('services.whatsapp.provider'):
 *   - 'twilio' : Twilio REST API (sandbox or production sender).
 *   - 'meta'   : Meta WhatsApp Cloud API (graph.facebook.com) — direct.
 *
 * Free-form text (send()) only reaches a patient inside the 24h customer-service
 * window. Business-initiated messages (reminders, etc.) MUST go via
 * sendTemplate() with a Meta-approved template. On the Twilio backend
 * sendTemplate() falls back to sending the rendered body as free-form so the
 * sandbox stays usable for testing.
 *
 * Module-gated by 'notifications'. If disabled the service silently no-ops and
 * returns ['ok'=>false,'skipped'=>'module_disabled']. Never throws — provider
 * errors are logged and returned as ok=false so appointment/billing flows never
 * break because of a messaging hiccup.
 */
class WhatsAppService
{
    public function __construct(private readonly LicenseService $license) {}

    /**
     * Send a free-form WhatsApp message. Only delivered inside the 24h window
     * (or in the Twilio sandbox). Returns ['ok'=>bool, ...].
     */
    public function send(
        ?string $phone,
        string $body,
        string $kind = 'manual',
        ?Patient $patient = null,
        ?int $sentBy = null,
    ): array {
        return $this->dispatch($phone, $body, $kind, $patient, $sentBy, null, []);
    }

    /**
     * Send a business-initiated message via a pre-approved template.
     *
     * @param string $templateKey  internal key resolved against
     *                             config('services.whatsapp.templates') to the
     *                             Meta-approved template name.
     * @param array  $params       ordered body variables ({{1}}, {{2}}, …).
     * @param string $renderedBody human-readable text logged to the audit table
     *                             (and sent verbatim on the Twilio backend).
     * @param array|null $document ['link' => public URL, 'filename' => name] for a
     *                             template approved with a DOCUMENT header (Gupshup):
     *                             the PDF arrives as an attachment in the chat.
     */
    public function sendTemplate(
        ?string $phone,
        string $templateKey,
        array $params,
        string $renderedBody,
        string $kind = 'manual',
        ?Patient $patient = null,
        ?int $sentBy = null,
        ?array $document = null,
    ): array {
        return $this->dispatch($phone, $renderedBody, $kind, $patient, $sentBy, $templateKey, $params, $document);
    }

    /** Convenience wrapper: free-form send to a Patient's primary phone, or to
     *  $phone instead when given (e.g. staff overrode it in a "send to" field).
     *  Auto-CCs the owner with a verbatim copy of the body either way. */
    public function sendToPatient(Patient $patient, string $body, string $kind = 'manual', ?int $sentBy = null, ?string $phone = null): array
    {
        $target = ($phone !== null && trim($phone) !== '') ? trim($phone) : ($patient->phone ?? $patient->alt_phone);
        $result = $this->send($target, $body, $kind, $patient, $sentBy);
        $this->ccOwnerOfPatientMessage($patient, $body, $kind);
        return $result;
    }

    /**
     * Convenience wrapper: template send to a Patient's primary phone.
     * $renderedBody is the human-readable text logged to the audit table (and
     * sent verbatim on the Twilio backend, which has no template concept).
     * Auto-CCs the owner with the rendered body so they see what the patient
     * received.
     */
    public function sendTemplateToPatient(Patient $patient, string $templateKey, array $params, string $renderedBody, string $kind = 'manual', ?int $sentBy = null, ?string $phone = null, ?array $document = null): array
    {
        $target = ($phone !== null && trim($phone) !== '') ? trim($phone) : ($patient->phone ?? $patient->alt_phone);
        $result = $this->sendTemplate($target, $templateKey, $params, $renderedBody, $kind, $patient, $sentBy, $document);
        $this->ccOwnerOfPatientMessage($patient, $renderedBody, $kind, $templateKey, $params, $document);
        return $result;
    }

    /**
     * Auto-CC the owner whenever a WhatsApp message is sent to a patient.
     * Free-form send, depends on the owner's 24h session window being open.
     * No-ops silently if owner_phone is unset or equals the patient's phone
     * (avoids double-send during testing).
     */
    private function ccOwnerOfPatientMessage(Patient $patient, string $body, string $originalKind, ?string $templateKey = null, array $params = [], ?array $document = null): void
    {
        $owner = (string) config('services.whatsapp.owner_phone');
        if ($owner === '') return;
        // Don't CC owner_* sends (already owner-facing)
        if (str_starts_with($originalKind, 'owner_') || str_starts_with($originalKind, 'cc_')) return;
        $patientPhone = (string) ($patient->phone ?? $patient->alt_phone ?? '');
        // Compare last 10 digits so +91/0/no-prefix variants of the same
        // line are recognised as identical (skip self-CC during testing).
        $tail = fn ($p) => substr(preg_replace('/\D+/', '', (string) $p), -10);
        if ($tail($owner) !== '' && $tail($owner) === $tail($patientPhone)) return;
        $label = $patient->full_name ?: 'patient';
        if (!empty($patient->jhc_id)) $label .= " ({$patient->jhc_id})";
        $ccBody = "📋 CC — sent to {$label}\n\n" . $body;

        // Reliable path: when the patient message used an approved template, send
        // the owner the SAME template with the same params. Template messages are
        // business-initiated and ignore the 24h free-form window, so the owner
        // ALWAYS receives the copy — no need to message the clinic number daily.
        // The owner sees the patient-formatted template; the audit log keeps the
        // human-readable "CC — sent to <patient>" body.
        if ($templateKey !== null && $this->hasTemplate($templateKey)) {
            $this->sendTemplate($owner, $templateKey, $params, $ccBody, "cc_{$originalKind}", $patient, null, $document);
            return;
        }

        // Fallback for free-form patient messages (e.g. manual chats): there is no
        // template, so this copy is free-form and only lands inside the owner's
        // 24h window.
        $this->send($owner, $ccBody, "cc_{$originalKind}", $patient);
    }

    /**
     * Send a short owner-facing alert copy mirroring patient-facing messages so
     * the clinic owner has real-time visibility. No-ops silently if owner_phone
     * is unset (desired in dev). Always free-form — the owner has an open
     * session with the clinic number so the 24h window doesn't bite here.
     */
    public function sendOwnerAlert(string $body, string $kind = 'owner_alert', ?Patient $patient = null): array
    {
        $owner = (string) config('services.whatsapp.owner_phone');
        if ($owner === '') {
            return ['ok' => false, 'skipped' => 'owner_phone_not_configured'];
        }
        return $this->send($owner, $body, $kind, $patient);
    }

    /**
     * Template-backed owner alert — used for business-initiated owner notifications
     * (bill_created, bill_paid, etc.) where the owner's 24h session may be closed.
     * The owner's number receives the Meta-approved template with $params, while
     * the audit log stores $renderedBody for human readability.
     */
    public function sendTemplateOwnerAlert(string $templateKey, array $params, string $renderedBody, string $kind = 'owner_alert', ?Patient $patient = null): array
    {
        $owner = (string) config('services.whatsapp.owner_phone');
        if ($owner === '') {
            return ['ok' => false, 'skipped' => 'owner_phone_not_configured'];
        }
        return $this->sendTemplate($owner, $templateKey, $params, $renderedBody, $kind, $patient);
    }

    /**
     * Does the active provider have a (non-empty) approved template ID/name
     * configured for $templateKey? Used by callers to decide between template
     * send and free-form fallback.
     */
    public function hasTemplate(string $templateKey): bool
    {
        $provider = $this->provider();
        // For Meta: the value is the template *name* (string). For Gupshup: a UUID.
        $val = $provider === 'gupshup'
            ? config("services.whatsapp.gupshup.templates.{$templateKey}")
            : config("services.whatsapp.templates.{$templateKey}");
        return is_string($val) && $val !== '';
    }

    public function isEnabled(): bool
    {
        return $this->license->isEnabled('notifications');
    }

    /** Active backend: 'twilio' | 'meta'. */
    public function provider(): string
    {
        return (string) config('services.whatsapp.provider', 'twilio');
    }

    // ---------------------------------------------------------------------
    // Shared pipeline
    // ---------------------------------------------------------------------

    /**
     * Gate → normalise → limit → log → provider send → update log. $templateKey
     * null means a free-form send; otherwise a template send (meta) or rendered
     * free-form fallback (twilio).
     */
    private function dispatch(
        ?string $phone,
        string $body,
        string $kind,
        ?Patient $patient,
        ?int $sentBy,
        ?string $templateKey,
        array $params,
        ?array $document = null,
    ): array {
        // Per-host env kill switch (default ON). WHATSAPP_ENABLED=false on a
        // dev/home box stops ALL outbound WhatsApp there so test bills/appts
        // never reach real patients. Env isn't synced, so nvme stays unaffected.
        if (!config('services.whatsapp.enabled', true)) {
            return ['ok' => false, 'skipped' => 'wa_disabled_env'];
        }

        // Master kill switch — toggled from /admin/notifications/whatsapp.
        // Overrides every per-event preference. Set when the owner wants to
        // pause ALL outbound WhatsApp (template + free-form, patient + owner).
        // Does not affect the bash backup cron, which calls Gupshup directly.
        if (setting('wa_master_off', '0') === '1') {
            return ['ok' => false, 'skipped' => 'wa_master_off'];
        }

        if (!$this->license->isEnabled('notifications')) {
            return ['ok' => false, 'skipped' => 'module_disabled'];
        }

        $provider = $this->provider();

        $to = $this->normalisePhone($phone);
        if ($to === null) {
            // Log a failed row so admins can spot data-quality issues from the
            // Notifications page instead of the message just vanishing.
            WhatsAppMessage::create([
                'patient_id' => $patient?->id,
                'to_phone'   => substr((string) ($phone ?? ''), 0, 32),
                'kind'       => $kind,
                'provider'   => $provider,
                'body'       => $body,
                'status'     => 'failed',
                'error'      => 'invalid_phone',
                'sent_by'    => $sentBy,
            ]);
            return ['ok' => false, 'skipped' => 'invalid_phone'];
        }

        if (($cfgError = $this->providerConfigError($provider)) !== null) {
            return ['ok' => false, 'skipped' => $cfgError];
        }

        $dailyLimit = (int) config('services.whatsapp.daily_limit', 0);
        if ($dailyLimit > 0) {
            $todaySent = WhatsAppMessage::whereDate('created_at', today())
                ->whereIn('status', ['sent', 'queued'])
                ->count();
            if ($todaySent >= $dailyLimit) {
                Log::warning('WhatsApp daily limit reached', ['limit' => $dailyLimit, 'sent_today' => $todaySent]);
                return ['ok' => false, 'skipped' => 'daily_limit_reached'];
            }
        }

        $log = WhatsAppMessage::create([
            'patient_id' => $patient?->id,
            'to_phone'   => $to,
            'kind'       => $kind,
            'provider'   => $provider,
            'body'       => $body . ($document ? "\n[PDF attached: {$document['filename']}]" : ''),
            'status'     => 'queued',
            'sent_by'    => $sentBy,
        ]);

        return match ($provider) {
            'meta'    => $this->sendViaMeta($log, $to, $body, $templateKey, $params),
            'gupshup' => $this->sendViaGupshup($log, $to, $body, $templateKey, $params, $document),
            default   => $this->sendViaTwilio($log, $to, $body),
        };
    }

    /** Returns a skip-reason string if the active provider is misconfigured, else null. */
    private function providerConfigError(string $provider): ?string
    {
        if ($provider === 'meta') {
            $token = (string) config('services.whatsapp.meta.token');
            $pnid  = (string) config('services.whatsapp.meta.phone_number_id');
            return ($token === '' || $pnid === '') ? 'meta_not_configured' : null;
        }

        if ($provider === 'gupshup') {
            $apiKey = (string) config('services.whatsapp.gupshup.api_key');
            $source = (string) config('services.whatsapp.gupshup.source');
            return ($apiKey === '' || $source === '') ? 'gupshup_not_configured' : null;
        }

        $sid   = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from  = (string) config('services.twilio.whatsapp_from');
        return ($sid === '' || $token === '' || $from === '') ? 'twilio_not_configured' : null;
    }

    // ---------------------------------------------------------------------
    // Twilio backend
    // ---------------------------------------------------------------------

    private function sendViaTwilio(WhatsAppMessage $log, string $to, string $body): array
    {
        $sid   = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from  = (string) config('services.twilio.whatsapp_from');

        try {
            $resp = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => "whatsapp:{$to}",
                    'Body' => $body,
                ]);

            if ($resp->successful()) {
                $msgId = $resp->json('sid');
                $log->update([
                    'status'              => 'sent',
                    'twilio_sid'          => $msgId,
                    'provider_message_id' => $msgId,
                    'sent_at'             => now(),
                ]);
                return ['ok' => true, 'provider' => 'twilio', 'message_id' => $msgId, 'log_id' => $log->id];
            }

            $err = $resp->json('message') ?: $resp->body();
            $log->update(['status' => 'failed', 'error' => substr((string) $err, 0, 1000)]);
            Log::warning('WhatsApp send failed (twilio)', ['to' => $to, 'kind' => $log->kind, 'err' => $err]);
            return ['ok' => false, 'provider' => 'twilio', 'error' => $err, 'log_id' => $log->id];
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 1000)]);
            Log::error('WhatsApp send exception (twilio)', ['to' => $to, 'kind' => $log->kind, 'err' => $e->getMessage()]);
            return ['ok' => false, 'provider' => 'twilio', 'error' => $e->getMessage(), 'log_id' => $log->id];
        }
    }

    // ---------------------------------------------------------------------
    // Meta WhatsApp Cloud API backend
    // ---------------------------------------------------------------------

    /**
     * @param ?string $templateKey null => free-form text; otherwise resolves to
     *                             a Meta-approved template name + $params body vars.
     */
    private function sendViaMeta(WhatsAppMessage $log, string $to, string $body, ?string $templateKey, array $params): array
    {
        $token   = (string) config('services.whatsapp.meta.token');
        $pnid    = (string) config('services.whatsapp.meta.phone_number_id');
        $version = (string) config('services.whatsapp.meta.api_version', 'v21.0');

        // Meta wants the number in international format without the leading '+'.
        $toMeta = ltrim($to, '+');

        if ($templateKey !== null) {
            $name = (string) config("services.whatsapp.templates.{$templateKey}", $templateKey);
            $lang = (string) config('services.whatsapp.meta.lang', 'en');
            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $toMeta,
                'type'              => 'template',
                'template'          => [
                    'name'     => $name,
                    'language' => ['code' => $lang],
                ],
            ];
            if (!empty($params)) {
                $payload['template']['components'] = [[
                    'type'       => 'body',
                    'parameters' => array_map(
                        fn ($p) => ['type' => 'text', 'text' => (string) $p],
                        array_values($params),
                    ),
                ]];
            }
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $toMeta,
                'type'              => 'text',
                'text'              => ['body' => $body, 'preview_url' => false],
            ];
        }

        try {
            $resp = Http::withToken($token)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$version}/{$pnid}/messages", $payload);

            if ($resp->successful()) {
                $msgId = $resp->json('messages.0.id');
                $log->update([
                    'status'              => 'sent',
                    'provider_message_id' => $msgId,
                    'sent_at'             => now(),
                ]);
                return ['ok' => true, 'provider' => 'meta', 'message_id' => $msgId, 'log_id' => $log->id];
            }

            $err = $resp->json('error.message') ?: $resp->body();
            $log->update(['status' => 'failed', 'error' => substr((string) $err, 0, 1000)]);
            Log::warning('WhatsApp send failed (meta)', ['to' => $to, 'kind' => $log->kind, 'err' => $err]);
            return ['ok' => false, 'provider' => 'meta', 'error' => $err, 'log_id' => $log->id];
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 1000)]);
            Log::error('WhatsApp send exception (meta)', ['to' => $to, 'kind' => $log->kind, 'err' => $e->getMessage()]);
            return ['ok' => false, 'provider' => 'meta', 'error' => $e->getMessage(), 'log_id' => $log->id];
        }
    }

    // ---------------------------------------------------------------------
    // Gupshup BSP backend
    // ---------------------------------------------------------------------

    /**
     * @param ?string $templateKey null => free-form session text; otherwise
     *                             resolves to a Gupshup template *ID* + $params.
     * @param ?array  $document    ['link','filename'] header media for a DOCUMENT template.
     */
    private function sendViaGupshup(WhatsAppMessage $log, string $to, string $body, ?string $templateKey, array $params, ?array $document = null): array
    {
        $apiKey  = (string) config('services.whatsapp.gupshup.api_key');
        $source  = ltrim((string) config('services.whatsapp.gupshup.source'), '+');
        $appName = (string) config('services.whatsapp.gupshup.app_name');
        $dest    = ltrim($to, '+');

        if ($templateKey !== null) {
            $endpoint = 'https://api.gupshup.io/wa/api/v1/template/msg';
            $form = [
                'source'      => $source,
                'destination' => $dest,
                'src.name'    => $appName,
                'template'    => json_encode([
                    'id'     => (string) config("services.whatsapp.gupshup.templates.{$templateKey}", ''),
                    'params' => array_map(fn ($p) => (string) $p, array_values($params)),
                ]),
            ];
            if ($document) {
                $form['message'] = json_encode(['type' => 'document', 'document' => [
                    'link' => $document['link'], 'filename' => $document['filename'],
                ]]);
            }
        } else {
            $endpoint = 'https://api.gupshup.io/wa/api/v1/msg';
            $form = [
                'channel'     => 'whatsapp',
                'source'      => $source,
                'destination' => $dest,
                'src.name'    => $appName,
                'message'     => json_encode(['type' => 'text', 'text' => $body]),
            ];
        }

        try {
            $resp = Http::asForm()
                ->withHeaders(['apikey' => $apiKey])
                ->timeout(15)
                ->post($endpoint, $form);

            $status = $resp->json('status');
            if ($resp->successful() && in_array($status, ['submitted', 'success'], true)) {
                $msgId = $resp->json('messageId');
                $log->update([
                    'status'              => 'sent',
                    'provider_message_id' => $msgId,
                    'sent_at'             => now(),
                ]);
                return ['ok' => true, 'provider' => 'gupshup', 'message_id' => $msgId, 'log_id' => $log->id];
            }

            $err = $resp->json('message') ?: $resp->body();
            $log->update(['status' => 'failed', 'error' => substr((string) $err, 0, 1000)]);
            Log::warning('WhatsApp send failed (gupshup)', ['to' => $to, 'kind' => $log->kind, 'err' => $err]);
            return ['ok' => false, 'provider' => 'gupshup', 'error' => $err, 'log_id' => $log->id];
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 1000)]);
            Log::error('WhatsApp send exception (gupshup)', ['to' => $to, 'kind' => $log->kind, 'err' => $e->getMessage()]);
            return ['ok' => false, 'provider' => 'gupshup', 'error' => $e->getMessage(), 'log_id' => $log->id];
        }
    }

    /**
     * Convert a user-entered phone to E.164. Accepts:
     *   "9876543210"          -> "+919876543210" (uses whatsapp.default_country)
     *   "+91 98765-43210"     -> "+919876543210"
     *   "0091-9876543210"     -> "+919876543210"
     *   "whatsapp:+91..."     -> strips prefix
     * Returns null if the result isn't 8-15 digits (E.164 spec).
     */
    public function normalisePhone(?string $raw): ?string
    {
        if ($raw === null) return null;
        $raw = trim($raw);
        if ($raw === '') return null;

        $raw = preg_replace('/^whatsapp:/i', '', $raw);
        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '' || $digits === null) return null;

        // 0091... -> 91...
        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);

        // Bare local number -> prefix default country code.
        $cc = (string) config('services.whatsapp.default_country', '91');
        if (!$hasPlus && strlen($digits) === 10) {
            $digits = $cc . $digits;
        } elseif (!$hasPlus) {
            // Without a + prefix and not a 10-digit local mobile, treat as
            // garbage rather than blindly E.164-formatting it. Catches the
            // legacy "10000000" / "0000000000" rows in older imports.
            return null;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) return null;
        return '+' . $digits;
    }
}
