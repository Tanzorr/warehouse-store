# План 03 — Laravel Aggregate Roots + Domain Events

## Задача / Проблема
**Проблема:** зараз бізнес-логіка розкидана між контролерами, сервісами та Eloquent моделями.
Немає чіткого місця де живуть **бізнес-правила**:
- Можна створити `OrderItem` без `Order` — немає захисту агрегату.
- Логіка `markAsPaid` в `OrderApplicationService` — але чому не в `Order`?
- `OrderPaid` event диспетчується з сервісу — але домен не знає про це.
- Немає явного місця де перевіряється "чи можна підтвердити замовлення".

**Задача:** створити **Aggregate Root** — PHP-клас (не Eloquent Model) що:
1. Є єдиною точкою входу для змін (`Order`, `Product`).
2. Захищає **інваріанти** (правила що завжди мають виконуватись).
3. Генерує **Domain Events** при кожній зміні стану.
4. Не залежить від Laravel/Eloquent/БД.

**Що навчає:** ключова концепція DDD — Aggregate Root як серце домену.

---

## Які Aggregates потрібні

| Aggregate | Entities всередині | Основні правила |
|---|---|---|
| `Order` | `OrderItem[]` | Не можна оплатити вже оплачене; total = сума items |
| `Product` | — | sale > purchase; stock >= 0 |

---

## Файлова структура

```
app/Domain/
├── Order/
│   ├── Order.php                    ← Aggregate Root
│   ├── OrderItem.php                ← Entity (тільки через Order)
│   ├── Events/
│   │   ├── OrderCreated.php         ← Domain Event
│   │   ├── OrderPaid.php            ← Domain Event (замінює існуючий)
│   │   ├── OrderStatusChanged.php   ← Domain Event
│   │   └── OrderItemAdded.php       ← Domain Event
│   └── ValueObjects/
│       ├── OrderId.php              ← з плану 02
│       └── OrderStatus.php          ← вже є як enum
├── Product/
│   ├── Product.php                  ← Aggregate Root
│   ├── Events/
│   │   ├── ProductCreated.php       ← Domain Event
│   │   ├── ProductUpdated.php       ← Domain Event
│   │   └── ProductStockChanged.php  ← Domain Event
│   └── ValueObjects/
│       ├── ProductId.php            ← з плану 02
│       └── Sku.php                  ← з плану 02
└── Shared/
    └── AggregateRoot.php            ← базовий клас
```

---

## Кроки реалізації

### Крок 1 — Базовий AggregateRoot

**Задача:** механізм збору Domain Events всередині агрегату.
Events не диспетчуються одразу — збираються і відправляються після збереження.

