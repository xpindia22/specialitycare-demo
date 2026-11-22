<?php

namespace App\Console\Commands;

use App\Models\StockTransaction;
use App\Models\Voucher;
use App\Services\PharmacySaleVoucherPoster;
use Illuminate\Console\Command;

/**
 * Backfill pharmacy Sales vouchers for historical 'dispensed' stock
 * transactions that predate the auto-poster. Idempotent (the poster skips
 * any dispense that already has a voucher), so it's safe to re-run.
 *
 *   php artisan accounts:backfill-dispense-sales [--from=YYYY-MM-DD] [--dry-run]
 *
 * Reports honour the books-opening-date cutover, so backfilling everything is
 * safe — pre-cutover vouchers simply won't appear in the financial reports.
 */
class BackfillDispenseSales extends Command
{
    protected $signature = 'accounts:backfill-dispense-sales
                            {--from= : Only dispenses on/after this date (YYYY-MM-DD)}
                            {--dry-run : Report what would be posted without writing}';

    protected $description = 'Post pharmacy Sales vouchers for past dispensed-medicine stock transactions';

    public function handle(PharmacySaleVoucherPoster $poster): int
    {
        $from = $this->option('from');
        $dry  = (bool) $this->option('dry-run');

        $query = StockTransaction::with(['medicine', 'stock'])
            ->where('type', 'dispensed')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->orderBy('id');

        $total = $query->count();
        $this->info(($dry ? '[DRY RUN] ' : '') . "Scanning {$total} dispensed transactions" . ($from ? " from {$from}" : '') . ' …');

        $posted = 0; $existing = 0; $noPrice = 0; $value = 0.0;

        $query->chunkById(200, function ($txns) use ($poster, $dry, &$posted, &$existing, &$noPrice, &$value) {
            foreach ($txns as $txn) {
                if (Voucher::where('source_type', StockTransaction::class)->where('source_id', $txn->id)->exists()) {
                    $existing++;
                    continue;
                }
                $amount = $poster->saleValue($txn);
                if ($amount <= 0) { $noPrice++; continue; }

                if ($dry) { $value += $amount; $posted++; continue; }

                if ($poster->post($txn)) { $posted++; $value += $amount; } else { $noPrice++; }
            }
        });

        $this->newLine();
        $this->info(($dry ? 'Would post' : 'Posted') . ": {$posted} new voucher(s), total ₹" . number_format($value, 2));
        $this->line("Already posted (skipped): {$existing}");
        $this->line("No resolvable price (skipped): {$noPrice}");

        return self::SUCCESS;
    }
}
