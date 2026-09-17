# Ontology

The domain model, authoritative and versioned. If code and this file disagree
on a name or a rule, one of them is wrong — fix it, don't translate at the
boundary.

Two services model overlapping reality under **different names for the same
thing** (`Product` exists in both, `Category` exists in both) and there is no
shared database. The ontology's job is to say precisely where they touch.

## Concepts

| Concept | Owned by | What it is |
| :--- | :--- | :--- |
| `Product` (catalog) | `shop-2-back` | A sellable item: name, description, `purchase_price`, `sale_price`, `sku`, `category_id` |
| `Category` (catalog) | `shop-2-back` | Groups catalog `Product`s |
| `Order` | `shop-2-back` | A customer's purchase; has many `OrderItem` |
| `OrderItem` | `shop-2-back` | One line of an `Order` — references a catalog `Product`, quantity, price at time of sale |
| `User` | `shop-2-back` | The only identity store in the system; issues the JWT both services trust |
| `Product` (inventory) | `warehouses` | The same real-world item, keyed by `sku`: name, description, `price`, `category` |
| `Category` (inventory) | `warehouses` | Groups inventory `Product`s — **not** the same rows as the catalog `Category` |
| `Warehouse` | `warehouses` | A physical location holding stock |
| `StockAvailability` | `warehouses` | Quantity of one `Product` at one `Warehouse` |
| `ProductReservation` | `warehouses` | A hold against stock for an `Order`; aggregate root, status driven by `symfony/workflow` |
| `ProductReservationItem` | `warehouses` | One line of a `ProductReservation` — `Product` + quantity |
| `InventoryTransaction` | `warehouses` | Audit record of a `StockAvailability` change |
| `JwtClaimUser` | `warehouses` (derived) | Not persisted — built from the shared JWT's claims (`sub`, `email`, `role`) by `App\Security\JwtUserProvider` |

Also present in `shop-2-back` but outside the shop↔warehouse process:
`Tag`, `Page`, `Media`, `MediaRelation`, `SharedAccess` — CMS-adjacent, no
ontology entry needed until a task touches them.

## Relations

```
User ──issues──► JWT (HS256, shared secret) ──validated by──► JwtClaimUser
Order ──has many──► OrderItem ──references──► Product (catalog)

Product (catalog) ──same sku, no FK──► Product (inventory)

Product (inventory) ──has many, per Warehouse──► StockAvailability
ProductReservation ──has many──► ProductReservationItem ──references──► Product (inventory)
ProductReservation ──state machine (workflow.yaml)──► ReservationStatus
StockAvailability change ──recorded as──► InventoryTransaction

Order paid (shop-2-back) ──NotifyWarehouseOfPaymentJob──► ProductReservation created/confirmed (warehouses)
```

## Invariants

> **I-1** — `Product.sku` is the **only** link between the catalog `Product`
> (`shop-2-back`) and the inventory `Product` (`warehouses`). There is no
> shared database and no foreign key. A catalog product with no matching `sku`
> in `warehouses` cannot be fulfilled — that is a data problem, not a bug to
> code around.

> **I-2** — Auth is one shared HS256 JWT. Only Laravel's `LoginAction` issues
> it; both `shop-2-back` (`JwtAuthMiddleware`) and `warehouses`
> (lexik + `JwtUserProvider`) validate the **same** token against
> `JWT_SHARED_SECRET`, which must be byte-identical in both `.env` files.
> Neither service has its own user table for API auth — `warehouses` has none
> at all.

> **I-3** — A `ProductReservation`'s status changes only through the
> transitions defined in `warehouses/config/packages/workflow.yaml`, guarded by
> an `EventSubscriber`. Never set the status field directly.

> **I-4** — An `Order` payment dispatches exactly one
> `NotifyWarehouseOfPaymentJob` (`shop-2-back/app/Jobs/`), handled by
> `DispatchWarehouseNotification`. Not zero, not more than one per payment.

> **I-5** — In `warehouses`, `GET` is public; `POST`/`PUT`/`PATCH`/`DELETE`
> require `ROLE_ADMIN`, per `security.yaml`. A write endpoint with no role
> check is a bug, not a convenience.

> **I-6** — A catalog `Product` may not be deleted outside `App\Services` /
> `App\Actions` business logic — never a raw `Product::destroy()` in a
> controller or a `tinker` one-liner. Same rule for inventory `Product` in
> `warehouses`: never bypass the repository/service layer from a controller.

> **I-7** — Monetary values (`purchase_price`, `sale_price`, `price`) are
> `DECIMAL`, stored and passed as strings — never cast to PHP `float`.
> Comparing or summing them as floats is the invariant this exists to prevent.

> **I-8** — All persisted timestamps are UTC. Compare and publish in UTC;
> convert only when rendering in `shop-front`.

## Open Questions

Flag these rather than assuming an answer when a task depends on one:

- Does cancelling an `Order` release its `ProductReservation` automatically,
  or does an operator do it? (`ReservationStatus` transitions should say —
  verify against `workflow.yaml` before implementing anything that assumes
  either.)
- Is stock reserved at `Order` creation or only after payment confirms?
  `NotifyWarehouseOfPaymentJob` firing on payment suggests the latter, but
  this is inferred from the job name, not confirmed against its handler body.
- Inventory `Category` and catalog `Category` are separate rows with no link
  today — is that intentional, or a gap `sku`-style linking should also close?
