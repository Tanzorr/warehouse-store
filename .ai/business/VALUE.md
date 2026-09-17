# Value

Why this repository exists, and what it deliberately does not try to be.

## Value Drivers

> **V-1 — A working shop↔warehouse handoff.** A customer can browse a catalog,
> order, pay, and have that payment reliably turn into a stock reservation in
> a separately-owned warehouse service — without the two services sharing a
> database.

> **V-2 — One identity, two services.** A user authenticates once; the same
> credential (the shared JWT) is honoured by both the shop and the warehouse
> API, so the frontend doesn't juggle two sessions.

> **V-3 — Architecture as a practiced skill, not just a delivered feature.**
> Per `tasks/done/planned_ddd/README.md`, a stated goal of this codebase is
> applying CQRS, DDD (aggregates, value objects, domain events, repositories)
> and bounded contexts to a real, if small, system — not a tutorial toy. A
> task that "simplifies away" a pattern deliberately introduced for this
> reason should say so explicitly, not silently.

> **V-4 — Stock numbers stay honest.** `StockAvailability` and
> `InventoryTransaction` exist so that what the warehouse says is in stock is
> auditable and doesn't drift from reservations made against it.

> **V-5 — An admin can operate the system without touching either API
> directly.** `shop-front`'s admin views (orders, products, warehouse stock,
> auth) are the operational surface — a change that's only reachable via a
> raw API call isn't done from an operator's point of view.

## Requirement Trace

| Requirement | Ontology concept | Invariant |
| :--- | :--- | :--- |
| Payment reliably reserves stock | `Order`, `ProductReservation` | I-4 |
| One login works on both APIs | `User`, `JwtClaimUser` | I-2 |
| Reservation state can't be corrupted by a stray write | `ProductReservation` | I-3 |
| Catalog and inventory stay linkable without a shared DB | `Product` (×2) | I-1 |
| Only admins mutate warehouse state | `ProductReservation`, `StockAvailability` | I-5 |

## Success Criteria

- A payment on `shop-2-back` results in exactly one reservation-affecting
  action on `warehouses`, visible as an `InventoryTransaction`.
- Revoking or rotating `JWT_SHARED_SECRET` locks both services out
  simultaneously — never one without the other.
- A concept introduced in either backend for this flow has a name in
  `ontology.md`, not just a class.

## Non-Goals

Explicit, so a task doesn't quietly grow to include them:

- **Production-grade payment processing.** Whatever triggers
  `NotifyWarehouseOfPaymentJob` today is the payment concern of this repo —
  hardening it into a real gateway integration is a separate, explicit task,
  not a side effect of touching the job.
- **Multi-tenancy.** One shop, one set of warehouses.
- **Horizontal scaling / multi-instance JWT revocation lists.** The shared
  secret is the entire trust model.
- **A shared database or shared ORM between `shop-2-back` and `warehouses`.**
  I-1 is a design choice, not a gap to close.
- **Replacing the practice-project patterns with "simpler" code** in the name
  of pragmatism — see V-3. Raise it as a task with the trade-off stated,
  don't just do it.
