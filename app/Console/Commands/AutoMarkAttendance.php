<?php
namespace App\Console\Commands;

use App\Services\AttendanceAutoMarker;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoMarkAttendance extends Command
{
    protected $signature = 'attendance:auto-mark-present
                            {--from= : Start date YYYY-MM-DD (default: each staff\'s joining date)}
                            {--to=   : End date   YYYY-MM-DD (default: today)}';

    protected $description = 'Create Present attendance records for auto_present staff on any missing date.';

    public function handle(AttendanceAutoMarker $marker): int
    {
        $today = today();
        $to    = $this->option('to')   ? Carbon::parse($this->option('to'))   : $today->copy();
        // Use 1900-01-01 as "no floor" sentinel — the service still respects each staff's joining_date.
        $from  = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::parse('1900-01-01');

        $created = $marker->ensureForRange($from, $to);
        $this->info("Auto-marked Present on {$created} missing day(s).");
        return self::SUCCESS;
    }
}
