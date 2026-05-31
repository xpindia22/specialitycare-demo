<?php

namespace App\Services;

use App\Models\AccountLedger;
use App\Models\Medicine;
use App\Models\PharmacyPurchase;
use App\Models\PharmacyPurchaseItem;
use App\Models\PharmacyStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records a pharmacy stock receipt from a supplier:
 *
 *   1. Increment pharmacy_stock (creates a new batch row, or sums into an
 *      existing matching batch).
 *   2. Auto-post a Purchase voucher (PUR): Dr Pharmacy Purchase + Dr Input GST,
 *      Cr Supplier ledger. GST split is intra/inter-state based on clinic vs
 *      supplier state code.
 *
 * Mirrors BillVoucherPoster for the inverse (purchase) side.
 */
class StockReceiptPoster
{
    /** Ledger codes wired by the accounts module seed. */
    private const PURCHASE_LEDGER = 'PUR-PHARM';
    private const CGST_INPUT      = 'TAX-ICGST';
    private const SGST_INPUT      = 'TAX-ISGST';
    private const IGST_INPUT      = 'TAX-IIGST';

    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly GstSplitService $gstSplit,
    ) {}

    /**
     * @param array{
     *   supplier_ledger_id: int,
     *   invoice_no?: string|null,
     *   invoice_date: string|Carbon,
     *   payment_mode?: string,
     *   narration?: string|null,
     *   created_by?: int|null,
     *   items: list<array{
     *     medicine_id:int, quantity:float, unit_cost:float,
     *     batch_number?:string|null, expiry_date?:string|null,
     *     hsn_code?:string|null, gst_rate?:float|null,
     *   }>
     * } $data
     */
    public function record(array $data): PharmacyPurchase
    {
        return DB::transaction(function () use ($data) {
            $supplier = AccountLedger::findOrFail($data['supplier_ledger_id']);
            $clinicState  = $this->gstSplit->clinicState();
            $supplierState = $supplier->state_code;

            // 1. Calculate lines (and hydrate HSN/GST from medicine master if not supplied)
            $lines = [];
            $subtotal = $cgstTotal = $sgstTotal = $igstTotal = 0.0;

            foreach ($data['items'] as $row) {
                $med = Medicine::with('gstRate')->findOrFail($row['medicine_id']);
                $qty  = (float) $row['quantity'];
                $cost = (float) $row['unit_cost'];
                if ($qty <= 0 || $cost < 0) continue;

                $taxable = round($qty * $cost, 2);
                $hsn = $row['hsn_code']  ?? $med->hsn_code ?? '30049099';
                $rate = $row['gst_rate'] ?? (float) ($med->gstRate->rate ?? 0);

                $split = $this->gstSplit->split($taxable, (float) $rate, $clinicState, $supplierState);

                $line = [
                    'medicine_id'    => $med->id,
                    'batch_number'   => $row['batch_number'] ?? null,
                    'expiry_date'    => !empty($row['expiry_date']) ? Carbon::parse($row['expiry_date'])->toDateString() : null,
                    'quantity'       => $qty,
                    'unit_cost'      => $cost,
                    'hsn_code'       => $hsn,
                    'gst_rate'       => (float) $rate,
                    'taxable_amount' => $taxable,
                    'cgst_amount'    => $split['cgst'],
                    'sgst_amount'    => $split['sgst'],
                    'igst_amount'    => $split['igst'],
                    'line_total'     => round($taxable + $split['cgst'] + $split['sgst'] + $split['igst'], 2),
                ];
                $lines[] = $line;

                $subtotal   += $taxable;
                $cgstTotal  += $split['cgst'];
                $sgstTotal  += $split['sgst'];
                $igstTotal  += $split['igst'];
            }

            if (empty($lines)) {
                throw new \InvalidArgumentException('At least one stock line is required.');
            }

            $grandTotal = round($subtotal + $cgstTotal + $sgstTotal + $igstTotal, 2);
            $roundOff   = round(round($grandTotal) - $grandTotal, 2);
            $grandTotalRounded = round($grandTotal + $roundOff, 2);

            // 2. Persist header
            $purchase = PharmacyPurchase::create([
                'supplier_ledger_id' => $supplier->id,
                'invoice_no'         => $data['invoice_no']   ?? null,
                'invoice_date'       => Carbon::parse($data['invoice_date'])->toDateString(),
                'payment_mode'       => $data['payment_mode'] ?? 'Credit',
                'subtotal'           => round($subtotal, 2),
                'cgst_amount'        => round($cgstTotal, 2),
                'sgst_amount'        => round($sgstTotal, 2),
                'igst_amount'        => round($igstTotal, 2),
                'round_off'          => $roundOff,
                'total_amount'       => $grandTotalRounded,
                'narration'          => $data['narration']   ?? null,
                'created_by'         => $data['created_by']  ?? auth()->id(),
            ]);

            // 3. Persist items + adjust stock
            foreach ($lines as $line) {
                $stock = $this->upsertStockBatch($line, $supplier->name, $data['created_by'] ?? auth()->id());
                $item = PharmacyPurchaseItem::create(array_merge($line, [
                    'purchase_id' => $purchase->id,
                    'stock_id'    => $stock->id,
                ]));
                // Backfill the pivot on stock so we can trace receipt → stock row
                if (!$stock->purchase_item_id) {
                    $stock->update(['purchase_item_id' => $item->id]);
                }
            }

            // 4. Post Purchase voucher
            $voucher = $this->postVoucher($purchase, $clinicState, $supplierState);
            $purchase->update(['voucher_id' => $voucher->id]);

            return $purchase->fresh(['items.medicine', 'supplier', 'voucher']);
        });
    }

    private function upsertStockBatch(array $line, string $supplierName, ?int $userId): PharmacyStock
    {
        // Match same medicine + same batch (same expiry, if both have one)
        $query = PharmacyStock::where('medicine_id', $line['medicine_id'])
            ->where('batch_number', $line['batch_number']);
        if ($line['expiry_date']) $query->where('expiry_date', $line['expiry_date']);
        $existing = $query->first();

        if ($existing) {
            $existing->increment('quantity', $line['quantity']);
            $existing->update([
                'unit_cost' => $line['unit_cost'], // latest cost wins
                'supplier'  => $supplierName,
                'is_active' => true,
            ]);
            return $existing;
        }
        return PharmacyStock::create([
            'medicine_id'   => $line['medicine_id'],
            'batch_number'  => $line['batch_number'],
            'expiry_date'   => $line['expiry_date'],
            'quantity'      => $line['quantity'],
            'unit_cost'     => $line['unit_cost'],
            'supplier'      => $supplierName,
            'added_by'      => $userId,
            'is_active'     => true,
        ]);
    }

    private function postVoucher(PharmacyPurchase $purchase, ?string $clinicState, ?string $supplierState): \App\Models\Voucher
    {
        $intra = !$supplierState || ($clinicState && trim($supplierState) === trim($clinicState));

        $entries = [
            [
                'ledger'         => self::PURCHASE_LEDGER,
                'dr'             => (float) $purchase->subtotal,
                'narration'      => 'Medicines purchased',
                'taxable_amount' => (float) $purchase->subtotal,
            ],
        ];
        if ($intra) {
            if ((float) $purchase->cgst_amount > 0) {
                $entries[] = ['ledger' => self::CGST_INPUT, 'dr' => (float) $purchase->cgst_amount, 'narration' => 'Input CGST', 'cgst_amount' => (float) $purchase->cgst_amount];
                $entries[] = ['ledger' => self::SGST_INPUT, 'dr' => (float) $purchase->sgst_amount, 'narration' => 'Input SGST', 'sgst_amount' => (float) $purchase->sgst_amount];
            }
        } else {
            if ((float) $purchase->igst_amount > 0) {
                $entries[] = ['ledger' => self::IGST_INPUT, 'dr' => (float) $purchase->igst_amount, 'narration' => 'Input IGST', 'igst_amount' => (float) $purchase->igst_amount];
            }
        }
        // Cr Supplier (full grand total). For Cash/Bank payment mode, route the
        // credit there instead so the supplier ledger isn't carried as payable.
        $crLedgerId = match ($purchase->payment_mode) {
            'Cash' => AccountLedger::where('code', 'CASH')->value('id'),
            'Bank' => AccountLedger::where('code', 'BANK-PRI')->value('id'),
            default => $purchase->supplier_ledger_id,
        };
        $entries[] = [
            'ledger'    => $crLedgerId,
            'cr'        => (float) $purchase->total_amount,
            'narration' => $purchase->payment_mode === 'Credit' ? 'Payable to supplier' : "Paid by {$purchase->payment_mode}",
        ];
        // Round-off (if any) to balance the entry
        if (abs((float) $purchase->round_off) > 0.001) {
            $entries[] = [
                'ledger'    => AccountLedger::firstWhere('code', 'ROUND-OFF')?->id ?? AccountLedger::firstWhere('code', 'MISC-EXP')?->id ?? self::PURCHASE_LEDGER,
                ((float) $purchase->round_off > 0 ? 'dr' : 'cr') => abs((float) $purchase->round_off),
                'narration' => 'Round-off',
            ];
        }

        return $this->voucherService->create([
            'type'           => 'PUR',
            'date'           => $purchase->invoice_date,
            'narration'      => trim("Pharmacy purchase from {$purchase->supplier->name}"
                . ($purchase->invoice_no ? " — invoice {$purchase->invoice_no}" : '')),
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
