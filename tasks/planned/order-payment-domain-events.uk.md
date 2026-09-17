# Задача: Оплата замовлення — Domain Events, черги та CQRS

---

## Навіщо взагалі черги і DDD? (Просте пояснення)

### Проблема без черг

Уяви: клієнт натискає «Оплатити». Твій сервер повинен:
1. Зберегти оплату в базі даних
2. Відправити HTTP-запит на склад
3. Дочекатися відповіді від складу
4. Повернути відповідь клієнту

Якщо склад відповідає 3 секунди — клієнт чекає 3 секунди. Якщо склад впав — клієнт отримує помилку, хоча оплата пройшла успішно.

**Черга вирішує це так:**
- Сервер зберігає оплату і одразу відповідає клієнту: «Прийнято!»
- У фоні окремий процес (worker) відправляє запит на склад
- Якщо склад недоступний — worker спробує ще раз через 10 секунд, потім через 30, потім через 90
- Клієнт вже давно отримав відповідь і закрив браузер

### Проблема без DDD

Без DDD бізнес-логіка розповзається по всьому коду:
- В контролері перевіряється статус замовлення
- В моделі є метод `pay()` що робить 5 різних речей
- В Observer-і є ще одна перевірка статусу

Коли треба змінити правило «коли можна оплатити» — шукаєш по всьому проекту і боїшся щось пропустити.

**DDD вирішує це так:**
- Правило «з якого статусу можна перейти до якого» живе в одному місці — `OrderStatus`
- Подія «замовлення оплачено» — це окремий об'єкт `OrderPaymentConfirmed`
- Application Service `OrderApplicationService` — єдине місце де відбувається оплата

---

## Мета

Реалізувати бізнес-процес «замовлення оплачено → склад підтверджує резервацію» між двома сервісами.  
Це **навчальна задача** — кожен файл демонструє конкретну концепцію DDD або черг.  
Результат також можна продемонструвати наскрізно.

---

## Концепції, що вивчаються

| Концепція | Сервіс | Файл |
|-----------|--------|------|
| Value Object | shop-2-back | `Domain/Order/OrderStatus.php` |
| Domain Event | shop-2-back | `Domain/Order/OrderPaymentConfirmed.php` |
| Application Service | shop-2-back | `Services/OrderApplicationService.php` |
| Черговий Listener (`ShouldQueue`) | shop-2-back | `Listeners/DispatchWarehouseNotification.php` |
| Job з retry + backoff + `failed()` | shop-2-back | `Jobs/NotifyWarehouseOfPaymentJob.php` |
| Command (CQRS) | warehouses | `Application/Command/CommitReservation/CommitReservationCommand.php` |
| Command Handler | warehouses | `Application/Command/CommitReservation/CommitReservationHandler.php` |
| Symfony Messenger async transport | warehouses | `config/packages/messenger.yaml` |
| Webhook контролер | warehouses | `Controller/WebhookController.php` |

---

## Передумови

- Symfony Messenger має бути встановлений (див. [queue-improvements.uk.md](queue-improvements.uk.md), пункти 5a–5d)
- `php artisan queue:work` запущено в shop-2-back
- `php bin/console messenger:consume async` запущено в warehouses
- Новий API-маршрут `POST /orders/{id}/pay` додано в shop-2-back (створюється в цій задачі)

---

## Сценарій (що відбувається крок за кроком)

```
Клієнт → POST /orders/{id}/pay
           ↓
  OrderApplicationService::markAsPaid()   ← перевіряє правила переходу статусу
           ↓ генерує
  Domain Event: OrderPaymentConfirmed     ← чистий PHP-об'єкт, факт що сталося
           ↓ Laravel Event зв'язує домен з інфраструктурою
  OrderPaid (Laravel Event)
           ↓ черговий listener (ShouldQueue)
  DispatchWarehouseNotification           ← HTTP-відповідь клієнту вже повернута
           ↓ ставить в чергу
  NotifyWarehouseOfPaymentJob  ←── 3 спроби, затримки 10с / 30с / 90с
           ↓ HTTP POST
  warehouses: POST /webhook/order-paid
           ↓
  WebhookController → ставить в чергу
  CommitReservationCommand через Messenger bus
           ↓ асинхронний worker
  CommitReservationHandler → ProductReservation.status = committed
```

