# План 09 — Angular Admin: Перегляд та управління складом

## Задача / Проблема
**Проблема:** немає UI для управління складом з боку Angular-адміна.
Менеджер не може:
- Побачити поточний stock по складах.
- Вручну скоригувати кількість товару.
- Переглянути активні резервування та скасувати їх.
- Побачити аудит-лог змін стоку.

**Задача:** побудувати warehouse section в Angular admin що:
1. Відображає read-модель з Symfony Query handlers (план 06).
2. Відправляє команди до Symfony Command handlers (план 05).
3. Демонструє CQRS cross-service: Angular → Symfony read/write.

**Що навчає:**
- Bounded Context на практиці: Angular admin взаємодіє з двома різними
  мікросервісами (Laravel для shop, Symfony для warehouse) через різні API.
- Event-driven оновлення: після команди (AdjustStock) — перезавантажити query.

---

## Файлова структура

```
src/app/admin/warehouse/
├── warehouse.routes.ts
├── pages/
│   ├── stock-levels/
│   │   ├── stock-levels.component.ts     ← головна сторінка складу
│   │   └── stock-levels.component.html
│   ├── reservations/
│   │   ├── reservations.component.ts     ← таблиця резервувань
│   │   └── reservations.component.html
│   └── transactions/
│       ├── transactions.component.ts     ← аудит лог
│       └── transactions.component.html
├── components/
│   ├── adjust-stock-modal/
│   │   └── adjust-stock-modal.component.ts  ← форма коригування стоку
│   ├── transfer-stock-modal/
│   │   └── transfer-stock-modal.component.ts
│   ├── stock-level-row/
│   │   └── stock-level-row.component.ts  ← рядок в таблиці стоку
│   └── low-stock-badge/
│       └── low-stock-badge.component.ts
└── store/
    ├── warehouse.actions.ts
    ├── warehouse.effects.ts
    ├── warehouse.reducer.ts
    └── warehouse.selectors.ts
```

---

## Кроки реалізації

### Крок 1 — Models (відповідають Read Model DTO з плану 06)

```typescript
// src/app/admin/shared/models/warehouse.model.ts

// Відповідає StockLevelItem з Symfony
export interface StockLevel {
  productId:      number;
  productName:    string;
  sku:            string;
  categoryName:   string;
  warehouseId:    number;
  warehouseName:  string;
  totalStock:     number;
  reservedStock:  number;
  availableStock: number;
  isLowStock:     boolean;
}

// Відповідає ReservationItem з Symfony
export interface WarehouseReservation {
  id:          number;
  status:      'pending' | 'committed' | 'canceled';
  comment:     string;
  reservedAt:  string;
  releasedAt:  string | null;
  totalItems:  number;
  items: {
    productId: number;
    name:      string;
    sku:       string;
    amount:    number;
  }[];
}

// Відповідає TransactionItem з Symfony
export interface InventoryTransaction {
  id:              number;
  productName:     string;
  warehouseName:   string;
  delta:           number;   // + або -
  reason:          string;
  performedByName: string;
  createdAt:       string;
}

// Payload для команд (відповідають Command DTO з плану 05)
export interface AdjustStockPayload {
  productId:   number;
  warehouseId: number;
  delta:       number;
  reason:      string;
}

export interface TransferStockPayload {
  productId:       number;
  fromWarehouseId: number;
  toWarehouseId:   number;
  amount:          number;
  reason:          string;
}
```

### Крок 2 — HTTP Service (читає Symfony warehouse API)

```typescript
// src/app/admin/shared/services/warehouse.service.ts
@Injectable({ providedIn: 'root' })
export class WarehouseService {
  private readonly http       = inject(HttpClient);
  private readonly warehouseUrl = environment.warehouseApiUrl; // http://localhost:8001/api

  // QUERIES (план 06)
  getStockLevels(params: {
    warehouseId?: number;
    categoryId?:  number;
    search?:      string;
    lowStockOnly?: boolean;
  }): Observable<StockLevel[]> {
    return this.http.get<StockLevel[]>(`${this.warehouseUrl}/warehouse/stock`, { params });
  }

  getReservations(params: {
    status?:    string;
    productId?: number;
    page?:      number;
  }): Observable<{ items: WarehouseReservation[]; total: number }> {
    return this.http.get(`${this.warehouseUrl}/warehouse/reservations`, { params });
  }

  getTransactions(params: {
    productId?:   number;
    warehouseId?: number;
    page?:        number;
  }): Observable<InventoryTransaction[]> {
    return this.http.get<InventoryTransaction[]>(
      `${this.warehouseUrl}/warehouse/stock/transactions`, { params }
    );
  }

  // COMMANDS (план 05)
  adjustStock(payload: AdjustStockPayload): Observable<void> {
    return this.http.post<void>(`${this.warehouseUrl}/warehouse/stock/adjust`, payload);
  }

  transferStock(payload: TransferStockPayload): Observable<void> {
    return this.http.post<void>(`${this.warehouseUrl}/warehouse/stock/transfer`, payload);
  }

  cancelReservation(id: number, reason: string): Observable<void> {
    return this.http.post<void>(
      `${this.warehouseUrl}/warehouse/reservations/${id}/cancel`,
      { reason }
    );
  }
}
```

