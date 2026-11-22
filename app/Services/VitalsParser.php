<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Turns a free-form WhatsApp message ("sugar 145 fasting, BP 130/85 p72, temp 99.2")
 * into a flat list of structured vital readings. Primary path is Claude (handles
 * messy natural language + units); a regex fallback covers the common cases when
 * the AI key is missing or the call fails, so the feature still works offline.
 *
 * Each reading is one of:
 *   ['kind'=>'glucose','value'=>145,'glucose_type'=>'fasting'|null]
 *   ['kind'=>'bp','systolic'=>130,'diastolic'=>85]
 *   ['kind'=>'pulse','value'=>72]
 *   ['kind'=>'temperature','value'=>99.2,'unit'=>'F'|'C']
 *   ['kind'=>'respiratory_rate','value'=>18]
 *   ['kind'=>'spo2','value'=>97]
 *   ['kind'=>'weight','value'=>72]
 *   ['kind'=>'heart_rate','value'=>80]   (treated like pulse downstream)
 *
 * Returns ['readings'=>[...], 'unparsed'=>?string, 'via'=>'ai'|'regex'|'none'].
 */
class VitalsParser
{
    public const GLUCOSE_TYPES = ['fasting', 'pre_lunch', 'pre_dinner', 'post_prandial', 'bedtime', 'random'];

    public function parse(string $text): array
    {
        $text = trim($text);
        if ($text === '') return ['readings' => [], 'unparsed' => null, 'logged_at' => null, 'via' => 'none'];

        $ai = $this->parseWithAi($text);
        if ($ai !== null && !empty($ai['readings'])) {
            return $ai + ['via' => 'ai'];
        }

        $rx = $this->parseWithRegex($text);
        return $rx + ['via' => $rx['readings'] ? 'regex' : 'none'];
    }

