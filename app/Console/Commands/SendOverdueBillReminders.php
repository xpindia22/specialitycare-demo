<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Services\NotificationPreferenceService;
use App\Services\PatientNotifier;
use Illuminate\Console\Command;

/**
 * Multi-channel overdue bill reminders. Calls
 * PatientNotifier::dispatchBillOverdueReminder() per eligible bill so a single
 * code path drives email + WhatsApp + owner CC, all gated by
 * notification_preferences[bill_overdue]. Re-dedup'd via the
 * bills.last_overdue_reminded_at timestamp so the same bill isn't pinged
 * every scheduled run.
 *
 * Imported OpenEMR bills (bill_no starting "OE/") are excluded because their
 * paid/due values can be unreliable — the original reason this command was
 * disabled in May 2026.
 */
class SendOverdueBillReminders extends Command
{
    protected $signature = 'bills:send-overdue-reminders
                            {--days=7         : minimum days since bill_date}
                            {--repeat-every=7 : skip bills reminded within this many days}
                            {--limit=         : max bills per run (safety cap)}
                            {--dry-run        : list what would be sent, send nothing}
                            {--patient=       : restrict to a single patient ID (testing)}
                            {--include-oe     : include OpenEMR-imported (OE/...) bills (off by default)}';

    protected $description = 'Send overdue bill reminders (email + WhatsApp + owner CC, per notification_preferences[bill_overdue])';

    public function handle(NotificationPreferenceService $prefs, PatientNotifier $notifier): int
    {
        $eventKey = 'bill_overdue';
        $patientEmail = $prefs->shouldSend($eventKey, 'patient', 'email');
        $patientWA    = $prefs->shouldSend($eventKey, 'patient', 'whatsapp');
        $adminEmail   = $prefs->shouldSend($eventKey, 'admin',   'email');
        $adminWA      = $prefs->shouldSend($eventKey, 'admin',   'whatsapp');

        if (!($patientEmail || $patientWA || $adminEmail || $adminWA)) {
            $this->warn('All channels off for bill_overdue in notification_preferences. Nothing to do.');
            return self::SUCCESS;
        }

        $days        = (int) ($this->option('days') ?: 7);
        $repeatEvery = (int) ($this->option('repeat-every') ?: 7);
        $limit       = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun      = (bool) $this->option('dry-run');
        $onlyPatient = $this->option('patient') ? (int) $this->option('patient') : null;
        $includeOE   = (bool) $this->option('include-oe');

        $q = Bill::with('patient')
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->where('due_amount', '>', 0)
            ->whereDate('bill_date', '<=', now()->subDays($days)->toDateString())
            ->where(function ($q) use ($repeatEvery) {
                $q->whereNull('last_overdue_reminded_at')
                  ->orWhere('last_overdue_reminded_at', '<', now()->subDays($repeatEvery));
            });

        if (!$includeOE) {
            $q->where('bill_no', 'not like', 'OE/%');
        }
        if ($onlyPatient) {
            $q->where('patient_id', $onlyPatient);
        }

        $bills = $q->orderBy('bill_date')->get();

        $this->info("Channels: " . implode(', ', array_filter([
            $patientEmail ? 'patient_email' : null,
            $patientWA    ? 'patient_whatsapp' : null,
            $adminEmail   ? 'admin_email' : null,
            $adminWA      ? 'admin_whatsapp' : null,
        ])));
        $this->info("Candidates: {$bills->count()} bill(s) overdue by >={$days}d, not reminded in {$repeatEvery}d");

        $sent = $skipped = 0;
        foreach ($bills as $bill) {
            if ($limit !== null && $sent >= $limit) {
                $this->warn("Hit --limit={$limit}; stopping.");
                break;
            }
            if (!$bill->patient) { $skipped++; continue; }

            $label = "#{$bill->bill_no} · " . ($bill->patient->full_name ?: '—') . " · due ₹" . number_format((float) $bill->due_amount, 2);
            if ($dryRun) {
                $this->line("DRY-RUN would notify {$label}");
                $sent++;
                continue;
            }

            try {
                // CLI path: call the heavy lifter directly so dispatch->afterResponse()
                // (which only fires on HTTP terminate) isn't on the path.
                $notifier->dispatchBillOverdueReminder($bill, $patientEmail, $patientWA, $adminEmail, $adminWA);
                $bill->update(['last_overdue_reminded_at' => now()]);
                $sent++;
                $this->info("Sent {$label}");
            } catch (\Throwable $e) {
                $skipped++;
                $this->error("Failed {$label}: " . $e->getMessage());
            }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Done. {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
