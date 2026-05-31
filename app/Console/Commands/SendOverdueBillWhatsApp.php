<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Legacy alias for `bills:send-overdue-reminders`. The split between an
 * email-only and a WhatsApp-only command was retired 2026-05-25 — the new
 * unified command consults notification_preferences[bill_overdue] and fires
 * whichever channels are enabled. This shim is kept so external cron jobs
 * or docs referencing the old signature continue to work.
 */
class SendOverdueBillWhatsApp extends Command
{
    protected $signature = 'bills:send-overdue-whatsapp
                            {--days=7    : minimum days since bill_date}
                            {--limit=    : max bills per run}
                            {--dry-run   : list what would be sent}
                            {--patient=  : restrict to a single patient ID}';

    protected $description = '[DEPRECATED] alias for bills:send-overdue-reminders (calls the unified multi-channel command)';

    public function handle(): int
    {
        $args = [];
        if ($this->option('days') !== null)    $args['--days']    = $this->option('days');
        if ($this->option('limit') !== null)   $args['--limit']   = $this->option('limit');
        if ($this->option('dry-run'))          $args['--dry-run'] = true;
        if ($this->option('patient') !== null) $args['--patient'] = $this->option('patient');

        return $this->call('bills:send-overdue-reminders', $args);
    }
}