**Ключова ідея:** HTTP-відповідь клієнту повертається ще до того, як склад обробив запит. Обидва сервіси незалежні — якщо склад перезавантажується, оплата все одно дійде до нього через чергу.

---

## Частина 1 — shop-2-back (Laravel, DDD)

### 1.1 Value Object: `OrderStatus`

**Яку концепцію демонструє:** Value Object — незмінний об'єкт що несе бізнес-правила і замінює рядки типу `'pending'`.

**Проблема без Value Object:**
```php
// Логіка розповзається по контролерах і моделях:
if ($order->status === 'pending') {
    $order->status = 'paid';  // А якщо статус 'cancelled'? Перевірки немає!
}
```

**Рішення:**
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
            self::Paid      => [],       // з оплаченого нікуди не можна перейти
            self::Cancelled => [],       // з відміненого нікуди не можна перейти
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
```

**Навіщо це:** `canTransitionTo()` зберігає всі правила переходу в одному місці. Контролер ніколи не перевіряє `if ($order->status === 'pending')` — він питає у Value Object. Якщо треба додати новий статус — змінюєш тільки цей файл.

---

### 1.2 Domain Event: `OrderPaymentConfirmed`

**Яку концепцію демонструє:** Domain Events фіксують факти що сталися в домені. Вони несуть дані потрібні споживачам і є незмінними.

**Що таке Domain Event простими словами:**  
Це як «квиток» що фіксує що саме сталося і коли. Замість того щоб одразу реагувати на дію (платежі → повідомляємо склад), ми спочатку фіксуємо факт, а всі охочі потім можуть на нього підписатися.

```php
// app/Domain/Order/OrderPaymentConfirmed.php

