# Task Plan: Queue Improvements for shop-2-back and warehouses

## Introduction and Context

The monorepo consists of two backend services:
- **shop-2-back** — Laravel 11, queue driver `database`, jobs already exist but several heavy operations remain synchronous
- **warehouses** — Symfony 7.2, `symfony/messenger` not installed, all processing is synchronous

This plan describes concrete, incremental improvements to introduce or properly leverage queues in both services. Improvements are independent of each other and can be implemented separately.

---

## Analysis Findings

### Laravel (shop-2-back)

**Existing jobs:**
- `GenerateTopCategoriesReport`, `GenerateTopProductsReport`, `GenerateTopUsersReport`, `GenerateTopTimePeriodsReport` — write JSON to `storage/reports/`
- `GeneratePDFReport` — reads all 4 JSON files, generates `top_report.pdf` with DomPDF
- `SendTopProfitReportEmail` — emails the PDF to a hardcoded address
- `CalculateAnnualReportJob` — `ShouldBeUnique` + `ShouldBeEncrypted` + `RateLimitJob` middleware; dispatched on demand
- `CancelUnpaidOrderJob` — dispatched from `OrderObserver` with 5-minute delay ✅ already correct

**Problems identified:**
1. The 6-job report pipeline has no enforced execution order — `GeneratePDFReport` depends on all 4 JSON files existing, but nothing prevents it from running before them
2. `ProductImportController` calls `Excel::import()` synchronously — blocks the HTTP worker for large files
3. `ProductExportController` calls `->download()` synchronously — same issue
4. `ProfitReportController::generateTotalReport()` and `getAnnualUsersSpend()` execute heavy DB aggregations inline in the HTTP request

### Symfony (warehouses)

**Problems identified:**
1. `symfony/messenger` is not installed — no async processing available
2. `StockAvailabilityService::commitReservation()` is called synchronously inside `ProductReservationWorkflowSubscriber` — if the DB write fails, there is no retry mechanism
3. `InventoryTransaction` audit writes (if any) are blocking

---

## QA Clarification Rounds

**Q: Is the report pipeline triggered via API or scheduler?**
A: Jobs are individual classes; can be dispatched from `ProfitReportController` or a console command. No `Bus::chain()` or `Bus::batch()` wiring found — order is not guaranteed.

**Q: Does `maatwebsite/excel` support queued operations?**
A: Yes — `Excel::queueImport()` and `Excel::queue()->store()` are built-in. Requires the model/import class to implement `ShouldQueueWithoutChain` or use `WithChunkReading`.

**Q: What is the safest Symfony Messenger transport to add with zero new infrastructure?**
A: `doctrine` transport — uses the existing database, no Redis or RabbitMQ needed. Can be upgraded to Redis later.

**Q: Is cross-service async communication in scope?**
A: No — deferred. It requires a shared broker decision (RabbitMQ or Redis Streams) and is an architectural choice beyond this plan.

---

## Proposed Solutions and Rationale

### 1. Laravel — Fix report pipeline order with `Bus::chain()` [Priority: High]

**Problem:** `GeneratePDFReport` can run before the 4 JSON-generating jobs complete.

**Solution:** Replace individual `dispatch()` calls with a single `Bus::chain()` call that guarantees sequential execution.

**Where:** Create a new `GenerateFullReportAction` in `app/Actions/` that encapsulates the chain. Call it from wherever the report is triggered (controller or scheduler).

```php
// app/Actions/GenerateFullReportAction.php
use Illuminate\Support\Facades\Bus;

class GenerateFullReportAction
{
    public function handle(): void
    {
        Bus::chain([
            new GenerateTopCategoriesReport(),
            new GenerateTopProductsReport(),
            new GenerateTopUsersReport(),
            new GenerateTopTimePeriodsReport(),
            new GeneratePDFReport(),
            new SendTopProfitReportEmail(),
        ])->dispatch();
    }
}
```

**Rationale:** `Bus::chain()` guarantees each job starts only after the previous one completes successfully. If any job fails, the rest of the chain is not executed.

---

### 2. Laravel — Queue Excel import [Priority: High]

**Problem:** `Excel::import()` blocks the HTTP worker.

**Solution:**
1. Make `ProductImport` implement `ShouldQueueWithoutChain` and `WithChunkReading` (chunk size 200)
2. Replace `Excel::import()` in `ProductImportController` with `Excel::queueImport()`
3. Return HTTP 202 Accepted instead of 200
4. Optionally dispatch a notification job after import completes (using `AfterImport` hook)

```php
// app/Imports/ProductImport.php — add interfaces
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldQueueWithoutChain;

class ProductImport implements ToModel, WithHeadings, WithChunkReading, ShouldQueueWithoutChain
{
    public function chunkSize(): int
    {
        return 200;
    }
}
```

```php
// app/Http/Controllers/ProductImportController.php
public function import(Request $request): JsonResponse
{
    $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
    Excel::queueImport(new ProductImport(), $request->file('file'));
    return response()->json(['message' => 'Import queued'], 202);
}
```

**Rationale:** Chunked + queued import handles files of any size without exhausting PHP memory or HTTP timeout limits.

---

### 3. Laravel — Queue Excel export [Priority: Medium]

**Problem:** `->download()` generates the file inline and streams it, blocking the worker.

**Solution:**
1. Store the export file asynchronously to `storage/app/exports/`
2. After generation, email the download link or a signed temporary URL to the authenticated user
3. Return HTTP 202 Accepted immediately

```php
// app/Http/Controllers/ProductExportController.php
public function export(Request $request): JsonResponse
{
    $filename = 'exports/products_' . now()->format('Ymd_His') . '.xlsx';

    Excel::queue(new ProductsExport(), $filename, 'local')
        ->chain([
            new \App\Jobs\SendExportReadyEmail(auth()->user(), $filename),
        ]);

    return response()->json(['message' => 'Export queued, you will receive an email when ready'], 202);
}
```