### Крок 3 — NgRx Actions

```typescript
// src/app/admin/warehouse/store/warehouse.actions.ts
export const WarehouseQueryActions = createActionGroup({
  source: 'Warehouse Query',
  events: {
    'Load Stock Levels':          props<{ warehouseId?: number; search?: string; lowStockOnly?: boolean }>(),
    'Load Stock Levels Success':  props<{ items: StockLevel[] }>(),
    'Load Stock Levels Failure':  props<{ error: string }>(),

    'Load Reservations':          props<{ status?: string; page?: number }>(),
    'Load Reservations Success':  props<{ items: WarehouseReservation[]; total: number }>(),
    'Load Reservations Failure':  props<{ error: string }>(),

    'Load Transactions':          props<{ productId?: number; warehouseId?: number }>(),
    'Load Transactions Success':  props<{ items: InventoryTransaction[] }>(),
    'Load Transactions Failure':  props<{ error: string }>(),
  }
});

export const WarehouseCommandActions = createActionGroup({
  source: 'Warehouse Command',
  events: {
    'Adjust Stock':           props<{ payload: AdjustStockPayload }>(),
    'Adjust Stock Success':   emptyProps(),
    'Adjust Stock Failure':   props<{ error: string }>(),

    'Transfer Stock':          props<{ payload: TransferStockPayload }>(),
    'Transfer Stock Success':  emptyProps(),
    'Transfer Stock Failure':  props<{ error: string }>(),

    'Cancel Reservation':          props<{ id: number; reason: string }>(),
    'Cancel Reservation Success':  props<{ id: number }>(),
    'Cancel Reservation Failure':  props<{ error: string }>(),
  }
});
```

### Крок 4 — Effects з cross-service refresh

**Ключовий момент CQRS:** після успішної команди — перезавантажуємо query.

```typescript
// src/app/admin/warehouse/store/warehouse.effects.ts
@Injectable()
export class WarehouseEffects {
  private actions$ = inject(Actions);
  private service  = inject(WarehouseService);
  private store    = inject(Store);

  // QUERIES
  loadStockLevels$ = createEffect(() =>
    this.actions$.pipe(
      ofType(WarehouseQueryActions.loadStockLevels),
      switchMap(params =>
        this.service.getStockLevels(params).pipe(
          map(items   => WarehouseQueryActions.loadStockLevelsSuccess({ items })),
          catchError(err => of(WarehouseQueryActions.loadStockLevelsFailure({ error: err.message })))
        )
      )
    )
  );

  // COMMAND: adjustStock
  adjustStock$ = createEffect(() =>
    this.actions$.pipe(
      ofType(WarehouseCommandActions.adjustStock),
      concatMap(({ payload }) =>
        this.service.adjustStock(payload).pipe(
          map(() => WarehouseCommandActions.adjustStockSuccess()),
          catchError(err => of(WarehouseCommandActions.adjustStockFailure({
            error: err.error?.message ?? err.message
          })))
        )
      )
    )
  );

  // Після успішного AdjustStock → перезавантажити stock levels
  // Це CQRS цикл: Command успішно виконано → Query оновлюється
  refreshStockAfterAdjust$ = createEffect(() =>
    this.actions$.pipe(
      ofType(
        WarehouseCommandActions.adjustStockSuccess,
        WarehouseCommandActions.transferStockSuccess,
      ),
      map(() => WarehouseQueryActions.loadStockLevels({}))
    )
  );

  // Після cancelReservation → оновити список резервувань
  refreshAfterCancel$ = createEffect(() =>
    this.actions$.pipe(
      ofType(WarehouseCommandActions.cancelReservationSuccess),
      map(() => WarehouseQueryActions.loadReservations({}))
    )
  );
}
```

### Крок 5 — Stock Levels Component (головна сторінка складу)

