<?php

namespace App\Commands\Order;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;

readonly class CreateOrderCommand
{
    /**
     * @param array<int, array{product_id: int, quantity: int, price: float}> $items
     */
    public function __construct(
        public UserId $userId,
        public string $status,
        public Money $totalPrice,
        public string $paymentStatus,
        public Address $shippingAddress,
        public ?Address $billingAddress,
        public array $items,
    ) {}
}
