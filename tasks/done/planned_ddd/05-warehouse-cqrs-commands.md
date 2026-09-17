# План 05 — Warehouse: розширення CQRS Commands

## Задача / Проблема
**Проблема:** зараз в Symfony Warehouse є тільки одна команда — `CommitReservationCommand`.
Але для повноцінного управління складом потрібні операції:
- Скасування резервування (гроші повернуті, товар знову доступний).
- Ручне коригування стоку (надходження товару, списання браку).
- Переміщення товару між складами.

Без цих команд менеджер не може управляти складом — тільки читати дані.

**Задача:** додати 3 нові Command + Handler пари, використовуючи той самий
Symfony Messenger патерн що вже є для `CommitReservation`.

**Що навчає:** Command патерн на практиці в Symfony Messenger.
Кожна зміна стану = окрема команда з явною назвою.

---

## Файлова структура (доповнення до існуючої)

```
src/Application/Command/
├── CommitReservation/          ← вже є
│   ├── CommitReservationCommand.php
│   └── CommitReservationHandler.php
│
├── CancelReservation/          ← НОВЕ
│   ├── CancelReservationCommand.php
│   └── CancelReservationHandler.php
│
├── AdjustStock/                ← НОВЕ
│   ├── AdjustStockCommand.php
│   └── AdjustStockHandler.php
│
└── TransferStock/              ← НОВЕ
    ├── TransferStockCommand.php
    └── TransferStockHandler.php
```

---

## Кроки реалізації

### Команда 1: CancelReservation

**Задача:** скасувати резервування і повернути товар до доступного стоку.
Виникає коли: покупець відмовився від замовлення або час резервування минув.

```php
// src/Application/Command/CancelReservation/CancelReservationCommand.php
readonly class CancelReservationCommand
{
    public function __construct(
        public int    $reservationId,
        public string $reason,         // чому скасовано (для логу)
    ) {}
}
```

```php
// src/Application/Command/CancelReservation/CancelReservationHandler.php
#[AsMessageHandler]
readonly class CancelReservationHandler
{
    public function __construct(
        private ProductReservationRepository $reservationRepository,
        private StockAvailabilityService     $stockService,
        private EntityManagerInterface       $entityManager,
    ) {}

    public function __invoke(CancelReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);

        if ($reservation === null) {
            throw new \InvalidArgumentException(
                "Reservation #{$command->reservationId} not found"
            );
        }

        // Перевіряємо що можна скасувати — тільки pending
        if ($reservation->getStatus() !== ReservationStatus::PENDING) {
            throw new \DomainException(
                "Cannot cancel reservation in status: {$reservation->getStatus()->value}"
            );
        }

        // Workflow transition: pending → canceled
        // Symfony Workflow вже обробляє це через ProductReservationWorkflowSubscriber
        $reservation->setStatus(ReservationStatus::CANCELED);
        $reservation->setComment($command->reason);
        $reservation->setReleasedAt(new \DateTimeImmutable());

        // Повертаємо зарезервований stock
        // (StockAvailabilityService має метод для звільнення резервації)
        $this->stockService->releaseReservation($reservation);

        $this->entityManager->flush();
    }
}
```

**Що треба додати в `StockAvailabilityService`:**
```php
// src/Service/StockAvailabilityService.php — додати метод:
public function releaseReservation(ProductReservation $reservation): void
{
    // Нічого не робимо з фізичним стоком — він не змінювався при pending
    // Статус змінено — getAvailableStock() тепер рахуватиме правильно
    // бо рахує: total_stock - sum(pending_reservations)
}
```

---

### Команда 2: AdjustStock

**Задача:** ручне коригування кількості товару на складі.
Причини: надходження товару, списання браку, інвентаризація.

```php
// src/Application/Command/AdjustStock/AdjustStockCommand.php
readonly class AdjustStockCommand
{
    public function __construct(
        public int    $productId,
        public int    $warehouseId,
        public int    $delta,          // позитивне = надходження, від'ємне = списання
        public string $reason,         // "Надходження від постачальника" / "Брак"
        public int    $performedBy,    // userId хто виконав
    ) {}
}
```

