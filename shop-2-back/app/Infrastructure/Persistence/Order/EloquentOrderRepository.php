<?php

namespace App\Infrastructure\Persistence\Order;

use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\Order as DomainOrder;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\NotFoundException;
use App\Models\Order as EloquentOrder;
use App\Models\OrderItem as EloquentOrderItem;
use Illuminate\Support\Facades\DB;

class EloquentOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly OrderMapper $mapper,
    ) {}

    public function findById(OrderId $id): DomainOrder
    {
        $model = EloquentOrder::with('orderItems')->find($id->value);

        if ($model === null) {
            throw NotFoundException::forId('Order', $id->value);
        }

        return $this->mapper->toDomain($model);
    }

    public function findByUserId(UserId $userId): array
    {
        return EloquentOrder::with('orderItems')
            ->where('user_id', $userId->value)
            ->get()
            ->map(fn (EloquentOrder $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(DomainOrder $order): void
    {
        DB::transaction(function () use ($order) {
            EloquentOrder::updateOrCreate(
                ['id' => $order->getId()->value],
                $this->mapper->toPersistence($order),
            );

            EloquentOrderItem::where('order_id', $order->getId()->value)->delete();

            foreach ($order->getItems() as $item) {
                /** @var OrderItem $item */
                EloquentOrderItem::create([
                    'order_id' => $order->getId()->value,
                    'product_id' => $item->getProductId()->value,
                    'quantity' => $item->getQuantity()->value,
                    'price' => $item->getPrice()->amount,
                ]);
            }
        });
    }

    public function delete(OrderId $id): void
    {
        EloquentOrder::destroy($id->value);
    }

    public function nextId(): OrderId
    {
        return new OrderId((int) (EloquentOrder::max('id') ?? 0) + 1);
    }
}
