# План 10 — Angular Admin: Авторизація (Login + Auth Guard)

## Задача / Проблема
**Проблема:** адмінка (`/admin/*`, плани 07–09) захищена `adminGuard`, який читає
`user` з `localStorage`, але в проєкті **немає сторінки логіну** — немає способу
покласти туди валідного користувача й токен. Без цього неможливо протестувати
жоден з admin-розділів через справжній API (запити впадуть з 401, бо
`auth:sanctum` на бекенді вимагає Bearer-токен).

**Задача:** зробити мінімальний, але робочий шар авторизації:
1. Сторінка `/login` з формою email/password.
2. `AuthService` — виклик `POST /api/login`, збереження токена + юзера, `logout()`.
3. HTTP Interceptor — додає `Authorization: Bearer <token>` до всіх запитів.
4. Оновлений `adminGuard` — перевіряє і токен, і роль; редірект на `/login`
   замість `/home`.
5. Кнопка "Вийти" в admin sidebar.

**Що навчає:** мінімальний auth-шар як інфраструктурний (cross-cutting)
concern — interceptor і guard як "AOP" над усіма admin-командами/запитами.

---

## Backend (вже готовий, нічого міняти не треба)
- `POST /api/login` → `{ email, password }` → `{ authToken, loggedUser }`
  ([AuthController.php](../../../shop-2-back/app/Http/Controllers/AuthController.php),
  [LoginAction.php](../../../shop-2-back/app/Actions/LoginAction.php))
- `POST /api/logout` → видаляє поточний Sanctum-токен
- `loggedUser.role` — `'admin' | 'user'` ([User model](../../../shop-2-back/app/Models))

---

## Файлова структура

```
src/app/
├── auth/
│   ├── auth.routes.ts
│   ├── pages/
│   │   └── login/
│   │       ├── login.component.ts
│   │       └── login.component.html
│   ├── services/
│   │   └── auth.service.ts
│   ├── interceptors/
│   │   └── auth.interceptor.ts
│   └── models/
│       └── auth.model.ts
├── admin/
│   ├── guards/
│   │   └── admin.guard.ts          ← оновити (перевірка токена + role)
│   └── admin.component.ts          ← додати кнопку "Вийти"
├── app.routes.ts                   ← додати path: 'login'
└── app.config.ts                   ← зареєструвати authInterceptor
```

---

## Кроки реалізації

### Крок 1 — Models

```typescript
// src/app/auth/models/auth.model.ts
export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  authToken: string;
  loggedUser: User; // User з src/app/models/user.ts (id, name, email, role, ...)
}
```

### Крок 2 — AuthService

```typescript
// src/app/auth/services/auth.service.ts
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http    = inject(HttpClient);
  private readonly storage = inject(LocalStorageService);
  private readonly router  = inject(Router);

  private readonly TOKEN_KEY = 'authToken';
  private readonly USER_KEY  = 'user';

  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http.post<LoginResponse>('/api/login', credentials).pipe(
      tap(({ authToken, loggedUser }) => {
        this.storage.set(this.TOKEN_KEY, authToken);
        this.storage.set(this.USER_KEY, JSON.stringify(loggedUser));
      })
    );
  }

  logout(): void {
    this.http.post('/api/logout', {}).subscribe({
      complete: () => this.clearSession(),
      error:    () => this.clearSession(), // токен вже невалідний — все одно чистимо
    });
  }

  private clearSession(): void {
    this.storage.delete(this.TOKEN_KEY);
    this.storage.delete(this.USER_KEY);
    this.router.navigate(['/login']);
  }

  getToken(): string | null {
    return this.storage.get(this.TOKEN_KEY);
  }

  getUser(): User | null {
    const raw = this.storage.get(this.USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  isAdmin(): boolean {
    return this.getUser()?.role === 'admin';
  }
}
```

**Навіщо `tap` а не окремий reducer/store:**
Авторизація — наскрізна (cross-cutting) інфраструктурна задача, а не доменна
команда/запит. NgRx-стор тут не потрібен — досить сервісу + localStorage,
як це вже зроблено для `adminGuard`.

### Крок 3 — HTTP Interceptor

```typescript
// src/app/auth/interceptors/auth.interceptor.ts
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth  = inject(AuthService);
  const token = auth.getToken();

  // Токен летить в обидва бекенди — Laravel (apiUrl) і Symfony (warehouseApiUrl).
  // Деталі: план 11 (Shared JWT).
  const isApiRequest =
    req.url.startsWith(environment.apiUrl) ||
    req.url.startsWith(environment.warehouseApiUrl);

  const authReq = (token && isApiRequest)
    ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
    : req;

  return next(authReq).pipe(
    catchError((err: HttpErrorResponse) => {
      if (err.status === 401) {
        auth.logout(); // токен невалідний/протух → на /login
      }
      return throwError(() => err);
    })
  );
};
```

