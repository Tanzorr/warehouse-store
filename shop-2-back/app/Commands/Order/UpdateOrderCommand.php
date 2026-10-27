<?php

namespace App\Commands\Order;

use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;

readonly class UpdateOrderCommand
{
    public function __construct(
        public OrderId $orderId,
        public ?string $status = null,
        public ?string $paymentStatus = null,
        public ?Money $totalPrice = null,
        public ?Address $shippingAddress = null,
        public ?Address $billingAddress = null,
        public ?string $notes = null,
    ) {}
}
