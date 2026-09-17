# Mode: REVIEW

Judge a change against what this repo promised, before it's committed.

## Load

1. The diff — `git diff`, or `git diff --staged`
2. The task file it claims to implement
3. `../context/conventions.md` — the DON'T list and the Definition of Done
4. `../context/ontology.md` — invariants
5. `../business/VALUE.md` — only if scope looks wrong

## Checklist

**Correctness**
- Does the change satisfy the task's stated goal, or only part of it?
- Which invariants (I-1…I-8) does it touch? Is each still true?
- Failure modes: warehouse API unreachable, JWT secret mismatch, concurrent
  payment retry, a reservation transition attempted from an invalid state.
  What happens?
- Does anything set a `ProductReservation` status directly instead of through
  the workflow (I-3)? Delete a `Product` outside its service/repository
  layer (I-6)?

**Scope**
- Does it do what the task said and nothing else? Unticketed extras are a
  finding even when the code is good.
- Does it touch a second app when the task only asked for one, without that
  being the point of the task?

**Tests**
- Does a test exist for the new behaviour *and* its failure mode?
- Would the test fail if the behaviour regressed? If deleting the
  implementation leaves the test green, it's decorative.
- If the change touches payment→warehouse notification or reservation
  status, do T-1…T-3 (`testing.md`) still hold?

**Design principles**
- Two reasons for a class to change (SRP)? Domain code referencing
  infrastructure (DIP)?
- Name the smell if there is one — Feature Envy, Primitive Obsession, Shotgun
  Surgery — rather than a vague "could be cleaner."

**Conventions**
- Per-app style followed (`.github/instructions/*.md` for the app in
  question) — typed signatures, `final`/`readonly` where the app's convention
  calls for it, no `dd()`/`dump()`/commented-out code.
- Money handled as decimal/string, never cast to float (I-7).
- Names match the ontology.

**Documentation**
- New concept in the ontology? New decision as an ADR? Drifted context module
  fixed?
- Is any fact now stated in two files? Is there a paragraph that would lose
  nothing by being deleted?

## Report

Findings ordered by severity, each with:

- **What** is wrong — one sentence.
- **Where** — `path/to/file.php:line`.
- **Why it matters** — the concrete failure, not an abstract principle. If you
  can't describe an input that produces a wrong result, mark it a preference.

Say plainly when a change is good. A review that manufactures findings trains
everyone to ignore reviews.

## Rules

- **Review the diff, not the file.** Pre-existing problems are a separate
  task.
- **Verify claims.** If the task says tests pass, run them.
- **Don't fix while reviewing.** Report; fixing is a separate pass with its
  own diff.
- **Separate blocking findings from suggestions**, explicitly.
