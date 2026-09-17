# Shop & Warehouse Monorepo — Agent Entry Point

Three independent apps sharing one business process — a customer pays for a
product in the shop, the warehouse confirms and reserves stock:

| App | Framework | Role |
| :--- | :--- | :--- |
| `shop-2-back/` | Laravel 11 + PHP 8.2 | Shop REST API — catalog, orders, payments, users |
| `warehouses/` | Symfony 7.2 + API Platform 4 | Warehouse microservice — stock, reservations |
| `shop-front/` | Angular 20 + NgRx 19 | Admin + storefront SPA for both APIs |

This file routes to the spec-driven layer in [`.ai/`](.ai/README.md). Read the
relevant section below before acting — not this whole file, and not every
context module.

---

## 0. BE BRIEF

Whoever reviews this repository may have twenty minutes, not an afternoon.

- **Lead with the answer.** Context after it, or not at all.
- **One fact in one place.** If it is stated in another file, link — never restate.
- **Cut any sentence that survives its own deletion.**
- **Tables and lists** wherever the content is a set of items.
- **No preamble, no recap.** Don't announce what you're about to do, don't
  summarise what you just did.

Applies to documentation, tickets, commit messages, code comments, and replies
to the user alike.

---

## 1. ONTOLOGY

The domain model is authoritative and lives in
[`.ai/context/ontology.md`](.ai/context/ontology.md) — concepts, relations,
invariants I-1…I-8, open questions. One file, no second copy.

**Concepts:** `Product` (catalog + inventory) · `Category` · `Order` ·
`OrderItem` · `User` · `Warehouse` · `StockAvailability` · `ProductReservation` ·
`ProductReservationItem` · `InventoryTransaction`

The one to remember without looking it up:

> **I-1** — `Product.sku` is the only link between the shop's catalog `Product`
> (`shop-2-back`) and the warehouse's inventory `Product` (`warehouses`). There
> is no shared database and no foreign key between the two services.

---

## 2. ROUTING

Pick the mode that matches the work, load that file, follow it.

| Mode | File | Use when |
| :--- | :--- | :--- |
| **implement** | [`.ai/router/implement.md`](.ai/router/implement.md) | Building what a task file in `tasks/planned/` describes |
| **review** | [`.ai/router/review.md`](.ai/router/review.md) | Judging a diff before commit |

Anything else — answering a question, chasing a bug, shaping a new task — needs
no mode file. Read [`.ai/context/INDEX.md`](.ai/context/INDEX.md) and then only
what the question actually needs.

**Per-framework detail is not duplicated here** — load it from
`.github/instructions/` when touching that app:
[`laravel.instructions.md`](.github/instructions/laravel.instructions.md) ·
[`symfony.instructions.md`](.github/instructions/symfony.instructions.md) ·
[`angular.instructions.md`](.github/instructions/angular.instructions.md)

---

## 3. WORK TRACKING

- **Planned:** [`tasks/planned/`](tasks/planned/) — one file per initiative, not a strict ticket template
- **Done:** [`tasks/done/`](tasks/done/)
- This repo does not use a `TASK-NNN` id scheme — tasks are named by feature
  (`order-payment-domain-events.md`, `queue-improvements.md`, …)

Work is done against a task file. If there is no file for what you're about to
do, either write one or say the request falls outside the backlog — do not
silently expand scope.

---

## 4. TOOL USAGE

Full guidance: [`.ai/context/tools.md`](.ai/context/tools.md). Machine-enforced
permissions: [`.claude/settings.json`](.claude/settings.json).

**Search** — `rg` over `grep`, always excluding `vendor/`, `node_modules/`,
`dist/`, `var/`; `jq` for JSON. Search by ontology term first, then by app
folder — the three apps do not share a namespace.

**Edit** — read the file before editing it. Targeted replacements, not
whole-file rewrites. Never mix changes across two apps in one edit unless the
task is explicitly cross-service.

**Verify** — run the check, don't reason about whether it would pass:

```bash
# shop-2-back
php artisan test && ./vendor/bin/pint --test && ./vendor/bin/phpstan analyse
# warehouses
php bin/phpunit && ./vendor/bin/psalm
# shop-front
ng test
```

**Hard rules**

1. Never read `.env` / `.env.dev` / `.env.test` in any app — use `.env.example`.
   `JWT_SHARED_SECRET` in particular must never be printed or logged.
2. Never run `migrate:fresh`, `doctrine:database:drop`, `docker compose down -v`,
   `rm -rf` or `git push --force` unless explicitly asked.
3. Never commit or push unless asked.
4. Never add a dependency to any app's `composer.json`/`package.json` without
   raising it first — `shop-2-back`'s composer install is already fragile, see
   [`.ai/context/stack.md`](.ai/context/stack.md).
5. Never write outside the project root.
6. Report what actually happened. A failing command is reported with its
   output; a skipped step is stated, not omitted.

---

## 5. CURRENT STATE

All three apps have working code — this is not a greenfield spec layer.
`tasks/done/planned_ddd/` records the CQRS/DDD/JWT foundation already built;
`tasks/planned/` is the open backlog. Before assuming a class, route, or
config key exists, check — `.ai/context/stack.md` describes intent as well as
fact, and drift between the two is itself worth flagging.
