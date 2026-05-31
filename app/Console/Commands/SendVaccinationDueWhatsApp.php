<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Vaccination;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * For each child patient (age <= 18), compute due vaccinations from
 * Vaccination::SCHEDULE and DOB. If a recommended dose date falls within
 * the next 3 days AND that vaccine_code hasn't been recorded yet, send a
 * WhatsApp reminder to the patient/guardian phone. Idempotent per
 * patient+vaccine_code via the audit log.
 */
class SendVaccinationDueWhatsApp extends Command
{
    protected $signature = 'vaccinations:send-due-whatsapp
                            {--days=3   : window in days}
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'WhatsApp reminders for vaccinations recommended within the next N days';

    public function handle(WhatsAppService $wa): int
    {
        if (!$wa->isEnabled()) { $this->warn('Notifications module disabled.'); return self::SUCCESS; }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $windowDays = (int) ($this->option('days') ?: 3);
        $clinic = config('app.name', 'Speciality Care');
        $today = Carbon::today();
        $windowEnd = $today->copy()->addDays($windowDays);

        $patients = Patient::whereNotNull('date_of_birth')
            ->whereNotNull('phone')
            ->where('is_active', true)
            ->where('date_of_birth', '>=', $today->copy()->subYears(18)->toDateString())
            ->get();

        $sent = $skipped = 0;
        $stop = false;
        foreach ($patients as $p) {
            if ($stop) break;
            $given = Vaccination::where('patient_id', $p->id)->pluck('vaccine_code')->all();

            foreach (Vaccination::SCHEDULE as $entry) {
                if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); $stop = true; break; }
                if (in_array($entry['code'], $given, true)) continue;

                $dueDate = Carbon::parse($p->date_of_birth)->addDays((int) $entry['rec_days']);
                if ($dueDate->lt($today) || $dueDate->gt($windowEnd)) continue;

                $key = "vacc_{$p->id}_{$entry['code']}";
                if (WhatsAppMessage::where('kind', 'vaccination')
                    ->where('status', 'sent')
                    ->where('body', 'like', "%[ref:{$key}]%")
                    ->exists()) {
                    $skipped++;
                    continue;
                }

                $name = $p->full_name ?: 'there';
                $due  = $dueDate->format('d M Y');
                $body = "Hi {$name},\n\n"
                      . "Vaccination reminder: {$entry['name']} ({$entry['dose']} dose) is due on {$due}.\n\n"
                      . "Please book a slot at your earliest convenience.\n\n"
                      . "— {$clinic}\n[ref:{$key}]";

                if ($dryRun) {
                    $sent++;
                    $this->line("DRY-RUN would send vaccination ({$entry['code']}) to {$p->phone} for patient #{$p->id}");
                    continue;
                }

                // Template vaccination: {{1}} name, {{2}} vaccine, {{3}} dose, {{4}} due date, {{5}} clinic.
                $r = $wa->sendTemplateToPatient($p, 'vaccination', [$name, $entry['name'], $entry['dose'], $due, $clinic], $body, 'vaccination');
                if ($r['ok'] ?? false) { $sent++; }
                else { $skipped++; }
            }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Vaccination reminders — {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
