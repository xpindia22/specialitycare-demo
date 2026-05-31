<?php

namespace App\Console\Commands;

use App\Models\AccountGroup;
use App\Models\AccountLedger;
use App\Models\Medicine;
use App\Models\PharmacyPurchase;
use App\Models\PharmacyPurchaseItem;
use App\Models\PharmacyStock;
use App\Services\GstSplitService;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Walks pharmacy_stock rows that pre-date the PharmacyPurchases module and
 * synthesises a back-dated Stock Receipt (PharmacyPurchase + items + PUR
 * voucher) for each. Rows are grouped by (supplier, date) → one receipt
 * per group. Idempotent — only touches stock rows where purchase_item_id
 * is NULL.
 *
 * Default behaviour:
 *   - supplier='' / NULL  → mapped to existing 'SCR-GEN' (General Suppliers)
 *   - other supplier strings → find-or-create AccountLedger under Sundry Creditors
 *   - qty=0 OR unit_cost=0 rows  → skipped (no real purchase value)
 *   - HSN + GST rate pulled from medicine master (already classified)
 *   - GST split: intra-state (clinic only; legacy data has no party state)
 *   - Posts a PUR voucher (Dr PUR-PHARM + Dr Input GST, Cr Supplier)
 */
class BackfillPharmacyPurchases extends Command
{
    protected $signature = 'pharmacy:backfill-purchases
                            {--dry-run                : Preview groups + counts without writing}
                            {--include-zero-cost      : Include rows where unit_cost=0 (default: skip)}
                            {--include-null-supplier  : Include stock rows with no supplier name (default: skip — those are usually system-seeded opening stock, not real receipts)}';

    protected $description = 'Backfill PharmacyPurchase records from legacy pharmacy_stock so historical receipts appear in the Purchases UI';

    public function handle(VoucherService $vouchers, GstSplitService $gst): int
    {
        $dry              = (bool) $this->option('dry-run');
        $includeZero      = (bool) $this->option('include-zero-cost');
        $includeNullSup   = (bool) $this->option('include-null-supplier');
        $clinicState      = $gst->clinicState();

        $candidates = PharmacyStock::query()
            ->whereNull('purchase_item_id')
            ->where('quantity', '>', 0)
            ->when(!$includeZero,    fn($q) => $q->where('unit_cost', '>', 0))
            ->when(!$includeNullSup, fn($q) => $q->whereNotNull('supplier')->where('supplier', '<>', ''))
            ->orderBy('created_at')->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nothing to backfill — every stock row is already linked to a purchase.');
            return self::SUCCESS;
        }

        // Group by (supplier-string normalised, calendar date)
        $groups = $candidates->groupBy(function ($s) {
            $sup  = trim((string) $s->supplier);
            $date = Carbon::parse($s->created_at)->toDateString();
            return ($sup === '' ? '__NULL__' : $sup) . '|' . $date;
        });

        $this->info($dry ? '── DRY RUN ──' : '── BACKFILLING ──');
        $this->line(sprintf('Stock rows to process: %d, in %d (supplier × date) groups', $candidates->count(), $groups->count()));
        $this->newLine();

        $totals = ['purchases' => 0, 'lines' => 0, 'voucher_value' => 0.0, 'errors' => 0];

        foreach ($groups as $key => $rows) {
            [$supKey, $date] = explode('|', $key, 2);
            $supplierName    = $supKey === '__NULL__' ? null : $supKey;

            try {
                if ($dry) {
                    $value = $rows->sum(fn($r) => (float) $r->quantity * (float) $r->unit_cost);
                    $totals['purchases']++;
                    $totals['lines'] += $rows->count();
                    $totals['voucher_value'] += $value;
                    $this->line(sprintf('  %s | %s | %d lines | ₹%s',
                        $date, $supplierName ?? '(General Suppliers)', $rows->count(), number_format($value, 2)));
                    continue;
                }

                DB::transaction(function () use ($rows, $supplierName, $date, $clinicState, $gst, $vouchers, &$totals) {
                    $supplier = $this->resolveSupplier($supplierName);
                    $purchase = $this->buildPurchase($rows, $supplier, $date, $clinicState, $gst);
                    $voucher  = $this->postVoucher($purchase, $clinicState, $vouchers);
                    $purchase->update(['voucher_id' => $voucher->id]);
                    $totals['purchases']++;
                    $totals['lines'] += $rows->count();
                    $totals['voucher_value'] += (float) $purchase->total_amount;
                });
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->error(sprintf('  FAIL %s | %s: %s', $date, $supplierName ?? '(null)', $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] ' : '') . sprintf(
            'Created %d purchases (%d lines) totalling ₹%s. Errors: %d.',
            $totals['purchases'], $totals['lines'], number_format($totals['voucher_value'], 2), $totals['errors']
        ));

        return self::SUCCESS;
    }

