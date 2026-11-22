<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiAssistantController extends Controller
{
    public function chat(Request $request, Encounter $encounter)
    {
        $request->validate(['message' => 'required|string|max:3000']);

        // Each install pays for its own usage: prefer the key the buyer entered
        // under Settings → AI Assistant. The .env value is only a fallback for
        // the vendor's own installs (home/nvme/dev) that set it directly —
        // buyer installs won't have that value at all, so they must configure
        // their own key for this to work.
        $apiKey = setting('ai_assistant_api_key') ?: (env('ANTHROPIC_API_KEY') ?: $this->readEnvKey('ANTHROPIC_API_KEY'));
        if (!$apiKey) {
            return response()->json(['error' => 'AI assistant is not configured. Add your Anthropic API key under Settings → AI Assistant.'], 503);
        }

        $encounter->load([
            'patient.patientAllergies',
            'diagnoses',
            'prescriptions',
        ]);

        $systemPrompt = $this->buildSystemPrompt($encounter);
        $history      = collect($request->input('history', []))
            ->filter(fn($m) => in_array($m['role'] ?? '', ['user', 'assistant']) && !empty($m['content']))
            ->values()
            ->toArray();

        $messages = array_merge($history, [
            ['role' => 'user', 'content' => $request->message],
        ]);

        $payload = [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
            'thinking'   => ['type' => 'adaptive'],
            'stream'     => true,
            'system'     => [
                [
                    'type'          => 'text',
                    'text'          => $systemPrompt,
                    'cache_control' => ['type' => 'ephemeral'],
                ],
            ],
            'messages' => $messages,
        ];

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->withOptions(['stream' => true, 'timeout' => 120])->post(
            'https://api.anthropic.com/v1/messages',
            $payload
        );

        if (!$response->successful()) {
            $err = $response->json('error.message', 'Claude API error');
            return response()->json(['error' => $err], 502);
        }

        return response()->stream(function () use ($response) {
            $body   = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk   = $body->read(1024);
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line   = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    if (str_starts_with($line, 'data: ')) {
                        $data = json_decode(substr($line, 6), true);
                        if (!$data) continue;

                        if ($data['type'] === 'content_block_delta'
                            && ($data['delta']['type'] ?? '') === 'text_delta') {
                            echo 'data: ' . json_encode(['text' => $data['delta']['text']]) . "\n\n";
                            ob_flush();
                            flush();
                        }

                        if ($data['type'] === 'message_stop') {
                            echo "data: [DONE]\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                }
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function buildSystemPrompt(Encounter $encounter): string
    {
        $patient = $encounter->patient;
        $lines   = [];

        $lines[] = "You are an AI clinical assistant embedded in SpecialityCare HMS. You help doctors with differential diagnoses, treatment guidance, drug queries, and clinical decision support. Always be concise, evidence-based, and flag urgent findings. Remind the doctor to exercise their own clinical judgement.";
        $lines[] = '';
        $lines[] = '## Current Patient';
        $lines[] = "Name: {$patient->full_name}";
        $lines[] = "Age/Sex: {$patient->age}, {$patient->gender}";
        if ($patient->blood_group) $lines[] = "Blood Group: {$patient->blood_group}";
        $lines[] = "JHC ID: {$patient->jhcid}";

        $allergies = $encounter->patient->patientAllergies;
        if ($allergies->count()) {
            $lines[] = '';
            $lines[] = '## ⚠️ Allergies';
            foreach ($allergies as $a) {
                $lines[] = "- {$a->allergen}" . ($a->severity !== 'unknown' ? " ({$a->severity})" : '');
            }
        }

        if ($encounter->allergies) {
            $lines[] = "Encounter allergies note: {$encounter->allergies}";
        }

        $lines[] = '';
        $lines[] = '## Vitals (This Visit)';
        if ($encounter->bp_systolic && $encounter->bp_diastolic)
            $lines[] = "BP: {$encounter->bp_systolic}/{$encounter->bp_diastolic} mmHg";
        if ($encounter->pulse)       $lines[] = "Pulse: {$encounter->pulse}/min";
        if ($encounter->temperature) $lines[] = "Temp: {$encounter->temperature}°F";
        if ($encounter->spo2)        $lines[] = "SpO₂: {$encounter->spo2}%";
        if ($encounter->weight)      $lines[] = "Weight: {$encounter->weight} kg";
        if ($encounter->bmi)         $lines[] = "BMI: {$encounter->bmi}";
        if ($encounter->blood_glucose) $lines[] = "RBS: {$encounter->blood_glucose} mg/dL";

        if ($encounter->chief_complaint) {
            $lines[] = '';
            $lines[] = '## Chief Complaint';
            $lines[] = $encounter->chief_complaint;
        }

        if ($encounter->history_of_present_illness) {
            $lines[] = '';
            $lines[] = '## History of Present Illness';
            $lines[] = $encounter->history_of_present_illness;
        }

        if ($encounter->past_medical_history) {
            $lines[] = '';
            $lines[] = '## Past Medical History';
            $lines[] = $encounter->past_medical_history;
        }

        $diagnoses = $encounter->diagnoses;
        if ($diagnoses->count()) {
            $lines[] = '';
            $lines[] = '## Current Diagnoses';
            foreach ($diagnoses as $d) {
                $lines[] = "- {$d->diagnosis}" . ($d->icd_code ? " [{$d->icd_code}]" : '') . ($d->type ? " ({$d->type})" : '');
            }
        }

        $prescriptions = $encounter->prescriptions;
        if ($prescriptions->count()) {
            $lines[] = '';
            $lines[] = '## Current Prescriptions';
            foreach ($prescriptions->take(10) as $p) {
                $rx = "- {$p->drug_name}";
                if ($p->dose)      $rx .= " {$p->dose}";
                if ($p->frequency) $rx .= " {$p->frequency}";
                if ($p->duration)  $rx .= " × {$p->duration}";
                $lines[] = $rx;
            }
        }

        if ($encounter->soap_note) {
            $lines[] = '';
            $lines[] = '## SOAP Note';
            $lines[] = $encounter->soap_note;
        }

        $lines[] = '';
        $lines[] = "Encounter date: {$encounter->encounter_date->format('d M Y')}";
        $lines[] = "Visit type: {$encounter->visit_type}";

        return implode("\n", $lines);
    }

    private function readEnvKey(string $key): ?string
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
