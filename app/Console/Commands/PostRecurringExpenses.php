<?php

namespace App\Console\Commands;

use App\Services\RecurringExpenseRunner;
use Illuminate\Console\Command;

/**
 * Auto-posts due recurring expenses. Safe to schedule daily if cron is
 * available, but the app also fires this on every Accounts dashboard load
 * via RecurringExpenseRunner (so buyer servers without crontab still work).
 */
class PostRecurringExpenses extends Command
{
    protected $signature   = 'accounts:post-recurring {--dry-run : Show what would post without posting}';
    protected $description = 'Auto-post Payment vouchers for any recurring expenses due this month and not yet posted';

    public function handle(RecurringExpenseRunner $runner): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = $runner->postDue(null, $dry);

        foreach ($result['vouchers'] as $line) {
            $this->info(($dry ? 'DRY ' : 'OK  ') . $line);
        }
        foreach ($result['errors'] as $err) {
            $this->error('!!  ' . $err);
        }

        $this->newLine();
        $this->info("Recurring expenses run — posted {$result['posted']}, skipped {$result['skipped']}"
            . ($dry ? ' [DRY RUN]' : ''));

        return self::SUCCESS;
    }
}
