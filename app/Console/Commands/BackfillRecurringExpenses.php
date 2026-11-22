<?php

namespace App\Console\Commands;

use App\Models\AccountGroup;
use App\Models\AccountLedger;
use App\Models\Payee;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * One-shot historical backfill for the clinic's standard monthly outflows
 * (salaries + rent + utilities + Fredrick donation) across the last 3 FYs.
 *
 * Idempotent: dedupes per (debit-ledger, year-month-of-voucher-date), so
 * re-running posts only what's missing. Use --dry-run to preview.
 */
class BackfillRecurringExpenses extends Command
{
    protected $signature   = 'accounts:backfill-recurring
                              {--dry-run : Show what would post without writing anything}
                              {--from= : Override start month YYYY-MM (default: April 3 FYs ago)}
                              {--to= : Override end month YYYY-MM (default: current month)}';
    protected $description = 'Backfill 3 FYs of recurring salary + expense vouchers (idempotent)';

    /**
     * The recurring shape. Tweak amounts/pay-from here if anything changes.
     * Salaries paid from Cash (matches payee.pay_from_ledger_id); recurring
     * bills paid from Bank — Primary (matches existing voucher history).
     */
    private function plan(): array
    {
        return [
            ['label' => 'Salary - Anushika Rajan', 'dr' => 'EXP-PAY-ANUSHIKA',     'cr' => 'CASH',     'amount' => 18000, 'narration' => 'Salary - Anushika Rajan'],
            ['label' => 'Salary - Aparna MTS',     'dr' => 'EXP-PAY-APARNAMTS',    'cr' => 'CASH',     'amount' => 4000,  'narration' => 'Salary - Aparna MTS'],
            ['label' => 'Donation - Fredrick',     'dr' => 'EXP-DON-FREDRICK',     'cr' => 'CASH',     'amount' => 6000,  'narration' => 'Monthly donation - Fredrick'],
            ['label' => 'Rent JHC',                'dr' => 'EXP-RENT',             'cr' => 'BANK-PRI', 'amount' => 20414, 'narration' => 'Clinic rent (JHC)'],
            ['label' => 'Electricity',             'dr' => 'EXP-ELEC',             'cr' => 'BANK-PRI', 'amount' => 1500,  'narration' => 'Electricity bill'],
            ['label' => 'Telephone & Internet',    'dr' => 'EXP-PHONE',            'cr' => 'BANK-PRI', 'amount' => 1500,  'narration' => 'Phone & Internet'],
        ];
    }

    /** Ledger codes we know will exist after setup() runs, even if not in DB yet. */
    private array $pendingLedgers = [];

    public function handle(VoucherService $svc): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->info($dry ? '── DRY RUN ──' : '── POSTING FOR REAL ──');
        $this->newLine();

        // 1. Setup: fix payee links, ensure Fredrick exists.
        $this->setupPayees($dry);

        // 2. Build month range.
        [$from, $to] = $this->monthRange();
        $this->line(sprintf('Months: %s → %s (%d months)', $from->format('M Y'), $to->format('M Y'),
            $from->diffInMonths($to) + 1));
        $this->newLine();

        // 3. Walk months × plan, dedupe, post.
        $plan = $this->plan();
        $totals = ['posted' => 0, 'skipped' => 0, 'warned' => 0, 'amount' => 0.0];
        $rows = [];

        for ($month = $from->copy(); $month->lte($to); $month->addMonth()) {
            $date = $month->copy()->startOfMonth();
            foreach ($plan as $item) {
                $result = $this->processOne($svc, $date, $item, $dry);
                $rows[] = $result;
                $totals[$result['action']]++;
                if ($result['action'] === 'posted') $totals['amount'] += $item['amount'];
            }
        }

        $this->renderSummary($rows, $totals, $dry);