Create `app/Jobs/SendExportReadyEmail` — a simple mailable job that generates a signed URL for the stored file and emails it to the user.

**Rationale:** Avoids timeout on large datasets; user gets a download link when the file is ready.

---

### 4. Laravel — Queue heavy profit report aggregations [Priority: Medium]

**Problem:** `ProfitReportController::generateTotalReport()` runs heavy DB aggregations inline.

**Solution:**
1. Dispatch `CalculateAnnualReportJob` (already exists and is well-designed — reuse it)
2. For `generateTotalReport`, create a new `GenerateTotalProfitReportJob` that mirrors the `CalculateAnnualReportJob` pattern (`ShouldBeUnique`, rate limiter)
3. Both endpoints return HTTP 202 and notify via email when complete

**Rationale:** Heavy aggregations over large order sets can take several seconds. Moving them off the HTTP cycle improves API responsiveness and prevents gateway timeouts.

---

### 5. Symfony — Install Symfony Messenger and handle reservation commit async [Priority: Medium]

**Problem:** `StockAvailabilityService::commitReservation()` is called synchronously in `ProductReservationWorkflowSubscriber` — no retry on DB failure.

**Steps:**

#### 5a. Install Messenger
```bash
composer require symfony/messenger
```

#### 5b. Create the message and handler

```php
// src/Message/CommitReservationMessage.php
readonly class CommitReservationMessage
{
    public function __construct(public int $reservationId) {}
}
```

```php
// src/MessageHandler/CommitReservationMessageHandler.php
#[AsMessageHandler]
readonly class CommitReservationMessageHandler
{
    public function __construct(
        private ProductReservationRepository $reservationRepository,
        private StockAvailabilityService $stockService,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(CommitReservationMessage $message): void
    {
        $reservation = $this->reservationRepository->find($message->reservationId);
        if ($reservation === null) {
            return;
        }
        $this->stockService->commitReservation($reservation);
        $this->entityManager->flush();
    }
}
```

#### 5c. Update the Workflow Subscriber

```php
// src/EventSubscriber/ProductReservationWorkflowSubscriber.php
public function onCommit(CompletedEvent $event): void
{
    /** @var ProductReservation $reservation */
    $reservation = $event->getSubject();
    $this->bus->dispatch(new CommitReservationMessage($reservation->getId()));
}
```

#### 5d. Configure transport in `config/packages/messenger.yaml`

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2

        routing:
            App\Message\CommitReservationMessage: async
```

Add to `.env`:
```
MESSENGER_TRANSPORT_DSN=doctrine://default
```

**Rationale:** Decouples stock deduction from the Workflow event. Retries automatically on transient DB errors. Uses Doctrine transport — no new infrastructure needed.

---

### 6. Symfony — Async InventoryTransaction writes [Priority: Low]

**Problem:** If `InventoryTransaction` audit writes exist synchronously in services, they add latency to every stock operation.

**Solution:** Create `RecordInventoryTransactionMessage` + handler, route to the same `async` transport. Dispatch from `StockAvailabilityService` instead of writing inline.

**Note:** Implement only after item 5 is complete and the Messenger infrastructure is in place.

---

## Actionable Instructions and Next Steps

### Order of implementation

1. **[Laravel]** `GenerateFullReportAction` with `Bus::chain()` — no new dependencies, immediate correctness fix
2. **[Laravel]** Queue Excel import (`ShouldQueueWithoutChain` + `WithChunkReading`) — high impact, low risk
3. **[Laravel]** Queue Excel export + `SendExportReadyEmail` job + mail template
4. **[Laravel]** `GenerateTotalProfitReportJob` for inline profit aggregations
5. **[Symfony]** Install `symfony/messenger`, create `CommitReservationMessage` + handler, update Workflow Subscriber
6. **[Symfony]** `RecordInventoryTransactionMessage` handler (after step 5)

### Prerequisites

- Laravel: ensure `php artisan queue:work` is running (or use Supervisor in production)
- Symfony: run `php bin/console messenger:consume async` after step 5
- Symfony: run `php bin/console doctrine:migrations:diff && migrate` to create the `messenger_messages` table for the Doctrine transport

### Testing requirements

- Laravel: add Feature tests for 202 responses on import/export endpoints; assert jobs are on the queue using `Queue::fake()`
- Laravel: test `Bus::chain()` dispatches correct job sequence using `Bus::fake()`
- Symfony: add unit tests for `CommitReservationMessageHandler`; use `HandlerFailedException` assertions for retry logic

---

## Appendix — Relevant Existing Files

| File | Relevance |
|------|-----------|
| [shop-2-back/app/Jobs/](shop-2-back/app/Jobs/) | All existing Laravel jobs |
| [shop-2-back/app/Imports/ProductImport.php](shop-2-back/app/Imports/ProductImport.php) | Target for queueable import |
| [shop-2-back/app/Exports/ProductsExport.php](shop-2-back/app/Exports/ProductsExport.php) | Target for queueable export |
| [shop-2-back/app/Http/Controllers/ProductImportController.php](shop-2-back/app/Http/Controllers/ProductImportController.php) | Returns 200 synchronously — change to 202 |
| [shop-2-back/app/Http/Controllers/ProfitReportController.php](shop-2-back/app/Http/Controllers/ProfitReportController.php) | Inline aggregations — dispatch jobs instead |
| [warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php](warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php) | Synchronous stock commit — dispatch message instead |
| [warehouses/src/Service/StockAvailabilityService.php](warehouses/src/Service/StockAvailabilityService.php) | Called synchronously — becomes message handler target |
