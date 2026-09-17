# Задача: Покращення черг у shop-2-back та warehouses

---

## Навіщо черги? (Просте пояснення)

### Проблема синхронного коду

Уяви ресторан де є тільки один офіціант. Він приймає замовлення, йде на кухню, чекає поки приготують, несе страву — і тільки потім може прийняти наступне замовлення.

Так само працює синхронний веб-сервер:
- Клієнт завантажує Excel-файл на 10000 рядків
- Сервер читає файл → обробляє → зберігає в базу
- Це займає 30 секунд
- Клієнт чекає 30 секунд з крутилкою
- Всі інші запити стоять в черзі

**Черга вирішує це як ресторанний пейджер:**
- Клієнт підходить, робить замовлення, отримує пейджер («Запит прийнято, ми вас сповістимо»)
- Офіціант одразу вільний для наступного клієнта
- Кухня (worker) обробляє замовлення у фоні
- Пейджер дзенькає коли готово

### Три основних проблеми які вирішують черги

1. **Повільні операції** — Excel-імпорт, генерація PDF, складні SQL-агрегації не блокують HTTP
2. **Ненадійні зовнішні сервіси** — якщо склад недоступний, job спробує ще раз пізніше
3. **Порядок виконання** — `Bus::chain()` гарантує що наступний job стартує тільки після успіху попереднього

---

## Вступ і контекст

Монорепозиторій складається з двох бекенд-сервісів:
- **shop-2-back** — Laravel 11, драйвер черги `database`, jobs вже існують але кілька важких операцій залишаються синхронними
- **warehouses** — Symfony 7.2, `symfony/messenger` не встановлений, вся обробка синхронна

Цей план описує конкретні покрокові покращення для впровадження або правильного використання черг в обох сервісах. Покращення незалежні одне від одного і можуть реалізовуватися окремо.

---

## Результати аналізу

### Laravel (shop-2-back)

**Наявні jobs:**
- `GenerateTopCategoriesReport`, `GenerateTopProductsReport`, `GenerateTopUsersReport`, `GenerateTopTimePeriodsReport` — записують JSON у `storage/reports/`
- `GeneratePDFReport` — читає всі 4 JSON файли, генерує `top_report.pdf` через DomPDF
- `SendTopProfitReportEmail` — відправляє PDF на захардкоджену адресу
- `CalculateAnnualReportJob` — `ShouldBeUnique` + `ShouldBeEncrypted` + `RateLimitJob` middleware; запускається на вимогу
- `CancelUnpaidOrderJob` — запускається з `OrderObserver` з 5-хвилинною затримкою ✅ вже правильно

**Виявлені проблеми:**
1. Конвеєр з 6 jobs не має гарантованого порядку виконання — `GeneratePDFReport` залежить від 4 JSON-файлів, але ніщо не заважає йому запуститися раніше них
2. `ProductImportController` викликає `Excel::import()` синхронно — блокує HTTP-worker для великих файлів
3. `ProductExportController` викликає `->download()` синхронно — та сама проблема
4. `ProfitReportController::generateTotalReport()` та `getAnnualUsersSpend()` виконують важкі DB-агрегації прямо в HTTP-запиті

### Symfony (warehouses)

**Виявлені проблеми:**
1. `symfony/messenger` не встановлений — асинхронна обробка недоступна
2. `StockAvailabilityService::commitReservation()` викликається синхронно в `ProductReservationWorkflowSubscriber` — якщо запис в БД падає, немає механізму повторної спроби
3. Синхронні записи `InventoryTransaction` блокують операції зі складом

---

## Питання і відповіді

**П: Чи викликається конвеєр звітів через API чи планувальник?**
В: Jobs — окремі класи; можуть бути викликані з `ProfitReportController` або консольної команди. Зв'язки через `Bus::chain()` або `Bus::batch()` не знайдені — порядок не гарантований.

