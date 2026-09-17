# Conventions

Cross-cutting rules that apply regardless of which app is touched. Per-app
code style — folder layout, PHPStan level, NgRx patterns — is **not**
repeated here; it lives in `.github/instructions/` and is loaded per app:
[`laravel`](../../.github/instructions/laravel.instructions.md) ·
[`symfony`](../../.github/instructions/symfony.instructions.md) ·
[`angular`](../../.github/instructions/angular.instructions.md). The base
rules in [`copilot-instructions.md`](../../.github/copilot-instructions.md)
apply everywhere and are the same source Copilot reads — don't fork them here.

## Naming

**Names come from the ontology** ([`ontology.md`](ontology.md)). If the
ontology says `ProductReservation`, the class is not `Reservation` alone in
one file and `ProductReservation` in another — pick one and make the ontology
and the code agree. A new or renamed concept updates `ontology.md` **in the
same change** that introduces it.

The two `Product`s and two `Category`s are not a naming inconsistency to
"fix" — they are genuinely different concepts (catalog vs. inventory) that
happen to share an English word. Do not merge their code or rename one to
disambiguate; the ontology already disambiguates them by owner.

## Design Principles

- **This is a Service Layer over ORM models in `shop-2-back`, and Repository +
  Doctrine Entity in `warehouses`** — not full DDD in either, except the
  `Reservation` aggregate deliberately built in `warehouses/src/Domain/`.
  Don't "upgrade" other concepts to aggregates as a side effect of an
  unrelated task — that's scope, not improvement.
- **Name the smell** in review — Feature Envy, Primitive Obsession, Shotgun
  Surgery — rather than a vague "this could be cleaner."
- When a principle and a passing, readable test disagree, the test wins.

## DO

- Keep the shop↔warehouse boundary async (queued job / message), matching
  ADR-002 in `architecture.md`, unless a task explicitly changes that.
- Treat `JWT_SHARED_SECRET` as the one piece of config that must match across
  `shop-2-back/.env` and `warehouses/.env` — call this out explicitly in any
  change that touches auth on either side.
- Keep each app's dependencies in its own `composer.json`/`package.json`. A
  task that needs the same library in two apps adds it twice, deliberately.
- Write the task outcome into the task's file in `tasks/planned/` before
  moving it to `tasks/done/` — see [[../router/implement.md]].

## DON'T

- Don't add a dependency to any app without raising it first — `shop-2-back`'s
  Composer install is already fragile (`.ai/context/stack.md`); an
  unnecessary attempt there costs real time.
- Don't decrease PHPStan level (4, `shop-2-back`) or Psalm errorLevel (3,
  `warehouses`) to make a change pass.
- Don't commit `.env`, `.env.dev`, `.env.test`, or any real secret. `.env.example`
  files are the place for new keys, with a comment, no real value.
- Don't call a second app's database or filesystem directly — cross the
  boundary through its HTTP API or the async notification path, never a raw
  DB connection into another app's schema.
- Don't invent a `TASK-NNN` id — this repo names task files by feature, see
  `tasks/planned/` for the existing pattern.

## Commits

- Conventional commits: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`
  (already required by `copilot-instructions.md`).
- One logical change per commit; don't mix a refactor with a feature in one
  commit spanning two apps.

## Definition of Done

A change is not done until all of these hold:

1. Code follows the per-app instructions file; the app's own linter/formatter
   is clean (`pint`, `psalm`, `ng lint`).
2. The app's test suite passes in full — not just the new test.
3. Every invariant the change touches (`ontology.md`) is still true — name
   which, in the task file.
4. If a decision was made between real alternatives, it's an ADR in
   `architecture.md`.
5. If a concept was added or renamed, `ontology.md` is updated.
6. If the change crosses the shop↔warehouse boundary, both `.env.example`
   files and `stack.md` reflect any new config key.
7. The task file has moved to `tasks/done/`, or gained a clear status note if
   only partially complete.
