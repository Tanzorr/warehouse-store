# План 11 — Спільний JWT для Laravel (Shop) і Symfony (Warehouse)

## Задача / Проблема
**Проблема:** проєкт складається з **двох незалежних бекендів**:
- `shop-2-back` (Laravel) — захищений Sanctum (`auth:sanctum`, `role:admin`)
- `warehouses` (Symfony) — наразі **повністю public** (`access_control: PUBLIC_ACCESS`),
  хоча в ньому вже є `lexik/jwt-authentication-bundle` (^3.1.1) і навіть
  згенерована пара ключів у `config/jwt/`, але нічого не підключено.

Фронтенд (план 10) логіниться один раз, але робить запити в **обидва** API
(`environment.apiUrl` і `environment.warehouseApiUrl` — обидва вже визначені
в [environments-local.ts](../../../shop-front/src/environments/environments-local.ts)).
Потрібен **один токен**, який визнають обидва бекенди — без runtime-залежності
одного сервісу від іншого (зберігаємо ізоляцію bounded contexts).

**Рішення:** Laravel при логіні видає **JWT (HS256, спільний секрет)**.
Обидва бекенди валідують цей JWT **локально**, кожен своїм механізмом:
- Laravel — власний middleware (замінює `auth:sanctum` на API-роутах)
- Symfony — `lexik/jwt-authentication-bundle`, перенастроєний на HS256

---

## Крок 0 — Спільний секрет

Згенерувати один секрет і покласти в `.env` обох проєктів під однаковим ім'ям:

```bash
openssl rand -base64 32
```

```env
# shop-2-back/.env  і  warehouses/.env
JWT_SHARED_SECRET=<той самий рядок у обох файлах>
```

⚠️ Не комітити реальний секрет — тільки `.env.example` з плейсхолдером.
Існуючі `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` / `JWT_PASSPHRASE` (RSA-ключі
lexik) більше не використовуються — їх можна прибрати з `warehouses/.env`.

---

## Частина A — Laravel (`shop-2-back`)

### A1. Додати пакет для видачі/валідації JWT

```bash
composer require firebase/php-jwt
```
(легка бібліотека — повний `tymon/jwt-auth` не потрібен, бо Sanctum
залишається для решти SPA-функціоналу, якщо він використовується деінде;
JWT тут — лише для admin/cross-service запитів).

### A2. Видача токена в `LoginAction`

```php
// app/Actions/LoginAction.php
use Firebase\JWT\JWT;

class LoginAction implements ReadActionInterface
{
    public function handle(QueryInterface $query): array|false
    {
        if (! Auth::attempt($query->get('credentials'))) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $payload = [
            'sub'   => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
            'iat'   => time(),
            'exp'   => time() + 60 * 60 * 8, // 8 годин
        ];

        return [
            'authToken'  => JWT::encode($payload, config('app.jwt_shared_secret'), 'HS256'),
            'loggedUser' => $user,
        ];
    }
}
```

Додати в `config/app.php`:
```php
'jwt_shared_secret' => env('JWT_SHARED_SECRET'),
```

### A3. Middleware для валідації JWT

```php
// app/Http/Middleware/JwtAuthMiddleware.php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $payload = JWT::decode($token, new Key(config('app.jwt_shared_secret'), 'HS256'));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = User::find($payload->sub);
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        Auth::setUser($user); // $request->user() далі працює як завжди
        $request->attributes->set('jwt', $payload);

        return $next($request);
    }
}
```

Зареєструвати alias `jwt` в `bootstrap/app.php` / `Kernel.php`.

### A4. Замінити middleware на роутах

```php
// routes/api.php
Route::middleware('jwt')->group(function () {
    Route::middleware('role:admin')->group(function () {
        // ...існуючі admin-роути без змін (RoleMiddleware далі працює,
        // бо Auth::setUser($user) робить $request->user() доступним)
    });
    // ...
});
```

`RoleMiddleware` ([RoleMiddleware.php](../../../shop-2-back/app/Http/Middleware/RoleMiddleware.php))
не міняється — він читає `$request->user()->role`, а `JwtAuthMiddleware`
вже підставив користувача.

---

## Частина B — Symfony (`warehouses`)