    // ── Claude ────────────────────────────────────────────────────────────
    private function parseWithAi(string $text): ?array
    {
        $key = env('ANTHROPIC_API_KEY') ?: $this->envKey('ANTHROPIC_API_KEY');
        if (!$key) return null;

        $types = implode(' | ', self::GLUCOSE_TYPES);
        $now   = now()->format('Y-m-d H:i');
        $system = implode("\n", [
            'You extract patient-reported vital signs from a short WhatsApp message and return ONLY one JSON object, no prose, no code fences.',
            'Shape: {"readings":[...],"unparsed":string|null,"logged_at":string|null}. Each reading is exactly one of:',
            '{"kind":"glucose","value":number,"glucose_type":"'.$types.'"|null}',
            '{"kind":"bp","systolic":number,"diastolic":number}',
            '{"kind":"pulse","value":number}',
            '{"kind":"temperature","value":number,"unit":"F"|"C"}',
            '{"kind":"respiratory_rate","value":number}',
            '{"kind":"spo2","value":number}',
            '{"kind":"weight","value":number}',
            'Rules: "sugar"/"BS"/"FBS"/"PP" = glucose (FBS/fasting → glucose_type "fasting"; PP/after-food → "post_prandial"). BP like 130/85 → kind bp. "p"/"pulse"/"HR" number → pulse. temp default unit F unless °C/C stated or value < 45. RR/resp/breathing → respiratory_rate. SpO2/oxygen → spo2. Use null glucose_type if unknown. Put any text you could NOT interpret as a reading into "unparsed" (else null). Never invent values.',
            "Time: the message arrived at {$now} (clinic local time). If it says WHEN a reading was taken (e.g. \"at 7am\", \"this morning\", \"7:30 last night\", \"yesterday 10pm\"), set \"logged_at\" to that moment as \"YYYY-MM-DD HH:MM\" (24h) relative to that now; a bare time with no day = today, unless that would be in the future, then yesterday. If no time is mentioned, logged_at = null. Never set logged_at in the future.",
        ]);

        try {
            $resp = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 800,
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => $text]],
            ]);
        } catch (\Throwable $e) {
            return null;
        }
        if (!$resp->successful()) return null;

        $out = collect($resp->json('content', []))->where('type', 'text')->pluck('text')->implode('');
        $json = $this->extractJson($out);
        if (!is_array($json) || !isset($json['readings']) || !is_array($json['readings'])) return null;

        // Keep only well-formed readings.
        $clean = [];
        foreach ($json['readings'] as $r) {
            if (!is_array($r) || empty($r['kind'])) continue;
            $clean[] = $r;
        }
        $when = $json['logged_at'] ?? null;
        return ['readings' => $clean, 'unparsed' => $json['unparsed'] ?? null, 'logged_at' => is_string($when) ? $when : null];
    }

    // ── Regex fallback (common cases only) ────────────────────────────────
    private function parseWithRegex(string $text): array
    {
        $t = ' ' . strtolower($text) . ' ';
        $readings = [];

        if (preg_match('/\b(\d{2,3})\s*\/\s*(\d{2,3})\b/', $t, $m)) {
            $readings[] = ['kind' => 'bp', 'systolic' => (int) $m[1], 'diastolic' => (int) $m[2]];
        }
        if (preg_match('/\b(?:sugar|bs|fbs|glucose|rbs|ppbs|pp)\D{0,6}(\d{2,3}(?:\.\d)?)/', $t, $m)) {
            $type = preg_match('/\bfbs|fasting\b/', $t) ? 'fasting'
                  : (preg_match('/\bpp|ppbs|after\b/', $t) ? 'post_prandial' : null);
            $readings[] = ['kind' => 'glucose', 'value' => (float) $m[1], 'glucose_type' => $type];
        }
        if (preg_match('/\b(?:pulse|hr|heart\s*rate)\D{0,4}(\d{2,3})/', $t, $m)) {
            $readings[] = ['kind' => 'pulse', 'value' => (int) $m[1]];
        }
        if (preg_match('/\b(?:temp|temperature|fever)\D{0,4}(\d{2,3}(?:\.\d)?)/', $t, $m)) {
            $unit = (preg_match('/\bc\b|°c|celsius/', $t) || (float) $m[1] < 45) ? 'C' : 'F';
            $readings[] = ['kind' => 'temperature', 'value' => (float) $m[1], 'unit' => $unit];
        }
        if (preg_match('/\b(?:rr|resp(?:iratory)?|breathing)\D{0,6}(\d{1,2})/', $t, $m)) {
            $readings[] = ['kind' => 'respiratory_rate', 'value' => (int) $m[1]];
        }
        if (preg_match('/\b(?:spo2|spo₂|oxygen|o2|sats?)\D{0,4}(\d{2,3})/', $t, $m)) {
            $readings[] = ['kind' => 'spo2', 'value' => (int) $m[1]];
        }
        if (preg_match('/\b(?:weight|wt)\D{0,4}(\d{2,3}(?:\.\d)?)/', $t, $m)) {
            $readings[] = ['kind' => 'weight', 'value' => (float) $m[1]];
        }

        // Light time hint: "at 7am" / "7:30 pm". Today, or yesterday if future.
        $logged = null;
        if ($readings && preg_match('/\b(?:at|@)?\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/', $t, $tm)) {
            $h = (int) $tm[1] % 12;
            if (strtolower($tm[3]) === 'pm') $h += 12;
            $cand = now()->setTime($h, isset($tm[2]) ? (int) $tm[2] : 0, 0);
            if ($cand->greaterThan(now())) $cand->subDay();
            $logged = $cand->format('Y-m-d H:i');
        }

        return ['readings' => $readings, 'unparsed' => $readings ? null : trim($text), 'logged_at' => $logged];
    }

    private function extractJson(string $text): ?array
    {
        $s = strpos($text, '{');
        $e = strrpos($text, '}');
        if ($s === false || $e === false || $e <= $s) return null;
        $d = json_decode(substr($text, $s, $e - $s + 1), true);
        return is_array($d) ? $d : null;
    }

    private function envKey(string $key): ?string
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) return null;
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                if (trim($k) === $key) return trim($v, " \t\n\r\0\x0B\"'");
            }
        }
        return null;
    }
}
