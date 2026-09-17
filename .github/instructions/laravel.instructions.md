---
description: "Use when writing Laravel code in shop-2-back: controllers, services, models, requests, actions, queries, migrations, or tests."
applyTo: "shop-2-back/**"
---

# Laravel (shop-2-back) — Best Practices

## Architecture

- **Controllers are thin** — they validate input via Form Requests and delegate all logic to Services
- **Services** (`app/Services/`) contain business logic; inject them via constructor with `readonly`
- **Actions** (`app/Actions/`) are for single-purpose, complex operations (one public `handle()` method)
- **Queries** (`app/Queries/`) handle complex read-only queries; prefer over raw query builder in controllers
- **Scopes** (`app/Scopes/`) encapsulate reusable Eloquent query constraints
- Never put business logic directly in controllers or models

## Controllers

```php
// Good — thin controller, delegates to service
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function store(StoreProductRequest $request): JsonResponse
    {
        return response()->json($this->productService->storeProduct($request->validated()));
    }
}
```

- Always return `JsonResponse` from API controllers
- Use route model binding (`Product $product`) instead of manual `find()`
- Never call `$request->all()` — always use `$request->validated()`

## Form Requests

- Every write endpoint must have a dedicated Form Request in `app/Http/Requests/`
- Name pattern: `StoreXxxRequest`, `UpdateXxxRequest`, `XxxFilterRequest`
- All validation rules go inside `rules()` — no inline validation in controllers

## Models

- Use `$casts` for type coercion (dates, booleans, enums)
- Define `$fillable` explicitly — never use `$guarded = []`
- Relationships must have explicit return types
- Scopes belong to model only when they are simple; extract to `app/Scopes/` when complex

## Static Analysis

- PHPStan level **4** — do not decrease
- Run before committing: `vendor/bin/phpstan analyse`
- Format code with Laravel Pint: `vendor/bin/pint`
- Psalm is also present — run: `vendor/bin/psalm`

## API Documentation

- All public endpoints must have Swagger/OpenAPI annotations using `zircote/swagger-php` attributes
- Annotations go in `app/Http/Controllers/Swagger/` or directly on controller methods
- After changes run: `php artisan l5-swagger:generate`

## Testing

- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`
- Use `RefreshDatabase` trait for DB tests
- Mock external services; do not hit real APIs in tests
- Run: `php artisan test`

## Architecture Constraints (Deptrac)

- Deptrac enforces layer boundaries — do not bypass them
- Check violations: `vendor/bin/deptrac analyse`