```php
// src/Application/Command/AdjustStock/AdjustStockHandler.php
#[AsMessageHandler]
readonly class AdjustStockHandler
{
    public function __construct(
        private StockAvailabilityRepository $stockRepository,
        private ProductRepository           $productRepository,
        private WarehouseRepository         $warehouseRepository,
        private InventoryTransactionRepository $transactionRepository,
        private EntityManagerInterface      $entityManager,
    ) {}

    public function __invoke(AdjustStockCommand $command): void
    {
        $product   = $this->productRepository->find($command->productId)
            ?? throw new \InvalidArgumentException("Product #{$command->productId} not found");

        $warehouse = $this->warehouseRepository->find($command->warehouseId)
            ?? throw new \InvalidArgumentException("Warehouse #{$command->warehouseId} not found");

        $stock = $this->stockRepository->findByProductAndWarehouse(
            $command->productId,
            $command->warehouseId
        );

        // Якщо запис стоку ще не існує — створюємо
        if ($stock === null) {
            $stock = new StockAvailability();
            $stock->setProduct($product);
            $stock->setWarehouse($warehouse);
            $stock->setAmount(0);
            $this->entityManager->persist($stock);
        }

        $newAmount = $stock->getAmount() + $command->delta;

        // Бізнес-правило: stock не може бути від'ємним
        if ($newAmount < 0) {
            throw new \DomainException(
                "Cannot reduce stock below zero. Current: {$stock->getAmount()}, delta: {$command->delta}"
            );
        }

        $stock->setAmount($newAmount);

        // Логуємо транзакцію для аудиту
        $transaction = new InventoryTransaction();
        $transaction->setProduct($product);
        $transaction->setWarehouse($warehouse);
        $transaction->setDelta($command->delta);
        $transaction->setReason($command->reason);
        $transaction->setPerformedBy($command->performedBy);
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($transaction);

        $this->entityManager->flush();
    }
}
```

**Чому логуємо в `InventoryTransaction`:**
Аудит важливий для складу — хто, коли, скільки і чому змінив stock.
`InventoryTransaction` — це event log для стоку.

---

### Команда 3: TransferStock

**Задача:** переміщення товару між складами без зміни загальної кількості.
Виникає коли: оптимізація розподілу по складах.

```php
// src/Application/Command/TransferStock/TransferStockCommand.php
readonly class TransferStockCommand
{
    public function __construct(
        public int    $productId,
        public int    $fromWarehouseId,
        public int    $toWarehouseId,
        public int    $amount,          // скільки перемістити (завжди > 0)
        public string $reason,
        public int    $performedBy,
    ) {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Transfer amount must be positive");
        }

        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException("Cannot transfer to the same warehouse");
        }
    }
}
```

