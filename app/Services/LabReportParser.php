<?php

namespace App\Services;

/**
 * Parses a lab report PDF into header fields + analyte rows.
 *
 * Strategy:
 *   1. Try the PDF's text layer (pdftotext -layout) — reliable for digital
 *      reports (e.g. Crystal Reports output from the lab).
 *   2. If there is little/no text (a scanned image), fall back to OCR
 *      (pdftoppm -> PNG -> tesseract).
 *
 * The lab's layout is fixed, so each result row looks like:
 *     <Parameter name>   : <value>   <unit>   <low-high ref range>
 * Header lines give Name, Reg. ID, "Registred On" (sample date), "Reported On".
 */
class LabReportParser
{
    /** Minimum chars in the text layer before we trust it over OCR. */
    private const TEXT_THRESHOLD = 60;

    private const RANGE = '[0-9]+(?:\.[0-9]+)?\s*[-\x{2013}]\s*[0-9]+(?:\.[0-9]+)?';

    public function parse(string $pdfPath): array
    {
        [$text, $source] = $this->extractText($pdfPath);
        $lines = preg_split('/\r?\n/', $text);

        return [
            'source'   => $source,            // 'text' or 'ocr'
            'header'   => $this->parseHeader($lines),
            'analytes' => $this->parseAnalytes($lines),
            'raw_text' => $text,
        ];
    }

    /** @return array{0:string,1:string} [text, source] */
    private function extractText(string $pdfPath): array
    {
        $text = (string) shell_exec('pdftotext -layout ' . escapeshellarg($pdfPath) . ' - 2>/dev/null');
        if (strlen(preg_replace('/\s+/', '', $text)) >= self::TEXT_THRESHOLD) {
            return [$text, 'text'];
        }
        // OCR fallback for scanned reports
        return [$this->ocr($pdfPath), 'ocr'];
    }

    private function ocr(string $pdfPath): string
    {
        if (!shell_exec('command -v tesseract')) {
            return '';
        }
        $tmp = sys_get_temp_dir() . '/labocr_' . uniqid();
        @mkdir($tmp);
        // 300 DPI greyscale PNGs, one per page
        shell_exec('pdftoppm -r 300 -gray -png ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($tmp . '/p') . ' 2>/dev/null');
        $out = '';
        foreach (glob($tmp . '/p*.png') ?: [] as $png) {
            $out .= (string) shell_exec('tesseract ' . escapeshellarg($png) . ' - --psm 6 2>/dev/null') . "\n";
            @unlink($png);
        }
        @rmdir($tmp);
        return $out;
    }

    private function parseHeader(array $lines): array
    {
        $h = ['name' => null, 'reg_id' => null, 'sample_date' => null, 'reported_date' => null];
        foreach ($lines as $l) {
            if (!$h['name'] && preg_match('/Name\s*:\s*(.+?)(?:\s{2,}|$)/i', $l, $m)) {
                $h['name'] = trim($m[1]);
            }
            if (!$h['reg_id'] && preg_match('/Reg\.?\s*ID\s*:\s*(\S+)/i', $l, $m)) {
                $h['reg_id'] = trim($m[1]);
            }
            if (!$h['sample_date'] && preg_match('/Regist[er]*ed?\s*On\s*:\s*([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4})/i', $l, $m)) {
                $h['sample_date'] = $this->normDate($m[1]);
            }
            if (!$h['reported_date'] && preg_match('/Reported\s*On\s*:\s*([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4})/i', $l, $m)) {
                $h['reported_date'] = $this->normDate($m[1]);
            }
        }
        return $h;
    }

    private function parseAnalytes(array $lines): array
    {
        $rows = [];
        $lastHeading = null;

        // A reference range may be printed as a low-high band (3.5-7.2), a
        // one-sided limit (< 15, >= 5, <=0.4) or a textual upper bound
        // (Upto 0.7). Earlier only low-high was accepted, so one-sided ranges
        // (e.g. ESR "Male : < 15") made the whole row disappear.
        $rangeRe = '(?:' . self::RANGE
            . '|[<>]=?\s*[0-9]+(?:\.[0-9]+)?'
            . '|up\s*to\s*[0-9]+(?:\.[0-9]+)?'
            . ')';

        foreach ($lines as $l) {
            $t = trim($l);

            // A standalone line with no colon is a block title. Single-analyte
            // blocks (URIC ACID, ESR ...) put the test name here and label the
            // value row just "RESULT", so remember it to name that row.
            if ($t !== '' && strpos($t, ':') === false) {
                if (!preg_match('/\bTEST\b.*\bRESULT\b.*\bUNIT\b/i', $t) && mb_strlen($t) <= 60) {
                    $lastHeading = $t;
                }
                continue;
            }

            if (strpos($l, ':') === false) continue;
            if (!preg_match('/^\s*(.+?)\s*:\s*(.+)$/u', $l, $m)) continue;
            $name = trim($m[1]);
            $rest = trim($m[2]);

            // skip a block's own label rows (Sample Type / Method / Instrument /
            // Method Used). Anchored to the start so a real test whose name just
            // contains the word (e.g. "ESR (Westergrens Method)") is NOT dropped.
            if (preg_match('/^(sample\s*type|method|instrument)\b/i', $name)) continue;
            if (preg_match('/trimester/i', $name)) continue;

            // result must begin with a (optionally </>) numeric value
            if (!preg_match('/^([<>]?=?\s*[0-9]+(?:\.[0-9]+)?)\s+(.*)$/u', $rest, $r)) continue;
            $value = trim($r[1]);
            $after = trim($r[2]);

            // a reference range must be present somewhere after the value
            if (!preg_match('/(' . $rangeRe . ')/ui', $after, $rr)) continue;
            $range = $this->normRange(trim($rr[1]));

            // unit = whatever's left once the range + any Male/Female qualifier
            // and stray colons are stripped (unit may sit before OR after the
            // range, e.g. "MG/DL 3.5-7.2" vs "< 15 mm/hrs").
            $unitPart = preg_replace('/' . $rangeRe . '/ui', ' ', $after);
            $unitPart = preg_replace('/\b(?:male|female)\b/ui', ' ', $unitPart);
            $unitPart = trim(preg_replace('/[:\s]+/u', ' ', $unitPart));
            $unit  = $this->normUnit($unitPart);

            // single-analyte blocks label the value row "RESULT"/"VALUE";
            // use the block's title line (URIC ACID, ESR ...) as the real name.
            if (preg_match('/^(results?|value)$/i', $name) && $lastHeading) {
                $name = $lastHeading;
            }

            if ($name === '' || mb_strlen($name) > 120) continue;

            $rows[] = [
                'parameter'    => $name,
                'value'        => $value,
                'unit'         => $unit,
                'normal_range' => $range,
                'section'      => $lastHeading,   // report block this row sits under (LFT, CBC …)
            ];
        }
        return $rows;
    }

    private function normDate(string $d): string
    {
        // dd/mm/yyyy -> Y-m-d
        [$dd, $mm, $yy] = array_pad(explode('/', $d), 3, '01');
        return sprintf('%04d-%02d-%02d', (int) $yy, (int) $mm, (int) $dd);
    }

    private function normRange(string $r): string
    {
        return preg_replace(['/\s*-\s*/', '/\x{2013}/u'], ['-', '-'], $r);
    }

    private function normUnit(string $u): string
    {
        $u = preg_replace('/\s+/', '', $u);          // "nmol /L" -> "nmol/L"
        $map = ['ulU/ml' => 'µIU/mL', 'uIU/ml' => 'µIU/mL', 'ug/dl' => 'µg/dL'];
        return $map[$u] ?? $u;
    }
}
