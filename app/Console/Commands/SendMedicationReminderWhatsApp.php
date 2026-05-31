<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\WhatsAppMessage;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Daily morning medication-adherence reminder for chronic patients
 * (HTN / T2DM / post-MI etc.). Patients flagged via patients.is_chronic.
 * Idempotent per patient/day via [ref:medrem_{patient_id}_{YYYY-MM-DD}].
 */
class SendMedicationReminderWhatsApp extends Command
{
    protected $signature = 'patients:send-medication-reminder-whatsapp
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'WhatsApp a morning medication reminder to chronic patients';

    public function handle(WhatsAppService $wa, NotificationPreferenceService $prefs): int
    {
        if (!$wa->isEnabled() || !$prefs->shouldSend('medication_reminder', 'patient', 'whatsapp')) {
            $this->warn('Medication-reminder WhatsApp disabled.');
            return self::SUCCESS;
        }

        $limit  = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $today  = now()->toDateString();

        $patients = Patient::where('is_active', true)
            ->where('is_chronic', true)
            ->whereNotNull('phone')
            ->get();

        $sent = $skipped = 0;
        foreach ($patients as $p) {
            if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break; }

            $key = "medrem_{$p->id}_{$today}";
            $already = WhatsAppMessage::where('kind', 'medication_reminder')
                ->where('status', 'sent')
                ->where('body', 'like', "%[ref:{$key}]%")->exists();
            if ($already) { $skipped++; continue; }

            $name = $p->full_name ?: 'there';
            $body = "Good morning {$name},\n\nPlease remember to take your morning medicines as prescribed by {$clinic}.\n\nReply STOP to opt out of daily reminders.\n[ref:{$key}]";

            if ($dryRun) {
                $sent++;
                $this->line("DRY-RUN would remind {$p->full_name} ({$p->phone})");
                continue;
            }

            // Template medication_reminder: {{1}} name, {{2}} clinic.
            $r = $wa->sendTemplateToPatient($p, 'medication_reminder', [$name, $clinic], $body, 'medication_reminder');
            if ($r['ok'] ?? false) { $sent++; }
            else { $skipped++; }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Medication reminders — {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
