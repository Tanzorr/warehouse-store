<?php

namespace App\Domain\Order;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\Events\OrderCreated;
use App\Domain\Order\Events\OrderItemAdded;
use App\Domain\Order\Events\OrderStatusChanged;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\AggregateRoot;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Quantity;

class Order extends AggregateRoot
{
    private OrderStatus $paymentStatus;

    /** @var OrderItem[] */
    private array $items = [];

    private function __construct(
        private readonly OrderId $id,
        private readonly UserId $userId,
        private string $status,
        private Money $totalPrice,
        private Address $shippingAddress,
        private ?Address $billingAddress,
        private ?string $notes,
        private ?string $reservationId,
    ) {
        $this->paymentStatus = OrderStatus::Pending;
    }

    public static function create(
        OrderId $id,
        UserId $userId,
        string $status,
        Money $totalPrice,
        Address $shippingAddress,
        ?Address $billingAddress = null,
        ?string $notes = null,
    ): self {
        $order = new self($id, $userId, $status, $totalPrice, $shippingAddress, $billingAddress, $notes, null);

        $order->recordEvent(new OrderCreated(
            orderId: $id,
            userId: $userId,
            occurredAt: new \DateTimeImmutable,
        ));

        return $order;
    }

    /** @param OrderItem[] $items */
    public static function reconstitute(
        OrderId $id,
        UserId $userId,
        string $status,
        OrderStatus $paymentStatus,
        Money $totalPrice,
        Address $shippingAddress,
        ?Address $billingAddress,
        ?string $notes,
        ?string $reservationId,
        array $items,
    ): self {
        $order = new self($id, $userId, $status, $totalPrice, $shippingAddress, $billingAddress, $notes, $reservationId);
        $order->paymentStatus = $paymentStatus;
        $order->items = $items;

        return $order;
    }

    public function addItem(ProductId $productId, Money $price, Quantity $quantity): void
    {
        if ($this->paymentStatus !== OrderStatus::Pending) {
            throw new \DomainException(
                "Cannot add items to order with payment status: {$this->paymentStatus->value}"
            );
        }

        $this->items[] = OrderItem::create($productId, $price, $quantity);

        $this->recordEvent(new OrderItemAdded(
            orderId: $this->id,
            productId: $productId,
            occurredAt: new \DateTimeImmutable,
        ));
    }

    public function markAsPaid(string $reservationExternalId): void
    {
        if (! $this->paymentStatus->canTransitionTo(OrderStatus::Paid)) {
            throw new \DomainException(
                "Order #{$this->id->value} cannot transition from {$this->paymentStatus->value} to paid."
            );
        }

        $previousPaymentStatus = $this->paymentStatus;
        $this->paymentStatus = OrderStatus::Paid;
        $this->reservationId = $reservationExternalId;

        $this->recordEvent(new OrderPaymentConfirmed(
            orderId: $this->id->value,
            userId: $this->userId->value,
            reservationExternalId: $reservationExternalId,
            totalPrice: $this->calculateTotal()->amount,
            occurredAt: new \DateTimeImmutable,
        ));

        $this->recordEvent(new OrderStatusChanged(
            orderId: $this->id,
            from: $previousPaymentStatus,
            to: $this->paymentStatus,
            occurredAt: new \DateTimeImmutable,
        ));
    }

    public function calculateTotal(): Money
    {
        if (empty($this->items)) {
            return $this->totalPrice;
        }

        return array_reduce(
            $this->items,
            fn (Money $carry, OrderItem $item) => $carry->add($item->getSubtotal()),
            Money::zero()
        );
    }

    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaymentStatus(): OrderStatus
    {
        return $this->paymentStatus;
    }

    public function getTotalPrice(): Money
    {
        return $this->totalPrice;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getReservationId(): ?string
    {
        return $this->reservationId;
    }

    /** @return OrderItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
