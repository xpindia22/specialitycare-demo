<?php

namespace App\Console\Commands;

use App\Models\Encounter;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * For every encounter with follow_up_date == tomorrow, WhatsApp the patient
 * a reminder. Idempotent via a per-encounter ref key embedded in the body.
 */
class SendFollowUpWhatsAppReminders extends Command
{
    protected $signature = 'encounters:send-followup-whatsapp
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'WhatsApp follow-up reminders for encounters whose follow_up_date is tomorrow';

    public function handle(WhatsAppService $wa): int
    {
        if (!$wa->isEnabled()) { $this->warn('Notifications module disabled.'); return self::SUCCESS; }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $tomorrow = now()->addDay()->toDateString();
        $clinic = config('app.name', 'Speciality Care');

        $encounters = Encounter::with(['patient', 'doctor'])
            ->whereDate('follow_up_date', $tomorrow)
            ->get();

        $sent = $skipped = 0;
        foreach ($encounters as $e) {
            if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break; }
            if (!$e->patient || empty($e->patient->phone)) { $skipped++; continue; }

            $key = "followup_{$e->id}";
            if (WhatsAppMessage::where('kind', 'followup')
                ->where('status', 'sent')
                ->where('body', 'like', "%[ref:{$key}]%")
                ->exists()) {
                $skipped++;
                continue;
            }

            // Doctor::full_name already includes the "Dr." prefix.
            $doctor     = $e->doctor->full_name ?? null;
            $doctorName = $doctor ?: 'your doctor';
            $name       = $e->patient->full_name ?: 'there';
            $date       = $e->follow_up_date->format('d M Y');
            $note       = $e->follow_up_instructions ?: '-';
            $body = "Hi {$name},\n\n"
                  . "Friendly reminder: {$doctorName} advised a follow-up visit tomorrow, {$date}.\n\n"
                  . ($e->follow_up_instructions ? "Note: {$e->follow_up_instructions}\n\n" : '')
                  . "Please call us to confirm your slot.\n\n"
                  . "— {$clinic}\n[ref:{$key}]";

            if ($dryRun) {
                $sent++;
                $this->line("DRY-RUN would send follow-up to {$e->patient->phone} for encounter #{$e->id}");
                continue;
            }

            // Template followup: {{1}} name, {{2}} "Dr. X", {{3}} date, {{4}} note/"-", {{5}} clinic.
            $r = $wa->sendTemplateToPatient($e->patient, 'followup', [$name, $doctorName, $date, $note, $clinic], $body, 'followup');
            if ($r['ok'] ?? false) { $sent++; }
            else { $skipped++; }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Follow-up reminders — {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
