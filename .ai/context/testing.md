# Testing

Test layers per app, and the coverage that matters because it protects an
invariant — not coverage for its own sake.

## Layers

| App | Layer | Location | Run |
| :--- | :--- | :--- | :--- |
| `shop-2-back` | Feature | `tests/Feature/` | `php artisan test --testsuite=Feature` |
| `shop-2-back` | Unit | `tests/Unit/` (includes `Unit/Domain/`) | `php artisan test --testsuite=Unit` |
| `shop-2-back` | Doubles | `tests/Doubles/`, `tests/Unit/Doubles/` — fakes for external services (warehouse notification, mail) | used by Feature/Unit |
| `warehouses` | Domain | `tests/Domain/Reservation/` — the aggregate built in the DDD/CQRS track | `php bin/phpunit` |
| `shop-front` | Unit | none yet — `*.spec.ts` files exist in scaffolding but no suite has been written | `ng test` |

`shop-front` having no real spec files today is a gap, not a convention to
preserve — a task that adds frontend logic worth testing should add the spec
alongside it rather than treating the absence as precedent.

## Coverage That Matters

Each ties to an invariant in [`ontology.md`](ontology.md). A test that would
still pass if the invariant were violated is decorative — say so in review.

| Id | Behaviour | Invariant |
| :--- | :--- | :--- |
| T-1 | Placing/paying an order dispatches `NotifyWarehouseOfPaymentJob` exactly once | I-4 |
| T-2 | A second payment webhook / retry for the same order does not dispatch a second job | I-4 |
| T-3 | `ProductReservation` status only changes via a workflow transition; a direct field write is rejected or impossible through the public API | I-3 |
| T-4 | A `warehouses` write endpoint (POST/PUT/PATCH/DELETE) rejects a request without `ROLE_ADMIN` | I-5 |
| T-5 | A `warehouses` GET endpoint succeeds with no auth token | I-5 |
| T-6 | A JWT signed with the wrong secret is rejected by both `shop-2-back` and `warehouses` | I-2 |
| T-7 | Money fields round-trip through create/read without float precision loss | I-7 |
| T-8 | Deleting a `Product` (either service) goes through the service/repository layer — a test asserting the raw model/entity delete path is not called directly from a controller | I-6 |

## Practice

- **Fakes must fail like the real thing.** A fake warehouse notifier that
  always succeeds makes T-1/T-2 lie about the retry path.
- **Mock external services; never hit a real API in a test** — the warehouse
  HTTP call in `NotifyWarehouseOfPaymentJob` is exactly this case.
- **`RefreshDatabase`** for `shop-2-back` DB tests; Doctrine fixtures
  (`hautelook/alice-bundle`) for `warehouses`.
- **Run the check.** Don't reason about whether a test would pass — the
  Definition of Done in `conventions.md` requires the full suite to have
  actually run.
