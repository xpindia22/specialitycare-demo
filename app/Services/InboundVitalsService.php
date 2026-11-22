<?php

namespace App\Services;

use App\Models\BpLog;
use App\Models\GlucoseLog;
use App\Models\HealthMetric;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

/**
 * Takes an inbound WhatsApp message (sender phone + text), matches it to a
 * patient, parses the vitals, writes them into the diaries (GlucoseLog, BpLog,
 * HealthMetric) with range validation, and sends a confirmation reply.
 *
 * Patient-self-reported, so recorded_by is null. Everything is best-effort and
 * never throws — a bad message just yields a friendly reply.
 */
class InboundVitalsService
{
    public function __construct(
        private readonly VitalsParser $parser,
        private readonly WhatsAppService $whatsapp,
    ) {}

    public function process(string $fromPhone, string $text): array
    {
        $patient = $this->matchPatient($fromPhone);

        if (!$patient) {
            $clinic = setting('clinic_name', 'the clinic');
            $reply = "⚠️ This WhatsApp number isn't linked to a patient record at {$clinic} — "
                . "please contact us to register your number, then you can send your readings here.";
            $this->reply($fromPhone, $reply, null);
            return ['matched' => false, 'saved' => [], 'reply' => $reply];
        }

        $parsed   = $this->parser->parse($text);
        // Backdate to the time the patient says they measured, if given (else now).
        $now      = $this->resolveLoggedAt($parsed['logged_at'] ?? null);
        $saved    = [];
        $rejected = [];

        // Pull pulse out so it rides along with a BP reading in one Bp row.
        $bp    = null;
        $pulse = null;
        $others = [];
        foreach ($parsed['readings'] as $r) {
            $kind = $r['kind'] ?? '';
            if ($kind === 'bp' && $bp === null) $bp = $r;
            elseif (($kind === 'pulse' || $kind === 'heart_rate') && $pulse === null) $pulse = (int) round((float) ($r['value'] ?? 0));
            else $others[] = $r;
        }

        // ── BP (+ pulse) ───────────────────────────────────────────────
        if ($bp) {
            $sys = (int) round((float) ($bp['systolic'] ?? 0));
            $dia = (int) round((float) ($bp['diastolic'] ?? 0));
            if ($sys >= 50 && $sys <= 300 && $dia >= 30 && $dia <= 200 && $sys > $dia) {
                $row = ['patient_id' => $patient->id, 'logged_at' => $now, 'systolic' => $sys, 'diastolic' => $dia];
                if ($pulse !== null && $pulse >= 20 && $pulse <= 300) { $row['pulse'] = $pulse; $pulse = null; }
                BpLog::create($row);
                $c = BpLog::classify($sys, $dia);
                $saved[] = "🩺 BP: {$sys}/{$dia} mmHg" . (isset($row['pulse']) ? ", pulse {$row['pulse']}" : '')
                    . ($c['status'] !== 'none' ? " ({$c['label']})" : '');
            } else {
                $rejected[] = "BP {$sys}/{$dia}";
            }
        }
        // Pulse on its own (no BP in the message)
        if ($pulse !== null) {
            if ($pulse >= 20 && $pulse <= 300) {
                BpLog::create(['patient_id' => $patient->id, 'logged_at' => $now, 'pulse' => $pulse]);
                $saved[] = "💓 Pulse: {$pulse} /min";
            } else {
                $rejected[] = "pulse {$pulse}";
            }
        }

        // ── everything else ────────────────────────────────────────────
        foreach ($others as $r) {
            $kind = $r['kind'];
            $val  = (float) ($r['value'] ?? 0);

            if ($kind === 'glucose') {
                if ($val > 0 && $val <= 1000) {
                    $type = in_array($r['glucose_type'] ?? null, GlucoseLog::MGDL_TYPES, true) ? $r['glucose_type'] : 'random';
                    GlucoseLog::create(['patient_id' => $patient->id, 'logged_at' => $now, 'reading_type' => $type, 'value' => $val]);
                    $short = GlucoseLog::TYPES[$type]['short'] ?? 'Sugar';
                    $saved[] = "🩸 Blood sugar ({$short}): " . $this->num($val) . " mg/dL";
                } else $rejected[] = "sugar " . $this->num($val);
                continue;
            }

            // HealthMetric-backed vitals
            $metric = match ($kind) {
                'temperature'      => 'temperature',
                'respiratory_rate' => 'respiratory_rate',
                'spo2'             => 'spo2',
                'weight'           => 'weight',
                default            => null,
            };
            if (!$metric) continue;

            if ($metric === 'temperature') {
                // Normalise to °F (store unit is °F).
                if (($r['unit'] ?? 'F') === 'C' || $val < 45) $val = round($val * 9 / 5 + 32, 1);
            }

            $cfg = HealthMetric::METRICS[$metric];
            if ($val >= $cfg['min'] && $val <= $cfg['max']) {
                HealthMetric::create(['patient_id' => $patient->id, 'logged_at' => $now, 'metric' => $metric, 'value' => $val]);
                $emoji = ['temperature' => '🌡️', 'respiratory_rate' => '🫁', 'spo2' => '🫁', 'weight' => '⚖️'][$metric] ?? '•';
                $saved[] = "{$emoji} {$cfg['label']}: " . $this->num($val) . " {$cfg['unit']}";
            } else {
                $rejected[] = "{$cfg['label']} " . $this->num($val);
            }
        }

        $reply = $this->buildReply($patient, $saved, $rejected, $parsed['unparsed'] ?? null, $now);
        $this->reply($fromPhone, $reply, $patient);

        Log::info('Inbound vitals processed', [
            'patient_id' => $patient->id, 'saved' => count($saved), 'rejected' => count($rejected), 'via' => $parsed['via'],
        ]);

        return ['matched' => true, 'patient_id' => $patient->id, 'saved' => $saved, 'rejected' => $rejected, 'via' => $parsed['via'], 'reply' => $reply];
    }

