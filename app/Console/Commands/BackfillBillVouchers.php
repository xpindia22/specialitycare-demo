<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\BillVoucherPoster;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * One-shot backfill: walks every historical Bill and Payment that doesn't
 * yet have an auto-posted voucher and runs them through BillVoucherPoster
 * (Sales for bills, Receipt for payments).
 *
 * Idempotent — postBill/postPayment short-circuit on findBySource(), so
 * re-running posts only what's still missing.
 *
 * Usage:
 *   php artisan accounts:backfill-bills --dry-run
 *   php artisan accounts:backfill-bills --since=2026-04-01
 *   php artisan accounts:backfill-bills
 */
class BackfillBillVouchers extends Command
{
    protected $signature = 'accounts:backfill-bills
                            {--dry-run            : Count what would post, no writes}
                            {--since=             : Only bills/payments on or after this date (YYYY-MM-DD)}
                            {--until=             : Only bills/payments on or before this date (YYYY-MM-DD)}
                            {--bills-only         : Skip the payments pass}
                            {--payments-only      : Skip the bills pass}
                            {--limit=             : Cap how many of each are processed (useful for first-time canary runs)}';

    protected $description = 'Backfill Sales + Receipt vouchers for historical bills and payments that pre-date BillVoucherObserver';

    public function handle(BillVoucherPoster $poster, VoucherService $vouchers): int
    {
        $dry         = (bool) $this->option('dry-run');
        $since       = $this->option('since')        ? Carbon::parse($this->option('since')) : null;
        $until       = $this->option('until')        ? Carbon::parse($this->option('until')) : null;
        $billsOnly   = (bool) $this->option('bills-only');
        $paymentsOnly= (bool) $this->option('payments-only');
        $limit       = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info($dry ? '── DRY RUN ──' : '── POSTING ──');
        $window = '';
        if ($since) $window .= " since {$since->toDateString()}";
        if ($until) $window .= " until {$until->toDateString()}";
        if ($limit) $window .= " limit {$limit} each";
        if ($window) $this->line("Filters:{$window}");
        $this->newLine();

        $totals = ['bills_posted' => 0, 'bills_skipped' => 0, 'bills_errors' => 0,
                   'pay_posted'   => 0, 'pay_skipped'   => 0, 'pay_errors'   => 0];

        if (!$paymentsOnly) {
            $q = Bill::query()->where('total_amount', '>', 0)->orderBy('id');
            if ($since) $q->where('bill_date', '>=', $since->toDateString());
            if ($until) $q->where('bill_date', '<=', $until->toDateString());
            if ($limit) $q->limit($limit);

            $total = $q->count();
            $this->line("Bills to consider: {$total}");
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($q->cursor() as $bill) {
                try {
                    if ($dry) {
                        // Treat existing voucher as "skipped", missing as "would post"
                        $existing = $vouchers->findBySource(Bill::class, $bill->id);
                        $existing ? $totals['bills_skipped']++ : $totals['bills_posted']++;
                    } else {
                        $beforeId = $vouchers->findBySource(Bill::class, $bill->id)?->id;
                        $voucher  = $poster->postBill($bill->loadMissing('items'));
                        if (!$voucher) $totals['bills_skipped']++;
                        elseif ($beforeId && $voucher->id === $beforeId) $totals['bills_skipped']++;
                        else $totals['bills_posted']++;
                    }
                } catch (\Throwable $e) {
                    $totals['bills_errors']++;
                    $this->newLine();
                    $this->error("bill #{$bill->id} ({$bill->bill_no}): {$e->getMessage()}");
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        if (!$billsOnly) {
            $q = Payment::query()->where('amount', '>', 0)->orderBy('id');
            if ($since) $q->where('payment_date', '>=', $since->toDateString());
            if ($until) $q->where('payment_date', '<=', $until->toDateString());
            if ($limit) $q->limit($limit);

            $total = $q->count();
            $this->line("Payments to consider: {$total}");
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($q->cursor() as $payment) {
                try {
                    if ($dry) {
                        $existing = $vouchers->findBySource(Payment::class, $payment->id);
                        $existing ? $totals['pay_skipped']++ : $totals['pay_posted']++;
                    } else {
                        $beforeId = $vouchers->findBySource(Payment::class, $payment->id)?->id;
                        $voucher  = $poster->postPayment($payment);
                        if (!$voucher) $totals['pay_skipped']++;
                        elseif ($beforeId && $voucher->id === $beforeId) $totals['pay_skipped']++;
                        else $totals['pay_posted']++;
                    }
                } catch (\Throwable $e) {
                    $totals['pay_errors']++;
                    $this->newLine();
                    $this->error("payment #{$payment->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] ' : '') . sprintf(
            'Bills: %d posted, %d skipped, %d errors  |  Payments: %d posted, %d skipped, %d errors',
            $totals['bills_posted'], $totals['bills_skipped'], $totals['bills_errors'],
            $totals['pay_posted'],   $totals['pay_skipped'],   $totals['pay_errors']
        ));

        return self::SUCCESS;
    }
}
