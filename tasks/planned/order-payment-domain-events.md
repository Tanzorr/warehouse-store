# Task Plan: Order Payment — Domain Events, Queues & CQRS

## Goal

Implement the "order paid → warehouse confirms reservation" business process across both services.
This is a **learning task** — every file is chosen to demonstrate a specific DDD or queue concept clearly.
The end result is also demonstrable end-to-end.

---

## Concepts Covered

| Concept | Where | File |
|---------|-------|------|
| Value Object | shop-2-back | `Domain/Order/OrderStatus.php` |
| Domain Event | shop-2-back | `Domain/Order/OrderPaymentConfirmed.php` |
| Application Service | shop-2-back | `Services/OrderApplicationService.php` |
| Queued Listener (`ShouldQueue`) | shop-2-back | `Listeners/DispatchWarehouseNotification.php` |
| Job with retry + backoff + `failed()` | shop-2-back | `Jobs/NotifyWarehouseOfPaymentJob.php` |
| Command (CQRS) | warehouses | `Application/Command/CommitReservation/CommitReservationCommand.php` |
| Command Handler | warehouses | `Application/Command/CommitReservation/CommitReservationHandler.php` |
| Symfony Messenger async transport | warehouses | `config/packages/messenger.yaml` |
| Webhook controller | warehouses | `Controller/WebhookController.php` |

---

## Prerequisites

- Symfony Messenger must be installed (see [queue-improvements.md](queue-improvements.md), item 5a–5d)
- `php artisan queue:work` running in shop-2-back
- `php bin/console messenger:consume async` running in warehouses
- A new API route `POST /orders/{id}/pay` is added in shop-2-back (created as part of this task)

---

## Scenario

```
Client → POST /orders/{id}/pay
           ↓
  OrderApplicationService::markAsPaid()
           ↓ fires
  Domain Event: OrderPaymentConfirmed
           ↓ Laravel Event bridges domain → infra
  OrderPaid (Laravel Event)
           ↓ queued listener (ShouldQueue)
  DispatchWarehouseNotification
           ↓ dispatches
  NotifyWarehouseOfPaymentJob  ←── retries × 3, exponential backoff
           ↓ HTTP POST
  warehouses: POST /webhook/order-paid
           ↓
  WebhookController → dispatches
  CommitReservationCommand via Messenger bus
           ↓ async worker
  CommitReservationHandler → ProductReservation.status = committed
```

---

## Part 1 — shop-2-back (Laravel, DDD)

### 1.1 Value Object: `OrderStatus`

**Concept demonstrated:** Value Objects are immutable, carry business rules, and replace primitive strings.

```php
// app/Domain/Order/OrderStatus.php

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending   => [self::Paid, self::Cancelled],
            self::Paid      => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
```

**Why it matters:** `canTransitionTo()` centralises all transition rules. The controller never checks `if ($order->status === 'pending')` — it asks the Value Object.

---

### 1.2 Domain Event: `OrderPaymentConfirmed`

**Concept demonstrated:** Domain Events record facts that happened in the domain. They carry data needed by consumers and are immutable.

```php
// app/Domain/Order/OrderPaymentConfirmed.php

readonly class OrderPaymentConfirmed
{
    public function __construct(
        public int    $orderId,
        public int    $userId,
        public string $reservationExternalId, // warehouses reservation UUID
        public float  $totalPrice,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
```

**Why it matters:** The domain event is a pure PHP object — no Laravel, no Eloquent, no HTTP. It can be unit-tested in isolation and reused across any transport.

---

### 1.3 Application Service: `OrderApplicationService`

**Concept demonstrated:** Application Services orchestrate use cases. They own the transaction boundary and translate domain events into infrastructure events (Laravel Events).

