<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\StockTransaction;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;

/**
 * Books pharmacy SALES income when medicines are dispensed.
 *
 * A physician dispensing medicines is an EXEMPT supply in India (GST is borne
 * on the purchase, not charged again on dispensing) — so these Sales vouchers
 * carry NO output GST. Their only job is revenue recognition: without this,
 * pharmacy stock leaves as cost (booked at purchase) but no income is ever
 * recorded, so the P&L shows pharmacy as pure cost.
 *
 *   dispensed StockTransaction → SAL voucher: Dr Cash, Cr Pharmacy Sales
 *
 * Idempotent via source_type+source_id (one voucher per dispense line).
 * Toggleable via accounts_auto_post_dispense. Silent no-op if the accounts
 * module is not licensed (so non-licensed installs aren't impacted).
 *
 * Pricing waterfall per unit (selling price data is sparse on this install):
 *   1. medicine.unit_price (the selling price), when set
 *   2. else stock batch unit_cost × (1 + accounts_pharmacy_sale_markup_pct/100)
 *   3. else skip (nothing to value) — logged, not posted.
 */
class PharmacySaleVoucherPoster
{
    private const SALE_LEDGER = 'SALE-PHARM';
    private const CASH_LEDGER = 'CASH';

    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly LicenseService $license,
    ) {}

    public function post(StockTransaction $txn): ?Voucher
    {
        if ($txn->type !== 'dispensed') return null;
        if (!$this->shouldRun()) return null;
        if ($existing = $this->voucherService->findBySource(StockTransaction::class, $txn->id)) return $existing;

        $amount = $this->saleValue($txn);
        if ($amount <= 0) {
            Log::info("Dispense #{$txn->id} not booked as pharmacy sale: no resolvable price (medicine #{$txn->medicine_id}).");
            return null;
        }

        try {
            $medName = $txn->medicine->name ?? "medicine #{$txn->medicine_id}";
            return $this->voucherService->create([
                'type'         => 'SAL',
                'date'         => $txn->created_at ?: now(),
                'narration'    => "Pharmacy dispense — {$medName} x{$txn->quantity}"
                    . ($txn->reference ? " ({$txn->reference})" : ''),
                'reference_no' => $txn->reference,
                'source_type'  => StockTransaction::class,
                'source_id'    => $txn->id,
                'entries'      => [
                    ['ledger' => self::CASH_LEDGER, 'dr' => $amount, 'narration' => 'Pharmacy cash sale'],
                    ['ledger' => self::SALE_LEDGER, 'cr' => $amount, 'narration' => 'Medicines dispensed (exempt)'],
                ],
                'created_by'   => $txn->user_id ?: 1,
            ]);
        } catch (\Throwable $e) {
            // Never break the dispensing flow if accounting fails — log and move on.
            Log::warning("Auto-post pharmacy Sales voucher for dispense #{$txn->id} failed: " . $e->getMessage());
            return null;
        }
    }

    /** Per-line sale value. See class doc for the pricing waterfall. */
    public function saleValue(StockTransaction $txn): float
    {
        $qty = (float) $txn->quantity;
        if ($qty <= 0) return 0.0;

        $unitPrice = (float) ($txn->medicine->unit_price ?? 0);
        if ($unitPrice > 0) {
            return round($qty * $unitPrice, 2);
        }

        $cost = (float) ($txn->stock->unit_cost ?? 0);
        if ($cost > 0) {
            $markup = (float) (setting('accounts_pharmacy_sale_markup_pct') ?? 0);
            return round($qty * $cost * (1 + $markup / 100), 2);
        }

        return 0.0;
    }

    private function shouldRun(): bool
    {
        if (!$this->license->isEnabled('accounts')) return false;
        return (string) (setting('accounts_auto_post_dispense') ?? '1') === '1';
    }
}
