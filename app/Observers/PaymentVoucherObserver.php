<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\BillVoucherPoster;

class PaymentVoucherObserver
{
    public function __construct(private readonly BillVoucherPoster $poster) {}

    public function created(Payment $payment): void
    {
        $poster = $this->poster;
        dispatch(function () use ($poster, $payment) {
            $poster->postPayment($payment->fresh());
        })->afterResponse();
    }
}