```typescript
// src/app/admin/warehouse/pages/stock-levels/stock-levels.component.ts
@Component({
  selector: 'app-stock-levels',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="warehouse-header">
      <h2>Управління складом</h2>
      <div class="filters">
        <input
          type="text"
          placeholder="Пошук по назві або SKU..."
          (input)="onSearch($event)"
        />
        <label>
          <input type="checkbox" (change)="onLowStockToggle($event)" />
          Тільки малий залишок
        </label>
      </div>
    </div>

    <div *ngIf="loading$ | async" class="spinner">Завантаження...</div>

    <table *ngIf="!(loading$ | async)" class="table">
      <thead>
        <tr>
          <th>Товар</th><th>SKU</th><th>Склад</th>
          <th>Всього</th><th>Зарезервовано</th><th>Доступно</th>
          <th>Дії</th>
        </tr>
      </thead>
      <tbody>
        <tr *ngFor="let item of stockLevels$ | async"
            [class.low-stock]="item.isLowStock">
          <td>{{ item.productName }}</td>
          <td><code>{{ item.sku }}</code></td>
          <td>{{ item.warehouseName }}</td>
          <td>{{ item.totalStock }}</td>
          <td>{{ item.reservedStock }}</td>
          <td>
            <span [class.text-danger]="item.isLowStock">
              {{ item.availableStock }}
            </span>
            <app-low-stock-badge *ngIf="item.isLowStock" />
          </td>
          <td>
            <button (click)="openAdjustModal(item)">Коригувати</button>
            <button (click)="openTransferModal(item)">Перемістити</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal для коригування стоку -->
    <app-adjust-stock-modal
      *ngIf="adjustingItem"
      [item]="adjustingItem"
      (confirm)="onAdjustConfirm($event)"
      (cancel)="adjustingItem = null"
    />
  `
})
export class StockLevelsComponent implements OnInit {
  private store = inject(Store);

  stockLevels$ = this.store.select(selectStockLevels);
  loading$     = this.store.select(selectWarehouseLoading);
  adjustingItem: StockLevel | null = null;

  ngOnInit(): void {
    this.store.dispatch(WarehouseQueryActions.loadStockLevels({}));
  }

  onSearch(event: Event): void {
    const search = (event.target as HTMLInputElement).value;
    this.store.dispatch(WarehouseQueryActions.loadStockLevels({ search }));
  }

  onLowStockToggle(event: Event): void {
    const lowStockOnly = (event.target as HTMLInputElement).checked;
    this.store.dispatch(WarehouseQueryActions.loadStockLevels({ lowStockOnly }));
  }

  openAdjustModal(item: StockLevel): void {
    this.adjustingItem = item;
  }

