# Task Plan: Bug Fixes Across All Three Projects

## Вступ

Цей файл містить конкретні виправлення, знайдені під час аналізу кодової бази. Кожне виправлення є незалежним і може бути зроблено окремо. Проблеми відсортовані за пріоритетом.

## Статус (2026-10-26)

| Фікс | Стан |
|---|---|
| F1–F5, L1, L2, L4–L7, W1–W6 | ✅ у коді |
| L3 | ✅ метод, маршрут `annual-user-report/{userId}` і Swagger-заглушку видалено |
| W7 | 🟡 конфіг і тести зроблено; `curl`: анонім `401`, user-JWT `403`, admin-JWT `201`, `GET` `200`, CORS-preflight `200`. Нові тести W7 зелені |

Відкрите, тому файл не в `done/`:

- **4 API-тести `ProductReservationApiTest` падають і на HEAD** — `Item not found for "/api/products/N"`: дані з `EntityManager` тесту невидимі для запиту клієнта. До W7 не стосується, потрібна окрема таска.
- **Psalm не встановлено** у `warehouses` — є `psalm.xml`, немає пакета в `composer.json`. Додати залежність — рішення власника (правило 4 `CLAUDE.md`).
- **Не перевірено:** admin-JWT саме від `POST /api/login` `shop-2-back` (токен видано через lexik у `warehouses` тим самим секретом) і Adjust/Transfer/Cancel в адмінці `shop-front`.

---

## shop-front (Angular 20 + NgRx)

### FIX-F1 — `CategoriesEffects`: відсутній `@Injectable()` [Критична]

**Файл:** `src/app/store/categories/categories-effects.ts`

**Проблема:** Клас `CategoriesEffects` не має декоратора `@Injectable()`. NgRx не може зареєструвати ефект — функціонал категорій мовчки не працює.

**Виправлення:**
```typescript
// До
export class CategoriesEffects {

// Після
@Injectable()
export class CategoriesEffects {
```

Також додати імпорт: `import { Injectable } from '@angular/core';`

---

### FIX-F2 — `SearchComponent`: витік пам'яті [Критична]

**Файл:** `src/app/components/search/search.component.ts`

**Проблема:** Підписка на `valueChanges` ніколи не скасовується після знищення компонента.

**Виправлення:**
- Видалити `new FormBuilder()` та ручну підписку з конструктора
- Використати `inject(FormBuilder)` та `takeUntilDestroyed()`
- Додати `ChangeDetectionStrategy.OnPush`

```typescript
import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { inject } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { debounceTime, distinctUntilChanged } from 'rxjs';

@Component({
  selector: 'app-search',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, NgClass],
  templateUrl: './search.component.html',
  styleUrl: './search.component.scss'
})
export class SearchComponent {
  private readonly fb = inject(FormBuilder);

  searchForm = this.fb.group({ query: [''] });

  @Input() customClass: string = '';
  @Output() search = new EventEmitter<string>();

  constructor() {
    this.searchForm.get('query')?.valueChanges.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      takeUntilDestroyed()
    ).subscribe((query: string) => {
      this.search.emit(query.trim());
    });
  }

  clearSearch(): void {
    this.searchForm.reset();
    this.search.emit('');
  }
}
```

---

### FIX-F3 — `product-reducers.ts`: використання `any` [Висока]

**Файл:** `src/app/store/product/product-reducers.ts`

**Проблема:** `productResponse: any` — порушення правила явних типів.

**Виправлення:** Замінити `any` на правильний тип `ProductResponse` (або відповідний інтерфейс з `app/models/`).

---

### FIX-F4 — `ProductComponent` (page): використання `ActivatedRoute` [Середня]

**Файл:** `src/app/pages/products/product/product.component.ts`

**Проблема:** Використовує `ActivatedRoute.snapshot.paramMap.get('id')` замість `@Input()`. У додатку увімкнено `withComponentInputBinding()`.

