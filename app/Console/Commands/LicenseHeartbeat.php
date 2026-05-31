<?php
namespace App\Console\Commands;

use App\Services\HeartbeatService;
use Illuminate\Console\Command;

class LicenseHeartbeat extends Command
{
    protected $signature = 'license:heartbeat {--force : Skip the interval check}';
    protected $description = 'Send a license heartbeat to the configured receiver (clone-detection telemetry).';

    public function handle(HeartbeatService $hb): int
    {
        $result = $hb->sendIfDue($this->option('force'));
        $this->line("heartbeat: $result");
        return $result === 'sent' || $result === 'too_soon' || $result === 'no_url' || $result === 'no_license' || $result === 'no_license_id'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
