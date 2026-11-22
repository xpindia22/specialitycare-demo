<?php

namespace App\Console\Commands;

use App\Services\LabReportIntake;
use Illuminate\Console\Command;

/**
 * Simulate a lab-report PDF arriving on WhatsApp — without Gupshup — so the
 * inbound parse + queue flow can be tested on dev.
 *
 *   php artisan lab:simulate-inbound /path/report.pdf 919812345678
 *   php artisan lab:simulate-inbound /path/report.pdf 919812345678 --force   (skip allowlist)
 */
class SimulateInboundLabReport extends Command
{
    protected $signature = 'lab:simulate-inbound {pdf : path to a PDF} {from : sender WhatsApp number} {--force : bypass the sender allowlist}';
    protected $description = 'Simulate an inbound WhatsApp lab-report PDF (parses + queues it for review)';

    public function handle(LabReportIntake $intake): int
    {
        $pdf  = $this->argument('pdf');
        $from = $this->argument('from');

        if (!is_file($pdf)) {
            $this->error("PDF not found: {$pdf}");
            return self::FAILURE;
        }
        if (!$this->option('force') && !LabReportIntake::senderAllowed($from)) {
            $this->error("Sender {$from} is not on the allowlist (config/labreports.php). Use --force to override for testing.");
            return self::FAILURE;
        }

        $pending = $intake->intake($pdf, $from, 'whatsapp');

        $this->info("Queued pending report #{$pending->id}");
        $this->line("  from:     {$pending->sender}");
        $this->line("  on report: " . ($pending->report_name ?? '—') . "  (Reg {$pending->reg_id})");
        $this->line("  sample:   " . ($pending->sample_date?->toDateString() ?? '—'));
        $this->line("  analytes: {$pending->analyte_count}");
        $this->line("  review at: /lab/pending/{$pending->id}/review");
        return self::SUCCESS;
    }
}
