# План 06 — Warehouse: Query side (Read Model)

## Задача / Проблема
**Проблема:** зараз немає зручних endpoint-ів для читання стану складу.
Є Entity (ProductReservation, StockAvailability) але немає Query Handlers
що формують зручні read-моделі для UI:
- Скільки товару є на кожному складі?
- Які резервування активні зараз?
- Яка доступна кількість конкретного товару (total - reserved)?
- Транзакції стоку для конкретного товару (аудит)?

**Задача:** додати **Query side** в Symfony Messenger:
Query DTO → QueryHandler → Read Model DTO у відповідь.
Це доповнює Command side з плану 05 і дає повну CQRS картину.

**Що навчає:** CQRS read side — окремі оптимізовані read-моделі
що не зв'язані зі write-моделлю (Entities).

---

## CQRS Write vs Read моделі

```
WRITE side (план 05):           READ side (план 06):
─────────────────────           ──────────────────────
Command (намір змінити)         Query (запит на читання)
    ↓                               ↓
Handler (змінює Entity)         Handler (читає → формує DTO)
    ↓                               ↓
Entity/Aggregate (стан)         Read Model DTO (зручний для UI)

Entities оптимізовані           Read Models оптимізовані
для запису та перевірки         для відображення
інваріантів.                    (JOIN-и, агрегація, flatten)
```

---

## Які Queries потрібні

| Query | Що повертає | Для чого |
|---|---|---|
| `GetStockLevelsQuery` | Список товарів з кількістю по складах | Головна сторінка складу |
| `GetProductStockQuery` | Stock конкретного продукту по складах | Деталі продукту |
| `GetReservationsQuery` | Список резервувань з фільтрацією | Таблиця резервувань |
| `GetInventoryTransactionsQuery` | Транзакції стоку (аудит лог) | Аудит змін |
| `GetWarehouseSummaryQuery` | Загальна статистика по складу | Дашборд |

---

## Файлова структура

```
src/Application/Query/
├── GetStockLevels/
│   ├── GetStockLevelsQuery.php
│   ├── GetStockLevelsHandler.php
│   └── StockLevelItem.php          ← Read Model DTO
├── GetProductStock/
│   ├── GetProductStockQuery.php
│   ├── GetProductStockHandler.php
│   └── ProductStockDetail.php      ← Read Model DTO
├── GetReservations/
│   ├── GetReservationsQuery.php
│   ├── GetReservationsHandler.php
│   └── ReservationItem.php         ← Read Model DTO
├── GetInventoryTransactions/
│   ├── GetInventoryTransactionsQuery.php
│   ├── GetInventoryTransactionsHandler.php
│   └── TransactionItem.php         ← Read Model DTO
└── GetWarehouseSummary/
    ├── GetWarehouseSummaryQuery.php
    ├── GetWarehouseSummaryHandler.php
    └── WarehouseSummary.php         ← Read Model DTO
```

---

## Кроки реалізації

### Крок 1 — Базовий підхід: Query + Read Model DTO

**Задача:** показати патерн на прикладі GetStockLevels.

```php
// src/Application/Query/GetStockLevels/GetStockLevelsQuery.php
readonly class GetStockLevelsQuery
{
    public function __construct(
        public ?int    $warehouseId  = null,  // фільтр по складу
        public ?int    $categoryId   = null,  // фільтр по категорії
        public ?string $search       = null,  // пошук по назві/SKU
        public bool    $lowStockOnly = false, // тільки товари з малим стоком
        public int     $lowStockThreshold = 5,
    ) {}
}
```

```php
// src/Application/Query/GetStockLevels/StockLevelItem.php
// Read Model — плоска структура для UI, не Entity
readonly class StockLevelItem
{
    public function __construct(
        public int    $productId,
        public string $productName,
        public string $sku,
        public string $categoryName,
        public int    $warehouseId,
        public string $warehouseName,
        public int    $totalStock,
        public int    $reservedStock,
        public int    $availableStock,   // computed: total - reserved
        public bool   $isLowStock,       // computed: available <= threshold
    ) {}
}
```

