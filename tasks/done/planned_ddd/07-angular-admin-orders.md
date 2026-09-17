# План 07 — Angular Admin: Управління замовленнями

## Задача / Проблема
**Проблема:** зараз Angular має тільки каталог для покупців.
Менеджер не може переглянути замовлення, змінити статус або підтвердити оплату через UI.
Ці операції можливі тільки через прямі API виклики.

**Задача:** створити адмін-секцію в Angular з:
1. Списком замовлень з фільтрацією по статусу.
2. Деталями замовлення (items, адреса, total).
3. Діями: змінити статус, позначити як оплачене.

**Що навчає:** NgRx як CQRS на фронтенді:
- Actions = Commands і Queries
- Effects = виконання async команд
- Selectors = read side (Query side)
- Store = єдиний стан (Write model через commands, Read через selectors)

---

## Файлова структура

```
src/app/admin/
├── admin.routes.ts                  ← lazy-loaded admin routes
├── admin.component.ts               ← shell component з sidebar
├── guards/
│   └── admin.guard.ts               ← захист від неадмінів
├── orders/
│   ├── orders.routes.ts
│   ├── pages/
│   │   ├── order-list/
│   │   │   ├── order-list.component.ts
│   │   │   └── order-list.component.html
│   │   └── order-detail/
│   │       ├── order-detail.component.ts
│   │       └── order-detail.component.html
│   ├── components/
│   │   ├── order-status-badge/
│   │   │   └── order-status-badge.component.ts
│   │   └── order-actions/
│   │       └── order-actions.component.ts
│   └── store/
│       ├── orders.actions.ts
│       ├── orders.effects.ts
│       ├── orders.reducer.ts
│       └── orders.selectors.ts
└── shared/
    ├── models/
    │   ├── admin-order.model.ts
    │   └── order-status.enum.ts
    └── services/
        └── admin-orders.service.ts
```

---

## Кроки реалізації

### Крок 1 — Models (Read side типи)

```typescript
// src/app/admin/shared/models/admin-order.model.ts
export interface AdminOrderItem {
  productId: number;
  productName: string;
  quantity: number;
  price: number;
  subtotal: number;
}

export interface AdminOrder {
  id: number;
  userId: number;
  userName: string;
  status: OrderStatus;
  paymentStatus: 'pending' | 'paid';
  totalPrice: number;
  shippingAddress: string;
  items: AdminOrderItem[];
  createdAt: string;
  updatedAt: string;
}

export interface AdminOrdersResponse {
  data: AdminOrder[];
  total: number;
  currentPage: number;
  perPage: number;
}

// src/app/admin/shared/models/order-status.enum.ts
export enum OrderStatus {
  PENDING   = 'pending',
  CONFIRMED = 'confirmed',
  SHIPPED   = 'shipped',
  DELIVERED = 'delivered',
  CANCELLED = 'cancelled',
}
```

### Крок 2 — HTTP Service

```typescript
// src/app/admin/shared/services/admin-orders.service.ts
@Injectable({ providedIn: 'root' })
export class AdminOrdersService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = '/api/orders';

  // QUERY: отримати список
  getOrders(params: {
    status?: string;
    page?: number;
    perPage?: number;
  }): Observable<AdminOrdersResponse> {
    return this.http.get<AdminOrdersResponse>(this.baseUrl, { params });
  }

  // QUERY: деталі
  getOrderById(id: number): Observable<AdminOrder> {
    return this.http.get<AdminOrder>(`${this.baseUrl}/${id}`);
  }

  // COMMAND: змінити статус
  updateStatus(id: number, status: string): Observable<void> {
    return this.http.patch<void>(`${this.baseUrl}/${id}`, { status });
  }

  // COMMAND: оплатити
  payOrder(id: number): Observable<void> {
    return this.http.post<void>(`${this.baseUrl}/${id}/pay`, {});
  }
}
```

### Крок 3 — NgRx Actions (Commands і Queries)

```typescript
// src/app/admin/orders/store/orders.actions.ts
import { createActionGroup, emptyProps, props } from '@ngrx/store';

// QUERY actions — завантаження даних
export const OrdersQueryActions = createActionGroup({
  source: 'Admin Orders Query',
  events: {
    'Load Orders':          props<{ status?: string; page?: number }>(),
    'Load Orders Success':  props<{ response: AdminOrdersResponse }>(),
    'Load Orders Failure':  props<{ error: string }>(),
    'Load Order':           props<{ id: number }>(),
    'Load Order Success':   props<{ order: AdminOrder }>(),
    'Load Order Failure':   props<{ error: string }>(),
  }
});

// COMMAND actions — зміна стану
export const OrdersCommandActions = createActionGroup({
  source: 'Admin Orders Command',
  events: {
    'Update Status':          props<{ id: number; status: string }>(),
    'Update Status Success':  props<{ id: number; status: string }>(),
    'Update Status Failure':  props<{ error: string }>(),
    'Pay Order':              props<{ id: number }>(),
    'Pay Order Success':      props<{ id: number }>(),
    'Pay Order Failure':      props<{ error: string }>(),
  }
});
```

