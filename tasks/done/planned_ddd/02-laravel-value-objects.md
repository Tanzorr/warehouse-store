# План 02 — Laravel Value Objects

## Задача / Проблема
**Проблема:** зараз ціни — це `float`, SKU — це `string`, id — це `int`.
Примітивні типи не несуть сенсу і не захищають від помилок:
- Можна передати від'ємну ціну — немає захисту.
- Можна переплутати `purchase_price` і `sale_price` — обидва `float`.
- Можна передати порожній SKU — рядок валідний, але бізнес-правило порушено.
- `$order->total` і `$product->price` — обидва `float`, але їх не можна порівняти без контексту.

**Задача:** створити **Value Objects** — незмінні об'єкти що інкапсулюють
правила валідації та поведінку для конкретного поняття домену.

**Що навчає:** Value Object як будівельний блок DDD.
Різниця між Value Object (рівність за значенням) і Entity (рівність за identity).

---

## Які Value Objects потрібні

| Value Object | Поле | Що захищає |
|---|---|---|
| `Money` | purchase_price, sale_price, total_price | Не від'ємна, точність до копійок |
| `Sku` | sku | Формат, не порожній, uppercase |
| `ProductId` | id в Product aggregate | Типізований ідентифікатор |
| `OrderId` | id в Order aggregate | Типізований ідентифікатор |
| `CategoryId` | category_id | Типізований ідентифікатор |
| `UserId` | user_id | Типізований ідентифікатор |
| `Quantity` | stock, amount | Не від'ємна, ціле число |
| `Address` | shipping_address, billing_address | Структурована адреса |
| `Email` | email | Формат email |

---

## Файлова структура

```
app/Domain/
├── Shared/
│   └── ValueObjects/
│       ├── Money.php
│       ├── Quantity.php
│       ├── Email.php
│       └── Address.php
├── Product/
│   └── ValueObjects/
│       ├── ProductId.php
│       └── Sku.php
├── Order/
│   └── ValueObjects/
│       ├── OrderId.php
│       └── OrderStatus.php     ← вже є як enum, залишити
└── Identity/
    └── ValueObjects/
        ├── UserId.php
        └── CategoryId.php
```

---

## Кроки реалізації

### Крок 1 — Базовий abstract для Id Value Objects

**Задача:** всі Id-об'єкти однакові за структурою — узагальнити.

```php
// app/Domain/Shared/ValueObjects/IntId.php
abstract readonly class IntId
{
    public function __construct(public readonly int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(
                static::class . " must be positive, got: {$value}"
            );
        }
    }

    public function equals(static $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
```

### Крок 2 — Id Value Objects

**Задача:** типізовані id запобігають плутанині `$productId` vs `$orderId`.

```php
// app/Domain/Product/ValueObjects/ProductId.php
readonly class ProductId extends IntId {}

// app/Domain/Order/ValueObjects/OrderId.php
readonly class OrderId extends IntId {}

// app/Domain/Identity/ValueObjects/UserId.php
readonly class UserId extends IntId {}

// app/Domain/Identity/ValueObjects/CategoryId.php
readonly class CategoryId extends IntId {}
```

**Навіщо:** якщо метод приймає `ProductId` — не можна випадково передати `OrderId`.
Без Value Objects: `updateProduct(int $id, int $categoryId)` — легко переплутати аргументи.
З Value Objects: `updateProduct(ProductId $id, CategoryId $categoryId)` — помилка на етапі компіляції.

### Крок 3 — Money Value Object

**Задача:** гроші мають правила: не від'ємні, округлення, арифметика.

```php
// app/Domain/Shared/ValueObjects/Money.php
readonly class Money
{
    public readonly int $amount; // зберігаємо в копійках щоб уникнути float помилок

    public function __construct(float|int $amount)
    {
        $rounded = (int) round($amount * 100);

        if ($rounded < 0) {
            throw new \InvalidArgumentException("Money cannot be negative, got: {$amount}");
        }

        $this->amount = $rounded;
    }

    // Фабричні методи для зручності
    public static function fromFloat(float $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    // Арифметика — повертає новий об'єкт (незмінність)
    public function add(Money $other): self
    {
        $result = new self(0);
        // обходимо конструктор щоб працювати напряму з копійками
        return new self($this->toFloat() + $other->toFloat());
    }

    public function multiply(int $quantity): self
    {
        return new self($this->toFloat() * $quantity);
    }

    // Порівняння
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }

    // Представлення
    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function __toString(): string
    {
        return number_format($this->toFloat(), 2);
    }
}
```

**Чому в копійках:** `0.1 + 0.2 = 0.30000000000000004` у float.
Зберігання в цілих числах (копійки) уникає цих помилок.

### Крок 4 — Sku Value Object

**Задача:** SKU має формат — великі літери, цифри, дефіси, не порожній.

