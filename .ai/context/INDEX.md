# Context Index

Load only what the task needs.

| Module | Load when |
| :--- | :--- |
| [`ontology.md`](ontology.md) | Any task touching `Product`, `Order`, `Reservation`, or the shop↔warehouse boundary |
| [`stack.md`](stack.md) | Setting up, running, or debugging any of the three apps |
| [`architecture.md`](architecture.md) | Deciding where new code goes, or recording a design decision |
| [`conventions.md`](conventions.md) | Every implement/review pass, no exceptions |
| [`testing.md`](testing.md) | Writing or judging tests |
| [`tools.md`](tools.md) | Unsure whether a command or file is in scope |

Per-framework code style lives outside `.ai/`, in
[`.github/instructions/`](../../.github/instructions/) — load the one file for
the app you're touching, not all three:

- [`laravel.instructions.md`](../../.github/instructions/laravel.instructions.md) — `shop-2-back/**`
- [`symfony.instructions.md`](../../.github/instructions/symfony.instructions.md) — `warehouses/**`
- [`angular.instructions.md`](../../.github/instructions/angular.instructions.md) — `shop-front/**`

A question that doesn't change anything on disk needs none of these beyond
`ontology.md` — read that, answer, stop.
