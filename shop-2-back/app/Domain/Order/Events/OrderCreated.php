<?php

namespace App\Domain\Order\Events;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\ValueObjects\OrderId;

readonly class OrderCreated
{
    public function __construct(
        public OrderId $orderId,
        public UserId $userId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
