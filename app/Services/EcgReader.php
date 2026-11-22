<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Reads an uploaded ECG (PDF or image) and extracts the standard parameters
 * (HR, PR, QRS, QT/QTc, rhythm, axis) plus a draft interpretation.
 *
 * Strategy:
 *  - PDF with a text layer (e.g. Dr.Cardio device reports): the printed
 *    intervals are pulled out exactly with pdftotext + regex — authoritative.
 *  - The file (PDF document block, or image) is also sent to Claude so it can
 *    read the actual waveforms for rhythm, axis, ST/T morphology and the
 *    interpretation. Device-printed intervals always win over the AI's.
 *  - If Claude is unavailable, the printed numbers + a templated note are
 *    still returned. Everything is a DRAFT for the doctor to confirm.
 */
class EcgReader
{
    /** Must match the <select> options in the ECG edit/create forms. */
    public const RHYTHMS = [
        'Sinus Rhythm','Sinus Tachycardia','Sinus Bradycardia','Atrial Fibrillation','Atrial Flutter',
        'SVT','Ventricular Tachycardia','Ventricular Fibrillation','Junctional Rhythm','Paced Rhythm',
        'Complete Heart Block (CHB)','2nd Degree AV Block (Mobitz I)','2nd Degree AV Block (Mobitz II)',
        '1st Degree AV Block','LBBB','RBBB','LAHB','LPHB','Other',
    ];
    public const AXES = [
        'Normal Axis (-30 to +90)','Left Axis Deviation (<-30)','Right Axis Deviation (>+90)','Extreme / Northwest Axis',
    ];

    /**
     * @param  string  $absPath  absolute path to the uploaded/stored file
     * @param  string  $ext      lower-case extension (uploaded temp files have none)
     * @return array{ok:bool,error?:string,source?:string,ai_used?:bool,values?:array,qtc_ms?:?int,flag?:array}
     */
    public static function read(string $absPath, string $ext): array
    {
        if (!is_file($absPath)) {
            return ['ok' => false, 'error' => 'File not found on the server.'];
        }
        $ext = strtolower($ext);

        $printed = [];
        if ($ext === 'pdf') {
            $printed = self::parsePrinted(self::pdfText($absPath));
        }

        $ai = self::aiRead($absPath, $ext, $printed);

        // Device-printed intervals are authoritative; AI fills the gaps.
        $hr  = $printed['hr']  ?? ($ai['heart_rate'] ?? null);
        $pr  = $printed['pr']  ?? ($ai['pr_ms']  ?? null);
        $qrs = $printed['qrs'] ?? ($ai['qrs_ms'] ?? null);
        $qt  = $printed['qt']  ?? ($ai['qt_ms']  ?? null);
        $qtc = $printed['qtc'] ?? ($ai['qtc_ms'] ?? null);

        $haveAny = $hr !== null || $pr !== null || $qrs !== null || $qt !== null || $qtc !== null
            || !empty($ai['interpretation']);
        if (!$haveAny) {
            $hint = in_array($ext, ['bmp', 'tiff', 'tif'], true)
                ? 'This image format cannot be auto-read. Convert to PDF/JPG/PNG or enter values manually.'
                : 'Could not read values from this file automatically. Please enter them manually.';
            return ['ok' => false, 'error' => $hint];
        }

        $values = [
            'heart_rate'     => $hr  !== null ? (string) $hr : '',
            'pr_interval'    => $pr  !== null ? $pr . ' ms' : '',
            'qrs_duration'   => $qrs !== null ? $qrs . ' ms' : '',
            'qt_qtc'         => ($qt !== null || $qtc !== null) ? (($qt ?? '?') . '/' . ($qtc ?? '?') . ' ms') : '',
            'rhythm'         => self::mapOption($ai['rhythm'] ?? '', self::RHYTHMS),
            'axis'           => self::mapOption($ai['axis'] ?? '', self::AXES),
            'interpretation' => trim($ai['interpretation'] ?? '') !== ''
                ? trim($ai['interpretation'])
                : self::fallbackInterp($hr, $pr, $qrs, $qt, $qtc),
        ];

        $aiOk   = !empty($ai['_ok']);
        $source = $printed ? ($aiOk ? 'pdf+ai' : 'pdf') : ($aiOk ? 'ai' : 'none');

        return [
            'ok'      => true,
            'source'  => $source,
            'ai_used' => $aiOk,
            'values'  => $values,
            'qtc_ms'  => $qtc,
            'flag'    => self::qtcFlag($qtc),
        ];
    }

    /** Extract the text layer of a PDF (empty string if scanned / no poppler). */
    private static function pdfText(string $path): string
    {
        if (trim((string) @shell_exec('command -v pdftotext')) === '') {
            return '';
        }
        $out = @shell_exec('pdftotext -layout ' . escapeshellarg($path) . ' - 2>/dev/null');
        return is_string($out) ? $out : '';
    }

    /** Pull HR/PR/QRS/QT/QTc (ms / bpm) printed by the ECG device. */
    private static function parsePrinted(string $t): array
    {
        if (trim($t) === '') return [];
        $v = [];
        if (preg_match('/\bHR[:\s]+(\d{2,3})\s*\/?\s*min/i', $t, $m)
            || preg_match('/heart\s*rate[:\s]+(\d{2,3})/i', $t, $m)) {
            $v['hr'] = (int) $m[1];
        }
        if (preg_match('/\bPR[:\s]+(\d{2,3})\s*ms/i', $t, $m))   $v['pr']  = (int) $m[1];
        if (preg_match('/\bQRS[:\s]+(\d{2,3})\s*ms/i', $t, $m))  $v['qrs'] = (int) $m[1];
        if (preg_match('/\bQTc[:\s]+(\d{2,4})\s*ms/i', $t, $m))  $v['qtc'] = (int) $m[1];
        if (preg_match('/\bQT[:\s]+(\d{2,4})\s*ms/i', $t, $m))   $v['qt']  = (int) $m[1];
        return $v;
    }

