# `.ai/` — Spec-Driven Development Layer

The knowledge needed to work on this monorepo correctly, version-controlled and
reviewed like code rather than kept in prompts.

Prompting per task produces per-task quality: each session re-derives which
`Product` is which, re-decides settled questions about the shop/warehouse
boundary. This layer moves that knowledge into the repository, where it is
corrected once and reused.

The organising idea is **separation of altitudes** — neither level benefits
from the other's detail loaded at the wrong moment:

```
ontology  ──►  PLANNING       what exists, how it relates, across all 3 apps
context   ──►  IMPLEMENTING   where code goes in each app, how to write it
tools     ──►  ACTING         what may be read, run, changed
```

## Layout

```
.ai/
├── business/VALUE.md         Value drivers V-1…V-5, requirement trace,
│                             success criteria, non-goals
├── context/
│   ├── INDEX.md              Which module to load, and when
│   ├── ontology.md           The domain — concepts, relations, I-1…I-8
│   ├── stack.md              Versions, services, env keys, commands per app
│   ├── architecture.md       Layering, concept→code map, ADR-001…
│   ├── conventions.md        Cross-cutting rules; links to .github/instructions
│   │                         for per-framework detail (no duplication)
│   ├── testing.md            Test layers per app, required coverage T-1…
│   └── tools.md              Permission model, search/edit/verify practice
├── router/                   implement · review — the two modes that change
│                             something, or judge what changed
├── backlog/INDEX.md          Points into tasks/planned/ — this repo's real backlog
└── archive/INDEX.md          Points into tasks/done/ — this repo's real archive
```

Deliberately absent: a `.ai/backlog/TASK-NNN` scheme. `tasks/planned/` and
`tasks/done/` already do that job under their own naming — duplicating them
here would just create a second place to go stale.

[`../CLAUDE.md`](../CLAUDE.md) is the entry point that routes into all of this.

## The Traceability Chain

```
Value driver (V-n) ──► Requirement ──► Ontology concept ──► Invariant (I-n)
                                              │
                                              ▼
                                    Task file (tasks/planned/*.md)
                                              │
                                              ▼
                            Code ──► Test (T-n) ──► tasks/done/*.md
```

Each link is checkable, and a break in it is a defect worth naming:

- A task with no value driver is scope creep.
- An invariant with no test is a promise nobody is keeping.
- A concept in the code absent from the ontology is a name nobody agreed on.
- A `Product` in `shop-2-back` with no matching `sku` in `warehouses` is a
  catalog entry the warehouse cannot fulfil.

## Maintenance

- Context describes the **current** system across all three apps. Edit in
  place; git holds the history.
- A decision between real alternatives becomes an ADR in `architecture.md`.
- A new or renamed concept updates [`context/ontology.md`](context/ontology.md)
  **in the same change**.
- Drift between a module and the code is a defect. A stale context file is
  worse than a missing one, because it is trusted.
