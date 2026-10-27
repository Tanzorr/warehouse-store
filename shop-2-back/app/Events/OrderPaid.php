<?php

namespace App\Events;

use App\Domain\Order\OrderPaymentConfirmed;
use Illuminate\Foundation\Events\Dispatchable;

class OrderPaid
{
    use Dispatchable;

    public function __construct(
        public readonly OrderPaymentConfirmed $domainEvent,
    ) {}
}
