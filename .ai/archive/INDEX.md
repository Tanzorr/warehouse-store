# Archive

This repo's real archive is [`../../tasks/done/`](../../tasks/done/) — a
pointer, not a copy.

## `tasks/done/planned_ddd/` — CQRS/DDD/JWT practice track

Ten completed tasks building the foundation the rest of the backlog assumes
already exists (see [`README.md`](../../tasks/done/planned_ddd/README.md) for
the full map and recommended order):

| # | Task | Delivered |
| :--- | :--- | :--- |
| 01 | `01-laravel-command-query-bus.md` | Command Bus + Query Bus (Laravel) |
| 02 | `02-laravel-value-objects.md` | Value Objects — `Money`, `Sku`, `OrderId`, … |
| 03 | `03-laravel-aggregate-roots.md` | Aggregate Root + domain events |
| 04 | `04-laravel-repository-pattern.md` | Repository interface + Eloquent implementation |
| 05 | `05-warehouse-cqrs-commands.md` | `CancelReservation`, `AdjustStock`, `TransferStock` (Symfony) |
| 06 | `06-warehouse-cqrs-queries.md` | `GetStockLevels`, `GetReservations` — query side |
| 07 | `07-angular-admin-orders.md` | Admin: order management UI |
| 08 | `08-angular-admin-products.md` | Admin: product/category management UI |
| 09 | `09-angular-admin-warehouse.md` | Admin: stock + reservation views |
| 10 | `10-angular-admin-auth.md` | Login page, `AuthService`, interceptor, admin guard |
| 11 | `11-shared-jwt-auth.md` | Shared HS256 JWT — Laravel issues, both back-ends validate (ADR-001) |

This is where `ontology.md`'s `ProductReservation`/`Reservation` aggregate,
the CQRS command/query buses, and the shared-JWT invariant (I-2) all trace
back to.

Closing a task from `tasks/planned/`: move the file here and add a short
outcome note per the Definition of Done in
[`../context/conventions.md`](../context/conventions.md) — what shipped,
what deviated from the plan, which invariants were verified.