```php
// app/Services/OrderApplicationService.php

readonly class OrderApplicationService
{
    public function __construct(
        private \App\Models\Order $orderModel, // injected repository-style
    ) {}

    public function markAsPaid(int $orderId): \App\Models\Order
    {
        $order = $this->orderModel->findOrFail($orderId);

        $current = OrderStatus::from($order->payment_status);

        if (! $current->canTransitionTo(OrderStatus::Paid)) {
            throw new \DomainException(
                "Order #{$orderId} cannot transition from {$current->value} to paid."
            );
        }

        $order->payment_status = OrderStatus::Paid->value;
        $order->save();

        // Bridge: domain event → Laravel infrastructure event
        $domainEvent = new OrderPaymentConfirmed(
            orderId: $order->id,
            userId: $order->user_id,
            reservationExternalId: $order->reservation_id ?? '',
            totalPrice: (float) $order->total_price,
            occurredAt: new \DateTimeImmutable(),
        );

        event(new \App\Events\OrderPaid($domainEvent));

        return $order;
    }
}
```

**Why it matters:** The HTTP controller becomes trivial (`$service->markAsPaid($id)` + return JSON). Business rules live in the service, not scattered across controllers.

---

### 1.4 Laravel Event bridge: `OrderPaid`

**Concept demonstrated:** The Laravel Event is an infrastructure wrapper. It carries the domain event and is what Laravel's event system dispatches to listeners.

```php
// app/Events/OrderPaid.php

class OrderPaid
{
    use \Illuminate\Foundation\Events\Dispatchable;

    public function __construct(
        public readonly \App\Domain\Order\OrderPaymentConfirmed $domainEvent,
    ) {}
}
```

---

### 1.5 Queued Listener: `DispatchWarehouseNotification`

**Concept demonstrated:** `ShouldQueue` moves listener execution off the HTTP cycle. The HTTP response is returned immediately; the listener runs in the queue worker.

```php
// app/Listeners/DispatchWarehouseNotification.php

class DispatchWarehouseNotification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function handle(\App\Events\OrderPaid $event): void
    {
        \App\Jobs\NotifyWarehouseOfPaymentJob::dispatch($event->domainEvent);
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\OrderPaid::class => [
        \App\Listeners\DispatchWarehouseNotification::class,
    ],
];
```

**Why it matters:** Decouples "order was paid" (domain) from "send HTTP request to warehouse" (infrastructure side-effect). Adding a second listener (e.g. send confirmation email) never touches this class.

---

### 1.6 Job with retry and backoff: `NotifyWarehouseOfPaymentJob`

**Concept demonstrated:** Jobs encapsulate a single unit of async work. `$tries`, `backoff()`, and `failed()` are the three methods every production job should define.

```php
// app/Jobs/NotifyWarehouseOfPaymentJob.php

class NotifyWarehouseOfPaymentJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Queue\SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly \App\Domain\Order\OrderPaymentConfirmed $event,
    ) {}

    /** Exponential backoff: 10s, 30s, 90s */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(\Illuminate\Http\Client\Factory $http): void
    {
        $response = $http
            ->withToken(config('services.warehouses.secret'))
            ->post(config('services.warehouses.url') . '/webhook/order-paid', [
                'order_id'       => $this->event->orderId,
                'reservation_id' => $this->event->reservationExternalId,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Warehouse webhook failed: HTTP {$response->status()}"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted — log for manual intervention.
        \Illuminate\Support\Facades\Log::error('NotifyWarehouseOfPaymentJob permanently failed', [
            'order_id' => $this->event->orderId,
            'error'    => $exception->getMessage(),
        ]);
    }
}
```

Add to `config/services.php`:

```php
'warehouses' => [
    'url'    => env('WAREHOUSES_URL', 'http://localhost:8001'),
    'secret' => env('WAREHOUSES_WEBHOOK_SECRET'),
],
```

**Why it matters:** Throwing from `handle()` triggers a retry. `failed()` is the last resort — fires only after all retries are exhausted. This pattern covers 95% of real-world job requirements.