    /** Send the file to Claude for rhythm/axis/morphology + interpretation. */
    private static function aiRead(string $path, string $ext, array $printed): array
    {
        $key = env('ANTHROPIC_API_KEY') ?: self::envKey('ANTHROPIC_API_KEY');
        if (!$key) return [];

        $media = match ($ext) {
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'pdf'         => 'application/pdf',
            default       => null,
        };
        if (!$media) return [];

        $data = @file_get_contents($path);
        if ($data === false || $data === '') return [];
        $b64 = base64_encode($data);

        $fileBlock = $ext === 'pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $b64]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $media, 'data' => $b64]];

        $rhythms = implode(' | ', self::RHYTHMS);
        $axes    = implode(' | ', self::AXES);
        $printedNote = $printed
            ? 'Device-printed values (authoritative for intervals): ' . json_encode($printed)
            : 'No printed values were detected; read everything from the trace.';

        $system = implode("\n", [
            'You are a cardiology decision-support assistant reading a 12-lead ECG for a qualified doctor inside an HMS.',
            'Read the attached ECG (waveforms and any printed values) and return ONLY a single JSON object — no prose, no code fences.',
            'JSON keys: heart_rate (integer bpm or null), rhythm (string), axis (string), pr_ms (int|null), qrs_ms (int|null), qt_ms (int|null), qtc_ms (int|null), interpretation (string).',
            "rhythm MUST be exactly one of: {$rhythms}.",
            "axis MUST be exactly one of: {$axes}.",
            'Use printed values for the intervals when present. Never invent a value you cannot determine — use null.',
            'interpretation: a concise structured read — rate, rhythm, axis, intervals, ST/T morphology and chamber findings, then an "IMPRESSION:" line.',
            'If QTc is prolonged (>500 ms markedly, or 460-500 borderline) state it FIRST as a safety flag and advise checking electrolytes (K/Mg/Ca) and QT-prolonging drugs.',
            'End the interpretation with: "Auto-generated draft — to be confirmed by the doctor."',
        ]);

        try {
            $resp = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 1200,
                'system'     => $system,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => "Read this ECG and return the JSON. {$printedNote}"],
                        $fileBlock,
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        if (!$resp->successful()) return [];

        $text = collect($resp->json('content', []))->where('type', 'text')->pluck('text')->implode('');
        $json = self::extractJson($text);
        if (!is_array($json)) return [];

        $json['_ok'] = true;
        return $json;
    }

    /** First {...} JSON object found in a string, decoded; null if none. */
    private static function extractJson(string $text): ?array
    {
        $s = strpos($text, '{');
        $e = strrpos($text, '}');
        if ($s === false || $e === false || $e <= $s) return null;
        $decoded = json_decode(substr($text, $s, $e - $s + 1), true);
        return is_array($decoded) ? $decoded : null;
    }

    /** Map a free-text rhythm/axis from the AI to an exact form option, or ''. */
    private static function mapOption(string $val, array $options): string
    {
        $val = trim($val);
        if ($val === '') return '';
        foreach ($options as $o) {
            if (strcasecmp($o, $val) === 0) return $o;
        }
        $lv = strtolower($val);
        foreach ($options as $o) {
            $lo = strtolower($o);
            if (str_contains($lo, $lv) || str_contains($lv, $lo)) return $o;
        }
        // common phrasings
        if (str_contains($lv, 'normal sinus') || $lv === 'sinus') return 'Sinus Rhythm';
        if (str_contains($lv, 'normal axis')) return 'Normal Axis (-30 to +90)';
        return '';
    }

    /** QTc red-flag classification used for the UI banner. */
    public static function qtcFlag(?int $qtc): array
    {
        if ($qtc === null)  return ['level' => 'none', 'text' => ''];
        if ($qtc > 500)     return ['level' => 'critical', 'text' => "QTc {$qtc} ms — markedly prolonged (>500). High Torsades risk: confirm manually, check K/Mg/Ca and QT-prolonging drugs."];
        if ($qtc >= 460)    return ['level' => 'warn', 'text' => "QTc {$qtc} ms — prolonged. Confirm manually."];
        return ['level' => 'ok', 'text' => "QTc {$qtc} ms — within normal range."];
    }

    private static function fallbackInterp(?int $hr, ?int $pr, ?int $qrs, ?int $qt, ?int $qtc): string
    {
        $flag  = self::qtcFlag($qtc);
        $lines = [];
        if (in_array($flag['level'], ['critical', 'warn'], true)) $lines[] = '⚠ ' . $flag['text'];
        $lines[] = 'Auto-extracted intervals: '
            . 'HR ' . ($hr ?? '?') . ' bpm, PR ' . ($pr ?? '?') . ' ms, QRS ' . ($qrs ?? '?') . ' ms, '
            . 'QT ' . ($qt ?? '?') . ' ms, QTc ' . ($qtc ?? '?') . ' ms.';
        $lines[] = 'Rhythm, axis and waveform morphology to be confirmed by the doctor.';
        $lines[] = 'Auto-generated draft — to be confirmed by the doctor.';
        return implode("\n", $lines);
    }

    /** Read a key straight from .env (mirrors AiSuggestionController). */
    private static function envKey(string $key): ?string
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
