<?php

namespace App\Observers;

use App\Models\StockTransaction;
use App\Services\PharmacySaleVoucherPoster;

/**
 * Dispensing → pharmacy Sales voucher.
 *
 * Fires for every 'dispensed' stock transaction (manual dispense in the
 * Pharmacy UI and the OpenEMR stock sync both create these), so income is
 * captured regardless of source. Posting runs after the HTTP response so a
 * slow accounting write never delays dispensing (same pattern as
 * BillVoucherObserver — see memory: feedback_async_notifications).
 */
class StockTransactionObserver
{
    public function __construct(private readonly PharmacySaleVoucherPoster $poster) {}

    public function created(StockTransaction $txn): void
    {
        if ($txn->type !== 'dispensed') return;
        $poster = $this->poster;
        dispatch(function () use ($poster, $txn) {
            $poster->post($txn->fresh(['medicine', 'stock']));
        })->afterResponse();
    }
}
