# Task Plan: Bug Fixes Across All Three Projects

## Вступ

Цей файл містить конкретні виправлення, знайдені під час аналізу кодової бази. Кожне виправлення є незалежним і може бути зроблено окремо. Проблеми відсортовані за пріоритетом.

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

## Підсумок

| ID | Серйозність | Проект | Файл |
|----|-------------|--------|------|
| FIX-F1 | 🔴 Критична | shop-front | `categories-effects.ts` |
| FIX-F2 | 🔴 Критична | shop-front | `search.component.ts` |
| FIX-W1 | 🔴 Критична | warehouses | `InventoryTransaction.php` |
| FIX-L1 | 🔴 Критична | shop-2-back | `PageController.php` |
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
