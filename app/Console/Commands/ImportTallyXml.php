<?php

namespace App\Console\Commands;

use App\Models\AccountGroup;
use App\Models\AccountLedger;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Models\VoucherType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import a Tally XML export (Masters + Day Book) into the accounts module.
 *
 * Tally's data model maps 1:1 onto ours: GROUP -> account_groups,
 * LEDGER -> account_ledgers, VOUCHER -> vouchers + voucher_entries. The seeded
 * system groups already match Tally's reserved groups by name, so most parents
 * resolve directly.
 *
 * v1 maps the double-entry faithfully (Dr/Cr from ISDEEMEDPOSITIVE), which makes
 * the Trial Balance / P&L / Balance Sheet correct. The per-line GST breakdown
 * (cgst/sgst/igst/hsn) is left for a follow-up — those fields only feed GSTR
 * reports and are nullable.
 *
 *   php artisan accounts:import-tally storage/app/tally/export.xml --dry-run
 */
class ImportTallyXml extends Command
{
    protected $signature = 'accounts:import-tally {file : Path to the Tally XML export}
        {--dry-run : Parse + validate + report, then roll back (write nothing)}
        {--replace : Replace vouchers that already exist (same type+FY+number)}
        {--user= : users.id to attribute created vouchers to (default: first user)}';

    protected $description = 'Import Tally XML (groups, ledgers, vouchers) into the accounts module';

    private int $userId;
    private array $s = [
        'groups_new' => 0, 'groups_existing' => 0,
        'ledgers_new' => 0, 'ledgers_existing' => 0,
        'vouchers_new' => 0, 'vouchers_replaced' => 0, 'vouchers_skipped' => 0,
        'entries' => 0, 'unbalanced' => 0,
    ];
    private array $warnings = [];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->userId = (int) ($this->option('user') ?: optional(User::orderBy('id')->first())->id);
        if (! $this->userId) {
            $this->error('No user found to attribute vouchers to (pass --user=ID).');
            return self::FAILURE;
        }

        $xml = $this->load($file);
        if ($xml === null) {
            return self::FAILURE;
        }

        $messages = $xml->xpath('//TALLYMESSAGE') ?: [];
        $this->info(count($messages).' TALLYMESSAGE block(s) found. Attributing to user #'.$this->userId.'.');

        DB::beginTransaction();
        try {
            foreach ($messages as $m) {
                if (isset($m->GROUP)) {
                    $this->importGroup($m->GROUP);
                }
            }
            foreach ($messages as $m) {
                if (isset($m->LEDGER)) {
                    $this->importLedger($m->LEDGER);
                }
            }
            foreach ($messages as $m) {
                if (isset($m->VOUCHER)) {
                    $this->importVoucher($m->VOUCHER);
                }
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('DRY-RUN: all changes rolled back.');
            } else {
                DB::commit();
                $this->info('Committed.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted (rolled back): '.$e->getMessage());
            $this->line('  at '.$e->getFile().':'.$e->getLine());
            return self::FAILURE;
        }

        $this->report();
        return self::SUCCESS;
    }

