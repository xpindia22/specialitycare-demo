<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\WhatsAppMessage;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Daily birthday greeting via WhatsApp template `birthday_greeting`.
 * Idempotent per patient/year via [ref:bday_{patient_id}_{year}].
 */
class SendBirthdayWhatsApp extends Command
{
    protected $signature = 'patients:send-birthday-whatsapp
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'WhatsApp a birthday greeting to patients whose DOB is today';

    public function handle(WhatsAppService $wa, NotificationPreferenceService $prefs): int
    {
        if (!$wa->isEnabled() || !$prefs->shouldSend('birthday_greeting', 'patient', 'whatsapp')) {
            $this->warn('Birthday WhatsApp disabled.');
            return self::SUCCESS;
        }

        $limit  = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $clinicSig = clinic_signature();
        $today  = now();
        $year   = $today->year;

        // MySQL: match DOB month + day to today's, regardless of year.
        $patients = Patient::where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->whereNotNull('phone')
            ->whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', [$today->month, $today->day])
            ->get();

        $sent = $skipped = 0;
        foreach ($patients as $p) {
            if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break; }

            $key = "bday_{$p->id}_{$year}";
            $already = WhatsAppMessage::where('kind', 'birthday_greeting')
                ->where('status', 'sent')
                ->where('body', 'like', "%[ref:{$key}]%")->exists();
            if ($already) { $skipped++; continue; }

            $name = $p->full_name ?: 'there';
            $body = "Hi {$name},\n\nWishing you a very happy birthday! May the year ahead bring good health, joy and many smiles.\n\nWarm wishes from the team at {$clinicSig}.\n[ref:{$key}]";

            if ($dryRun) {
                $sent++;
                $this->line("DRY-RUN would wish {$p->full_name} ({$p->phone}) on their birthday");
                continue;
            }

            // Template birthday_greeting: {{1}} name, {{2}} clinic.
            $r = $wa->sendTemplateToPatient($p, 'birthday_greeting', [$name, $clinicSig], $body, 'birthday_greeting');
            if ($r['ok'] ?? false) { $sent++; }
            else { $skipped++; }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Birthday greetings — {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