```php
// src/Application/Query/GetStockLevels/GetStockLevelsHandler.php
#[AsMessageHandler]
class GetStockLevelsHandler
{
    public function __construct(
        private readonly Connection $connection  // Doctrine DBAL для raw SQL
    ) {}

    /** @return StockLevelItem[] */
    public function __invoke(GetStockLevelsQuery $query): array
    {
        // Query side може робити прямі SQL запити — не зобов'язаний
        // використовувати Entities. Це ключовий момент CQRS.
        $sql = '
            SELECT
                p.id           AS product_id,
                p.name         AS product_name,
                p.sku,
                c.name         AS category_name,
                w.id           AS warehouse_id,
                w.name         AS warehouse_name,
                sa.amount      AS total_stock,
                COALESCE(
                    (SELECT SUM(pri.amount)
                     FROM product_reservation_item pri
                     JOIN product_reservation pr ON pr.id = pri.reservation_id
                     WHERE pri.product_id = p.id
                       AND pr.status = :pending_status),
                    0
                ) AS reserved_stock
            FROM stock_availability sa
            JOIN product p   ON p.id = sa.product_id
            JOIN warehouse w ON w.id = sa.warehouse_id
            JOIN category c  ON c.id = p.category_id
            WHERE 1=1
        ';

        $params = ['pending_status' => 'pending'];

        if ($query->warehouseId !== null) {
            $sql .= ' AND w.id = :warehouse_id';
            $params['warehouse_id'] = $query->warehouseId;
        }

        if ($query->categoryId !== null) {
            $sql .= ' AND c.id = :category_id';
            $params['category_id'] = $query->categoryId;
        }

        if ($query->search !== null) {
            $sql .= ' AND (p.name LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $query->search . '%';
        }

        $sql .= ' ORDER BY w.name, p.name';

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $items = array_map(function (array $row) use ($query): StockLevelItem {
            $available = $row['total_stock'] - $row['reserved_stock'];
            return new StockLevelItem(
                productId:      $row['product_id'],
                productName:    $row['product_name'],
                sku:            $row['sku'],
                categoryName:   $row['category_name'],
                warehouseId:    $row['warehouse_id'],
                warehouseName:  $row['warehouse_name'],
                totalStock:     $row['total_stock'],
                reservedStock:  $row['reserved_stock'],
                availableStock: $available,
                isLowStock:     $available <= $query->lowStockThreshold,
            );
        }, $rows);

        if ($query->lowStockOnly) {
            $items = array_filter($items, fn($item) => $item->isLowStock);
        }

        return array_values($items);
    }
}
```

**Чому Doctrine DBAL а не ORM в Query Handler:**
ORM (Entities + relations) оптимізований для запису з lazy loading.
Query side потребує JOIN-ів і агрегацій — DBAL дає повний контроль над SQL
і генерує точно один запит замість N+1.

---

### Крок 2 — GetReservations Query

```php
// src/Application/Query/GetReservations/GetReservationsQuery.php
readonly class GetReservationsQuery
{
    public function __construct(
        public ?string $status     = null,  // pending/committed/canceled
        public ?int    $productId  = null,
        public int     $page       = 1,
        public int     $perPage    = 20,
    ) {}
}

// src/Application/Query/GetReservations/ReservationItem.php
readonly class ReservationItem
{
    public function __construct(
        public int    $id,
        public string $status,
        public string $comment,
        public string $reservedAt,
        public ?string $releasedAt,
        /** @var array{productId: int, name: string, sku: string, amount: int}[] */
        public array  $items,
        public int    $totalItems,
    ) {}
}
```

```php
// src/Application/Query/GetReservations/GetReservationsHandler.php
#[AsMessageHandler]
class GetReservationsHandler
{
    public function __construct(
        private readonly ProductReservationRepository $reservationRepository
    ) {}

    /** @return array{items: ReservationItem[], total: int} */
    public function __invoke(GetReservationsQuery $query): array
    {
        // Тут можна використати ORM бо структура проста і pagination потрібен
        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->orderBy('r.reservedAt', 'DESC');

        if ($query->status !== null) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $query->status);
        }

        if ($query->productId !== null) {
            $qb->andWhere('i.product = :productId')
               ->setParameter('productId', $query->productId);
        }

        $total        = count($qb->getQuery()->getResult());
        $reservations = $qb
            ->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage)
            ->getQuery()
            ->getResult();

        $items = array_map(function (ProductReservation $r): ReservationItem {
            return new ReservationItem(
                id:         $r->getId(),
                status:     $r->getStatus()->value,
                comment:    $r->getComment() ?? '',
                reservedAt: $r->getReservedAt()->format('Y-m-d H:i'),
                releasedAt: $r->getReleasedAt()?->format('Y-m-d H:i'),
                items:      array_map(
                    fn($item) => [
                        'productId' => $item->getProduct()->getId(),
                        'name'      => $item->getProduct()->getName(),
                        'sku'       => $item->getProduct()->getSku(),
                        'amount'    => $item->getAmount(),
                    ],
                    $r->getItems()->toArray()
                ),
                totalItems: $r->getItems()->count(),
            );
        }, $reservations);

        return ['items' => $items, 'total' => $total];
    }
}
```

