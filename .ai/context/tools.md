# Tool Usage

Which tool for which job, and what the agent may touch. The machine-enforced
half lives in [`../../.claude/settings.json`](../../.claude/settings.json);
this file explains the intent behind it.

## Permission Model

Three tiers, deliberately:

| Tier | Contents | Rationale |
| :--- | :--- | :--- |
| **Allowed, no prompt** | Reading anything in the repo; read-only shell (`rg`, `find`, `jq`, `ls`, `git status/log/diff`); running each app's tests, linters, static analysis, and read-only framework inspection commands | Reversible, no side effects — prompting on them wastes attention on noise |
| **Ask first** | `composer require/update`, `npm install`, any migration, `docker compose up/down/build`, `git add/commit/push/reset/checkout/rebase/stash`, editing a `composer.json`/`package.json`/`docker-compose.yml`/`.env.example` | Each changes durable state — dependencies, schema, containers, history |
| **Denied** | Reading `.env`/`.env.local`/`.env.dev`/`.env.test` in any app, `*.key`/`*.pem`/`auth.json`, `~/.ssh`, `~/.aws`; `rm -rf`; `git push --force`; writing into `vendor/`, `node_modules/`, `storage/framework/`, or `warehouses/var/` | No legitimate need for a live secret or to destroy tracked/generated state |

`.env.example` in every app is intentionally **not** denied — it's where a new
env key gets documented. The agent needs the key *name*
(`JWT_SHARED_SECRET`, `EXCHANGE_RATE_API_URL`, …), never a live value.

## Searching

Read a file when you know which one. Search when you don't — and search by
ontology term first; a domain concept with no hits is not implemented yet,
which is itself a finding.

```bash
rg 'ProductReservation' --type php                    # symbol across warehouses
rg -n 'JWT_SHARED_SECRET' -g '!vendor' -g '!node_modules'
rg -l 'NotifyWarehouseOfPaymentJob'                    # which files, not which lines
find shop-2-back/app -name '*.php' -path '*Jobs*'
jq '.require' shop-2-back/composer.json                # structured files → jq
```

- Prefer `rg` to `grep` — it respects `.gitignore` and skips `vendor/`/`node_modules/` by default.
- Three apps, three namespaces — scope a search to the app's folder once you
  know which one owns the concept (see the concept→code map in
  `architecture.md`), rather than searching the whole monorepo every time.

## Editing

- **Read before you edit.** Always.
- Prefer targeted string replacement over rewriting a file.
- One concern per edit. Don't reformat a file you're also changing.
- Never edit `vendor/`, `node_modules/`, a lockfile by hand (regenerate it),
  or `warehouses/var/` (cache/generated).
- Don't touch two apps in one edit unless the task is explicitly cross-service
  (e.g. the JWT contract, or the warehouse notification payload shape).

## Verifying

```bash
# shop-2-back
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
./vendor/bin/deptrac analyse

# warehouses
php bin/phpunit
./vendor/bin/psalm

# shop-front
ng test
ng lint
```

Run the check. Do not reason about whether it would pass.

## Hard Rules

1. **Never read a live `.env`.** Read `.env.example` instead.
2. **Never run a destructive command without being asked**: `migrate:fresh`,
   `doctrine:database:drop`, `docker compose down -v`, `rm -rf`,
   `git push --force`.
3. **Never commit or push unless explicitly asked.**
4. **Never install a dependency without raising it first** — see the DON'T
   list in `conventions.md`, and `stack.md` for why `shop-2-back` specifically
   is fragile here.
5. **Never write outside the project root.** Scratch work goes in a temp dir.
6. **Report what actually happened.** If a command failed, show the output. A
   skipped step is stated, not omitted.
