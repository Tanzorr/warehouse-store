# План 01 — Laravel Command Bus + Query Bus

## Задача / Проблема
**Проблема:** зараз контролери напряму викликають `ProductService`, `OrderApplicationService`,
`GetCategoryAction` тощо. Немає чіткої межі між операціями що **змінюють** стан і
операціями що **читають** стан. Це ускладнює розуміння потоку даних і порушує CQRS.

**Задача:** ввести явний `CommandBus` і `QueryBus` щоб:
1. Контролер тільки **збирає вхідні дані** і **диспетчує** команду/запит.
2. Handler **виконує** бізнес-логіку (одна відповідальність).
3. Команди і запити — прості PHP-класи (DTO) без залежностей.

**Що навчає:** CQRS на практиці — явне розділення write/read потоків.

---

## Файлова структура яку треба створити

```
app/
├── Bus/
│   ├── CommandBus.php
│   ├── CommandBusInterface.php
│   ├── QueryBus.php
│   └── QueryBusInterface.php
├── Commands/
│   ├── Product/
│   │   ├── CreateProductCommand.php
│   │   ├── UpdateProductCommand.php
│   │   └── DeleteProductCommand.php
│   ├── Order/
│   │   ├── CreateOrderCommand.php
│   │   ├── UpdateOrderStatusCommand.php
│   │   └── PayOrderCommand.php
│   └── Category/
│       ├── CreateCategoryCommand.php
│       └── UpdateCategoryCommand.php
├── CommandHandlers/
│   ├── Product/
│   │   ├── CreateProductHandler.php
│   │   ├── UpdateProductHandler.php
│   │   └── DeleteProductHandler.php
│   ├── Order/
│   │   ├── CreateOrderHandler.php
│   │   ├── UpdateOrderStatusHandler.php
│   │   └── PayOrderHandler.php
│   └── Category/
│       ├── CreateCategoryHandler.php
│       └── UpdateCategoryHandler.php
├── Queries/
│   ├── Product/
│   │   ├── GetProductsQuery.php
│   │   └── GetProductByIdQuery.php
│   ├── Order/
│   │   ├── GetOrdersQuery.php
│   │   └── GetOrderByIdQuery.php
│   └── Category/
│       └── GetCategoriesQuery.php
└── QueryHandlers/
    ├── Product/
    │   ├── GetProductsHandler.php
    │   └── GetProductByIdHandler.php
    ├── Order/
    │   ├── GetOrdersHandler.php
    │   └── GetOrderByIdHandler.php
    └── Category/
        └── GetCategoriesHandler.php
```

---

## Кроки реалізації

### Крок 1 — Інтерфейси Bus

**Задача:** визначити контракт для bus-ів щоб можна було підмінити реалізацію.

```php
// app/Bus/CommandBusInterface.php
interface CommandBusInterface
{
    public function dispatch(object $command): void;
}

// app/Bus/QueryBusInterface.php
interface QueryBusInterface
{
    public function ask(object $query): mixed;
}
```

### Крок 2 — Реалізація Bus через Laravel Container

**Задача:** Laravel Container резолвить Handler для кожної команди/запиту автоматично.

```php
// app/Bus/CommandBus.php
class CommandBus implements CommandBusInterface
{
    // Маппінг: CommandClass => HandlerClass
    private array $map = [
        CreateProductCommand::class  => CreateProductHandler::class,
        UpdateProductCommand::class  => UpdateProductHandler::class,
        DeleteProductCommand::class  => DeleteProductHandler::class,
        CreateOrderCommand::class    => CreateOrderHandler::class,
        PayOrderCommand::class       => PayOrderHandler::class,
        // ... решта команд
    ];

    public function __construct(private readonly Container $container) {}

    public function dispatch(object $command): void
    {
        $handlerClass = $this->map[$command::class]
            ?? throw new \RuntimeException("No handler for " . $command::class);

        $handler = $this->container->make($handlerClass);
        $handler->handle($command);
    }
}
```

**Чому map всередині Bus:** просто і явно — відразу видно всі команди системи.
Альтернатива — авто-discovery через конвенцію імен (складніше, але масштабованіше).

### Крок 3 — Command як DTO

**Задача:** команда — це plain PHP object (DTO) що описує намір. Без логіки.

