<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email reminders for appointments scheduled tomorrow, with a CC to the
 * clinic. Skips cancelled/no-show/completed/rescheduled appointments and any
 * that were already reminded (reminder_email_sent_at), so re-running is safe.
 * Scheduled once a day (~18:05 local).
 */
class SendAppointmentEmailReminders extends Command
{
    protected $signature = 'appointments:send-email-reminders
                            {--limit=   : max emails this run (safety cap)}
                            {--dry-run  : list who would be emailed, send nothing}';
    protected $description = 'Email reminders for tomorrow\'s appointments (CC clinic)';

    public function handle(): int
    {
        $limit  = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');

        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $clinicSig = clinic_signature();
        $phone  = setting('clinic_phone');
        $cc     = setting('clinic_email') ?: 'demo@specialitycare.co.in';   // CC the owner/clinic

        // Lead times to remind at, each tracked separately so an appointment
        // can get both without the dedup blocking the second.
        $offsets = [
            2 => 'reminder_email_2d_at',     // 2 days before
            1 => 'reminder_email_sent_at',   // 1 day before
        ];

        $sent = $skipped = 0;
        foreach ($offsets as $days => $column) {
            $date  = now()->addDays($days)->toDateString();
            $appts = Appointment::with(['patient', 'doctor'])
                ->whereDate('appointment_date', $date)
                ->whereNotIn('status', ['Cancelled', 'No-Show', 'Completed', 'Rescheduled'])
                ->whereNull($column)
                ->get();

            foreach ($appts as $a) {
                if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break 2; }
                if (!$a->patient || empty($a->patient->email)) { $skipped++; continue; }

                $when = $a->appointment_date->format('d M Y')
                      . ($a->appointment_time ? ' at ' . Carbon::parse($a->appointment_time)->format('h:i A') : '');
                $lead = $days === 1 ? 'tomorrow' : "in {$days} days";
                $doctor = $a->doctor->full_name ?? null;

                $subject = "Appointment reminder — {$clinic} ({$a->appointment_date->format('d M Y')})";
                $body = "Dear " . ($a->patient->full_name ?: 'Patient') . ",\n\n"
                      . "This is a reminder of your appointment" . ($doctor ? " with Dr. {$doctor}" : '') . " {$lead}, {$when}.\n"
                      . "Appointment no: " . ($a->appointment_no ?: $a->id) . "\n\n"
                      . "Please arrive about 10 minutes early. If you need to reschedule, please call the clinic"
                      . ($phone ? " on {$phone}" : '') . ".\n\n"
                      . "— {$clinicSig}";

                if ($dryRun) {
                    $sent++;
                    $this->line("DRY-RUN would email {$a->patient->email} (cc {$cc}) for appt #{$a->id} ({$lead}, {$when})");
                    continue;
                }

                try {
                    Mail::raw($body, function ($m) use ($a, $cc, $subject) {
                        $m->to($a->patient->email)->subject($subject);
                        if ($cc) $m->cc($cc);
                    });
                    $a->forceFill([$column => now()])->save();
                    $sent++;
                    $this->info("Emailed {$a->patient->email} for appt #{$a->id} ({$lead})");
                } catch (\Throwable $e) {
                    $skipped++;
                    Log::warning("Appt email reminder failed for #{$a->id}: " . $e->getMessage());
                    $this->warn("Failed appt #{$a->id}: " . $e->getMessage());
                }
            }
        }

        $verb = $dryRun ? 'WOULD email' : 'emailed';
        $this->info("Done. {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