---

### 1.7 Route and thin controller

```php
// routes/api.php — add inside the sanctum auth group
Route::post('/orders/{order}/pay', [\App\Http\Controllers\OrderController::class, 'pay']);
```

```php
// app/Http/Controllers/OrderController.php — add method
public function pay(int $order, OrderApplicationService $service): \Illuminate\Http\JsonResponse
{
    $order = $service->markAsPaid($order);
    return response()->json(['message' => 'Payment accepted', 'order' => $order], 202);
}
```

**Why it matters:** The controller has no business logic. It delegates to the Application Service and returns a response.

---

## Part 2 — warehouses (Symfony, CQRS + Messenger)

### 2.1 Command: `CommitReservationCommand`

**Concept demonstrated:** A Command is a DTO that expresses an *intent* to change state. It carries only what the handler needs to execute.

```php
// src/Application/Command/CommitReservation/CommitReservationCommand.php

readonly class CommitReservationCommand
{
    public function __construct(
        public string $reservationId, // UUID
    ) {}
}
```

**Why it matters:** The Command is a plain PHP object. It is serialisable, loggable, and testable in isolation. The Messenger bus does not know about HTTP.

---

### 2.2 Command Handler: `CommitReservationHandler`

**Concept demonstrated:** The Handler contains all business logic for the use case. It is the only class that knows about repositories and domain services.

```php
// src/Application/Command/CommitReservation/CommitReservationHandler.php

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommitReservationHandler
{
    public function __construct(
        private \App\Repository\ProductReservationRepository $reservations,
        private \App\Service\StockAvailabilityService $stockService,
        private \Doctrine\ORM\EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(CommitReservationCommand $command): void
    {
        $reservation = $this->reservations->find($command->reservationId);

        if ($reservation === null) {
            // Already deleted or never existed — idempotent, not an error.
            return;
        }

        if ($reservation->getStatus() !== 'pending') {
            // Already committed or cancelled — idempotent.
            return;
        }

        $this->stockService->commitReservation($reservation);
        $this->entityManager->flush();
    }
}
```

**Why it matters:** The `if ... return` guards make the handler **idempotent** — safe to retry if the Messenger delivers the message more than once (at-least-once delivery guarantee).

---

### 2.3 Async Messenger transport

**Concept demonstrated:** Symfony Messenger routes messages to transports. `async` transport uses the existing database — zero new infrastructure.

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000       # ms
                    multiplier: 2     # 1s → 2s → 4s

        routing:
            App\Application\Command\CommitReservation\CommitReservationCommand: async
```

Add to `.env`:

```
MESSENGER_TRANSPORT_DSN=doctrine://default
```

Run migration to create the `messenger_messages` table:

```bash
php bin/console messenger:setup-transports
```

**Why it matters:** The `routing` section is where you decide which messages go async and which run synchronously. All future commands can be added here without touching handler code.

---

### 2.4 Webhook Controller: `WebhookController`

**Concept demonstrated:** The controller is the entry point for the outside world. It validates the request, builds a Command, dispatches it to the bus, and returns immediately (202). It does not call services directly.

```php
// src/Controller/WebhookController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/webhook/order-paid', name: 'webhook_order_paid', methods: ['POST'])]
    public function orderPaid(Request $request): JsonResponse
    {
        $secret = $request->headers->get('Authorization');
        if ($secret !== 'Bearer ' . $_ENV['WEBHOOK_SECRET']) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->toArray();

        if (empty($data['reservation_id'])) {
            return $this->json(['error' => 'reservation_id is required'], 422);
        }

        $this->bus->dispatch(new \App\Application\Command\CommitReservation\CommitReservationCommand(
            reservationId: (string) $data['reservation_id'],
        ));

        return $this->json(['message' => 'Accepted'], 202);
    }
}
```

Add to `.env`:

```
WEBHOOK_SECRET=your-shared-secret
```

**Why it matters:** The webhook returns 202 before the handler runs. The client (shop-2-back job) considers the call successful as soon as the command is queued — decoupling request lifetime from processing time.

---

## Part 3 — Tests

### 3.1 Unit test: `OrderStatusTest` (shop-2-back)

```php
// tests/Unit/Domain/Order/OrderStatusTest.php