---

### Крок 3 — GetInventoryTransactions Query (аудит лог)

```php
// src/Application/Query/GetInventoryTransactions/GetInventoryTransactionsQuery.php
readonly class GetInventoryTransactionsQuery
{
    public function __construct(
        public ?int $productId   = null,
        public ?int $warehouseId = null,
        public int  $page        = 1,
        public int  $perPage     = 50,
    ) {}
}

// Read Model
readonly class TransactionItem
{
    public function __construct(
        public int    $id,
        public string $productName,
        public string $warehouseName,
        public int    $delta,           // + або -
        public string $reason,
        public string $performedByName,
        public string $createdAt,
    ) {}
}
```

---

### Крок 4 — GetWarehouseSummary (дашборд)

```php
// src/Application/Query/GetWarehouseSummary/GetWarehouseSummaryQuery.php
readonly class GetWarehouseSummaryQuery
{
    public function __construct(public int $warehouseId) {}
}

// Read Model для дашборду
readonly class WarehouseSummary
{
    public function __construct(
        public int    $warehouseId,
        public string $warehouseName,
        public int    $totalProducts,       // кількість унікальних продуктів
        public int    $totalStock,          // загальна кількість одиниць
        public int    $lowStockCount,       // продукти з малим стоком
        public int    $activeReservations,  // поточні pending резервування
        public int    $todayTransactions,   // операцій сьогодні
    ) {}
}
```

---

### Крок 5 — API Controller для Queries

```php
// src/Controller/WarehouseQueryController.php
#[Route('/api/warehouse')]
class WarehouseQueryController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {}

    #[Route('/stock', methods: ['GET'])]
    public function getStockLevels(Request $request): JsonResponse
    {
        /** @var StockLevelItem[] $items */
        $items = $this->queryBus->dispatch(new GetStockLevelsQuery(
            warehouseId:       $request->query->getInt('warehouse_id') ?: null,
            categoryId:        $request->query->getInt('category_id') ?: null,
            search:            $request->query->get('search'),
            lowStockOnly:      $request->query->getBoolean('low_stock_only'),
            lowStockThreshold: $request->query->getInt('threshold', 5),
        ));

        return $this->json($items);
    }

    #[Route('/reservations', methods: ['GET'])]
    public function getReservations(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetReservationsQuery(
            status:    $request->query->get('status'),
            productId: $request->query->getInt('product_id') ?: null,
            page:      $request->query->getInt('page', 1),
            perPage:   $request->query->getInt('per_page', 20),
        ));

        return $this->json($result);
    }

    #[Route('/stock/transactions', methods: ['GET'])]
    public function getTransactions(Request $request): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetInventoryTransactionsQuery(
            productId:   $request->query->getInt('product_id') ?: null,
            warehouseId: $request->query->getInt('warehouse_id') ?: null,
            page:        $request->query->getInt('page', 1),
        ));

        return $this->json($items);
    }

    #[Route('/warehouses/{id}/summary', methods: ['GET'])]
    public function getWarehouseSummary(int $id): JsonResponse
    {
        $summary = $this->queryBus->dispatch(new GetWarehouseSummaryQuery($id));
        return $this->json($summary);
    }
}
```

---

## Що перевірити після реалізації

- [ ] `GET /api/warehouse/stock` → список з total/reserved/available по кожному товару
- [ ] `GET /api/warehouse/stock?low_stock_only=true` → тільки товари де available <= 5
- [ ] `GET /api/warehouse/reservations?status=pending` → тільки pending резервування
- [ ] `GET /api/warehouse/stock/transactions?product_id=1` → аудит лог продукту
- [ ] `GET /api/warehouse/warehouses/1/summary` → статистика по складу
- [ ] Query handlers не викликають flush() або persist() — тільки читання

---

## Зв'язок з іншими планами
- **План 05** (Warehouse Commands) — commands змінюють стан, queries його читають
- **План 09** (Angular Admin Warehouse) — Angular викликає ці endpoints