    private function load(string $file): ?\SimpleXMLElement
    {
        $raw = file_get_contents($file);
        // Tally often exports as ISO-8859-1 and sprinkles in stray control chars.
        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw);
        if (stripos($raw, 'encoding="utf-8"') === false && ! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_PARSEHUGE);
        if ($xml === false) {
            $this->error('Failed to parse XML:');
            foreach (libxml_get_errors() as $err) {
                $this->line('  '.trim($err->message));
            }
            return null;
        }
        return $xml;
    }

    /** First non-empty value among the given child tag names. */
    private function tag(\SimpleXMLElement $el, array $names): ?string
    {
        foreach ($names as $n) {
            if (isset($el->{$n}) && trim((string) $el->{$n}) !== '') {
                return trim((string) $el->{$n});
            }
        }
        return null;
    }

    private function uniqueCode(string $base, string $table): string
    {
        $base = strtoupper(Str::slug($base, '_')) ?: 'X';
        $code = substr($base, 0, 28);
        $n = 0;
        while (DB::table($table)->where('code', $code)->exists()) {
            $n++;
            $code = substr($base, 0, 24).'_'.$n;
        }
        return $code;
    }

    private function importGroup(\SimpleXMLElement $g): AccountGroup
    {
        $name = trim((string) ($g['NAME'] ?? $this->tag($g, ['NAME']) ?? ''));
        $existing = AccountGroup::whereRaw('LOWER(name)=?', [mb_strtolower($name)])->first();
        if ($existing) {
            $this->s['groups_existing']++;
            return $existing;
        }

        $parentName = $this->tag($g, ['PARENT']);
        $parent = $parentName && strcasecmp($parentName, 'Primary') !== 0
            ? AccountGroup::whereRaw('LOWER(name)=?', [mb_strtolower($parentName)])->first()
            : null;

        // nature: inherit from parent; else infer from Tally's primary-group name.
        $nature = $parent?->nature ?? $this->inferNature($name, $parentName);

        $grp = AccountGroup::create([
            'name' => $name,
            'code' => $this->uniqueCode($name, 'account_groups'),
            'parent_id' => $parent?->id,
            'nature' => $nature,
            'affects_gross_profit' => $parent?->affects_gross_profit ?? false,
            'is_system' => false,
        ]);
        $this->s['groups_new']++;
        return $grp;
    }

    private function inferNature(string $name, ?string $parent): string
    {
        $hay = mb_strtolower($name.' '.$parent);
        if (Str::contains($hay, ['sales', 'income', 'revenue'])) return 'Income';
        if (Str::contains($hay, ['purchase', 'expense', 'expenditure', 'cost'])) return 'Expenses';
        if (Str::contains($hay, ['liabilit', 'capital', 'loan', 'creditor', 'duties', 'tax', 'provision'])) return 'Liabilities';
        return 'Assets';
    }

    private function resolveGroupForLedger(?string $parentName): AccountGroup
    {
        if ($parentName) {
            $g = AccountGroup::whereRaw('LOWER(name)=?', [mb_strtolower($parentName)])->first();
            if ($g) return $g;
        }
        // unknown group -> Suspense (keeps the import lossless; user re-files later)
        $this->warnOnce("Group '{$parentName}' not found; filing ledger under Suspense Account.");
        return AccountGroup::where('code', 'SUSP')->firstOrFail();
    }

    private function importLedger(\SimpleXMLElement $l): AccountLedger
    {
        $name = trim((string) ($l['NAME'] ?? ''));
        $existing = AccountLedger::whereRaw('LOWER(name)=?', [mb_strtolower($name)])->first();
        if ($existing) {
            $this->s['ledgers_existing']++;
            return $existing;
        }

        $group = $this->resolveGroupForLedger($this->tag($l, ['PARENT']));
        $ob = (float) ($this->tag($l, ['OPENINGBALANCE']) ?? 0);

        $led = AccountLedger::create([
            'name' => $name,
            'code' => $this->uniqueCode($name, 'account_ledgers'),
            'group_id' => $group->id,
            'opening_balance' => abs($ob),
            'opening_balance_dr_cr' => $ob < 0 ? 'Cr' : 'Dr',
            'gstin' => $this->tag($l, ['GSTREGISTRATIONNUMBER', 'PARTYGSTIN', 'GSTIN']),
            'state_code' => $this->tag($l, ['STATECODE']),
            'party_type' => $this->partyTypeFor($group),
            'contact_phone' => $this->tag($l, ['LEDGERPHONE', 'LEDGERMOBILE']),
            'contact_email' => $this->tag($l, ['EMAIL']),
            'address' => $this->tag($l, ['LEDGERADDRESS', 'ADDRESS']),
            'is_system' => false,
            'is_active' => true,
        ]);
        $this->s['ledgers_new']++;
        return $led;
    }

    private function partyTypeFor(AccountGroup $g): ?string
    {
        return match (true) {
            str_contains(mb_strtolower($g->name), 'sundry debtor') => 'Customer',
            str_contains(mb_strtolower($g->name), 'sundry creditor') => 'Supplier',
            $g->code === 'BANK' => 'Bank',
            $g->code === 'CASH' => 'Cash',
            $g->code === 'DT' => 'Tax',
            default => 'Other',
        };
    }

    private function findOrCreateLedgerByName(string $name): AccountLedger
    {
        $l = AccountLedger::whereRaw('LOWER(name)=?', [mb_strtolower($name)])->first();
        if ($l) return $l;
        $this->warnOnce("Ledger '{$name}' referenced by a voucher but not in masters; created under Suspense.");
        $susp = AccountGroup::where('code', 'SUSP')->firstOrFail();
        $this->s['ledgers_new']++;
        return AccountLedger::create([
            'name' => $name,
            'code' => $this->uniqueCode($name, 'account_ledgers'),
            'group_id' => $susp->id,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    private function importVoucher(\SimpleXMLElement $v): void
    {
        $typeName = $this->tag($v, ['VOUCHERTYPENAME']) ?? 'Journal';
        $type = VoucherType::whereRaw('LOWER(name)=?', [mb_strtolower($typeName)])->first()
            ?? VoucherType::where('code', 'JV')->firstOrFail();

        $dateRaw = $this->tag($v, ['DATE', 'EFFECTIVEDATE']) ?? '';
        $date = $this->tallyDate($dateRaw);
        if (! $date) {
            $this->warnOnce("Voucher with bad/blank date '{$dateRaw}' skipped.");
            $this->s['vouchers_skipped']++;
            return;
        }
        $fy = (int) ($date->format('n') >= 4 ? $date->format('Y') : $date->format('Y') - 1);
        $no = $this->tag($v, ['VOUCHERNUMBER']) ?? $this->nextAutoNo($type->id, $fy);

        // collect entries (Tally uses ALLLEDGERENTRIES.LIST, some exports LEDGERENTRIES.LIST)
        $lines = [];
        foreach (['ALLLEDGERENTRIES.LIST', 'LEDGERENTRIES.LIST'] as $node) {
            foreach ($v->{$node} ?? [] as $e) {
                $ledName = $this->tag($e, ['LEDGERNAME']);
                if (! $ledName) continue;
                $amt = (float) ($this->tag($e, ['AMOUNT']) ?? 0);
                $isDr = strcasecmp($this->tag($e, ['ISDEEMEDPOSITIVE']) ?? ($amt < 0 ? 'Yes' : 'No'), 'Yes') === 0;
                $lines[] = [
                    'ledger' => $this->findOrCreateLedgerByName($ledName),
                    'dr' => $isDr ? abs($amt) : 0.0,
                    'cr' => $isDr ? 0.0 : abs($amt),
                    'narration' => $this->tag($e, ['NARRATION']),
                ];
            }
        }
        if (empty($lines)) {
            $this->s['vouchers_skipped']++;
            return;
        }

        $sumDr = round(array_sum(array_column($lines, 'dr')), 2);
        $sumCr = round(array_sum(array_column($lines, 'cr')), 2);
        if (abs($sumDr - $sumCr) > 0.01) {
            $this->s['unbalanced']++;
            $this->warnOnce("Voucher {$type->code}/{$no} ({$dateRaw}) unbalanced: Dr {$sumDr} != Cr {$sumCr}; skipped.");
            $this->s['vouchers_skipped']++;
            return;
        }

        $existing = Voucher::where(['voucher_type_id' => $type->id, 'fy_year' => $fy, 'voucher_no' => $no])->first();
        if ($existing) {
            if (! $this->option('replace')) {
                $this->s['vouchers_skipped']++;
                return;
            }
            $existing->entries()->delete();
            $existing->delete();
            $this->s['vouchers_replaced']++;
        } else {
            $this->s['vouchers_new']++;
        }

        // party ledger = the Customer/Supplier ledger on the voucher, if any
        $party = collect($lines)->first(fn ($r) => in_array($r['ledger']->party_type, ['Customer', 'Supplier']));

        $voucher = Voucher::create([
            'voucher_type_id' => $type->id,
            'voucher_no' => $no,
            'fy_year' => $fy,
            'voucher_date' => $date->format('Y-m-d'),
            'party_ledger_id' => $party['ledger']->id ?? null,
            'reference_no' => $this->tag($v, ['REFERENCE']),
            'narration' => $this->tag($v, ['NARRATION']),
            'total_amount' => $sumDr,
            'source_type' => 'tally',
            'status' => 'Posted',
            'created_by' => $this->userId,
        ]);

        $line = 0;
        foreach ($lines as $r) {
            VoucherEntry::create([
                'voucher_id' => $voucher->id,
                'ledger_id' => $r['ledger']->id,
                'dr_amount' => $r['dr'],
                'cr_amount' => $r['cr'],
                'narration' => $r['narration'],
                'line_no' => ++$line,
            ]);
            $this->s['entries']++;
        }
    }

    private function tallyDate(string $raw): ?\DateTimeImmutable
    {
        $raw = trim($raw);
        foreach (['Ymd', 'd-m-Y', 'd-M-Y', 'Y-m-d'] as $fmt) {
            $d = \DateTimeImmutable::createFromFormat('!'.$fmt, $raw);
            if ($d !== false) return $d;
        }
        return null;
    }

    private function nextAutoNo(int $typeId, int $fy): string
    {
        $n = Voucher::where(['voucher_type_id' => $typeId, 'fy_year' => $fy])->count() + 1;
        return 'TLY-'.$n;
    }

    private function warnOnce(string $msg): void
    {
        if (! in_array($msg, $this->warnings, true)) {
            $this->warnings[] = $msg;
        }
    }

    private function report(): void
    {
        $this->newLine();
        $this->table(['Metric', 'Count'], collect($this->s)->map(fn ($v, $k) => [$k, $v])->values()->all());
        if ($this->warnings) {
            $this->newLine();
            $this->warn('Notes ('.count($this->warnings).'):');
            foreach (array_slice($this->warnings, 0, 25) as $w) {
                $this->line('  • '.$w);
            }
        }
    }
}