```php
// app/Domain/Product/ValueObjects/Sku.php
readonly class Sku
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (empty($normalized)) {
            throw new \InvalidArgumentException("SKU cannot be empty");
        }

        if (!preg_match('/^[A-Z0-9\-]{2,50}$/', $normalized)) {
            throw new \InvalidArgumentException(
                "SKU must contain only uppercase letters, digits and hyphens (2-50 chars), got: {$value}"
            );
        }

        $this->value = $normalized;
    }

    public function equals(Sku $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Крок 5 — Quantity Value Object

**Задача:** кількість завжди невід'ємна ціле число.

```php
// app/Domain/Shared/ValueObjects/Quantity.php
readonly class Quantity
{
    public function __construct(public readonly int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException("Quantity cannot be negative, got: {$value}");
        }
    }

    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(Quantity $other): self
    {
        // Бізнес-правило: не можна взяти більше ніж є
        if ($other->value > $this->value) {
            throw new \DomainException(
                "Cannot subtract {$other->value} from {$this->value}"
            );
        }
        return new self($this->value - $other->value);
    }

    public function isAvailable(Quantity $requested): bool
    {
        return $this->value >= $requested->value;
    }

    public function equals(Quantity $other): bool
    {
        return $this->value === $other->value;
    }
}
```

### Крок 6 — Address Value Object

**Задача:** адреса — це структура, не рядок. Валідація при створенні.

```php
// app/Domain/Shared/ValueObjects/Address.php
readonly class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
        public readonly string $postalCode,
        public readonly ?string $apartment = null,
    ) {
        if (empty(trim($this->street))) {
            throw new \InvalidArgumentException("Street cannot be empty");
        }
        if (empty(trim($this->city))) {
            throw new \InvalidArgumentException("City cannot be empty");
        }
        if (empty(trim($this->country))) {
            throw new \InvalidArgumentException("Country cannot be empty");
        }
    }

    // Для збереження в БД як JSON
    public function toArray(): array
    {
        return [
            'street'     => $this->street,
            'city'       => $this->city,
            'country'    => $this->country,
            'postalCode' => $this->postalCode,
            'apartment'  => $this->apartment,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            street:     $data['street'],
            city:       $data['city'],
            country:    $data['country'],
            postalCode: $data['postalCode'],
            apartment:  $data['apartment'] ?? null,
        );
    }

    public function equals(Address $other): bool
    {
        return $this->toArray() === $other->toArray();
    }
}
```

### Крок 7 — Використання в Commands (план 01)

**Задача:** команди стають типобезпечними — помилки ловляться до виконання Handler.

```php
// app/Commands/Product/CreateProductCommand.php — оновлений
readonly class CreateProductCommand
{
    public function __construct(
        public Sku      $sku,           // замість string
        public string   $name,
        public string   $description,
        public Money    $purchasePrice, // замість float
        public Money    $salePrice,     // замість float
        public Quantity $stock,         // замість int
        public CategoryId $categoryId, // замість int
        public UserId   $userId,        // замість int
    ) {
        // Бізнес-правило: sale price має бути > purchase price
        if (!$salePrice->isGreaterThan($purchasePrice)) {
            throw new \DomainException(
                "Sale price ({$salePrice}) must be greater than purchase price ({$purchasePrice})"
            );
        }
    }
}
```

```php
// app/Http/Controllers/ProductController.php — оновлений store()
public function store(CreateProductRequest $request): JsonResponse
{
    try {
        $this->commandBus->dispatch(new CreateProductCommand(
            sku:           new Sku($request->input('sku')),
            name:          $request->input('name'),
            description:   $request->input('description'),
            purchasePrice: Money::fromFloat($request->input('purchase_price')),
            salePrice:     Money::fromFloat($request->input('sale_price')),
            stock:         new Quantity($request->input('stock')),
            categoryId:    new CategoryId($request->input('category_id')),
            userId:        new UserId($request->user()->id),
        ));
    } catch (\DomainException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }

    return response()->json([], 201);
}
```

---

## Що перевірити після реалізації

- [ ] `new Money(-10)` → throws `InvalidArgumentException`
- [ ] `new Sku('')` → throws `InvalidArgumentException`
- [ ] `new Sku('abc def')` → throws (пробіл не дозволено)
- [ ] `new Quantity(5)->subtract(new Quantity(10))` → throws `DomainException`
- [ ] `POST /api/products` з `sale_price < purchase_price` → 422 з domain error
- [ ] `new ProductId(1)->equals(new OrderId(1))` → PHP type error (різні типи)

---

## Зв'язок з іншими планами
- **План 01** (Command Bus) — команди використовують Value Objects
- **План 03** (Aggregate Root) — Aggregate приймає Value Objects в конструктор
- **План 04** (Repository) — Repository серіалізує Value Objects при збереженні