        return self::SUCCESS;
    }

    // ── setup ─────────────────────────────────────────────────────────────

    private function setupPayees(bool $dry): void
    {
        $this->line('<comment>1. Payee links</comment>');

        // Anushika → EXP-PAY-ANUSHIKA
        $this->relinkPayee('Anushika Rajan', 'EXP-PAY-ANUSHIKA', $dry);
        // Aparna → EXP-PAY-APARNAMTS (currently wrongly points at Tea & Refreshments)
        $this->relinkPayee('Aparna MTS', 'EXP-PAY-APARNAMTS', $dry);

        // Fredrick: ensure ledger + payee exist.
        $this->ensureFredrick($dry);
        $this->newLine();
    }

    private function relinkPayee(string $name, string $ledgerCode, bool $dry): void
    {
        $payee = Payee::where('name', $name)->first();
        if (!$payee) {
            $this->warn("   • payee '$name' not found — skipping link fix");
            return;
        }
        $ledger = AccountLedger::where('code', $ledgerCode)->first();
        if (!$ledger) {
            $this->warn("   • ledger $ledgerCode not found — skipping link fix for $name");
            return;
        }
        if ((int) $payee->ledger_id === (int) $ledger->id) {
            $this->line("   • $name → $ledgerCode (already linked)");
            return;
        }
        $oldName = $payee->ledger?->name ?? 'NULL';
        if ($dry) {
            $this->line("   • $name: would relink ledger from [$oldName] → [{$ledger->name}]");
        } else {
            $payee->ledger_id = $ledger->id;
            $payee->saveQuietly();   // skip Payee::updating hook (it touches ledger names)
            $this->info("   ✓ $name relinked: [$oldName] → [{$ledger->name}]");
        }
    }

    private function ensureFredrick(bool $dry): void
    {
        $ledger = AccountLedger::where('code', 'EXP-DON-FREDRICK')->first();
        if (!$ledger) {
            $this->pendingLedgers[] = 'EXP-DON-FREDRICK';
            if ($dry) {
                $this->line('   • would create ledger EXP-DON-FREDRICK "Donation - Fredrick" under IXP');
            } else {
                $groupId = AccountGroup::where('code', 'IXP')->value('id');
                $ledger = AccountLedger::create([
                    'name' => 'Donation - Fredrick',
                    'code' => 'EXP-DON-FREDRICK',
                    'group_id' => $groupId,
                    'opening_balance' => 0,
                    'opening_balance_dr_cr' => 'Dr',
                    'party_type' => 'Other',
                    'is_system' => 1,
                    'is_active' => true,
                ]);
                $this->info('   ✓ created ledger EXP-DON-FREDRICK');
            }
        } else {
            $this->line('   • ledger EXP-DON-FREDRICK exists');
        }

        $payee = Payee::where('name', 'Fredrick')->first();
        if (!$payee) {
            if ($dry) {
                $this->line('   • would create payee "Fredrick" (role=Donation, ₹6,000/mo from Cash)');
            } else {
                $cashId = AccountLedger::where('code', 'CASH')->value('id');
                Payee::create([
                    'name' => 'Fredrick',
                    'role' => 'Donation',
                    'monthly_amount' => 6000,
                    'ledger_id' => $ledger->id,     // set explicitly to skip auto-Salary-ledger
                    'pay_from_ledger_id' => $cashId,
                    'is_active' => true,
                    'notes' => 'Monthly donation recipient',
                ]);
                $this->info('   ✓ created payee Fredrick (₹6,000/mo, Cash)');
            }
        } else {
            $this->line('   • payee Fredrick exists');
        }
    }

    // ── month range ───────────────────────────────────────────────────────

    private function monthRange(): array
    {
        $svc = app(VoucherService::class);
        $today = Carbon::today();
        $currentFy = $svc->fyYear($today);    // e.g., 2026

        $defaultFromFy = $currentFy - 2;       // 3 FYs total
        $startMonth = (int) (setting('fy_start_month') ?: 4);
        $defaultFrom = Carbon::create($defaultFromFy, $startMonth, 1)->startOfMonth();
        $defaultTo   = $today->copy()->startOfMonth();

        $from = $this->option('from') ? Carbon::createFromFormat('Y-m', $this->option('from'))->startOfMonth() : $defaultFrom;
        $to   = $this->option('to')   ? Carbon::createFromFormat('Y-m', $this->option('to'))->startOfMonth()   : $defaultTo;

        return [$from, $to];
    }

    // ── one entry ─────────────────────────────────────────────────────────

    /**
     * @return array{action: 'posted'|'skipped'|'warned', label: string, date: string, voucher_no: ?string, note: ?string}
     */
    private function processOne(VoucherService $svc, Carbon $date, array $item, bool $dry): array
    {
        $drLedger = AccountLedger::where('code', $item['dr'])->first();
        $crLedger = AccountLedger::where('code', $item['cr'])->first();

        $drPending = in_array($item['dr'], $this->pendingLedgers, true);
        if ((!$drLedger && !$drPending) || !$crLedger) {
            return ['action' => 'skipped', 'label' => $item['label'], 'date' => $date->format('Y-m-d'),
                    'voucher_no' => null, 'note' => 'missing ledger'];
        }
        // Dry-run with pending ledger: simulate post, skip dedupe (nothing to collide with).
        if (!$drLedger && $drPending && $dry) {
            return ['action' => 'posted', 'label' => $item['label'], 'date' => $date->format('Y-m-d'),
                    'voucher_no' => '(dry, pending ledger)', 'note' => null];
        }

        // Strict dedupe: an existing voucher entry that debits this ledger in this calendar month.
        $exists = VoucherEntry::where('ledger_id', $drLedger->id)
            ->where('dr_amount', '>', 0)
            ->whereHas('voucher', function ($q) use ($date) {
                $q->where('status', 'Posted')
                  ->whereYear('voucher_date', $date->year)
                  ->whereMonth('voucher_date', $date->month);
            })
            ->exists();

        if ($exists) {
            return ['action' => 'skipped', 'label' => $item['label'], 'date' => $date->format('Y-m-d'),
                    'voucher_no' => null, 'note' => 'already debited this month'];
        }

        // Soft warning: same month, similar amount (±5%), debits a different ledger.
        // Catches cases like Anushika's ₹18,000 posted to generic "Staff Salaries"
        // before the per-payee ledger was linked.
        $warnNote = $this->checkSoftDuplicate($item, $date);

        if ($dry) {
            return ['action' => $warnNote ? 'warned' : 'posted', 'label' => $item['label'],
                    'date' => $date->format('Y-m-d'), 'voucher_no' => '(dry)',
                    'note' => $warnNote ?: 'would post'];
        }

        $voucher = $svc->create([
            'type'      => 'PAY',
            'date'      => $date,
            'narration' => $item['narration'] . ' — ' . $date->format('M Y'),
            'entries'   => [
                ['ledger' => $drLedger->id, 'dr' => (float) $item['amount']],
                ['ledger' => $crLedger->id, 'cr' => (float) $item['amount']],
            ],
        ]);

        return ['action' => $warnNote ? 'warned' : 'posted', 'label' => $item['label'],
                'date' => $date->format('Y-m-d'), 'voucher_no' => $voucher->voucher_no,
                'note' => $warnNote ?: null];
    }

    private function checkSoftDuplicate(array $item, Carbon $date): ?string
    {
        $amount = (float) $item['amount'];
        $minA   = $amount * 0.95;
        $maxA   = $amount * 1.05;

        $hit = VoucherEntry::where('dr_amount', '>=', $minA)
            ->where('dr_amount', '<=', $maxA)
            ->whereHas('voucher', function ($q) use ($date) {
                $q->where('status', 'Posted')
                  ->whereYear('voucher_date', $date->year)
                  ->whereMonth('voucher_date', $date->month);
            })
            ->with('ledger:id,name,code')
            ->first();

        if ($hit && $hit->ledger?->code !== $item['dr']) {
            return "likely dup — same month already has ₹{$hit->dr_amount} on {$hit->ledger?->name}";
        }
        return null;
    }

    // ── summary ───────────────────────────────────────────────────────────

    private function renderSummary(array $rows, array $totals, bool $dry): void
    {
        $this->line('<comment>2. Voucher backfill</comment>');

        // Show every warning row explicitly.
        $warnings = array_filter($rows, fn($r) => $r['action'] === 'warned' || ($r['action'] === 'skipped' && $r['note'] === 'already debited this month'));
        if ($warnings) {
            $this->newLine();
            $this->line('<comment>Notable rows:</comment>');
            foreach ($warnings as $r) {
                $tag = $r['action'] === 'warned' ? '<fg=yellow>WARN</>' : '<fg=cyan>SKIP</>';
                $this->line(sprintf('  %s %s %s — %s', $tag, $r['date'], $r['label'], $r['note']));
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '<info>Summary:</info> posted=%d  warned=%d (posted, but flagged)  skipped=%d  total ₹%s%s',
            $totals['posted'], $totals['warned'], $totals['skipped'],
            number_format($totals['amount'], 2),
            $dry ? '  [DRY RUN — nothing written]' : ''
        ));
    }
}