Реєстрація в `app.config.ts`:

```typescript
provideHttpClient(withInterceptors([authInterceptor])),
```

### Крок 4 — Login Component (мінімальний)

```typescript
// src/app/auth/pages/login/login.component.ts
@Component({
  selector: 'app-login',
  standalone: true,
  imports: [ReactiveFormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <form class="login-form" [formGroup]="form" (ngSubmit)="onSubmit()">
      <h2>Вхід</h2>
      <input type="email" formControlName="email" placeholder="Email" />
      <input type="password" formControlName="password" placeholder="Пароль" />
      <div *ngIf="error()" class="error">{{ error() }}</div>
      <button type="submit" [disabled]="form.invalid || loading()">Увійти</button>
    </form>
  `
})
export class LoginComponent {
  private readonly auth   = inject(AuthService);
  private readonly router = inject(Router);
  private readonly fb     = inject(FormBuilder);

  loading = signal(false);
  error   = signal<string | null>(null);

  form = this.fb.nonNullable.group({
    email:    ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  onSubmit(): void {
    if (this.form.invalid) return;

    this.loading.set(true);
    this.error.set(null);

    this.auth.login(this.form.getRawValue()).subscribe({
      next: ({ loggedUser }) => {
        this.loading.set(false);
        this.router.navigate([loggedUser.role === 'admin' ? '/admin' : '/home']);
      },
      error: () => {
        this.loading.set(false);
        this.error.set('Невірний email або пароль');
      },
    });
  }
}
```

```typescript
// src/app/auth/auth.routes.ts
export const routes: Routes = [
  { path: '', component: LoginComponent },
];
```

### Крок 5 — Реєстрація роута

```typescript
// src/app/app.routes.ts
{
  path: 'login',
  loadChildren: () => import('./auth/auth.routes').then(m => m.routes),
},
```

### Крок 6 — Оновити adminGuard

```typescript
// src/app/admin/guards/admin.guard.ts
export const adminGuard: CanActivateFn = () => {
  const auth   = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) {
    return router.createUrlTree(['/login']);
  }

  if (!auth.isAdmin()) {
    return router.createUrlTree(['/home']);
  }

  return true;
};
```

### Крок 7 — Кнопка "Вийти" в admin sidebar

```typescript
// src/app/admin/admin.component.ts
export class AdminComponent {
  private readonly auth = inject(AuthService);

  navLinks = [
    { label: 'Orders', url: '/admin/orders' },
    { label: 'Products', url: '/admin/products' },
    { label: 'Categories', url: '/admin/categories' },
    { label: 'Warehouse', url: '/admin/warehouse' },
  ];

  logout(): void {
    this.auth.logout();
  }
}
```

```html
<!-- admin.component.html: додати в кінець sidebar -->
<button (click)="logout()">Вийти</button>
```

---

## Що перевірити після реалізації

- [ ] `/login` → форма відображається, валідація email/required працює
- [ ] Невірні credentials → показується "Невірний email або пароль"
- [ ] Успішний логін адміна → редірект на `/admin`, в localStorage є `authToken` і `user`
- [ ] Успішний логін звичайного юзера → редірект на `/home` (не на `/admin`)
- [ ] Запити з admin-сторінок (07–09) містять заголовок `Authorization: Bearer ...`
      і повертають 200 (а не 401)
- [ ] Прямий перехід на `/admin/*` без логіну → редірект на `/login`
- [ ] Логін звичайним юзером і спроба зайти на `/admin/*` → редірект на `/home`
- [ ] Кнопка "Вийти" → токен/юзер видаляються з localStorage, редірект на `/login`
- [ ] 401 від API (протухлий токен) → автоматичний logout + редірект на `/login`

---

## Зв'язок з іншими планами
- **Плани 07–09** (Angular Admin Orders/Products/Warehouse) — без цього плану
  їхні API-запити не проходять автентифікацію (401), тому план 10 є
  передумовою для **реальної** перевірки 07–09, хоча технічно не залежить
  від їх коду.
- **План 11** (Shared JWT) — `authToken`, який зберігає `AuthService`, видається
  Laravel у вигляді JWT і визнається **обома** бекендами (Laravel + Symfony).
  Без плану 11 інтерсептор все одно відправляє токен у Symfony, але Symfony
  його ігнорує (роути public) — для warehouse-частини адмінки (план 09)
  це працюватиме, але без реального контролю доступу.
- Використовує існуючий `LocalStorageService` ([local-storage.service.ts](../../../shop-front/src/app/services/storage/local-storage.service.ts))
  і `User` модель ([user.ts](../../../shop-front/src/app/models/user.ts)).