**Навіщо два окремих ActionGroup:**
Явне розділення Query actions (читання) і Command actions (зміна) — це CQRS у store.
Легко зрозуміти що змінює стан, а що тільки читає.

### Крок 4 — Reducer (State)

```typescript
// src/app/admin/orders/store/orders.reducer.ts
export interface AdminOrdersState {
  orders:      AdminOrder[];
  total:       number;
  currentPage: number;
  perPage:     number;
  selectedOrder: AdminOrder | null;
  loading:     boolean;
  actionLoading: Record<number, boolean>; // loading per order id
  error:       string | null;
  statusFilter: string | null;
}

const initialState: AdminOrdersState = {
  orders:        [],
  total:         0,
  currentPage:   1,
  perPage:       15,
  selectedOrder: null,
  loading:       false,
  actionLoading: {},
  error:         null,
  statusFilter:  null,
};

export const adminOrdersReducer = createReducer(
  initialState,

  // Queries
  on(OrdersQueryActions.loadOrders, state => ({ ...state, loading: true })),
  on(OrdersQueryActions.loadOrdersSuccess, (state, { response }) => ({
    ...state,
    loading:     false,
    orders:      response.data,
    total:       response.total,
    currentPage: response.currentPage,
  })),
  on(OrdersQueryActions.loadOrdersFailure, (state, { error }) => ({
    ...state, loading: false, error,
  })),

  on(OrdersQueryActions.loadOrderSuccess, (state, { order }) => ({
    ...state, selectedOrder: order,
  })),

  // Commands — оновлюємо локальний стан оптимістично
  on(OrdersCommandActions.updateStatus, (state, { id }) => ({
    ...state,
    actionLoading: { ...state.actionLoading, [id]: true },
  })),
  on(OrdersCommandActions.updateStatusSuccess, (state, { id, status }) => ({
    ...state,
    actionLoading: { ...state.actionLoading, [id]: false },
    orders: state.orders.map(o =>
      o.id === id ? { ...o, status: status as OrderStatus } : o
    ),
    selectedOrder: state.selectedOrder?.id === id
      ? { ...state.selectedOrder, status: status as OrderStatus }
      : state.selectedOrder,
  })),

  on(OrdersCommandActions.payOrderSuccess, (state, { id }) => ({
    ...state,
    actionLoading: { ...state.actionLoading, [id]: false },
    orders: state.orders.map(o =>
      o.id === id ? { ...o, paymentStatus: 'paid' as const } : o
    ),
  })),
);
```

### Крок 5 — Effects (async side effects)

```typescript
// src/app/admin/orders/store/orders.effects.ts
@Injectable()
export class AdminOrdersEffects {
  private readonly actions$ = inject(Actions);
  private readonly service  = inject(AdminOrdersService);

  // QUERY effect
  loadOrders$ = createEffect(() =>
    this.actions$.pipe(
      ofType(OrdersQueryActions.loadOrders),
      switchMap(({ status, page }) =>
        this.service.getOrders({ status, page }).pipe(
          map(response  => OrdersQueryActions.loadOrdersSuccess({ response })),
          catchError(err => of(OrdersQueryActions.loadOrdersFailure({
            error: err.message
          })))
        )
      )
    )
  );

  // COMMAND effect: updateStatus
  updateStatus$ = createEffect(() =>
    this.actions$.pipe(
      ofType(OrdersCommandActions.updateStatus),
      concatMap(({ id, status }) =>      // concatMap — команди виконуються по черзі
        this.service.updateStatus(id, status).pipe(
          map(() => OrdersCommandActions.updateStatusSuccess({ id, status })),
          catchError(err => of(OrdersCommandActions.updateStatusFailure({
            error: err.message
          })))
        )
      )
    )
  );

  // COMMAND effect: payOrder → після успіху перезавантажити замовлення
  payOrder$ = createEffect(() =>
    this.actions$.pipe(
      ofType(OrdersCommandActions.payOrder),
      concatMap(({ id }) =>
        this.service.payOrder(id).pipe(
          map(() => OrdersCommandActions.payOrderSuccess({ id })),
          catchError(err => of(OrdersCommandActions.payOrderFailure({
            error: err.message
          })))
        )
      )
    )
  );

  // Після успішної оплати — перезавантажити деталі замовлення
  reloadAfterPay$ = createEffect(() =>
    this.actions$.pipe(
      ofType(OrdersCommandActions.payOrderSuccess),
      map(({ id }) => OrdersQueryActions.loadOrder({ id }))
    )
  );
}
```