```php
// src/Application/Command/TransferStock/TransferStockHandler.php
#[AsMessageHandler]
readonly class TransferStockHandler
{
    public function __construct(
        private StockAvailabilityRepository    $stockRepository,
        private InventoryTransactionRepository $transactionRepository,
        private ProductRepository              $productRepository,
        private WarehouseRepository            $warehouseRepository,
        private EntityManagerInterface         $entityManager,
    ) {}

    public function __invoke(TransferStockCommand $command): void
    {
        $fromStock = $this->stockRepository->findByProductAndWarehouse(
            $command->productId,
            $command->fromWarehouseId
        );

        if ($fromStock === null || $fromStock->getAmount() < $command->amount) {
            $available = $fromStock?->getAmount() ?? 0;
            throw new \DomainException(
                "Insufficient stock in warehouse #{$command->fromWarehouseId}. " .
                "Available: {$available}, requested: {$command->amount}"
            );
        }

        // Знімаємо з джерела
        $fromStock->setAmount($fromStock->getAmount() - $command->amount);

        // Додаємо до цілі (або створюємо запис)
        $toStock = $this->stockRepository->findByProductAndWarehouse(
            $command->productId,
            $command->toWarehouseId
        ) ?? $this->createEmptyStock($command->productId, $command->toWarehouseId);

        $toStock->setAmount($toStock->getAmount() + $command->amount);

        // Дві транзакції — "вихід" і "вхід"
        $this->logTransaction(
            productId:   $command->productId,
            warehouseId: $command->fromWarehouseId,
            delta:       -$command->amount,
            reason:      "Transfer to warehouse #{$command->toWarehouseId}: {$command->reason}",
            performedBy: $command->performedBy,
        );

        $this->logTransaction(
            productId:   $command->productId,
            warehouseId: $command->toWarehouseId,
            delta:       +$command->amount,
            reason:      "Transfer from warehouse #{$command->fromWarehouseId}: {$command->reason}",
            performedBy: $command->performedBy,
        );

        $this->entityManager->flush();
    }

    private function createEmptyStock(int $productId, int $warehouseId): StockAvailability
    {
        $stock = new StockAvailability();
        $stock->setProduct($this->productRepository->find($productId));
        $stock->setWarehouse($this->warehouseRepository->find($warehouseId));
        $stock->setAmount(0);
        $this->entityManager->persist($stock);
        return $stock;
    }

    private function logTransaction(
        int $productId, int $warehouseId, int $delta, string $reason, int $performedBy
    ): void {
        $tx = new InventoryTransaction();
        $tx->setProduct($this->productRepository->find($productId));
        $tx->setWarehouse($this->warehouseRepository->find($warehouseId));
        $tx->setDelta($delta);
        $tx->setReason($reason);
        $tx->setPerformedBy($performedBy);
        $tx->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($tx);
    }
}
```

---

## API Endpoints для нових команд

**Задача:** додати REST endpoints в Symfony щоб Angular-адмін міг викликати команди.

```php
// src/Controller/WarehouseCommandController.php
#[Route('/api/warehouse')]
class WarehouseCommandController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {}

    #[Route('/reservations/{id}/cancel', methods: ['POST'])]
    public function cancelReservation(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->bus->dispatch(new CancelReservationCommand(
            reservationId: $id,
            reason:        $data['reason'] ?? 'Manual cancellation',
        ));

        return new JsonResponse(['status' => 'cancelled']);
    }

    #[Route('/stock/adjust', methods: ['POST'])]
    public function adjustStock(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->bus->dispatch(new AdjustStockCommand(
            productId:   $data['product_id'],
            warehouseId: $data['warehouse_id'],
            delta:       $data['delta'],
            reason:      $data['reason'],
            performedBy: $this->getUser()->getId(),
        ));

        return new JsonResponse(['status' => 'adjusted']);
    }

    #[Route('/stock/transfer', methods: ['POST'])]
    public function transferStock(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->bus->dispatch(new TransferStockCommand(
            productId:       $data['product_id'],
            fromWarehouseId: $data['from_warehouse_id'],
            toWarehouseId:   $data['to_warehouse_id'],
            amount:          $data['amount'],
            reason:          $data['reason'],
            performedBy:     $this->getUser()->getId(),
        ));

        return new JsonResponse(['status' => 'transferred']);
    }
}
```

---

## Що перевірити після реалізації

- [ ] `POST /api/warehouse/reservations/1/cancel` → статус змінено на canceled, stock повернуто
- [ ] `POST /api/warehouse/stock/adjust` з `delta: -999` при stock=5 → 422 помилка
- [ ] `POST /api/warehouse/stock/transfer` між різними складами → stock змінено в обох
- [ ] `POST /api/warehouse/stock/transfer` з `fromWarehouseId == toWarehouseId` → 400 помилка
- [ ] Кожна операція логується в `InventoryTransaction`
- [ ] `CommitReservationCommand` продовжує працювати (не зламали)

---

## Зв'язок з іншими планами
- **План 06** (Warehouse Queries) — читання стоку після коригування
- **План 09** (Angular Admin Warehouse) — UI для цих команд