**П: Чи підтримує `maatwebsite/excel` чергові операції?**
В: Так — `Excel::queueImport()` та `Excel::queue()->store()` вбудовані. Потребує щоб клас імпорту реалізував `ShouldQueueWithoutChain` або використовував `WithChunkReading`.

**П: Який найбезпечніший транспорт Symfony Messenger без нової інфраструктури?**
В: `doctrine` транспорт — використовує наявну базу даних, Redis або RabbitMQ не потрібні. Можна перейти на Redis пізніше.

**П: Асинхронна комунікація між сервісами в скоупі?**
В: Ні — відкладено. Потребує рішення щодо спільного брокера (RabbitMQ або Redis Streams) і виходить за рамки цього плану.

---

## Запропоновані рішення

### 1. Laravel — Виправлення порядку конвеєра звітів через `Bus::chain()` [Пріоритет: Високий]

**Проблема:** `GeneratePDFReport` може запуститися до завершення 4 jobs генерації JSON.

**Що таке Bus::chain() простими словами:**  
Звичайний `dispatch()` — це як кинути всі задачі одразу на дошку: workers беруть їх в довільному порядку.  
`Bus::chain()` — це як черга в банку: задача 2 стартує тільки після того як задача 1 повністю завершилася успішно.

**Рішення:** Замінити окремі виклики `dispatch()` одним викликом `Bus::chain()` що гарантує послідовне виконання.

**Де:** Створити новий `GenerateFullReportAction` в `app/Actions/`. Викликати звідти де запускається звіт (контролер або планувальник).

```php
// app/Actions/GenerateFullReportAction.php
use Illuminate\Support\Facades\Bus;

class GenerateFullReportAction
{
    public function handle(): void
    {
        Bus::chain([
            new GenerateTopCategoriesReport(),   // крок 1
            new GenerateTopProductsReport(),     // крок 2
            new GenerateTopUsersReport(),        // крок 3
            new GenerateTopTimePeriodsReport(),  // крок 4
            new GeneratePDFReport(),             // крок 5: всі JSON вже є
            new SendTopProfitReportEmail(),      // крок 6: PDF вже є
        ])->dispatch();
    }
}
```

**Обґрунтування:** `Bus::chain()` гарантує що кожен job стартує тільки після успішного завершення попереднього. Якщо будь-який job падає — решта ланцюга не виконується.

---

### 2. Laravel — Чергова обробка Excel-імпорту [Пріоритет: Високий]

**Проблема:** `Excel::import()` блокує HTTP-worker.

**Що відбувається без черги:**
1. Клієнт завантажує файл 10МБ (5000 рядків)
2. PHP читає весь файл у пам'ять
3. Перебирає 5000 рядків, вставляє в БД
4. Клієнт чекає ~30 секунд
5. Nginx закриває з'єднання по таймауту — помилка

**Рішення:**
1. Зробити `ProductImport` реалізуючим `ShouldQueueWithoutChain` і `WithChunkReading` (chunk size 200)
2. Замінити `Excel::import()` в `ProductImportController` на `Excel::queueImport()`
3. Повертати HTTP 202 Accepted замість 200

```php
// app/Imports/ProductImport.php — додати інтерфейси
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldQueueWithoutChain;

class ProductImport implements ToModel, WithHeadings, WithChunkReading, ShouldQueueWithoutChain
{
    public function chunkSize(): int
    {
        return 200; // обробляти по 200 рядків за раз, а не весь файл одразу
    }
}
```

```php
// app/Http/Controllers/ProductImportController.php
public function import(Request $request): JsonResponse
{
    $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
    Excel::queueImport(new ProductImport(), $request->file('file'));
    return response()->json(['message' => 'Імпорт поставлено в чергу'], 202);
}
```

**Обґрунтування:** Chunked + queued імпорт обробляє файли будь-якого розміру без вичерпання пам'яті PHP або HTTP timeout-ів.

---

