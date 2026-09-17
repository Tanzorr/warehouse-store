# Mode: IMPLEMENT

Build what a task file in `tasks/planned/` describes.

## Load

1. The task file — the whole thing, including any "out of scope" section
2. [`../context/conventions.md`](../context/conventions.md) — every time, no exceptions
3. [`../context/architecture.md`](../context/architecture.md) — layering, concept→code map, ADRs
4. [`../context/stack.md`](../context/stack.md) — versions, services, env keys, for the app(s) in scope
5. [`../context/testing.md`](../context/testing.md) — before writing tests
6. The invariants the task touches, from [`../context/ontology.md`](../context/ontology.md)
7. The per-app instructions file for the app being touched (see
   [`../context/INDEX.md`](../context/INDEX.md))

## Sequence

1. **Re-read the acceptance criteria** (or, absent a formal list, the task's
   stated goal). If ambiguous, resolve it now — an assumption found at review
   costs the whole change.
2. **Locate the seams.** Which existing files change, in which app? Read them
   fully before editing. Use the concept→code map rather than guessing paths.
3. **Write the test first for invariant-bearing behaviour.** Not TDD dogma —
   an I-3/I-4-class invariant is exactly what a written-afterwards test
   quietly fails to cover.
4. **Implement the smallest thing that satisfies the task.** Not the general
   case, not the adjacent feature nobody asked for.
5. **Run the checks** for every app touched — see `tools.md`.
6. **Update what the change invalidated** — ontology, ADR, `stack.md` env
   keys, `.env.example`.
7. **Close out**: move the task file to `../../tasks/done/`, or add a status
   note if only partially complete, per the Definition of Done in
   `conventions.md`.

## Rules

- **Stay inside the task.** Something else being broken is a new task file.
  Fixing it here makes the diff unreviewable and the revert impossible.
- **Don't cross the shop↔warehouse boundary synchronously** without it being
  what the task asks for — see ADR-002.
- **New concept ⇒ update `ontology.md`** in the same change, not later.
- **New decision ⇒ ADR** in `architecture.md`.
- **Blocked, or the task is wrong?** Stop and say so. Don't implement
  something adjacent and describe it as the task.
- **Never claim a passing test you didn't run.** Paste the failure if it failed.

## Done When

The Definition of Done in
[`../context/conventions.md`](../context/conventions.md) is satisfied in
full — every item, not the convenient five.
