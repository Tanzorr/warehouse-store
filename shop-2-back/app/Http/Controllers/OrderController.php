<?php

namespace App\Http\Controllers;

use App\Bus\CommandBusInterface;
use App\Bus\QueryBusInterface;
use App\Commands\Order\CreateOrderCommand;
use App\Commands\Order\DeleteOrderCommand;
use App\Commands\Order\PayOrderCommand;
use App\Commands\Order\UpdateOrderCommand;
use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\ValueObjects\Address;
use App\Domain\Shared\ValueObjects\Money;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Queries\Order\GetOrderByIdQuery;
use App\Queries\Order\GetOrdersQuery;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->queryBus->ask(new GetOrdersQuery));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $order = $this->commandBus->dispatch(new CreateOrderCommand(
                userId: new UserId($data['user_id']),
                status: $data['status'],
                totalPrice: new Money((float) $data['total_price']),
                paymentStatus: $data['payment_status'],
                shippingAddress: new Address($data['shipping_address']),
                billingAddress: isset($data['billing_address']) ? new Address($data['billing_address']) : null,
                items: $data['items'],
            ));
        } catch (\InvalidArgumentException|\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($order->load('orderItems'), 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($this->queryBus->ask(new GetOrderByIdQuery($order->id)));
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        try {
            $updated = $this->commandBus->dispatch(new UpdateOrderCommand(
                orderId: new OrderId($order->id),
                status: $data['status'] ?? null,
                paymentStatus: $data['payment_status'] ?? null,
                totalPrice: isset($data['total_price']) ? new Money((float) $data['total_price']) : null,
                shippingAddress: isset($data['shipping_address']) ? new Address($data['shipping_address']) : null,
                billingAddress: isset($data['billing_address']) ? new Address($data['billing_address']) : null,
                notes: $data['notes'] ?? null,
            ));
        } catch (\InvalidArgumentException|\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($updated);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteOrderCommand(new OrderId($order->id)));

        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function pay(Order $order): JsonResponse
    {
        try {
            $order = $this->commandBus->dispatch(new PayOrderCommand(new OrderId($order->id)));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Payment accepted', 'order' => $order], 202);
    }
}
