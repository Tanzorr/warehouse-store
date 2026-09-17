# План 04 — Laravel Repository Pattern

## Задача / Проблема
**Проблема:** зараз Handlers (і сервіси) напряму використовують Eloquent:
`Product::create([...])`, `Order::find($id)`, `$product->update([...])`.
Це означає що:
1. Domain логіка залежить від деталей persistence (Eloquent, SQL).
2. Не можна протестувати Handler без БД.
3. Якщо змінюється схема БД — треба міняти бізнес-логіку.
4. Aggregate Root (план 03) — чистий PHP клас, але Eloquent про нього нічого не знає.

**Задача:** створити **Repository** — прошарок між доменом і persistence:
1. Domain знає тільки про Interface (`OrderRepository`) — не про Eloquent.
2. Конкретна реалізація (`EloquentOrderRepository`) — в Infrastructure шарі.
3. Handlers залежать від Interface → легко підмінити на тести або іншу БД.

**Що навчає:** Repository Pattern + Dependency Inversion (D в SOLID) + DDD шари.

---

## DDD Шари після реалізації

```
┌─────────────────────────────────────┐
│  Presentation (Controllers)         │  ← Laravel HTTP
│  Application (CommandHandlers)      │  ← диспетч команд
│  Domain (Aggregates, Events, VOs)   │  ← чиста PHP, без фреймворку
│  Infrastructure (Repositories impl) │  ← Eloquent, БД
└─────────────────────────────────────┘
```

Залежності йдуть тільки **вниз** або через **інтерфейси вгору**.
Domain НЕ залежить від Infrastructure.

---

## Файлова структура

```
app/
├── Domain/
│   ├── Product/
│   │   └── ProductRepository.php        ← Interface (в домені)
│   ├── Order/
│   │   └── OrderRepository.php          ← Interface (в домені)
│   └── Shared/
│       └── NotFoundException.php        ← Domain exception
│
└── Infrastructure/
    └── Persistence/
        ├── Product/
        │   ├── EloquentProductRepository.php  ← реалізація
        │   └── ProductMapper.php              ← Eloquent ↔ Domain
        └── Order/
            ├── EloquentOrderRepository.php    ← реалізація
            └── OrderMapper.php                ← Eloquent ↔ Domain
```

---

## Кроки реалізації

### Крок 1 — Repository Interfaces (в Domain шарі)

**Задача:** Domain визначає контракт — що може Repository.
Infrastructure виконує контракт — як це зберігається.

```php
// app/Domain/Product/ProductRepository.php
interface ProductRepository
{
    public function findById(ProductId $id): Product; // throws NotFoundException
    public function findBySku(Sku $sku): ?Product;
    public function save(Product $product): void;
    public function delete(ProductId $id): void;
    public function nextId(): ProductId; // генерація нового Id
}

// app/Domain/Order/OrderRepository.php
interface OrderRepository
{
    public function findById(OrderId $id): Order; // throws NotFoundException
    public function findByUserId(UserId $userId): array; // Order[]
    public function save(Order $order): void;
    public function nextId(): OrderId;
}
```

**Чому `findById` кидає exception а не повертає null:**
В домені відсутній product за id — це помилка (порушений інваріант), не очікуваний стан.
Null повертається тільки для "можливо відсутнього" (`findBySku` — sku може не існувати).

### Крок 2 — Domain Exception

```php
// app/Domain/Shared/NotFoundException.php
class NotFoundException extends \DomainException
{
    public static function forId(string $type, int|string $id): self
    {
        return new self("{$type} with id '{$id}' not found");
    }
}
```

### Крок 3 — Mapper: Eloquent ↔ Domain Aggregate

**Задача:** перетворення між Eloquent Model (persistence) і Domain Aggregate (бізнес-логіка).
Це ключовий клас — ізолює два світи.

```php
// app/Infrastructure/Persistence/Product/ProductMapper.php
class ProductMapper
{
    // Eloquent Model → Domain Aggregate (для читання з БД)
    public function toDomain(\App\Models\Product $model): DomainProduct
    {
        return DomainProduct::reconstitute(
            id:            new ProductId($model->id),
            name:          $model->name,
            description:   $model->description,
            purchasePrice: Money::fromFloat($model->purchase_price),
            salePrice:     Money::fromFloat($model->sale_price),
            stock:         new Quantity($model->stock),
            sku:           new Sku($model->sku),
            categoryId:    new CategoryId($model->category_id),
            userId:        new UserId($model->user_id),
        );
    }

    // Domain Aggregate → array для Eloquent (для запису в БД)
    public function toPersistence(DomainProduct $product): array
    {
        return [
            'id'             => $product->getId()->value,
            'name'           => $product->getName(),
            'description'    => $product->getDescription(),
            'purchase_price' => $product->getPurchasePrice()->toFloat(),
            'sale_price'     => $product->getSalePrice()->toFloat(),
            'stock'          => $product->getStock()->value,
            'sku'            => $product->getSku()->value,
            'category_id'    => $product->getCategoryId()->value,
            'user_id'        => $product->getUserId()->value,
        ];
    }
}
```

**Чому окремий Mapper а не методи в Aggregate:**
Domain Aggregate не повинен знати про формат БД (назви колонок, типи).
Mapper — це деталь Infrastructure. Агрегат залишається чистим.

### Крок 4 — Eloquent Repository Implementation