**Виправлення:**
```typescript
// До
constructor(private route: ActivatedRoute) {
  this.productId = this.route.snapshot.paramMap.get('id');
}

// Після
@Input() id!: string;
```

---

### FIX-F5 — `ProductComponent` (presentational): відсутній `OnPush` [Середня]

**Файл:** `src/app/components/product/product.component.ts`

**Проблема:** Відсутній `ChangeDetectionStrategy.OnPush`. У Zoneless-додатку це обов'язково.

**Виправлення:** Додати `changeDetection: ChangeDetectionStrategy.OnPush` та видалити unused import `JsonPipe`.

---

## shop-2-back (Laravel 11)

### FIX-L1 — `PageController::update()`: `200` як елемент масиву [Критична]

**Файл:** `app/Http/Controllers/PageController.php`

**Проблема:** `200` стоїть третім елементом масиву в `response()->json([..., 200])` замість другого аргументу методу. Також `$page->update()` повертає `bool`, а не оновлену модель.

**Виправлення:**
```php
// До
return response()->json([
    'message' => 'Page updated successfully',
    'user' => $page->update($validated), 200,
]);

// Після
$page->update($validated);

return response()->json([
    'message' => 'Page updated successfully',
    'page' => $page->fresh(),
], 200);
```

---

### FIX-L2 — `AuthController`: `$request->all()` [Висока]

**Файл:** `app/Http/Controllers/AuthController.php`

**Проблема:** `$request->all()` обходить валідацію і передає неперевірені дані в `LoginAction`.

**Виправлення:**
```php
// До
return response()->json($loginAction->handle(new GetQuery(['credentials' => $request->all()])), 200);

// Після
return response()->json($loginAction->handle(new GetQuery(['credentials' => $request->validated()])), 200);
```

---

### FIX-L3 — `ProfitReportController::getAnnualUsersSpend()`: мертвий код [Висока]

**Файл:** `app/Http/Controllers/ProfitReportController.php`

**Проблема:** Метод повертає `void`, ігнорує всі вхідні параметри, хардкодить дати та `[10, 11, 23]`, викликає `$job->handle()` напряму.

**Виправлення:** Видалити метод або переписати правильно — отримувати параметри з Form Request, диспатчити job через чергу та повертати `JsonResponse`.

---

### FIX-L4 — `TagController` і `SharedAccessController`: `update()` повертає `bool` [Висока]

**Файли:** `app/Http/Controllers/TagController.php`, `app/Http/Controllers/SharedAccessController.php`

**Проблема:** `$tag->update(...)` та `$sharedAccess->update(...)` повертають `bool`. API-клієнт отримує `true` замість оновленого ресурсу.

**Виправлення (шаблон):**
```php
// До
return response()->json($tag->update($request->validated()));

// Після
$tag->update($request->validated());

return response()->json($tag->fresh());
```

---

### FIX-L5 — `SharedAccessController`: `$request->all()` [Висока]

**Файл:** `app/Http/Controllers/SharedAccessController.php`

**Проблема:** Використовує `$request->all()` замість `$request->validated()`.

**Виправлення:** Замінити на `$request->validated()`.

---

### FIX-L6 — `StoreSharedAccessAction`: доступ до ключа масиву без перевірки [Висока]

**Файл:** `app/Actions/StoreSharedAccessAction.php`

**Проблема:** `SharedAccess::ACCESS_TYPE_MAP[$data['accessible_type']]` кине `Undefined array key` при невалідному типі.

**Виправлення:**
```php
// До
$accessType = SharedAccess::ACCESS_TYPE_MAP[$data['accessible_type']];

// Після
$accessType = SharedAccess::ACCESS_TYPE_MAP[$data['accessible_type']]
    ?? throw new \InvalidArgumentException("Unknown accessible_type: {$data['accessible_type']}");
```

---

### FIX-L7 — `UpdateProductRequest`: `'id' => 'required'` у тілі запиту [Середня]

**Файл:** `app/Http/Requests/UpdateProductRequest.php`

