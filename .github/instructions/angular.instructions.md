---
description: "Use when writing Angular code in shop-front: components, services, NgRx store (actions, reducers, effects, selectors), models, routes, or tests."
applyTo: "shop-front/**"
---

# Angular / NgRx (shop-front) — Best Practices

## Architecture

- **Pages** (`app/pages/`) — smart (container) components; connect to NgRx store, pass data to presentational components
- **Presentational** (`app/presentational/`) — dumb components; accept data via `@Input()`, emit via `@Output()`; no store dependency
- **Components** (`app/components/`) — shared components used across multiple pages
- **Services** (`app/services/`) — HTTP calls only; return `Observable<T>`; no state management here
- **Store** (`app/store/`) — NgRx state management; one folder per feature
- **Models** (`app/models/`) — TypeScript interfaces and types; no classes unless needed

## NgRx Pattern

Each feature in `app/store/<feature>/` must have:

```
<feature>-actions.ts
<feature>-reducers.ts
<feature>-effects.ts
<feature>-selectors.ts   (named *-response-selectors.ts or *-selectors.ts)
```

### Actions

```typescript
// Naming: '[Feature] Action Name'
export const getProducts = createAction('[Products] Get Products', (params?: string) => ({ params }));
export const getProductsSuccess = createAction('[Products] Get Products Success', (products: ProductsResponse) => ({ products }));
export const getProductsFailure = createAction('[Products] Get Products Failure', (error: unknown) => ({ error }));
```

- Always create three actions per async operation: trigger, success, failure
- Never use `any` for the error type — use `unknown` and narrow in the effect

### Effects

- Effects handle **only** HTTP calls and side effects — no business logic
- Always handle errors with `catchError` returning a failure action
- Use `inject()` for dependency injection in effects (functional style preferred)

### Reducers

- Pure functions only — no side effects, no HTTP calls
- Use `on()` handlers for each action
- Initial state must be explicitly typed with an interface

### Selectors

- Create a selector for every piece of state consumed by a component
- Use `createSelector` for derived/computed state — never compute in templates

## Components

- App uses **Zoneless** change detection (`provideZonelessChangeDetection()`) — always use `OnPush` or signals-compatible patterns
- `withComponentInputBinding()` is enabled — use `@Input()` to receive route params instead of `ActivatedRoute`
- Never use `document` or `window` directly — use Angular abstractions or `DOCUMENT` token

## Services

```typescript
@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly http = inject(HttpClient);

  getProducts(params?: string): Observable<ProductsResponse> {
    return this.http.get<ProductsResponse>('/api/products', { params });
  }
}
```

- Use `inject()` over constructor injection in services
- Return typed `Observable<T>` — never subscribe inside a service

## Models

- Define one interface per domain entity in `app/models/`
- Use `readonly` properties for immutable DTOs
- Avoid `any` — use `unknown` and type-guard when necessary

## Routing

- Lazy-load all feature routes
- Use `withComponentInputBinding()` — pass route params as component inputs

## Styling

- Bootstrap 5 for layout and UI components
- Component-scoped SCSS in `*.component.scss`
- No global styles except in `styles.scss`

## Testing

- Unit test services and pure NgRx functions (reducers, selectors)
- Use `TestBed` for component tests
- Run: `ng test`