```php
// app/Infrastructure/Persistence/Product/EloquentProductRepository.php
class EloquentProductRepository implements ProductRepository
{
    public function __construct(
        private readonly ProductMapper $mapper
    ) {}

    public function findById(ProductId $id): DomainProduct
    {
        $model = EloquentProduct::find($id->value);

        if ($model === null) {
            throw NotFoundException::forId('Product', $id->value);
        }

        return $this->mapper->toDomain($model);
    }

    public function findBySku(Sku $sku): ?DomainProduct
    {
        $model = EloquentProduct::where('sku', $sku->value)->first();
        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(DomainProduct $product): void
    {
        EloquentProduct::updateOrCreate(
            ['id' => $product->getId()->value],
            $this->mapper->toPersistence($product),
        );
    }

    public function delete(ProductId $id): void
    {
        EloquentProduct::destroy($id->value);
    }

    public function nextId(): ProductId
    {
        // Автоінкремент — дізнаємось Id після save
        // Для UUID: return new ProductId(Str::uuid()->toString());
        // Для auto-increment: тимчасовий підхід через sequence
        return new ProductId(
            (EloquentProduct::max('id') ?? 0) + 1
        );
    }
}
```

### Крок 5 — Order Mapper (складніший: має OrderItems)

```php
// app/Infrastructure/Persistence/Order/OrderMapper.php
class OrderMapper
{
    public function toDomain(EloquentOrder $model): DomainOrder
    {
        $items = $model->items->map(function (EloquentOrderItem $item) {
            return OrderItem::create(
                productId: new ProductId($item->product_id),
                price:     Money::fromFloat($item->price),
                quantity:  new Quantity($item->quantity),
            );
        })->all();

        return DomainOrder::reconstitute(
            id:     new OrderId($model->id),
            userId: new UserId($model->user_id),
            status: OrderStatus::from($model->status),
            isPaid: $model->payment_status === 'paid',
            items:  $items,
        );
    }

    public function toPersistence(DomainOrder $order): array
    {
        return [
            'user_id'          => $order->getUserId()->value,
            'status'           => $order->getStatus()->value,
            'payment_status'   => $order->isPaid() ? 'paid' : 'pending',
            'total_price'      => $order->calculateTotal()->toFloat(),
        ];
    }
}
```

### Крок 6 — Реєстрація в ServiceProvider (Dependency Injection)

**Задача:** Laravel Container знає що коли хтось просить `ProductRepository` —
треба дати `EloquentProductRepository`.

```php
// app/Providers/AppServiceProvider.php — додати в register():
use App\Domain\Product\ProductRepository;
use App\Domain\Order\OrderRepository;
use App\Infrastructure\Persistence\Product\EloquentProductRepository;
use App\Infrastructure\Persistence\Order\EloquentOrderRepository;

$this->app->bind(ProductRepository::class, EloquentProductRepository::class);
$this->app->bind(OrderRepository::class,   EloquentOrderRepository::class);
```

### Крок 7 — Оновлений Handler використовує Repository

**Задача:** Handler більше не знає про Eloquent.

```php
// app/CommandHandlers/Product/CreateProductHandler.php
class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepository     $repository,
        private readonly DomainEventDispatcher $eventDispatcher,
    ) {}

    public function handle(CreateProductCommand $command): void
    {
        // Перевіряємо унікальність SKU через repository
        $existing = $this->repository->findBySku($command->sku);
        if ($existing !== null) {
            throw new \DomainException("Product with SKU {$command->sku} already exists");
        }

        // Створюємо через Aggregate — інваріанти перевірені всередині
        $product = DomainProduct::create(
            id:            $this->repository->nextId(),
            name:          $command->name,
            description:   $command->description,
            purchasePrice: $command->purchasePrice,
            salePrice:     $command->salePrice,
            stock:         $command->stock,
            sku:           $command->sku,
            categoryId:    $command->categoryId,
            userId:        $command->userId,
        );

        $this->repository->save($product);           // зберегли
        $this->eventDispatcher->dispatch($product);  // відправили events
    }
}
```

---

## Що перевірити після реалізації

- [ ] `CreateProductHandler` не містить жодного `Eloquent::` або `DB::` виклику
- [ ] `ProductRepository::findById` кидає `NotFoundException` якщо немає в БД
- [ ] Зміна схеми БД (перейменування колонки) → зміна тільки в Mapper, не в Domain
- [ ] Handler можна протестувати з `InMemoryProductRepository` без БД

---

## Бонус: InMemory Repository для тестів

```php
// tests/Doubles/InMemoryProductRepository.php
class InMemoryProductRepository implements ProductRepository
{
    private array $products = [];
    private int   $nextId   = 1;

    public function findById(ProductId $id): DomainProduct
    {
        return $this->products[$id->value]
            ?? throw NotFoundException::forId('Product', $id->value);
    }

    public function findBySku(Sku $sku): ?DomainProduct
    {
        foreach ($this->products as $product) {
            if ($product->getSku()->equals($sku)) return $product;
        }
        return null;
    }

    public function save(DomainProduct $product): void
    {
        $this->products[$product->getId()->value] = $product;
    }

    public function delete(ProductId $id): void
    {
        unset($this->products[$id->value]);
    }

    public function nextId(): ProductId
    {
        return new ProductId($this->nextId++);
    }
}
```

**Навіщо:** тест `CreateProductHandler` тепер:
```php
$repo    = new InMemoryProductRepository();
$handler = new CreateProductHandler($repo, $dispatcher);
$handler->handle($command);
// перевіряємо $repo->findById(...) — без БД, без Laravel, за 1мс
```

---

## Зв'язок з іншими планами
- **План 03** (Aggregate Roots) — Repository зберігає/відновлює Aggregates
- **План 01** (Command Bus) — Handlers ін'єктують Repository через конструктор
- **План 02** (Value Objects) — Mapper перетворює примітиви ↔ VO
