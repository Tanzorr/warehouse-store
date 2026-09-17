# Architecture

Layering per app, the concept→code map, and the decision log. Per-framework
code style (naming, folder conventions, DTO rules) lives in
`.github/instructions/*.md` — this file is about **where things are and why**,
not how to format them.

## Layering

```
shop-2-back (Laravel)          warehouses (Symfony)           shop-front (Angular)
────────────────────           ─────────────────────          ────────────────────
Http/Controllers   thin        Controller / ApiResource        pages (smart)
  ↓ validated input              ↓ DTO in, DTO out               ↓ dispatch action
Services / Actions  logic      Service                         effects → HTTP
  ↓                              ↓                                ↓
Models (Eloquent)              Repository → Entity (Doctrine)   store (NgRx)
  ↓                                                                ↓
MySQL                          MySQL                            presentational
```

`shop-2-back` and `warehouses` do not call each other synchronously in the
request path for the payment flow — `shop-2-back` dispatches a queued job
(`NotifyWarehouseOfPaymentJob`) that calls the warehouse API asynchronously.
Treat that boundary as async-first; adding a synchronous call across services
is a decision, not a default (raise it, don't just do it — see ADR-002).

## Concept → Code Map

| Concept | `shop-2-back` | `warehouses` |
| :--- | :--- | :--- |
| `Product` | `app/Models/Product.php` | `src/Entity/Product.php` |
| `Category` | `app/Models/Category.php` | `src/Entity/Category.php` |
| `Order` / `OrderItem` | `app/Models/Order.php`, `OrderItem.php` | — |
| `User` / auth | `app/Models/User.php`, JWT issued in `LoginAction` | `App\Security\JwtUserProvider`, `JwtClaimUser` (not persisted) |
| `Warehouse` | — | `src/Entity/Warehouse.php` |
| `StockAvailability` | — | `src/Entity/StockAvailability.php` |
| `ProductReservation` / Item | — | `src/Domain/Reservation/Reservation.php`, `ReservationItem.php`, `ReservationStatus.php` |
| `InventoryTransaction` | — | `src/Entity/InventoryTransaction.php` |
| Reservation state machine | — | `config/packages/workflow.yaml` + an `EventSubscriber` |
| Payment→warehouse notification | `app/Jobs/NotifyWarehouseOfPaymentJob.php`, `app/Listeners/DispatchWarehouseNotification.php` | receiving endpoint under `src/Controller/` or `src/ApiResource/` — locate before assuming its shape |

`warehouses/src/Domain/` is the framework-free layer (aggregate roots, value
objects, events) for `Reservation`; `warehouses/src/Application/{Command,Query}`
is the CQRS side built in the DDD/CQRS practice track
(`tasks/done/planned_ddd/05…06`).

## Decisions (ADR Log)

Format: what was decided, why, what it cost. Add a new entry rather than
editing an old one — a reversed decision gets its own ADR that supersedes.

> **ADR-001 — One shared HS256 JWT instead of per-service auth**
> **What:** Laravel issues a single JWT; both `shop-2-back` and `warehouses`
> validate it against the same `JWT_SHARED_SECRET`, rather than each service
> running its own auth (e.g. a second user table, or service-to-service API
> keys).
> **Why:** `warehouses` has no natural source of user identity of its own —
> Laravel already owns `User`. One token avoids a sync problem between two
> identity stores.
> **Cost:** the secret is now a piece of config two independent codebases must
> keep byte-identical; a silent mismatch fails auth on one side only (see
> [[shared-jwt-laravel-symfony]]). No token revocation short of rotating the
> shared secret for everyone.

> **ADR-002 — `JwtAuthMiddleware` replaces Sanctum on API routes**
> **What:** `shop-2-back` keeps `laravel/sanctum` installed but API routes are
> guarded by a custom `JwtAuthMiddleware` (alias `jwt`), which calls
> `Auth::setUser` so `RoleMiddleware` keeps working unchanged.
> **Why:** Sanctum's session/cookie model doesn't produce a token a separate
> Symfony service can validate; a custom HS256 middleware does.
> **Cost:** Sanctum's own middleware and helpers (`auth:sanctum`) are dead
> weight on these routes — don't reach for them out of habit.

Add here when a real decision between alternatives is made — not for routine
implementation choices already covered by `conventions.md`.