**Проблема:** `id` має братися з route model binding, а не з тіла запиту.

**Виправлення:** Видалити рядок `'id' => 'required'` з масиву `rules()`.

---

## warehouses (Symfony 7.2 + API Platform)

### FIX-W1 — `InventoryTransaction`: друкарська помилка `$entitType` [Критична]

**Файл:** `src/Entity/InventoryTransaction.php`

**Проблема:** Параметр конструктора називається `$entitType` (пропущена літера `y`). Через це `$entityType` ніколи не заповнюється — завжди `null`.

**Виправлення:**
```php
// До
, ?string $entitType = null

// Після
, ?string $entityType = null
```

І відповідно в тілі конструктора:
```php
$this->entityType = $entityType;
```

---

### FIX-W2 — `InventoryTransaction`: видалити `#[AllowDynamicProperties]` [Висока]

**Файл:** `src/Entity/InventoryTransaction.php`

**Проблема:** Атрибут `#[AllowDynamicProperties]` відключає суворі перевірки властивостей і порушує Psalm errorLevel 3.

**Виправлення:** Видалити атрибут `#[AllowDynamicProperties]` та відповідний `use AllowDynamicProperties;`.

---

### FIX-W3 — `ProductReservationsService`: `||` замість `??` [Висока]

**Файл:** `src/Service/ProductReservationsService.php`

**Проблема:** `$amount || 0` — логічне АБО, а не null-coalesce. Логічно некоректно.

**Виправлення:**
```php
// До
return $amount || 0;

// Після
return $amount ?? 0;
```

---

### FIX-W4 — `ProductReservation::$status`: `public` замість `private` [Висока]

**Файл:** `src/Entity/ProductReservation.php`

**Проблема:** `public string $status` порушує інкапсуляцію — будь-який код може змінити статус в обхід workflow.

**Виправлення:** Змінити на `private` і додати геттер/сеттер або залишити геттер та встановлювати значення лише через workflow.

---

### FIX-W5 — Відсутні `#[Assert\NotBlank]` на обов'язкових полях [Середня]

**Файли:**
- `src/Entity/Product.php` — `$name`, `$description`, `$sku`, `$price`
- `src/Entity/Category.php` — `$name`
- `src/Entity/Warehouse.php` — `$description`
- `src/Entity/StockAvailability.php` — `$amount`

**Виправлення:** Додати `#[Assert\NotBlank]` до кожного обов'язкового рядкового поля. Для `$price` також `#[Assert\Positive]`. Для `$sku` також `#[Assert\NotBlank]`.

---

### FIX-W6 — `ReserveInput`: захардкоджений `'Example comment'` [Середня]

**Файл:** `src/DTO/ReserveInput.php`

**Проблема:** Конструктор встановлює `$this->comment = 'Example comment'` — тестове значення у продакшн-коді.

**Виправлення:** Видалити дефолтне значення або замінити на `null`.

---

### FIX-W7 — `security.yaml`: запис через API Platform відкритий анонімам [Критична]

**Файли:** `config/packages/security.yaml`, `tests/ProductReservationApiTest.php`

**Проблема:** Останнє правило `access_control` — `{ path: ^/api, roles: PUBLIC_ACCESS }` без `methods`. Під нього потрапляє все, що не `^/api/warehouse`. Це порушує I-5 ([`ontology.md`](../../.ai/context/ontology.md)). Без токена доступні:

| Ресурс | Методи |
|---|---|
| `/api/products`, `/api/categories`, `/api/warehouses` | `POST`, `PUT`, `PATCH`, `DELETE` |
| `/api/product_reservations` | `POST`, `PATCH` (commit/cancel через workflow), `DELETE` |

Жоден клієнт у ці ендпоінти не пише: `shop-front` ходить лише в `/api/warehouse/*`, `shop-2-back` — лише в `/webhook/order-paid` (свій секрет). Фікс ламає тільки тести.