### B1. Перенастроїти lexik на HS256 зі спільним секретом

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(JWT_SHARED_SECRET)%'
    public_key: '%env(JWT_SHARED_SECRET)%'   # для HS256 secret == public
    algorithm:  HS256
    token_ttl:  28800                         # 8 годин, як у Laravel
```

```env
# warehouses/.env
JWT_SHARED_SECRET=<той самий секрет, що в Laravel>
```

### B2. User Provider, що читає роль із claim `role` (без БД users)

Symfony не має таблиці users — рішення про admin-доступ приймається
виключно на основі `role` claim із JWT, виданого Laravel.

```php
// src/Security/JwtUserProvider.php
class JwtClaimUser implements UserInterface
{
    public function __construct(
        private readonly string $email,
        private readonly array $roles,
    ) {}

    public function getRoles(): array { return $this->roles; }
    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
}
```

Lexik дозволяє підмінити "user creation" через
`lexik_jwt_authentication.user_provider` або events
(`jwt_authenticated` listener) — мапимо claim `role: "admin"` →
`ROLE_ADMIN`.

### B3. Firewall + access control

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            lazy: true
            stateless: true
            jwt: ~   # lexik authenticator

    access_control:
        # читання стоку — публічне (або теж під JWT, залежно від вимог)
        - { path: ^/api/warehouse/stock-levels, roles: PUBLIC_ACCESS, methods: [GET] }
        - { path: ^/api/warehouse/reservations, roles: PUBLIC_ACCESS, methods: [GET] }
        # команди (план 05) — лише admin
        - { path: ^/api/warehouse/.*/(adjust|transfer|cancel)$, roles: ROLE_ADMIN }
        - { path: ^/api, roles: PUBLIC_ACCESS }
```

Точні шляхи звірити з фактичними роутами плану 05
([05-warehouse-cqrs-commands.md](./05-warehouse-cqrs-commands.md)).

---

## Частина C — Frontend (доповнення до плану 10)

Інтерсептор ([auth.interceptor.ts](./10-angular-admin-auth.md)) додає
`Authorization: Bearer <jwt>` до запитів, чий URL починається з
`environment.apiUrl` **АБО** `environment.warehouseApiUrl` — обидва базові
URL вже є в конфігурації, нічого нового заводити не треба:

```typescript
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const token = auth.getToken();

  const isApiRequest =
    req.url.startsWith(environment.apiUrl) ||
    req.url.startsWith(environment.warehouseApiUrl);

  const authReq = (token && isApiRequest)
    ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
    : req;

  return next(authReq).pipe(/* ...catchError(401) як в плані 10 */);
};
```

`AuthService` і `adminGuard` з плану 10 **не змінюються** — `authToken`,
який вони зберігають, тепер просто є JWT, а не Sanctum opaque-токеном.

---

## Що перевірити після реалізації

- [ ] `POST /api/login` (Laravel) повертає JWT (3 частини через `.`, декодується на jwt.io)
- [ ] Запит до Laravel admin-роута з цим JWT в `Authorization: Bearer` → 200
- [ ] Той самий JWT в запиті до Symfony `/api/warehouse/...` → 200 (раніше працювало й без токена — переконатись що команди тепер дійсно вимагають ROLE_ADMIN)
- [ ] Запит до Symfony admin-команди (adjust/transfer) **без** токена → 401/403
- [ ] JWT з `role: "user"` → Symfony admin-команди повертають 403, Laravel `role:admin` повертає 401 (як і раніше)
- [ ] Протухлий JWT (`exp` у минулому) → обидва бекенди повертають 401
- [ ] Фронтенд (план 10): один логін → admin-сторінки 07–09 успішно читають і пишуть і в Laravel, і в Symfony

---

## Зв'язок з іншими планами
- **План 10** (Angular Admin Auth) — `AuthService`/interceptor не змінюються
  структурно, лише `Authorization` header тепер летить в обидва base URL.
- **План 05** (Warehouse CQRS Commands) — команди `AdjustStock`,
  `TransferStock`, `CancelReservation` отримують `ROLE_ADMIN` access control.
- **План 06** (Warehouse CQRS Queries) — query-роути можуть залишитись
  `PUBLIC_ACCESS` або теж вимагати JWT (рішення — за потребою проєкту).
