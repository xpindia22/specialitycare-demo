<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\WhatsAppMessage;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Daily refill reminder for every patient whose prescribed medicines are
 * about to run out within the next N days (default 3). Mirrors the
 * `refillAlerts` math used on the patient profile:
 *     prescribed_date + duration_days = expected end date
 *
 * One consolidated WhatsApp per patient per run, listing every medicine
 * that becomes due in the window. Idempotent per (patient, due-date) via
 * [ref:refill_{patient_id}_{due_date}] so we don't spam the patient if
 * the cron runs multiple times in a day or on consecutive days for the
 * same upcoming due date.
 *
 * Template `refill_reminder` (UTILITY): {{1}} name, {{2}} list of meds
 * with dates, {{3}} clinic.
 */
class SendRefillReminderWhatsApp extends Command
{
    protected $signature = 'prescriptions:send-refill-reminder-whatsapp
                            {--days=3   : how many days before "ran out" to remind}
                            {--limit=   : max messages this run (safety cap)}
                            {--dry-run  : list who would be messaged, send nothing}';

    protected $description = 'WhatsApp refill reminders to patients whose meds are about to run out';

    public function handle(WhatsAppService $wa, NotificationPreferenceService $prefs): int
    {
        if (!$wa->isEnabled() || !$prefs->shouldSend('refill_reminder', 'patient', 'whatsapp')) {
            $this->warn('Refill-reminder WhatsApp disabled.');
            return self::SUCCESS;
        }

        $windowDays = (int) ($this->option('days') ?: 3);
        $limit  = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $clinic = setting('clinic_name', config('app.name', 'Speciality Care'));
        $clinicSig = clinic_signature();
        $today  = Carbon::today();
        $windowEnd = $today->copy()->addDays($windowDays);

        $patients = Patient::with(['prescriptions.items.medicine'])
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get();

        $sent = $skipped = 0;
        foreach ($patients as $p) {
            if ($limit !== null && $sent >= $limit) { $this->warn("Hit --limit={$limit}; stopping."); break; }

            // Build the list of meds for this patient that fall due in the window.
            // Match the profile UI's logic exactly (PatientController::show).
            $dueItems = [];
            foreach ($p->prescriptions as $rx) {
                foreach ($rx->items as $item) {
                    $days  = $item->duration_days > 0 ? (int) $item->duration_days : 30;
                    $dueAt = Carbon::parse($rx->prescribed_date)->addDays($days);
                    if ($dueAt->lt($today) || $dueAt->gt($windowEnd)) continue;
                    $dueItems[] = [
                        'name'    => $item->medicine?->name ?? $item->generic_name ?? 'Medication',
                        'due_at'  => $dueAt,
                        'item_id' => $item->id,
                    ];
                }
            }
            if (empty($dueItems)) continue;

            // Dedupe by medicine name + due date — patients commonly have the
            // same medicine across multiple prescription rows (refills, repeats
            // from different encounters) and each row generates an item. Without
            // this dedup the message lists "Nitrocontin … 30 May 2026" 3 times.
            // Different due dates for the same med are kept (patient should
            // see both refill points), but same med + same date is collapsed.
            $seen = [];
            $dueItems = array_values(array_filter($dueItems, function ($d) use (&$seen) {
                $key = strtolower(trim($d['name'])) . '|' . $d['due_at']->toDateString();
                if (isset($seen[$key])) return false;
                $seen[$key] = true;
                return true;
            }));

            // Idempotency: bucket by earliest due date so we send at most one
            // reminder per upcoming due batch (re-running the cron later that
            // day or next day for the same batch is a no-op).
            usort($dueItems, fn ($a, $b) => $a['due_at']->getTimestamp() <=> $b['due_at']->getTimestamp());
            $earliest = $dueItems[0]['due_at']->toDateString();
            $key = "refill_{$p->id}_{$earliest}";
            $already = WhatsAppMessage::where('kind', 'refill_reminder')
                ->where('status', 'sent')
                ->where('body', 'like', "%[ref:{$key}]%")->exists();
            if ($already) { $skipped++; continue; }

            $listForTemplate = collect($dueItems)
                ->map(fn ($d) => $d['name'] . ' (' . $d['due_at']->format('d M') . ')')
                ->implode(', ');
            $name = $p->full_name ?: 'there';

            $body = "Hi {$name},\n\n"
                  . "Your prescription is running low. Please come for a refill so you don't miss any doses.\n\n"
                  . "Medicines due soon:\n"
                  . collect($dueItems)->map(fn ($d) => '• ' . $d['name'] . ' — ' . $d['due_at']->format('d M Y'))->implode("\n")
                  . "\n\nRegards, {$clinicSig}.\n[ref:{$key}]";

            if ($dryRun) {
                $sent++;
                $this->line("DRY-RUN would remind {$p->full_name} ({$p->phone}) — " . count($dueItems) . " med(s) due by {$earliest}");
                continue;
            }

            // Template refill_reminder: {{1}} name, {{2}} med list, {{3}} clinic.
            $r = $wa->sendTemplateToPatient($p, 'refill_reminder', [$name, $listForTemplate, $clinicSig], $body, 'refill_reminder');
            if ($r['ok'] ?? false) { $sent++; }
            else { $skipped++; }
        }

        $verb = $dryRun ? 'WOULD send' : 'sent';
        $this->info("Refill reminders — {$verb}: {$sent}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
