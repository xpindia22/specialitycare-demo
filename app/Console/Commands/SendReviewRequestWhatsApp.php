<?php

namespace App\Console\Commands;

use App\Models\Encounter;
use App\Models\WhatsAppMessage;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Two days after each encounter, ask the patient for a Google review via
 * WhatsApp template `review_request`. Single biggest local-SEO lever.
 * Idempotent per encounter via [ref:review_{enc_id}] in the audit log.
 */
class SendReviewRequestWhatsApp extends Command
{
    protected $signature = 'reviews:send-request-whatsapp
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'WhatsApp a Google-review request to patients 2 days after their visit';

    public function handle(WhatsAppService $wa, NotificationPreferenceService $prefs): int
    {
        if (!$wa->isEnabled() || !$prefs->shouldSend('review_request', 'patient', 'whatsapp')) {
            $this->warn('Review-request WhatsApp disabled (module off, or notification preference off).');
            return self::SUCCESS;
        }

        $limit  = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $date   = now()->subDays(2)->toDateString();

        $encounters = Encounter::with('patient')
            ->whereDate('encounter_date', $date)
            ->get();

        $sent = $skipped = 0;
        foreach ($encounters as $e) {
            if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break; }
            if (!$e->patient || !$e->patient->is_active || empty($e->patient->phone)) { $skipped++; continue; }

            $key = "review_{$e->id}";
            $already = WhatsAppMessage::where('kind', 'review_request')
                ->where('status', 'sent')
                ->where('body', 'like', "%[ref:{$key}]%")->exists();
            if ($already) { $skipped++; continue; }

            $name = $e->patient->full_name ?: 'there';
            $body = "Hi {$name},\n\nThank you for choosing {$clinic}. Your feedback helps us serve you better — could you share a Google review of your visit? Regards, {$clinic} team.\n[ref:{$key}]";

            if ($dryRun) {
                $sent++;
                $this->line("DRY-RUN would send review request to {$e->patient->phone} for encounter #{$e->id}");
                continue;
            }

            // Template review_request: {{1}} name, {{2}} clinic.
            $r = $wa->sendTemplateToPatient($e->patient, 'review_request', [$name, $clinic], $body, 'review_request');
            if ($r['ok'] ?? false) { $sent++; }
            else { $skipped++; }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Review requests — {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