### 3. Laravel — Чергова обробка Excel-експорту [Пріоритет: Середній]

**Проблема:** `->download()` генерує файл синхронно і стримить його, блокуючи worker.

**Рішення:**
1. Зберігати файл експорту асинхронно в `storage/app/exports/`
2. Після генерації надсилати посилання або підписаний тимчасовий URL авторизованому користувачу
3. Одразу повертати HTTP 202 Accepted

```php
// app/Http/Controllers/ProductExportController.php
public function export(Request $request): JsonResponse
{
    $filename = 'exports/products_' . now()->format('Ymd_His') . '.xlsx';

    Excel::queue(new ProductsExport(), $filename, 'local')
        ->chain([
            new \App\Jobs\SendExportReadyEmail(auth()->user(), $filename),
        ]);

    return response()->json(['message' => 'Експорт поставлено в чергу, ви отримаєте email коли буде готово'], 202);
}
```

Створити `app/Jobs/SendExportReadyEmail` — простий job-mailable що генерує підписаний URL для збереженого файлу і відправляє email користувачу.

**Обґрунтування:** Уникає таймауту на великих датасетах; користувач отримує посилання для завантаження коли файл готовий.

---

### 4. Laravel — Черговий розрахунок важких агрегацій звіту про прибуток [Пріоритет: Середній]

**Проблема:** `ProfitReportController::generateTotalReport()` виконує важкі DB-агрегації прямо в запиті.

**Чому це погано:**  
SQL запит типу `SELECT SUM(...) FROM orders JOIN ...` по мільйону рядків може виконуватись 5-10 секунд.  
За цей час HTTP-worker заблокований і не може обробляти інші запити.

**Рішення:**
1. Dispatch `CalculateAnnualReportJob` (вже існує і добре спроектований — повторно використати)
2. Для `generateTotalReport` створити новий `GenerateTotalProfitReportJob` за патерном `CalculateAnnualReportJob` (`ShouldBeUnique`, rate limiter)
3. Обидва endpoints повертають HTTP 202 і сповіщають через email коли готово

**Обґрунтування:** Важкі агрегації по великих наборах замовлень можуть займати кілька секунд. Винесення їх за межі HTTP-циклу покращує відповідь API і запобігає таймаутам шлюзу.

---

### 5. Symfony — Встановити Symfony Messenger і асинхронно підтверджувати резервацію [Пріоритет: Середній]

**Проблема:** `StockAvailabilityService::commitReservation()` викликається синхронно в `ProductReservationWorkflowSubscriber` — немає retry при помилці БД.

**Що таке Symfony Messenger простими словами:**  
Це «поштовий сортувальний центр» для повідомлень:
- Ти кидаєш повідомлення в bus: `$bus->dispatch(new CommitReservationMessage(...))`
- Messenger дивиться в конфіг: «це повідомлення йде async»
- Кладе його в таблицю `messenger_messages`
- Окремий процес (`messenger:consume`) читає таблицю і викликає handler
- Якщо handler падає — Messenger спробує ще раз автоматично

**Кроки:**

#### 5a. Встановити Messenger
```bash
composer require symfony/messenger
```

#### 5b. Створити повідомлення і handler

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
            return; // ідемпотентно — не помилка
        }
        $this->stockService->commitReservation($reservation);
        $this->entityManager->flush();
    }
}
```

#### 5c. Оновити Workflow Subscriber

**До (синхронно):**
```php
// Якщо БД упала тут — операція просто втрачається. Немає retry.
$this->stockService->commitReservation($reservation);
```

**Після (через чергу):**
```php
// src/EventSubscriber/ProductReservationWorkflowSubscriber.php
public function onCommit(CompletedEvent $event): void
{
    /** @var ProductReservation $reservation */
    $reservation = $event->getSubject();
    // Тепер просто ставимо повідомлення в чергу — handler запуститься асинхронно
    $this->bus->dispatch(new CommitReservationMessage($reservation->getId()));
}
```

#### 5d. Налаштувати transport у `config/packages/messenger.yaml`

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000      # мс
                    multiplier: 2   # 1с → 2с → 4с

        routing:
            App\Message\CommitReservationMessage: async
```