  onAdjustConfirm(payload: AdjustStockPayload): void {
    this.store.dispatch(WarehouseCommandActions.adjustStock({ payload }));
    this.adjustingItem = null;
    // Effect автоматично перезавантажить stock levels після успіху
  }
}
```

### Крок 6 — AdjustStock Modal Component

```typescript
// src/app/admin/warehouse/components/adjust-stock-modal/adjust-stock-modal.component.ts
@Component({
  selector: 'app-adjust-stock-modal',
  standalone: true,
  imports: [ReactiveFormsModule, NgIf],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="modal-overlay">
      <div class="modal">
        <h4>Коригувати stock: {{ item.productName }}</h4>
        <p>Поточний залишок: <strong>{{ item.totalStock }}</strong></p>

        <form [formGroup]="form" (ngSubmit)="onSubmit()">
          <div class="form-group">
            <label>Зміна кількості</label>
            <input formControlName="delta" type="number" class="form-control"
                   placeholder="+10 або -5">
            <small class="text-muted">
              Позитивне = надходження. Від'ємне = списання.
            </small>
            <div *ngIf="newAmount < 0" class="text-danger">
              Результат не може бути від'ємним. Максимально можна списати: {{ item.totalStock }}
            </div>
          </div>

          <div class="form-group">
            <label>Причина</label>
            <input formControlName="reason" type="text" class="form-control"
                   placeholder="Надходження від постачальника / Брак / Інвентаризація">
          </div>

          <div class="preview" *ngIf="form.get('delta')?.value">
            Буде: {{ item.totalStock }} {{ delta >= 0 ? '+' : '' }}{{ delta }}
            = <strong>{{ newAmount }}</strong>
          </div>

          <div class="modal-actions">
            <button type="submit" [disabled]="form.invalid || newAmount < 0">
              Підтвердити
            </button>
            <button type="button" (click)="cancel.emit()">Скасувати</button>
          </div>
        </form>
      </div>
    </div>
  `
})
export class AdjustStockModalComponent {
  @Input({ required: true }) item!: StockLevel;
  @Output() confirm = new EventEmitter<AdjustStockPayload>();
  @Output() cancel  = new EventEmitter<void>();

  private fb = inject(FormBuilder);

  form = this.fb.group({
    delta:  [0, [Validators.required, Validators.min(-9999), Validators.max(9999)]],
    reason: ['', [Validators.required, Validators.minLength(3)]],
  });

  get delta(): number {
    return this.form.get('delta')?.value ?? 0;
  }

  get newAmount(): number {
    return this.item.totalStock + this.delta;
  }

  onSubmit(): void {
    if (this.form.invalid || this.newAmount < 0) return;

    this.confirm.emit({
      productId:   this.item.productId,
      warehouseId: this.item.warehouseId,
      delta:       this.delta,
      reason:      this.form.get('reason')!.value!,
    });
  }
}
```

### Крок 7 — Reservations Component

```typescript
// src/app/admin/warehouse/pages/reservations/reservations.component.ts
@Component({ /* ... */ template: `
  <div class="reservations">
    <div class="filters">
      <select (change)="filterByStatus($event)">
        <option value="">Всі</option>
        <option value="pending">Pending</option>
        <option value="committed">Committed</option>
        <option value="canceled">Canceled</option>
      </select>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th><th>Статус</th><th>Товарів</th>
          <th>Зарезервовано</th><th>Коментар</th><th>Дії</th>
        </tr>
      </thead>
      <tbody>
        <tr *ngFor="let r of reservations$ | async">
          <td>{{ r.id }}</td>
          <td>
            <span [class]="'badge badge-' + r.status">{{ r.status }}</span>
          </td>
          <td>
            <span *ngFor="let item of r.items">
              {{ item.name }} × {{ item.amount }}<br>
            </span>
          </td>
          <td>{{ r.reservedAt }}</td>
          <td>{{ r.comment }}</td>
          <td>
            <button
              *ngIf="r.status === 'pending'"
              (click)="onCancel(r.id)"
            >
              Скасувати
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
` })
export class ReservationsComponent implements OnInit {
  private store = inject(Store);

  reservations$ = this.store.select(selectReservations);

  ngOnInit(): void {
    this.store.dispatch(WarehouseQueryActions.loadReservations({}));
  }

  filterByStatus(event: Event): void {
    const status = (event.target as HTMLSelectElement).value || undefined;
    this.store.dispatch(WarehouseQueryActions.loadReservations({ status }));
  }

  onCancel(id: number): void {
    const reason = prompt('Причина скасування:') ?? 'Manual cancellation';
    this.store.dispatch(WarehouseCommandActions.cancelReservation({ id, reason }));
  }
}
```

---

## Повний CQRS Flow в цьому плані

```
Менеджер натискає "Коригувати"
    ↓
AdjustStockModal заповнює форму
    ↓
dispatch(WarehouseCommandActions.adjustStock({ payload }))   ← COMMAND
    ↓
Effect: adjustStock$ → POST /api/warehouse/stock/adjust      ← HTTP до Symfony
    ↓
Symfony: AdjustStockHandler → змінює StockAvailability       ← Write side
    ↓
adjustStockSuccess()
    ↓
refreshStockAfterAdjust$ effect
    ↓
dispatch(WarehouseQueryActions.loadStockLevels({}))           ← QUERY
    ↓
Effect: loadStockLevels$ → GET /api/warehouse/stock           ← HTTP до Symfony
    ↓
Symfony: GetStockLevelsHandler → читає актуальні дані        ← Read side
    ↓
loadStockLevelsSuccess({ items })
    ↓
Reducer: оновлює state.stockLevels
    ↓
selectStockLevels selector → компонент ре-рендериться         ← UI оновлений
```

---

## Що перевірити після реалізації

- [ ] `/admin/warehouse/stock` → таблиця зі stock по всіх складах
- [ ] Фільтр "Тільки малий залишок" → показує тільки isLowStock товари
- [ ] "Коригувати" → modal → підтвердити → stock оновлюється в таблиці автоматично
- [ ] delta що робить total від'ємним → кнопка заблокована в modal
- [ ] `/admin/warehouse/reservations` → список резервувань
- [ ] "Скасувати" резервування → зникає з pending списку
- [ ] `/admin/warehouse/transactions` → аудит лог

---

## Зв'язок з іншими планами
- **План 05** (Warehouse Commands) — Angular відправляє ці команди
- **План 06** (Warehouse Queries) — Angular читає ці query результати
- **План 07** (Admin Orders) — той самий admin layout
- **План 08** (Admin Products) — той самий NgRx патерн
