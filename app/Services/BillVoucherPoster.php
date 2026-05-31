<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;

/**
 * Converts billing events into double-entry vouchers.
 *
 *   Bill created   → Sales voucher    (Dr Sundry Debtor, Cr Income heads + Cr Output GST)
 *   Payment posted → Receipt voucher  (Dr Cash/Bank,     Cr Sundry Debtor)
 *
 * Idempotent via source_type+source_id on the voucher. Toggleable via the
 * accounts_auto_post_bills setting. Silent no-op if the accounts module is
 * not enabled (so non-licensed installs aren't impacted).
 */
class BillVoucherPoster
{
    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly GstSplitService $gstSplit,
        private readonly LicenseService $license,
    ) {}

    public function postBill(Bill $bill): ?Voucher
    {
        if (!$this->shouldRun('bills')) return null;
        if ($existing = $this->voucherService->findBySource(Bill::class, $bill->id)) return $existing;
        if ((float) $bill->total_amount <= 0) return null;

        try {
            $entries = $this->buildBillEntries($bill);
            if (empty($entries)) return null;

            return $this->voucherService->create([
                'type'           => 'SAL',
                'date'           => $bill->bill_date ?: $bill->created_at,
                'narration'      => "Auto-posted from bill #{$bill->bill_no} (patient: " . ($bill->patient->full_name ?? $bill->patient_id) . ')',
                'party_ledger'   => $this->customerLedgerId(),
                'reference_no'   => $bill->bill_no,
                'reference_date' => $bill->bill_date,
                'source_type'    => Bill::class,
                'source_id'      => $bill->id,
                'entries'        => $entries,
                'created_by'     => $bill->created_by,
            ]);
        } catch (\Throwable $e) {
            // Don't break the bill flow if accounting fails — log and move on
            Log::warning('Auto-post Sales voucher for bill #' . $bill->id . ' failed: ' . $e->getMessage());
            return null;
        }
    }

    public function postPayment(Payment $payment): ?Voucher
    {
        if (!$this->shouldRun('bills')) return null;
        if ($existing = $this->voucherService->findBySource(Payment::class, $payment->id)) return $existing;
        if ((float) $payment->amount <= 0) return null;

        try {
            $bill = Bill::find($payment->bill_id);
            $payFromCode = $this->paymentMethodToLedgerCode($payment->payment_method);

            return $this->voucherService->create([
                'type'           => 'RCT',
                'date'           => $payment->payment_date ?: $payment->created_at,
                'narration'      => 'Received against bill ' . ($bill?->bill_no ?? "#{$payment->bill_id}") . " via {$payment->payment_method}",
                'party_ledger'   => $this->customerLedgerId(),
                'reference_no'   => $payment->transaction_ref ?: ($bill?->bill_no),
                'source_type'    => Payment::class,
                'source_id'      => $payment->id,
                'entries' => [
                    ['ledger' => $payFromCode,                  'dr' => (float) $payment->amount, 'narration' => 'Bank/Cash receipt'],
                    ['ledger' => $this->customerLedgerCode(),   'cr' => (float) $payment->amount, 'narration' => 'Settled debtor'],
                ],
                'created_by' => $payment->received_by ?: 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Auto-post Receipt voucher for payment #' . $payment->id . ' failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── internals ─────────────────────────────────────────────────────────

    /** Item_type → income ledger code map. Anything unknown falls to SALE-OTHER. */
    private function incomeLedgerCode(string $itemType): string
    {
        $map = [
            'Consultation'  => 'SALE-CONS',
            'Investigation' => 'SALE-LAB',
            'Lab'           => 'SALE-LAB',
            'Radiology'     => 'SALE-RAD',
            'Procedure'     => 'SALE-PROC',
            'Surgery'       => 'SALE-PROC',
            'Medicine'      => 'SALE-PHARM',
            'Pharmacy'      => 'SALE-PHARM',
            'Room Rent'     => 'SALE-IPD',
            'Bed'           => 'SALE-IPD',
            'Admission'     => 'SALE-IPD',
            'Vaccination'   => 'SALE-VAC',
            'Service'       => 'SALE-OTHER',
            'OpenEMR'       => 'SALE-OTHER',
        ];
        return $map[$itemType] ?? 'SALE-OTHER';
    }

    private function paymentMethodToLedgerCode(?string $method): string
    {
        $method = strtolower((string) $method);
        if (str_contains($method, 'card') || str_contains($method, 'upi') || str_contains($method, 'bank') ||
            str_contains($method, 'cheque') || str_contains($method, 'neft') || str_contains($method, 'rtgs') || str_contains($method, 'online')) {
            return 'BANK-PRI';
        }
        return 'CASH';
    }

    /** Cash patient default (overrideable later by mapping a patient→ledger). */
    private function customerLedgerCode(): string { return 'SDR-CASH'; }
    private function customerLedgerId(): ?int { return AccountLedger::where('code', 'SDR-CASH')->value('id'); }

    private function buildBillEntries(Bill $bill): array
    {
        $bill->loadMissing('items');
        $items = $bill->items;

        // 1) Aggregate taxable + tax per income ledger
        $byLedger = []; // code => ['taxable'=>..., 'tax'=>...]
        foreach ($items as $it) {
            $code = $this->incomeLedgerCode((string) $it->item_type);
            $taxable = max(0, (float) $it->total - (float) $it->tax);
            $byLedger[$code]['taxable'] = ($byLedger[$code]['taxable'] ?? 0) + $taxable;
            $byLedger[$code]['tax']     = ($byLedger[$code]['tax']     ?? 0) + (float) $it->tax;
        }
        // Header-only bills (no line items — common for legacy/imported bills)
        // would otherwise leave $byLedger empty, sending the whole bill total
        // to TAX-RNDOFF via the round-off step. Fall back to SALE-OTHER for
        // the full taxable amount so the income side is properly recognised.
        if (empty($byLedger) && (float) $bill->total_amount > 0) {
            $byLedger['SALE-OTHER'] = ['taxable' => (float) $bill->total_amount, 'tax' => 0];
        }

        // Apply bill-level discount proportionally (reduce taxable side)
        $discount = (float) $bill->discount_amount;
        $totalTaxable = array_sum(array_column($byLedger, 'taxable')) ?: 1;
        if ($discount > 0) {
            foreach ($byLedger as $code => &$bucket) {
                $share = $bucket['taxable'] / $totalTaxable;
                $bucket['taxable'] = max(0, round($bucket['taxable'] - $discount * $share, 2));
            }
            unset($bucket);
        }

        $clinicState = $this->gstSplit->clinicState();
        $partyState  = null; // cash patient — future: map per-patient

        $cgstTotal = 0; $sgstTotal = 0; $igstTotal = 0;
        $entries = [];

        // 2) Sundry Debtor (or Cash Patients) on Dr side — the full bill amount
        $entries[] = ['ledger' => $this->customerLedgerCode(), 'dr' => round((float) $bill->total_amount, 2), 'narration' => 'Bill ' . $bill->bill_no];

        // 3) One credit row per income ledger (taxable only)
        foreach ($byLedger as $code => $bucket) {
            if ($bucket['taxable'] <= 0 && $bucket['tax'] <= 0) continue;
            $rate = $bucket['taxable'] > 0 ? round($bucket['tax'] / $bucket['taxable'] * 100, 2) : 0;
            $split = $this->gstSplit->split($bucket['taxable'], $rate, $clinicState, $partyState);
            $cgstTotal += $split['cgst']; $sgstTotal += $split['sgst']; $igstTotal += $split['igst'];

            $entries[] = [
                'ledger'         => $code,
                'cr'             => round($bucket['taxable'], 2),
                'narration'      => null,
                'taxable_amount' => $bucket['taxable'] > 0 ? round($bucket['taxable'], 2) : null,
                'gst_rate'       => $rate ?: null,
                'cgst_amount'    => $split['cgst'] ?: null,
                'sgst_amount'    => $split['sgst'] ?: null,
                'igst_amount'    => $split['igst'] ?: null,
            ];
        }

        // 4) Aggregated GST credit rows
        if ($cgstTotal > 0) $entries[] = ['ledger' => 'TAX-OCGST', 'cr' => round($cgstTotal, 2), 'cgst_amount' => round($cgstTotal, 2)];
        if ($sgstTotal > 0) $entries[] = ['ledger' => 'TAX-OSGST', 'cr' => round($sgstTotal, 2), 'sgst_amount' => round($sgstTotal, 2)];
        if ($igstTotal > 0) $entries[] = ['ledger' => 'TAX-OIGST', 'cr' => round($igstTotal, 2), 'igst_amount' => round($igstTotal, 2)];

        // 5) Round off so Σdr == Σcr (handles all the per-line rounding)
        $dr = round(collect($entries)->sum(fn($e) => (float) ($e['dr'] ?? 0)), 2);
        $cr = round(collect($entries)->sum(fn($e) => (float) ($e['cr'] ?? 0)), 2);
        $diff = round($dr - $cr, 2);
        if (abs($diff) > 0.005) {
            // diff > 0 means Cr is short — credit Round Off; diff < 0 means Dr is short — debit Round Off
            if ($diff > 0) {
                $entries[] = ['ledger' => 'TAX-RNDOFF', 'cr' => abs($diff), 'narration' => 'Round off'];
            } else {
                $entries[] = ['ledger' => 'TAX-RNDOFF', 'dr' => abs($diff), 'narration' => 'Round off'];
            }
        }

        return $entries;
    }

    private function shouldRun(string $kind): bool
    {
        if (!$this->license->isEnabled('accounts')) return false;
        $key = $kind === 'bills' ? 'accounts_auto_post_bills' : 'accounts_auto_post_purchases';
        return (string) (setting($key) ?? '1') === '1';
    }
}
