<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Models\VoucherType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Central voucher creator. Enforces double-entry rules so controllers
 * don't have to think about them.
 *
 *   $service->create([
 *     'type'         => 'PAY',
 *     'date'         => '2026-05-16',
 *     'narration'    => 'May rent',
 *     'party_ledger' => null,                 // or AccountLedger / id
 *     'entries'      => [
 *       ['ledger' => 'EXP-RENT', 'dr' => 25000],   // Dr Rent
 *       ['ledger' => 'CASH',     'cr' => 25000],   // Cr Cash
 *     ],
 *   ]);
 *
 * Throws InvalidArgumentException if Σdr != Σcr or an entry has both
 * sides populated. Wraps the whole create in DB::transaction().
 *
 * For auto-posted vouchers (e.g., a Bill creating a Sales voucher),
 * pass `source_type` + `source_id` and call `findBySource()` first to
 * skip duplicates — that's how integrations stay idempotent.
 */
class VoucherService
{
    /**
     * @param  array{
     *   type: string|VoucherType,
     *   date: string|Carbon,
     *   entries: list<array{ledger: string|int|AccountLedger, dr?: float, cr?: float, narration?: string,
     *                       hsn_sac?: string, taxable_amount?: float, gst_rate?: float,
     *                       cgst_amount?: float, sgst_amount?: float, igst_amount?: float, cess_amount?: float}>,
     *   narration?: string,
     *   party_ledger?: string|int|AccountLedger|null,
     *   reference_no?: string,
     *   reference_date?: string|Carbon,
     *   source_type?: string,
     *   source_id?: int,
     *   created_by?: int,
     * } $data
     */
    public function create(array $data): Voucher
    {
        $type    = $this->resolveType($data['type']);
        $date    = Carbon::parse($data['date']);
        $fy      = $this->fyYear($date);
        $entries = $this->normalizeEntries($data['entries'] ?? []);

        $this->validateBalanced($entries);

        $total = array_sum(array_map(fn($e) => $e['dr_amount'], $entries));

        return DB::transaction(function () use ($type, $date, $fy, $entries, $total, $data) {
            $voucher = Voucher::create([
                'voucher_type_id'  => $type->id,
                'voucher_no'       => $this->nextVoucherNo($type, $fy),
                'fy_year'          => $fy,
                'voucher_date'     => $date->toDateString(),
                'party_ledger_id'  => $this->resolveLedgerId($data['party_ledger'] ?? null),
                'reference_no'     => $data['reference_no']   ?? null,
                'reference_date'   => isset($data['reference_date']) ? Carbon::parse($data['reference_date'])->toDateString() : null,
                'narration'        => $data['narration']      ?? null,
                'total_amount'     => $total,
                'source_type'      => $data['source_type']    ?? null,
                'source_id'        => $data['source_id']      ?? null,
                'status'           => 'Posted',
                'created_by'       => $data['created_by'] ?? (auth()->id() ?? $this->systemUserId()),
            ]);

            foreach ($entries as $i => $e) {
                VoucherEntry::create([
                    'voucher_id'     => $voucher->id,
                    'ledger_id'      => $e['ledger_id'],
                    'dr_amount'      => $e['dr_amount'],
                    'cr_amount'      => $e['cr_amount'],
                    'narration'      => $e['narration']      ?? null,
                    'hsn_sac'        => $e['hsn_sac']        ?? null,
                    'taxable_amount' => $e['taxable_amount'] ?? null,
                    'gst_rate'       => $e['gst_rate']       ?? null,
                    'cgst_amount'    => $e['cgst_amount']    ?? null,
                    'sgst_amount'    => $e['sgst_amount']    ?? null,
                    'igst_amount'    => $e['igst_amount']    ?? null,
                    'cess_amount'    => $e['cess_amount']    ?? null,
                    'line_no'        => $i + 1,
                ]);
            }

            return $voucher->load('entries.ledger', 'type', 'party');
        });
    }

    /** Find a voucher previously auto-posted for a given source row. */
    public function findBySource(string $sourceType, int $sourceId): ?Voucher
    {
        return Voucher::where('source_type', $sourceType)->where('source_id', $sourceId)->first();
    }

    /** Soft-cancel — preserves audit trail. Re-posting requires a new voucher. */
    public function cancel(Voucher $voucher, ?string $reason = null): Voucher
    {
        $voucher->update([
            'status'    => 'Cancelled',
            'narration' => trim(($voucher->narration ?? '') . "\n[CANCELLED" . ($reason ? ': ' . $reason : '') . "]"),
        ]);
        return $voucher;
    }