it('allows pending to transition to paid', function () {
    $status = OrderStatus::Pending;
    expect($status->canTransitionTo(OrderStatus::Paid))->toBeTrue();
});

it('does not allow paid to transition to cancelled', function () {
    $status = OrderStatus::Paid;
    expect($status->canTransitionTo(OrderStatus::Cancelled))->toBeFalse();
});
```

---

### 3.2 Feature test: `NotifyWarehouseOfPaymentJobTest` (shop-2-back)

```php
// tests/Feature/Jobs/NotifyWarehouseOfPaymentJobTest.php

it('dispatches NotifyWarehouseOfPaymentJob when order is marked as paid', function () {
    Queue::fake();

    $order = Order::factory()->create(['payment_status' => 'pending', 'reservation_id' => 'uuid-123']);

    $this->postJson("/api/orders/{$order->id}/pay")
         ->assertStatus(202);

    Queue::assertPushed(NotifyWarehouseOfPaymentJob::class);
});
```

---

## Order of Implementation

1. **Value Object** `OrderStatus` + unit tests — no dependencies, pure PHP
2. **Domain Event** `OrderPaymentConfirmed` — pure PHP, no dependencies
3. **Application Service** `OrderApplicationService` + controller method + route
4. **Laravel Event** `OrderPaid` + **Queued Listener** `DispatchWarehouseNotification` — register in EventServiceProvider
5. **Job** `NotifyWarehouseOfPaymentJob` — add `WAREHOUSES_URL` and `WAREHOUSES_WEBHOOK_SECRET` to `.env`
6. **Messenger setup** in warehouses (if not done from queue-improvements.md item 5a–5c)
7. **Command + Handler** `CommitReservation*`
8. **WebhookController** + add `WEBHOOK_SECRET` to warehouses `.env`
9. **Feature test** for the job

---

## Demo Checklist

- [ ] `POST /api/orders/{id}/pay` returns 202
- [ ] `jobs` table in shop-2-back has a `NotifyWarehouseOfPaymentJob` row
- [ ] `php artisan queue:work` processes the job and calls warehouses
- [ ] `messenger_messages` table in warehouses has a `CommitReservationCommand` row
- [ ] `php bin/console messenger:consume async` processes the command
- [ ] `ProductReservation.status` is `committed` in the warehouses database

---

## Appendix — Relevant Existing Files

| File | Relevance |
|------|-----------|
| [shop-2-back/app/Jobs/CancelUnpaidOrderJob.php](shop-2-back/app/Jobs/CancelUnpaidOrderJob.php) | Reference for job structure (retry, delay) |
| [shop-2-back/app/Observers/OrderObserver.php](shop-2-back/app/Observers/OrderObserver.php) | Shows how jobs are dispatched from model events |
| [shop-2-back/app/Models/Order.php](shop-2-back/app/Models/Order.php) | `payment_status`, `reservation_id` fields |
| [shop-2-back/config/queue.php](shop-2-back/config/queue.php) | Queue driver (`database`) |
| [warehouses/src/Service/StockAvailabilityService.php](warehouses/src/Service/StockAvailabilityService.php) | `commitReservation()` called by handler |
| [warehouses/src/Entity/ProductReservation.php](warehouses/src/Entity/ProductReservation.php) | Entity with state machine — `status` field |
| [warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php](warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php) | Existing sync subscriber — contrast with new async approach |
| [tasks/planned/queue-improvements.md](tasks/planned/queue-improvements.md) | Messenger install steps (item 5) — prerequisite |