readonly class OrderPaymentConfirmed
{
    public function __construct(
        public int    $orderId,
        public int    $userId,
        public string $reservationExternalId, // UUID резервації на складі
        public float  $totalPrice,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
```

**Навіщо це:** Domain event — чистий PHP-об'єкт без Laravel, без Eloquent, без HTTP. Його можна тестувати ізольовано і передавати через будь-який транспорт (чергу, HTTP, websocket). Якщо завтра треба буде надіслати email при оплаті — просто додаєш ще один listener на цю подію.

---

### 1.3 Application Service: `OrderApplicationService`

**Яку концепцію демонструє:** Application Services оркеструють use-case. Вони відповідають за транзакцію і перекладають domain events в infrastructure events (Laravel Events).

**Навіщо Application Service (а не логіка в контролері):**
- Контролер займається HTTP: читає запит, повертає відповідь
- Application Service займається бізнес-логікою: перевіряє правила, зберігає, генерує події
- Якщо треба викликати цю ж логіку з консольної команди — просто викликаєш той самий сервіс

```php
// app/Services/OrderApplicationService.php

readonly class OrderApplicationService
{
    public function __construct(
        private \App\Models\Order $orderModel,
    ) {}

    public function markAsPaid(int $orderId): \App\Models\Order
    {
        $order = $this->orderModel->findOrFail($orderId);

        $current = OrderStatus::from($order->payment_status);

        if (! $current->canTransitionTo(OrderStatus::Paid)) {
            throw new \DomainException(
                "Замовлення #{$orderId} не може перейти з {$current->value} до paid."
            );
        }

        $order->payment_status = OrderStatus::Paid->value;
        $order->save();

        // Міст: domain event → Laravel infrastructure event
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

**Навіщо це:** HTTP-контролер стає тривіальним (`$service->markAsPaid($id)` + return JSON). Бізнес-правила живуть у сервісі, а не розкидані по контролерах.

---

### 1.4 Laravel Event bridge: `OrderPaid`

**Яку концепцію демонструє:** Laravel Event — це інфраструктурна обгортка. Вона несе domain event і є тим що Laravel dispatches до listeners.

**Навіщо два об'єкти (OrderPaymentConfirmed і OrderPaid):**
- `OrderPaymentConfirmed` — чистий бізнес-об'єкт, не знає про Laravel
- `OrderPaid` — Laravel-обгортка, яка дозволяє використовувати систему listeners
- Це розділення дозволяє переносити код між фреймворками

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

### 1.5 Черговий Listener: `DispatchWarehouseNotification`

**Яку концепцію демонструє:** `ShouldQueue` переносить виконання listener-а за межі HTTP-циклу. HTTP-відповідь повертається одразу; listener запускається у worker-і черги.

**Що таке ShouldQueue простими словами:**  
Без черги: запит → обробка (2 секунди) → відповідь клієнту  
З чергою: запит → відповідь клієнту (0.1 секунди) → worker обробляє у фоні

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

Реєстрація в `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\OrderPaid::class => [
        \App\Listeners\DispatchWarehouseNotification::class,
    ],
];
```

**Навіщо це:** Розв'язує «замовлення оплачено» (домен) від «відправити HTTP-запит на склад» (side-effect інфраструктури). Якщо треба додати другий listener (наприклад, відправити email підтвердження) — цей клас не чіпаємо.

---

### 1.6 Job з retry та backoff: `NotifyWarehouseOfPaymentJob`

**Яку концепцію демонструє:** Job інкапсулює одну одиницю асинхронної роботи. `$tries`, `backoff()` і `failed()` — три методи які має визначати кожен production job.

**Навіщо retry і backoff (збільшувана затримка):**  
Склад може бути тимчасово недоступний. Без retry — задача просто падає і оплата не доходить до складу.  
З retry + backoff: спроба 1 → чекаємо 10с → спроба 2 → чекаємо 30с → спроба 3 → чекаємо 90с → `failed()`

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

    /** Експоненційний backoff: 10с, 30с, 90с */
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
                "Webhook складу провалився: HTTP {$response->status()}"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Всі спроби вичерпано — логуємо для ручного втручання.
        \Illuminate\Support\Facades\Log::error('NotifyWarehouseOfPaymentJob остаточно провалився', [
            'order_id' => $this->event->orderId,
            'error'    => $exception->getMessage(),
        ]);
    }
}
```

Додати до `config/services.php`:

```php
'warehouses' => [
    'url'    => env('WAREHOUSES_URL', 'http://localhost:8001'),
    'secret' => env('WAREHOUSES_WEBHOOK_SECRET'),
],
```

**Навіщо це:** Кидання виключення з `handle()` запускає retry. `failed()` — останній рубіж: спрацьовує тільки після того як всі спроби вичерпано. Цей патерн покриває 95% реальних потреб.

---

### 1.7 Маршрут і тонкий контролер

```php
// routes/api.php — додати всередину sanctum auth групи
Route::post('/orders/{order}/pay', [\App\Http\Controllers\OrderController::class, 'pay']);
```

```php
// app/Http/Controllers/OrderController.php — додати метод
public function pay(int $order, OrderApplicationService $service): \Illuminate\Http\JsonResponse
{
    $order = $service->markAsPaid($order);
    return response()->json(['message' => 'Оплату прийнято', 'order' => $order], 202);
}
```

**Навіщо це:** Контролер не має бізнес-логіки. Він делегує Application Service і повертає відповідь. Два рядки — і все.

---

## Частина 2 — warehouses (Symfony, CQRS + Messenger)

### Що таке CQRS простими словами

CQRS (Command Query Responsibility Segregation) — це принцип: **зміни** і **читання** розділені.

- **Command** — команда на зміну стану: «Підтверди резервацію»
- **Query** — запит даних: «Яким є статус резервації?»

Навіщо: якщо зміни і читання в одному місці — метод `save()` починає також повертати дані, а `get()` починає щось змінювати. CQRS запобігає цьому хаосу.

---

### 2.1 Command: `CommitReservationCommand`

**Яку концепцію демонструє:** Command — це DTO що виражає *намір* змінити стан. Несе тільки те що потрібно handler-у.

**Аналогія:** Якщо Domain Event — це «квиток про те що сталося», то Command — це «наказ зробити щось».

```php
// src/Application/Command/CommitReservation/CommitReservationCommand.php

readonly class CommitReservationCommand
{
    public function __construct(
        public string $reservationId, // UUID
    ) {}
}
```

**Навіщо це:** Command — чистий PHP-об'єкт. Його можна серіалізувати (записати в чергу), логувати і тестувати ізольовано. Messenger bus не знає про HTTP.

---

### 2.2 Command Handler: `CommitReservationHandler`

**Яку концепцію демонструє:** Handler містить всю бізнес-логіку use-case. Це єдиний клас що знає про репозиторії і domain services.

**Ідемпотентність — важлива властивість:**  
Якщо Messenger доставить повідомлення двічі (гарантія «at-least-once»), handler відпрацює без помилки — перевірки `if ... return` захищають від подвійного підтвердження.

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
            // Вже видалено або ніколи не існувало — ідемпотентно, не помилка.
            return;
        }

        if ($reservation->getStatus() !== 'pending') {
            // Вже підтверджено або скасовано — ідемпотентно.
            return;
        }

        $this->stockService->commitReservation($reservation);
        $this->entityManager->flush();
    }
}
```

**Навіщо це:** `if ... return` робить handler **ідемпотентним** — безпечним для повторного виконання якщо Messenger доставить повідомлення більш ніж один раз (гарантія at-least-once delivery).

---

### 2.3 Асинхронний Messenger transport

**Яку концепцію демонструє:** Symfony Messenger маршрутизує повідомлення до транспортів. `async` транспорт використовує наявну базу даних — нова інфраструктура не потрібна.

**Як це працює:**
1. WebhookController отримує запит і кладе `CommitReservationCommand` в таблицю `messenger_messages`
2. Відповідає 202 одразу
3. `messenger:consume async` — окремий процес — читає з таблиці і викликає handler

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000       # мс
                    multiplier: 2     # 1с → 2с → 4с

        routing:
            App\Application\Command\CommitReservation\CommitReservationCommand: async
```

Додати до `.env`:

```
MESSENGER_TRANSPORT_DSN=doctrine://default
```

Запустити міграцію для створення таблиці `messenger_messages`:

```bash
php bin/console messenger:setup-transports
```

**Навіщо це:** Секція `routing` визначає які повідомлення йдуть асинхронно, а які синхронно. Всі майбутні команди додаються сюди без змін у handler-ах.

---

### 2.4 Webhook контролер: `WebhookController`

**Яку концепцію демонструє:** Контролер — точка входу зовнішнього світу. Він валідує запит, будує Command, відправляє його до bus і одразу повертає 202. Він не викликає сервіси напряму.

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
            return $this->json(['error' => 'reservation_id обов\'язковий'], 422);
        }

        $this->bus->dispatch(new \App\Application\Command\CommitReservation\CommitReservationCommand(
            reservationId: (string) $data['reservation_id'],
        ));

        return $this->json(['message' => 'Прийнято'], 202);
    }
}
```

Додати до `.env`:

```
WEBHOOK_SECRET=your-shared-secret
```

**Навіщо це:** Webhook повертає 202 до того, як handler запуститься. Клієнт (job в shop-2-back) вважає виклик успішним одразу після постановки команди в чергу — відв'язує час обробки від часу запиту.

---

## Частина 3 — Тести

### 3.1 Unit тест: `OrderStatusTest` (shop-2-back)

```php
// tests/Unit/Domain/Order/OrderStatusTest.php

it('дозволяє перехід з pending до paid', function () {
    $status = OrderStatus::Pending;
    expect($status->canTransitionTo(OrderStatus::Paid))->toBeTrue();
});

it('не дозволяє перехід з paid до cancelled', function () {
    $status = OrderStatus::Paid;
    expect($status->canTransitionTo(OrderStatus::Cancelled))->toBeFalse();
});
```

---

### 3.2 Feature тест: `NotifyWarehouseOfPaymentJobTest` (shop-2-back)

```php
// tests/Feature/Jobs/NotifyWarehouseOfPaymentJobTest.php

it('ставить NotifyWarehouseOfPaymentJob в чергу коли замовлення відмічене як оплачене', function () {
    Queue::fake();

    $order = Order::factory()->create(['payment_status' => 'pending', 'reservation_id' => 'uuid-123']);

    $this->postJson("/api/orders/{$order->id}/pay")
         ->assertStatus(202);

    Queue::assertPushed(NotifyWarehouseOfPaymentJob::class);
});
```

---

## Порядок реалізації

1. **Value Object** `OrderStatus` + unit тести — без залежностей, чистий PHP
2. **Domain Event** `OrderPaymentConfirmed` — чистий PHP, без залежностей
3. **Application Service** `OrderApplicationService` + метод контролера + маршрут
4. **Laravel Event** `OrderPaid` + **Черговий Listener** `DispatchWarehouseNotification` — зареєструвати в EventServiceProvider
5. **Job** `NotifyWarehouseOfPaymentJob` — додати `WAREHOUSES_URL` та `WAREHOUSES_WEBHOOK_SECRET` до `.env`
6. **Налаштування Messenger** у warehouses (якщо не зроблено з queue-improvements.uk.md пункт 5a–5c)
7. **Command + Handler** `CommitReservation*`
8. **WebhookController** + додати `WEBHOOK_SECRET` до `.env` warehouses
9. **Feature тест** для job-а

---

## Чеклист демонстрації

- [ ] `POST /api/orders/{id}/pay` повертає 202
- [ ] Таблиця `jobs` в shop-2-back має рядок `NotifyWarehouseOfPaymentJob`
- [ ] `php artisan queue:work` обробляє job і викликає warehouses
- [ ] Таблиця `messenger_messages` у warehouses має рядок `CommitReservationCommand`
- [ ] `php bin/console messenger:consume async` обробляє команду
- [ ] `ProductReservation.status` стало `committed` в базі даних warehouses

---

## Додаток — Пов'язані наявні файли

| Файл | Навіщо |
|------|--------|
| [shop-2-back/app/Jobs/CancelUnpaidOrderJob.php](shop-2-back/app/Jobs/CancelUnpaidOrderJob.php) | Приклад структури job (retry, delay) |
| [shop-2-back/app/Observers/OrderObserver.php](shop-2-back/app/Observers/OrderObserver.php) | Показує як job-и ставляться з model events |
| [shop-2-back/app/Models/Order.php](shop-2-back/app/Models/Order.php) | Поля `payment_status`, `reservation_id` |
| [shop-2-back/config/queue.php](shop-2-back/config/queue.php) | Драйвер черги (`database`) |
| [warehouses/src/Service/StockAvailabilityService.php](warehouses/src/Service/StockAvailabilityService.php) | `commitReservation()` викликається handler-ом |
| [warehouses/src/Entity/ProductReservation.php](warehouses/src/Entity/ProductReservation.php) | Entity зі state machine — поле `status` |
| [warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php](warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php) | Існуючий sync subscriber — контраст з новим async підходом |
| [tasks/planned/queue-improvements.uk.md](tasks/planned/queue-improvements.uk.md) | Кроки встановлення Messenger (пункт 5) — передумова |
