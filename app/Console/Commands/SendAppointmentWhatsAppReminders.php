<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Send WhatsApp reminders for appointments scheduled tomorrow. Skips
 * cancelled/completed appointments and any appointment that has already
 * received an `appointment_reminder` message (so re-running the command
 * is safe). Intended to run once a day, ~18:00 local.
 */
class SendAppointmentWhatsAppReminders extends Command
{
    protected $signature = 'appointments:send-whatsapp-reminders
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';
    protected $description = 'Send WhatsApp reminders for tomorrow\'s appointments';

    public function handle(WhatsAppService $wa): int
    {
        if (!$wa->isEnabled()) {
            $this->warn('Notifications module is disabled — no reminders sent.');
            return self::SUCCESS;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $clinicSig = clinic_signature();

        // Remind 2 days before AND 1 day before; each tracked by its own ref
        // key so an appointment can get both without re-sending.
        $offsets = [
            2 => 'appt_reminder_2d',
            1 => 'appt_reminder',
        ];

        $sent = $skipped = 0;
        foreach ($offsets as $days => $prefix) {
            $date = now()->addDays($days)->toDateString();
            $appts = Appointment::with(['patient', 'doctor'])
                ->whereDate('appointment_date', $date)
                ->where('status', '!=', 'cancelled')
                ->get();

            foreach ($appts as $a) {
                if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break 2; }
                if (!$a->patient || empty($a->patient->phone)) { $skipped++; continue; }

                $key = "{$prefix}_{$a->id}";
                if (WhatsAppMessage::where('kind', 'appointment_reminder')
                    ->where('status', 'sent')
                    ->where('body', 'like', "%[ref:{$key}]%")
                    ->exists()) {
                    $skipped++;
                    continue;
                }

                $when = trim(
                    ($a->appointment_date->format('d M Y')) .
                    ($a->appointment_time ? ' at ' . \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') : '')
                );
                $lead = $days === 1 ? 'tomorrow' : "in {$days} days";
                // Doctor::full_name already includes the "Dr." prefix.
                $doctor = $a->doctor->full_name ?? null;
                $name      = $a->patient->full_name ?: 'there';
                $docClause = $doctor ? "with {$doctor}" : 'with our doctor';
                $apptNo    = (string) ($a->appointment_no ?: $a->id);

                $body = "Hi {$name},\n\n"
                      . "Reminder: your appointment {$docClause} is {$lead}, {$when}.\n"
                      . "Appointment no: {$apptNo}\n\n"
                      . "Please arrive 10 minutes early. Reply if you need to reschedule.\n\n"
                      . "— {$clinicSig}\n[ref:{$key}]";

                if ($dryRun) {
                    $sent++;
                    $this->line("DRY-RUN would send to {$a->patient->phone} for appt #{$a->id} ({$lead})");
                    continue;
                }

                // Template appointment_reminder: {{1}} name, {{2}} "with Dr. X", {{3}} when, {{4}} appt no, {{5}} clinic.
                $r = $wa->sendTemplateToPatient($a->patient, 'appointment_reminder', [$name, $docClause, $when, $apptNo, $clinicSig], $body, 'appointment_reminder');
                if ($r['ok'] ?? false) { $sent++; $this->info("Reminded {$a->patient->phone} for appt #{$a->id} ({$lead})"); }
                else { $skipped++; $this->warn("Skipped appt #{$a->id}: " . ($r['skipped'] ?? $r['error'] ?? '?')); }
            }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Done. {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