**Виправлення:**
```yaml
# До
- { path: ^/api/warehouse, roles: PUBLIC_ACCESS, methods: [GET] }
- { path: ^/api/warehouse, roles: ROLE_ADMIN, methods: [POST, PUT, PATCH, DELETE] }
- { path: ^/api, roles: PUBLIC_ACCESS }

# Після
- { path: ^/api, roles: PUBLIC_ACCESS, methods: [GET] }
- { path: ^/api, roles: ROLE_ADMIN }
```

**Кроки виконання** (з `warehouses/`, `alias wx='docker compose exec web'`):

1. **Стан «до».** `docker compose up -d`, потім запит без токена — очікуємо `201`:
   ```bash
   curl -i -X POST localhost:8088/api/categories \
     -H 'Content-Type: application/ld+json' -d '{"name":"fix-w7"}'
   ```
   Створену категорію видалити тим самим анонімним `DELETE`.
2. **Конфіг.** Замінити правила в `security.yaml`, як вище.
3. **Тести.** У `ProductReservationApiTest`:
   - хелпер `adminToken()`: токен через lexik `JWTTokenManagerInterface::createFromPayload()` з `JwtClaimUser` і claim `role: admin`. Секрет у тесті не читається і не друкується;
   - `'auth_bearer' => $this->adminToken()` у 7 наявних `POST`/`PATCH`;
   - нові тести: анонімний `POST /api/categories` → `401`, анонімний `POST /api/product_reservations` → `401`, анонімний `GET /api/products` → `200`.
4. **Перевірка.**
   ```bash
   wx php bin/phpunit && wx ./vendor/bin/psalm
   ```
   Повторити `curl` з кроку 1: без токена → `401`, з admin-JWT від `POST /api/login` (`shop-2-back`) → `201`. В адмінці `shop-front` перевірити Adjust/Transfer/Cancel — CORS-preflight має проходити, як і раніше.

**Поза обсягом:** `security:` на операціях `#[ApiResource]` — [`graphql-lab.uk.md`](graphql-lab.uk.md), рівень 3.

---

## Підсумок

| ID | Серйозність | Проект | Файл |
|----|-------------|--------|------|
| FIX-F1 | 🔴 Критична | shop-front | `categories-effects.ts` |
| FIX-F2 | 🔴 Критична | shop-front | `search.component.ts` |
| FIX-W1 | 🔴 Критична | warehouses | `InventoryTransaction.php` |
| FIX-L1 | 🔴 Критична | shop-2-back | `PageController.php` |
| FIX-W7 | 🔴 Критична | warehouses | `security.yaml` |
| FIX-L2 | 🟠 Висока | shop-2-back | `AuthController.php` |
| FIX-L3 | 🟠 Висока | shop-2-back | `ProfitReportController.php` |
| FIX-L4 | 🟠 Висока | shop-2-back | `TagController.php`, `SharedAccessController.php` |
| FIX-L5 | 🟠 Висока | shop-2-back | `SharedAccessController.php` |
| FIX-L6 | 🟠 Висока | shop-2-back | `StoreSharedAccessAction.php` |
| FIX-W2 | 🟠 Висока | warehouses | `InventoryTransaction.php` |
| FIX-W3 | 🟠 Висока | warehouses | `ProductReservationsService.php` |
| FIX-W4 | 🟠 Висока | warehouses | `ProductReservation.php` |
| FIX-F3 | 🟡 Середня | shop-front | `product-reducers.ts` |
| FIX-F4 | 🟡 Середня | shop-front | `product.component.ts` (page) |
| FIX-F5 | 🟡 Середня | shop-front | `product.component.ts` (presentational) |
| FIX-L7 | 🟡 Середня | shop-2-back | `UpdateProductRequest.php` |
| FIX-W5 | 🟡 Середня | warehouses | Product, Category, Warehouse, StockAvailability entities |
| FIX-W6 | 🟡 Середня | warehouses | `ReserveInput.php` |