    /** Indian FY: April → March. fy_year stored as the year the FY starts in. */
    public function fyYear(Carbon $date): int
    {
        $startMonth = (int) (setting('fy_start_month') ?: 4);
        return $date->month >= $startMonth ? $date->year : $date->year - 1;
    }

    /** Date range for an FY year. */
    public function fyRange(int $fyYear): array
    {
        $startMonth = (int) (setting('fy_start_month') ?: 4);
        $start = Carbon::create($fyYear,     $startMonth, 1)->startOfDay();
        $end   = Carbon::create($fyYear + 1, $startMonth, 1)->subDay()->endOfDay();
        return [$start, $end];
    }

    // ── internals ─────────────────────────────────────────────────────────

    private function resolveType(string|VoucherType $type): VoucherType
    {
        return $type instanceof VoucherType ? $type : VoucherType::byCode($type);
    }

    private function resolveLedgerId(string|int|AccountLedger|null $ref): ?int
    {
        if ($ref === null) return null;
        if ($ref instanceof AccountLedger) return $ref->id;
        if (is_int($ref) || ctype_digit((string) $ref)) return (int) $ref;
        return AccountLedger::byCode((string) $ref)->id;
    }

    private function normalizeEntries(array $entries): array
    {
        $out = [];
        foreach ($entries as $e) {
            $dr = (float) ($e['dr'] ?? $e['dr_amount'] ?? 0);
            $cr = (float) ($e['cr'] ?? $e['cr_amount'] ?? 0);
            if ($dr < 0 || $cr < 0) {
                throw new \InvalidArgumentException('Dr/Cr amounts must be non-negative.');
            }
            if ($dr > 0 && $cr > 0) {
                throw new \InvalidArgumentException('Entry cannot have both Dr and Cr amounts.');
            }
            if ($dr == 0 && $cr == 0) {
                throw new \InvalidArgumentException('Entry must have either Dr or Cr amount > 0.');
            }
            $out[] = array_merge($e, [
                'ledger_id' => $this->resolveLedgerId($e['ledger'] ?? $e['ledger_id'] ?? null),
                'dr_amount' => round($dr, 2),
                'cr_amount' => round($cr, 2),
            ]);
        }
        return $out;
    }

    private function validateBalanced(array $entries): void
    {
        if (count($entries) < 2) {
            throw new \InvalidArgumentException('A voucher needs at least 2 entries.');
        }
        $dr = round(array_sum(array_map(fn($e) => $e['dr_amount'], $entries)), 2);
        $cr = round(array_sum(array_map(fn($e) => $e['cr_amount'], $entries)), 2);
        if (abs($dr - $cr) > 0.005) {
            throw new \InvalidArgumentException("Voucher not balanced: Σdr={$dr} ≠ Σcr={$cr}");
        }
        if ($dr <= 0) {
            throw new \InvalidArgumentException('Voucher total must be > 0.');
        }
        foreach ($entries as $e) {
            if (empty($e['ledger_id'])) {
                throw new \InvalidArgumentException('Each entry must specify a ledger.');
            }
        }
    }

    /** Numbering like "PAY/2026-27/00001". Per-type, per-FY. */
    private function nextVoucherNo(VoucherType $type, int $fyYear): string
    {
        $prefix = sprintf('%s/%d-%02d/', $type->numbering_prefix, $fyYear, ($fyYear + 1) % 100);
        // Atomic-ish: rely on the unique index (voucher_type_id, fy_year, voucher_no)
        // to surface duplicates if two requests race; transaction will retry at controller level.
        // Order by voucher_no (not id) — after cancellations, manual deletes, or
        // cross-host syncs, id-order can diverge from number-order. The fixed-
        // width zero-padded suffix means lex sort == numeric sort within a
        // single (type, fy) bucket.
        $last = Voucher::where('voucher_type_id', $type->id)
            ->where('fy_year', $fyYear)
            ->where('voucher_no', 'like', $prefix . '%')
            ->orderByDesc('voucher_no')
            ->value('voucher_no');
        $num = $last ? ((int) substr($last, -5) + 1) : 1;
        return $prefix . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    /** Used when nothing is authed (artisan / observers). Falls back to id=1 (first admin). */
    private function systemUserId(): int
    {
        return (int) (\DB::table('users')->orderBy('id')->value('id') ?? 1);
    }
}
