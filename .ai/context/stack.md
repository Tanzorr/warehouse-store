# Stack

Versions, services, env keys, and the commands that actually run each app.
Describes intent as well as fact — verify before assuming a command exists.

## `shop-2-back` — Laravel shop API

| | |
| :--- | :--- |
| Runtime | PHP `^8.2`, Laravel `^11.9` |
| Auth | `firebase/php-jwt` + custom `JwtAuthMiddleware` (alias `jwt`) — **not** `laravel/sanctum` for API routes, even though Sanctum is installed |
| DB | MySQL 8.0, `DB_CONNECTION=mysql`, port `3307` on host via Sail |
| Queue | `QUEUE_CONNECTION=database` |
| Cache/Session | `database` driver |
| Search | Meilisearch (`SCOUT_DRIVER=meilisearch`) |
| Docs | `zircote/swagger-php` + `darkaonline/l5-swagger`; export/import via `maatwebsite/excel`; PDF via `barryvdh/laravel-dompdf` |
| Static analysis | PHPStan (Larastan) level **4**, Psalm errorLevel 3, Deptrac, Laravel Pint |
| Local runtime | Laravel Sail — `docker-compose.yml` brings up `laravel.test`, `mysql`, `redis`, `meilisearch`, `mailpit`, `selenium` |

**Env keys that matter beyond framework defaults** (`.env.example`):
`JWT_SHARED_SECRET` (must match `warehouses/.env`, generate with
`openssl rand -base64 32`), `AWS_*` (S3, currently blank), `MEILISEARCH_HOST`.

**Composer is fragile here** — see [[composer-install-blocked-shop-2-back]] in
project memory: `composer require/update` fails because `vimeo/psalm`'s
resolved version wants PHP ~8.3.16 against a local 8.3.6, and several vendor
packages are source-installed with uncommitted changes that abort
`VcsDownloader`. Adding a dependency may require the manual
`vendor/<pkg>` + `installed.json` + `dump-autoload --no-scripts` workaround —
raise it before attempting, per the DON'T-add-a-dependency rule.

**Commands:**
```bash
php artisan test
./vendor/bin/pint            # format
./vendor/bin/pint --test     # check only
./vendor/bin/phpstan analyse
./vendor/bin/psalm
./vendor/bin/deptrac analyse
php artisan l5-swagger:generate   # after touching Swagger annotations
```

## `warehouses` — Symfony warehouse microservice

| | |
| :--- | :--- |
| Runtime | PHP `>=8.2`, Symfony `7.2.*`, API Platform `>=4.1.18` |
| Auth | `lexik/jwt-authentication-bundle` configured for **HS256** with the same shared secret — no local user table, identity is claims-only (`JwtClaimUser`) |
| DB | MySQL 8.0 via Doctrine ORM `^3.5`, `DATABASE_URL` |
| Async | `symfony/messenger` + `symfony/doctrine-messenger` present |
| State machine | `symfony/workflow` — `config/packages/workflow.yaml` drives `ProductReservation` status |
| Static analysis | Psalm errorLevel **3** |
| Local runtime | Plain `docker-compose.yml` — `web` (PHP built-in server on :80, mapped to host `8088`), `db` (MySQL, host `3306`), `phpmyadmin` |

**Env keys:** `JWT_SHARED_SECRET` (must match `shop-2-back/.env`),
`CORS_ALLOW_ORIGIN` (regex allowing any localhost port — Angular dev server on
`:4200`, gateway on `:8088`), `EXCHANGE_RATE_API_URL` / `SUPPORTED_CURRENCIES`
(external rate lookup, scope unclear — check before assuming it's load-bearing).

**Commands:**
```bash
php bin/phpunit
./vendor/bin/psalm
php bin/console doctrine:migrations:diff     # after entity changes
php bin/console doctrine:migrations:migrate  # ask before running
```

## `shop-front` — Angular admin + storefront

| | |
| :--- | :--- |
| Runtime | Angular `^20.0.0`, zoneless change detection |
| State | NgRx `^19.2.1` (`store`, `effects`, `router-store`, `store-devtools`) |
| UI | Bootstrap 5 + `ngx-bootstrap` |
| Test | Karma + Jasmine |

**Talks to both backends** — `AuthService` + `authInterceptor` attach the
shared JWT to both `apiUrl` (shop-2-back) and `warehouseApiUrl` (warehouses);
`adminGuard` checks token + role.

**Commands:**
```bash
ng test
ng lint
ng build
```

## Cross-cutting

- **`JWT_SHARED_SECRET`** is the one piece of config three apps must agree on.
  A mismatch fails auth silently on one side only — check both `.env` files
  first when JWT validation breaks unexpectedly.
- No shared database, no shared vendor/node_modules — each app's dependency
  file is independent; never edit two apps' lockfiles in one change unless the
  task is explicitly about the JWT contract or the shop↔warehouse API shape.
- `warehouses/openapi.yaml` documents that service's HTTP contract — check it
  before assuming a warehouse endpoint's shape.