    private function buildReply(Patient $patient, array $saved, array $rejected, ?string $unparsed, $now): string
    {
        $clinic = setting('clinic_name', 'Speciality Care');

        if (empty($saved)) {
            return "⚠️ Sorry, I couldn't read any readings from your message.\n\n"
                . "Examples you can send:\n"
                . "• Sugar 120 fasting\n• BP 130/85 P72\n• Temp 99.2\n• SpO2 97\n• Weight 70\n• Resp 18\n"
                . "Tip: add a time to backdate — e.g. \"Sugar 95 fasting at 7am\".\n\n— {$clinic}";
        }

        $name  = $patient->full_name ?: 'you';
        $jhc   = $patient->jhc_id ? " ({$patient->jhc_id})" : '';
        $lines = "✅ Recorded for {$name}{$jhc}:\n" . implode("\n", array_map(fn ($l) => "  {$l}", $saved));
        $backdated = $now->lt(now()->subMinutes(90));
        $lines .= "\n🕘 " . $now->format('d M Y, h:i A') . ($backdated ? ' (as you reported)' : '');
        if ($rejected) $lines .= "\n⚠️ Skipped (out of range): " . implode(', ', $rejected);
        if ($unparsed) $lines .= "\nℹ️ Couldn't read: \"" . trim($unparsed) . "\"";
        $lines .= "\n\nReviewed by your doctor at the clinic. — {$clinic}";
        return $lines;
    }

    /** Use the patient-stated measurement time if sane, else now (never future / >30d old). */
    private function resolveLoggedAt(?string $raw): \Illuminate\Support\Carbon
    {
        if (!$raw) return now();
        try {
            $t = \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable $e) {
            return now();
        }
        $now = now();
        if ($t->greaterThan($now->copy()->addMinutes(5))) return $now;
        if ($t->lessThan($now->copy()->subDays(30)))      return $now;
        return $t;
    }

    /** Match the sender's number to a patient by the last 10 digits of phone/alt_phone. */
    private function matchPatient(string $fromPhone): ?Patient
    {
        $tail = substr(preg_replace('/\D+/', '', $fromPhone), -10);
        if (strlen($tail) < 10) return null;

        $strip = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s,' ',''),'-',''),'+',''),'(',''),')',''),'.','')";
        $phoneExpr = sprintf($strip, 'phone');
        $altExpr   = sprintf($strip, 'alt_phone');

        return Patient::whereRaw("$phoneExpr LIKE ?", ["%{$tail}"])
            ->orWhereRaw("$altExpr LIKE ?", ["%{$tail}"])
            ->orderByDesc('is_active')
            ->first();
    }

    private function reply(string $toPhone, string $body, ?Patient $patient): void
    {
        // Gupshup/Meta deliver the sender as full international digits without a
        // leading '+' (e.g. 919242041215). WhatsAppService::normalisePhone() only
        // auto-prefixes bare 10-digit locals, so add the '+' for longer numbers.
        $to     = trim($toPhone);
        $digits = preg_replace('/\D+/', '', $to);
        if (!str_starts_with($to, '+') && strlen($digits) > 10) {
            $to = '+' . $digits;
        }
        try {
            $this->whatsapp->send($to, $body, 'inbound_vitals_reply', $patient);
        } catch (\Throwable $e) {
            Log::warning('Inbound vitals reply failed', ['err' => $e->getMessage()]);
        }
    }

    private function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }
}
