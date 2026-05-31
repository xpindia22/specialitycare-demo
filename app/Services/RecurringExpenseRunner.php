<?php

namespace App\Services;

use App\Models\RecurringExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringExpenseRunner
{
    public function __construct(private VoucherService $vouchers) {}

    /**
     * Post every recurring expense whose day-of-month has arrived this calendar
     * month and which hasn't already posted for this month. Idempotent — safe
     * to call on every page load.
     *
     * @return array{posted:int, skipped:int, errors:array<int,string>, vouchers:array<int,string>}
     */
    public function postDue(?Carbon $today = null, bool $dryRun = false): array
    {
        $today = $today?->copy()->startOfDay() ?? Carbon::today();
        $monthKey = $today->format('Y-m');

        $candidates = RecurringExpense::with('expenseLedger', 'counterLedger')
            ->where('is_active', true)
            ->where('day_of_month', '<=', $today->day)
            ->where(fn($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $today))
            ->where(fn($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->get();

        $posted = 0; $skipped = 0; $errors = []; $vouchers = [];

        foreach ($candidates as $rx) {
            if ($rx->last_posted_on && $rx->last_posted_on->format('Y-m') === $monthKey) {
                $skipped++;
                continue;
            }
            // Guard against orphan ledger references (ledger deleted after the
            // recurring entry was created). Surface a clean message instead of
            // letting an FK violation bubble up.
            if (!$rx->expenseLedger || !$rx->counterLedger) {
                $missing = !$rx->expenseLedger ? "expense ledger #{$rx->expense_ledger_id}" : "paid-from ledger #{$rx->counter_ledger_id}";
                $errors[] = "{$rx->name}: {$missing} no longer exists — edit or remove this recurring entry";
                continue;
            }
            // Defense in depth: if a voucher has already been posted this month
            // to the same expense ledger (e.g., by manual entry, the backfill
            // command, or a duplicate recurring row), skip. Stops double posts
            // even when last_posted_on is unreliable.
            $alreadyExists = DB::table('voucher_entries as ve')
                ->join('vouchers as v', 'v.id', '=', 've.voucher_id')
                ->where('v.status', 'Posted')
                ->where('ve.ledger_id', $rx->expense_ledger_id)
                ->where('ve.dr_amount', '>', 0)
                ->whereBetween('v.voucher_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->exists();
            if ($alreadyExists) {
                $rx->update(['last_posted_on' => $today]); // sync the flag so we don't keep checking
                $skipped++;
                continue;
            }
            if ($dryRun) { $posted++; continue; }

            try {
                $voucher = $this->vouchers->create([
                    'type'      => 'PAY',
                    'date'      => $today,
                    'narration' => $rx->narration ?: "Recurring: {$rx->name}",
                    'entries'   => [
                        ['ledger' => $rx->expense_ledger_id, 'dr' => (float) $rx->amount],
                        ['ledger' => $rx->counter_ledger_id, 'cr' => (float) $rx->amount],
                    ],
                ]);
                $rx->update(['last_posted_on' => $today]);
                $posted++;
                $vouchers[] = "{$voucher->voucher_no} — {$rx->name} ₹" . number_format((float) $rx->amount, 2);
            } catch (\Throwable $e) {
                $errors[] = "{$rx->name}: {$e->getMessage()}";
            }
        }

        return compact('posted', 'skipped', 'errors', 'vouchers');
    }
}
