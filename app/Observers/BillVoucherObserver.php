<?php

namespace App\Observers;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\BillVoucherPoster;
use Illuminate\Support\Facades\Bus;

/**
 * Bill/Payment lifecycle → accounting voucher.
 *
 * Posting runs after the HTTP response so a slow accounting DB write
 * never delays the billing UI (mirrors the WhatsApp/email pattern Robert
 * documented — see memory: feedback_async_notifications).
 */
class BillVoucherObserver
{
    public function __construct(private readonly BillVoucherPoster $poster) {}

    public function created(Bill $bill): void
    {
        $poster = $this->poster;
        dispatch(function () use ($poster, $bill) {
            $poster->postBill($bill->fresh('items'));
        })->afterResponse();
    }

    public function updated(Bill $bill): void
    {
        // Only repost if the totals or status changed AND no voucher exists yet
        // (we don't auto-revise; user can cancel + re-create manually if needed).
        if (!$bill->wasChanged(['total_amount', 'tax_amount', 'discount_amount', 'status'])) return;
        $poster = $this->poster;
        dispatch(function () use ($poster, $bill) {
            $poster->postBill($bill->fresh('items'));
        })->afterResponse();
    }
}