Додати до `.env`:
```
MESSENGER_TRANSPORT_DSN=doctrine://default
```

**Обґрунтування:** Відв'язує списання зі складу від Workflow event. Автоматичні retry при тимчасових помилках БД. Використовує Doctrine transport — нова інфраструктура не потрібна.

---

### 6. Symfony — Асинхронні записи InventoryTransaction [Пріоритет: Низький]

**Проблема:** Якщо `InventoryTransaction` записи існують синхронно в сервісах, вони додають затримку до кожної операції зі складом.

**Рішення:** Створити `RecordInventoryTransactionMessage` + handler, маршрутизувати до того самого `async` транспорту. Dispatch з `StockAvailabilityService` замість inline-запису.

**Примітка:** Реалізовувати тільки після завершення пункту 5 — коли Messenger інфраструктура вже є.

---

## Actionable інструкції та наступні кроки

### Порядок реалізації

1. **[Laravel]** `GenerateFullReportAction` з `Bus::chain()` — без нових залежностей, миттєве виправлення коректності
2. **[Laravel]** Черговий Excel-імпорт (`ShouldQueueWithoutChain` + `WithChunkReading`) — великий вплив, низький ризик
3. **[Laravel]** Черговий Excel-експорт + job `SendExportReadyEmail` + mail-шаблон
4. **[Laravel]** `GenerateTotalProfitReportJob` для inline агрегацій прибутку
5. **[Symfony]** Встановити `symfony/messenger`, створити `CommitReservationMessage` + handler, оновити Workflow Subscriber
6. **[Symfony]** `RecordInventoryTransactionMessage` handler (після кроку 5)

### Передумови

- Laravel: переконатися що `php artisan queue:work` запущений (або використовувати Supervisor у production)
- Symfony: запустити `php bin/console messenger:consume async` після кроку 5
- Symfony: запустити `php bin/console doctrine:migrations:diff && migrate` для створення таблиці `messenger_messages` для Doctrine transport

### Вимоги до тестування

- Laravel: додати Feature тести для відповідей 202 на endpoints імпорту/експорту; перевіряти jobs у черзі через `Queue::fake()`
- Laravel: тестувати `Bus::chain()` що dispatches правильну послідовність jobs через `Bus::fake()`
- Symfony: додати unit тести для `CommitReservationMessageHandler`; використовувати `HandlerFailedException` assertions для retry-логіки

---

## Додаток — Пов'язані наявні файли

| Файл | Навіщо |
|------|--------|
| [shop-2-back/app/Jobs/](shop-2-back/app/Jobs/) | Всі наявні Laravel jobs |
| [shop-2-back/app/Imports/ProductImport.php](shop-2-back/app/Imports/ProductImport.php) | Ціль для queueable-імпорту |
| [shop-2-back/app/Exports/ProductsExport.php](shop-2-back/app/Exports/ProductsExport.php) | Ціль для queueable-експорту |
| [shop-2-back/app/Http/Controllers/ProductImportController.php](shop-2-back/app/Http/Controllers/ProductImportController.php) | Повертає 200 синхронно — замінити на 202 |
| [shop-2-back/app/Http/Controllers/ProfitReportController.php](shop-2-back/app/Http/Controllers/ProfitReportController.php) | Inline агрегації — замість них dispatch jobs |
| [warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php](warehouses/src/EventSubscriber/ProductReservationWorkflowSubscriber.php) | Синхронний commit складу — замість нього dispatch message |
| [warehouses/src/Service/StockAvailabilityService.php](warehouses/src/Service/StockAvailabilityService.php) | Викликається синхронно — стає ціллю message handler-а |