    private function resolveSupplier(?string $name): AccountLedger
    {
        // Null/empty → use existing General Suppliers
        if (!$name) {
            $l = AccountLedger::where('code', 'SCR-GEN')->first();
            if (!$l) throw new \RuntimeException('Default supplier ledger SCR-GEN not found.');
            return $l;
        }
        // Exact-name match against existing suppliers
        $existing = AccountLedger::where('name', $name)->where('party_type', 'Supplier')->first();
        if ($existing) return $existing;

        // Create under Sundry Creditors
        $group = AccountGroup::where('name', 'Sundry Creditors')->firstOrFail();
        $code = 'SCR-' . strtoupper(preg_replace('/[^A-Z0-9]+/i', '-', $name));
        $code = trim($code, '-');
        $i = 0;
        while (AccountLedger::where('code', $code . ($i ? "-{$i}" : ''))->exists()) $i++;
        if ($i) $code .= "-{$i}";

        return AccountLedger::create([
            'group_id'   => $group->id,
            'code'       => $code,
            'name'       => $name,
            'party_type' => 'Supplier',
            'is_active'  => true,
            'notes'      => 'Auto-created during pharmacy_purchases backfill on ' . now()->toDateString(),
        ]);
    }

    private function buildPurchase($rows, AccountLedger $supplier, string $date, ?string $clinicState, GstSplitService $gst): PharmacyPurchase
    {
        $supplierState = $supplier->state_code;
        $subtotal = $cgst = $sgst = $igst = 0.0;
        $lineRows = [];

        foreach ($rows as $stock) {
            $med = $stock->medicine()->with('gstRate')->first();
            $qty  = (float) $stock->quantity;
            $cost = (float) $stock->unit_cost;
            $taxable = round($qty * $cost, 2);
            $hsn  = $med?->hsn_code ?? '30049099';
            $rate = (float) ($med?->gstRate->rate ?? 12);
            $split = $gst->split($taxable, $rate, $clinicState, $supplierState);

            $lineRows[] = [
                'stock'   => $stock,
                'medicine_id'    => $stock->medicine_id,
                'batch_number'   => $stock->batch_number,
                'expiry_date'    => $stock->expiry_date,
                'quantity'       => $qty,
                'unit_cost'      => $cost,
                'hsn_code'       => $hsn,
                'gst_rate'       => $rate,
                'taxable_amount' => $taxable,
                'cgst_amount'    => $split['cgst'],
                'sgst_amount'    => $split['sgst'],
                'igst_amount'    => $split['igst'],
                'line_total'     => round($taxable + $split['cgst'] + $split['sgst'] + $split['igst'], 2),
            ];
            $subtotal += $taxable;
            $cgst     += $split['cgst'];
            $sgst     += $split['sgst'];
            $igst     += $split['igst'];
        }

        $grand = round($subtotal + $cgst + $sgst + $igst, 2);
        $purchase = PharmacyPurchase::create([
            'supplier_ledger_id' => $supplier->id,
            'invoice_no'         => sprintf('IMPORTED-%s', str_replace('-', '', $date)),
            'invoice_date'       => $date,
            'payment_mode'       => 'Credit',
            'subtotal'           => round($subtotal, 2),
            'cgst_amount'        => round($cgst, 2),
            'sgst_amount'        => round($sgst, 2),
            'igst_amount'        => round($igst, 2),
            'round_off'          => 0,
            'total_amount'       => $grand,
            'narration'          => 'Backfilled from legacy pharmacy_stock',
            'created_by'         => auth()->id() ?: 1,
        ]);

        // Persist items and link existing stock rows back via purchase_item_id
        foreach ($lineRows as $lr) {
            $stock = $lr['stock'];
            unset($lr['stock']);
            $item = PharmacyPurchaseItem::create(array_merge($lr, [
                'purchase_id' => $purchase->id,
                'stock_id'    => $stock->id,
            ]));
            $stock->update(['purchase_item_id' => $item->id]);
        }

        return $purchase->fresh('items', 'supplier');
    }

    private function postVoucher(PharmacyPurchase $purchase, ?string $clinicState, VoucherService $vouchers): \App\Models\Voucher
    {
        $intra = !$purchase->supplier->state_code
              || ($clinicState && trim($purchase->supplier->state_code) === trim($clinicState));

        $entries = [['ledger' => 'PUR-PHARM', 'dr' => (float) $purchase->subtotal, 'taxable_amount' => (float) $purchase->subtotal]];
        if ($intra && (float) $purchase->cgst_amount > 0) {
            $entries[] = ['ledger' => 'TAX-ICGST', 'dr' => (float) $purchase->cgst_amount, 'cgst_amount' => (float) $purchase->cgst_amount];
            $entries[] = ['ledger' => 'TAX-ISGST', 'dr' => (float) $purchase->sgst_amount, 'sgst_amount' => (float) $purchase->sgst_amount];
        } elseif (!$intra && (float) $purchase->igst_amount > 0) {
            $entries[] = ['ledger' => 'TAX-IIGST', 'dr' => (float) $purchase->igst_amount, 'igst_amount' => (float) $purchase->igst_amount];
        }
        $entries[] = ['ledger' => $purchase->supplier_ledger_id, 'cr' => (float) $purchase->total_amount];

        return $vouchers->create([
            'type'           => 'PUR',
            'date'           => $purchase->invoice_date,
            'narration'      => "Backfill: pharmacy stock from {$purchase->supplier->name} ({$purchase->invoice_no})",
            'party_ledger'   => $purchase->supplier_ledger_id,
            'reference_no'   => $purchase->invoice_no,
            'reference_date' => $purchase->invoice_date,
            'source_type'    => PharmacyPurchase::class,
            'source_id'      => $purchase->id,
            'entries'        => $entries,
            'created_by'     => $purchase->created_by,
        ]);
    }
}