```php
// app/Domain/Shared/AggregateRoot.php
abstract class AggregateRoot
{
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

**Чому `pullEvents` очищає масив:** event публікується один раз. Якщо aggregate
збережено — events відправлені. При повторному save не дублюються.

### Крок 2 — Domain Events як plain DTO

**Задача:** Domain Event — факт що стався. Незмінний. Без методів.

```php
// app/Domain/Order/Events/OrderPaid.php
readonly class OrderPaid
{
    public function __construct(
        public OrderId  $orderId,
        public UserId   $userId,
        public Money    $total,
        public string   $reservationId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}

// app/Domain/Product/Events/ProductCreated.php
readonly class ProductCreated
{
    public function __construct(
        public ProductId  $productId,
        public Sku        $sku,
        public string     $name,
        public Money      $salePrice,
        public \DateTimeImmutable $occurredAt,
    ) {}
}

// app/Domain/Order/Events/OrderStatusChanged.php
readonly class OrderStatusChanged
{
    public function __construct(
        public OrderId    $orderId,
        public OrderStatus $from,
        public OrderStatus $to,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
```

### Крок 3 — Product Aggregate Root

**Задача:** `Product` захищає свої інваріанти — не допускає невалідних станів.

```php
// app/Domain/Product/Product.php
class Product extends AggregateRoot
{
    private ProductId  $id;
    private string     $name;
    private string     $description;
    private Money      $purchasePrice;
    private Money      $salePrice;
    private Quantity   $stock;
    private Sku        $sku;
    private CategoryId $categoryId;
    private UserId     $userId;

    // Приватний конструктор — тільки через фабричні методи
    private function __construct() {}

    // Фабричний метод для створення нового Product
    public static function create(
        ProductId  $id,
        string     $name,
        string     $description,
        Money      $purchasePrice,
        Money      $salePrice,
        Quantity   $stock,
        Sku        $sku,
        CategoryId $categoryId,
        UserId     $userId,
    ): self {
        // Перевірка інваріанту
        if (!$salePrice->isGreaterThan($purchasePrice)) {
            throw new \DomainException(
                "Sale price must be greater than purchase price"
            );
        }

        $product = new self();
        $product->id            = $id;
        $product->name          = $name;
        $product->description   = $description;
        $product->purchasePrice = $purchasePrice;
        $product->salePrice     = $salePrice;
        $product->stock         = $stock;
        $product->sku           = $sku;
        $product->categoryId    = $categoryId;
        $product->userId        = $userId;

        // Записуємо domain event — факт що продукт створено
        $product->recordEvent(new ProductCreated(
            productId:  $id,
            sku:        $sku,
            name:       $name,
            salePrice:  $salePrice,
            occurredAt: new \DateTimeImmutable(),
        ));

        return $product;
    }

    // Фабричний метод для відновлення з БД (без events)
    public static function reconstitute(
        ProductId  $id,
        string     $name,
        string     $description,
        Money      $purchasePrice,
        Money      $salePrice,
        Quantity   $stock,
        Sku        $sku,
        CategoryId $categoryId,
        UserId     $userId,
    ): self {
        $product = new self();
        $product->id            = $id;
        $product->name          = $name;
        $product->description   = $description;
        $product->purchasePrice = $purchasePrice;
        $product->salePrice     = $salePrice;
        $product->stock         = $stock;
        $product->sku           = $sku;
        $product->categoryId    = $categoryId;
        $product->userId        = $userId;

        return $product; // без recordEvent — продукт вже існував
    }

    // Бізнес-метод: зміна ціни
    public function changePrice(Money $newSalePrice): void
    {
        if (!$newSalePrice->isGreaterThan($this->purchasePrice)) {
            throw new \DomainException("Sale price must be greater than purchase price");
        }

        $this->salePrice = $newSalePrice;

        $this->recordEvent(new ProductUpdated(
            productId:  $this->id,
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    // Getters (тільки читання ззовні)
    public function getId(): ProductId      { return $this->id; }
    public function getName(): string       { return $this->name; }
    public function getSalePrice(): Money   { return $this->salePrice; }
    public function getStock(): Quantity    { return $this->stock; }
    public function getSku(): Sku           { return $this->sku; }
}
```

### Крок 4 — OrderItem Entity

**Задача:** `OrderItem` існує тільки в контексті `Order`. Не є Aggregate Root.

```php
// app/Domain/Order/OrderItem.php
class OrderItem
{
    private function __construct(
        private readonly ProductId $productId,
        private readonly Money     $price,
        private Quantity           $quantity,
    ) {}

    public static function create(
        ProductId $productId,
        Money     $price,
        Quantity  $quantity,
    ): self {
        if ($quantity->value === 0) {
            throw new \DomainException("Order item quantity must be at least 1");
        }

        return new self($productId, $price, $quantity);
    }

    public function getSubtotal(): Money
    {
        return $this->price->multiply($this->quantity->value);
    }

    public function getProductId(): ProductId { return $this->productId; }
    public function getQuantity(): Quantity   { return $this->quantity; }
    public function getPrice(): Money         { return $this->price; }
}
```

### Крок 5 — Order Aggregate Root

**Задача:** `Order` керує `OrderItem[]` і захищає правила замовлення.

```php
// app/Domain/Order/Order.php
class Order extends AggregateRoot
{
    /** @var OrderItem[] */
    private array $items = [];

    private OrderStatus $status;
    private bool $isPaid = false;

    private function __construct(
        private OrderId $id,
        private UserId  $userId,
    ) {
        $this->status = OrderStatus::PENDING;
    }

    public static function create(OrderId $id, UserId $userId): self
    {
        $order = new self($id, $userId);

        $order->recordEvent(new OrderCreated(
            orderId:    $id,
            userId:     $userId,
            occurredAt: new \DateTimeImmutable(),
        ));

        return $order;
    }

    public static function reconstitute(
        OrderId     $id,
        UserId      $userId,
        OrderStatus $status,
        bool        $isPaid,
        array       $items,
    ): self {
        $order = new self($id, $userId);
        $order->status = $status;
        $order->isPaid = $isPaid;
        $order->items  = $items;
        return $order;
    }

    // Інваріант: додавати items можна тільки до pending замовлення
    public function addItem(ProductId $productId, Money $price, Quantity $quantity): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            throw new \DomainException(
                "Cannot add items to order in status: {$this->status->value}"
            );
        }

        $this->items[] = OrderItem::create($productId, $price, $quantity);

        $this->recordEvent(new OrderItemAdded(
            orderId:    $this->id,
            productId:  $productId,
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    // Інваріант: не можна оплатити вже оплачене або скасоване замовлення
    public function markAsPaid(string $reservationId): void
    {
        if ($this->isPaid) {
            throw new \DomainException("Order is already paid");
        }

        if ($this->status === OrderStatus::CANCELLED) {
            throw new \DomainException("Cannot pay cancelled order");
        }

        if (empty($this->items)) {
            throw new \DomainException("Cannot pay empty order");
        }

        $this->isPaid = true;
        $previousStatus = $this->status;
        $this->status = OrderStatus::CONFIRMED;

        $this->recordEvent(new OrderPaid(
            orderId:       $this->id,
            userId:        $this->userId,
            total:         $this->calculateTotal(),
            reservationId: $reservationId,
            occurredAt:    new \DateTimeImmutable(),
        ));

        $this->recordEvent(new OrderStatusChanged(
            orderId:    $this->id,
            from:       $previousStatus,
            to:         $this->status,
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    public function calculateTotal(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->getSubtotal()),
            Money::zero()
        );
    }

    public function getId(): OrderId       { return $this->id; }
    public function getStatus(): OrderStatus { return $this->status; }
    public function isPaid(): bool         { return $this->isPaid; }
    public function getItems(): array      { return $this->items; }
}
```

### Крок 6 — Диспетч Domain Events після збереження

**Задача:** після того як Repository зберіг Aggregate, треба відправити зібрані events.

```php
// app/Domain/Shared/DomainEventDispatcher.php
class DomainEventDispatcher
{
    public function __construct(private readonly Dispatcher $laravelDispatcher) {}

    public function dispatch(AggregateRoot $aggregate): void
    {
        foreach ($aggregate->pullEvents() as $event) {
            $this->laravelDispatcher->dispatch($event);
        }
    }
}
```

```php
// app/CommandHandlers/Order/PayOrderHandler.php — приклад використання
class PayOrderHandler
{
    public function __construct(
        private readonly OrderRepository       $repository,
        private readonly DomainEventDispatcher $eventDispatcher,
    ) {}

    public function handle(PayOrderCommand $command): void
    {
        $order = $this->repository->findById($command->orderId);

        $order->markAsPaid($command->reservationId); // інваріанти перевірені тут

        $this->repository->save($order); // зберегли

        $this->eventDispatcher->dispatch($order); // тепер відправляємо events
        // → OrderPaid event → DispatchWarehouseNotification listener (вже існує)
    }
}
```

---

## Що перевірити після реалізації

- [ ] `Order::create()` → в масиві events є `OrderCreated`
- [ ] `order->markAsPaid()` двічі → throws `DomainException`
- [ ] `order->addItem()` на CANCELLED order → throws `DomainException`
- [ ] `order->calculateTotal()` = сума всіх `item->getSubtotal()`
- [ ] `Product::create()` з `salePrice < purchasePrice` → throws `DomainException`
- [ ] `PayOrderHandler` → після save → `OrderPaid` event відправлено в Laravel

---

## Зв'язок з іншими планами
- **План 02** (Value Objects) — Aggregate приймає VO в методи
- **План 04** (Repository) — Repository зберігає/відновлює Aggregate
- **План 01** (Command Bus) — Handlers викликають методи Aggregate
