<?php

namespace App\Domain\Order;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;

class OrderItem
{
    private function __construct(
        private readonly ProductId $productId,
        private readonly Money $price,
        private readonly Quantity $quantity,
    ) {}

    public static function create(ProductId $productId, Money $price, Quantity $quantity): self
    {
        if ($quantity->value === 0) {
            throw new \DomainException('Order item quantity must be at least 1');
        }

        return new self($productId, $price, $quantity);
    }

    public function getSubtotal(): Money
    {
        return $this->price->multiply($this->quantity->value);
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }
}