```php
// app/Commands/Product/CreateProductCommand.php
readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public float  $purchasePrice,
        public float  $salePrice,
        public int    $stock,
        public string $sku,
        public int    $categoryId,
        public int    $userId,
    ) {}
}
```

**Чому `readonly`:** команда незмінна після створення — дані зафіксовані.

### Крок 4 — Handler

**Задача:** Handler містить бізнес-логіку. Один Handler = одна команда.

```php
// app/CommandHandlers/Product/CreateProductHandler.php
class CreateProductHandler
{
    public function __construct(
        private readonly ProductRepository $repository, // буде в плані 04
    ) {}

    public function handle(CreateProductCommand $command): void
    {
        // Тут буде Aggregate (план 03), поки Eloquent
        Product::create([
            'name'           => $command->name,
            'description'    => $command->description,
            'purchase_price' => $command->purchasePrice,
            'sale_price'     => $command->salePrice,
            'stock'          => $command->stock,
            'sku'            => $command->sku,
            'category_id'    => $command->categoryId,
            'user_id'        => $command->userId,
        ]);
    }
}
```

### Крок 5 — Query як DTO

**Задача:** Query — DTO для параметрів фільтрації/пагінації. Не змінює стан.

```php
// app/Queries/Product/GetProductsQuery.php
readonly class GetProductsQuery
{
    public function __construct(
        public ?string $search      = null,
        public ?int    $categoryId  = null,
        public int     $perPage     = 15,
        public int     $page        = 1,
        public string  $sortBy      = 'created_at',
        public string  $sortDir     = 'desc',
    ) {}
}
```

### Крок 6 — Query Handler

**Задача:** Query Handler читає дані і повертає результат. Ніколи не пише.

```php
// app/QueryHandlers/Product/GetProductsHandler.php
class GetProductsHandler
{
    public function handle(GetProductsQuery $query): LengthAwarePaginator
    {
        return Product::query()
            ->when($query->search,     fn($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->categoryId, fn($q) => $q->where('category_id', $query->categoryId))
            ->orderBy($query->sortBy, $query->sortDir)
            ->paginate($query->perPage, page: $query->page);
    }
}
```

### Крок 7 — Реєстрація в ServiceProvider

**Задача:** прив'язати інтерфейси до конкретних реалізацій в контейнері.

```php
// app/Providers/AppServiceProvider.php — додати в register():
$this->app->singleton(CommandBusInterface::class, CommandBus::class);
$this->app->singleton(QueryBusInterface::class,   QueryBus::class);
```

### Крок 8 — Оновлення контролерів

**Задача:** контролер стає тонким — тільки валідація + диспетч.

```php
// app/Http/Controllers/ProductController.php
class ProductController extends Controller
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface   $queryBus,
    ) {}

    public function index(GetProductsRequest $request): JsonResponse
    {
        $result = $this->queryBus->ask(new GetProductsQuery(
            search:     $request->input('search'),
            categoryId: $request->input('category_id'),
            perPage:    $request->input('per_page', 15),
            page:       $request->input('page', 1),
        ));

        return response()->json($result);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new CreateProductCommand(
            name:          $request->input('name'),
            description:   $request->input('description'),
            purchasePrice: $request->input('purchase_price'),
            salePrice:     $request->input('sale_price'),
            stock:         $request->input('stock'),
            sku:           $request->input('sku'),
            categoryId:    $request->input('category_id'),
            userId:        $request->user()->id,
        ));

        return response()->json([], 201);
    }
}
```

---

## Що перевірити після реалізації

- [ ] `POST /api/products` → диспетчить `CreateProductCommand` → `CreateProductHandler` → продукт в БД
- [ ] `GET /api/products` → диспетчить `GetProductsQuery` → `GetProductsHandler` → список продуктів
- [ ] `POST /api/orders/{id}/pay` → диспетчить `PayOrderCommand` → `PayOrderHandler` → статус змінено
- [ ] Контролери не мають прямих викликів до моделей або сервісів

---

## Зв'язок з іншими планами
- **План 02** (Value Objects) — команди використовуватимуть `Money`, `Sku` замість примітивів
- **План 03** (Aggregate Roots) — Handlers будуть працювати з Aggregate замість Eloquent напряму
- **План 04** (Repository) — Handlers залежатимуть від Repository Interface