**Навіщо `concatMap` для команд, `switchMap` для queries:**
- `switchMap` для queries: якщо прийшов новий запит — скасовуємо старий (остання фільтрація важлива).
- `concatMap` для команд: команди виконуються послідовно — не можна скасувати "оплатити замовлення".

### Крок 6 — Selectors (Read side)

```typescript
// src/app/admin/orders/store/orders.selectors.ts
const selectFeature = createFeatureSelector<AdminOrdersState>('adminOrders');

export const selectOrders        = createSelector(selectFeature, s => s.orders);
export const selectTotal         = createSelector(selectFeature, s => s.total);
export const selectLoading       = createSelector(selectFeature, s => s.loading);
export const selectSelectedOrder = createSelector(selectFeature, s => s.selectedOrder);
export const selectStatusFilter  = createSelector(selectFeature, s => s.statusFilter);

export const selectOrderById = (id: number) =>
  createSelector(selectOrders, orders => orders.find(o => o.id === id));

export const selectIsActionLoading = (id: number) =>
  createSelector(selectFeature, s => s.actionLoading[id] ?? false);

// Агрегований selector: статистика
export const selectOrderStats = createSelector(
  selectOrders,
  orders => ({
    total:     orders.length,
    pending:   orders.filter(o => o.status === OrderStatus.PENDING).length,
    confirmed: orders.filter(o => o.status === OrderStatus.CONFIRMED).length,
    unpaid:    orders.filter(o => o.paymentStatus === 'pending').length,
  })
);
```

### Крок 7 — Order List Component

```typescript
// src/app/admin/orders/pages/order-list/order-list.component.ts
@Component({
  selector: 'app-admin-order-list',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="admin-orders">
      <div class="filters">
        <select (change)="filterByStatus($event)">
          <option value="">Всі статуси</option>
          <option *ngFor="let s of statuses" [value]="s">{{ s }}</option>
        </select>
      </div>

      <div *ngIf="loading$ | async" class="spinner">Завантаження...</div>

      <table *ngIf="!(loading$ | async)">
        <thead>
          <tr>
            <th>#</th><th>Клієнт</th><th>Статус</th>
            <th>Оплата</th><th>Сума</th><th>Дата</th><th>Дії</th>
          </tr>
        </thead>
        <tbody>
          <tr *ngFor="let order of orders$ | async">
            <td>{{ order.id }}</td>
            <td>{{ order.userName }}</td>
            <td>
              <app-order-status-badge [status]="order.status" />
            </td>
            <td>{{ order.paymentStatus }}</td>
            <td>{{ order.totalPrice | currency }}</td>
            <td>{{ order.createdAt | date:'short' }}</td>
            <td>
              <app-order-actions
                [order]="order"
                [loading]="(isActionLoading$(order.id) | async)!"
                (statusChange)="onStatusChange(order.id, $event)"
                (pay)="onPay(order.id)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  `
})
export class OrderListComponent implements OnInit {
  private store = inject(Store);

  orders$  = this.store.select(selectOrders);
  loading$ = this.store.select(selectLoading);
  stats$   = this.store.select(selectOrderStats);

  statuses = Object.values(OrderStatus);

  isActionLoading$ = (id: number) =>
    this.store.select(selectIsActionLoading(id));

  ngOnInit(): void {
    this.store.dispatch(OrdersQueryActions.loadOrders({}));
  }

  filterByStatus(event: Event): void {
    const status = (event.target as HTMLSelectElement).value || undefined;
    this.store.dispatch(OrdersQueryActions.loadOrders({ status }));
  }

  onStatusChange(id: number, status: string): void {
    this.store.dispatch(OrdersCommandActions.updateStatus({ id, status }));
  }

  onPay(id: number): void {
    this.store.dispatch(OrdersCommandActions.payOrder({ id }));
  }
}
```

---

## Що перевірити після реалізації

- [ ] `/admin/orders` → список замовлень завантажується
- [ ] Фільтр по статусу → store диспетчить `loadOrders({ status })`
- [ ] Кнопка "Оплатити" → `payOrder` effect → статус оновлюється в таблиці
- [ ] Зміна статусу → оптимістичне оновлення в UI
- [ ] Неавторизований доступ → `AdminGuard` редіректить на login
- [ ] `ChangeDetectionStrategy.OnPush` — немає зайвих re-render-ів

---

## Зв'язок з іншими планами
- **План 01** (Laravel Command Bus) — `payOrder` викликає `PayOrderCommand`
- **План 08** (Angular Admin Products) — той самий admin layout
- **План 09** (Angular Admin Warehouse) — той самий NgRx патерн
