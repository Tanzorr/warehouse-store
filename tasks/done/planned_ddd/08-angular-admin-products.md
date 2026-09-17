# План 08 — Angular Admin: Управління продуктами та категоріями

## Задача / Проблема
**Проблема:** менеджер не може додавати, редагувати або видаляти продукти через UI.
Немає можливості управляти категоріями, медіа-файлами, тегами.

**Задача:** побудувати CRUD-інтерфейс для:
1. Продуктів — список, форма створення/редагування, видалення.
2. Категорій — список, форма, tree-структура.
3. Медіа — upload зображень і прив'язка до продуктів.

**Що навчає:**
- Форми Angular як "command builder" — форма збирає дані для Command.
- NgRx для CRUD: optimistic updates vs pessimistic updates.
- Reactive Forms + validation як захист від невалідних команд.

---

## Файлова структура

```
src/app/admin/
├── products/
│   ├── products.routes.ts
│   ├── pages/
│   │   ├── product-list/
│   │   │   ├── product-list.component.ts
│   │   │   └── product-list.component.html
│   │   ├── product-form/
│   │   │   ├── product-form.component.ts   ← create + edit (один компонент)
│   │   │   └── product-form.component.html
│   │   └── product-detail/
│   │       └── product-detail.component.ts
│   ├── components/
│   │   ├── product-table/
│   │   │   └── product-table.component.ts
│   │   └── media-uploader/
│   │       └── media-uploader.component.ts
│   └── store/
│       ├── admin-products.actions.ts
│       ├── admin-products.effects.ts
│       ├── admin-products.reducer.ts
│       └── admin-products.selectors.ts
└── categories/
    ├── categories.routes.ts
    ├── pages/
    │   ├── category-list/
    │   │   └── category-list.component.ts
    │   └── category-form/
    │       └── category-form.component.ts
    └── store/
        ├── admin-categories.actions.ts
        ├── admin-categories.effects.ts
        ├── admin-categories.reducer.ts
        └── admin-categories.selectors.ts
```

---

## Кроки реалізації

### Крок 1 — Models

```typescript
// src/app/admin/shared/models/admin-product.model.ts
export interface AdminProduct {
  id:            number;
  name:          string;
  description:   string;
  purchasePrice: number;
  salePrice:     number;
  stock:         number;
  sku:           string;
  categoryId:    number;
  categoryName:  string;
  tags:          string[];
  media:         AdminMedia[];
  createdAt:     string;
}

export interface AdminMedia {
  id:       number;
  filePath: string;
  fileName: string;
  mimeType: string;
}

// Payload для CREATE/UPDATE (відповідає полям CommandDTO на Laravel)
export interface ProductFormData {
  name:          string;
  description:   string;
  purchasePrice: number;
  salePrice:     number;
  stock:         number;
  sku:           string;
  categoryId:    number;
}
```

### Крок 2 — NgRx Actions

```typescript
// src/app/admin/products/store/admin-products.actions.ts
export const AdminProductsActions = createActionGroup({
  source: 'Admin Products',
  events: {
    // QUERY
    'Load Products':          props<{ search?: string; categoryId?: number; page?: number }>(),
    'Load Products Success':  props<{ products: AdminProduct[]; total: number }>(),
    'Load Products Failure':  props<{ error: string }>(),

    // COMMANDS
    'Create Product':         props<{ data: ProductFormData }>(),
    'Create Product Success': props<{ product: AdminProduct }>(),
    'Create Product Failure': props<{ error: string }>(),

    'Update Product':         props<{ id: number; data: Partial<ProductFormData> }>(),
    'Update Product Success': props<{ product: AdminProduct }>(),
    'Update Product Failure': props<{ error: string }>(),

    'Delete Product':         props<{ id: number }>(),
    'Delete Product Success': props<{ id: number }>(),
    'Delete Product Failure': props<{ error: string }>(),

    // Media commands
    'Attach Media':           props<{ productId: number; mediaId: number }>(),
    'Attach Media Success':   props<{ productId: number; media: AdminMedia }>(),
    'Detach Media':           props<{ productId: number; mediaId: number }>(),
    'Detach Media Success':   props<{ productId: number; mediaId: number }>(),
  }
});
```

### Крок 3 — Reducer з оптимістичними оновленнями

```typescript
// src/app/admin/products/store/admin-products.reducer.ts
export interface AdminProductsState {
  products:       AdminProduct[];
  total:          number;
  loading:        boolean;
  savingId:       number | null;  // id продукту що зараз зберігається
  deletingId:     number | null;  // id продукту що видаляється
  error:          string | null;
}

export const adminProductsReducer = createReducer(
  initialState,

  on(AdminProductsActions.loadProductsSuccess, (state, { products, total }) => ({
    ...state, loading: false, products, total
  })),

  // CREATE — додаємо в список після успіху (pessimistic: чекаємо відповідь сервера)
  on(AdminProductsActions.createProduct, state => ({
    ...state, savingId: -1  // -1 = новий продукт
  })),
  on(AdminProductsActions.createProductSuccess, (state, { product }) => ({
    ...state,
    savingId:  null,
    products:  [product, ...state.products],
    total:     state.total + 1,
  })),

  // UPDATE — оновлюємо локально одразу (optimistic), rollback при помилці
  on(AdminProductsActions.updateProduct, (state, { id, data }) => ({
    ...state,
    savingId: id,
    products: state.products.map(p =>
      p.id === id ? { ...p, ...data } : p   // optimistic update
    ),
  })),
  on(AdminProductsActions.updateProductSuccess, (state, { product }) => ({
    ...state,
    savingId: null,
    products: state.products.map(p => p.id === product.id ? product : p),
  })),

  // DELETE — видаляємо зі списку після успіху (pessimistic)
  on(AdminProductsActions.deleteProduct, (state, { id }) => ({
    ...state, deletingId: id
  })),
  on(AdminProductsActions.deleteProductSuccess, (state, { id }) => ({
    ...state,
    deletingId: null,
    products:   state.products.filter(p => p.id !== id),
    total:      state.total - 1,
  })),
);
```

**Optimistic vs Pessimistic update:**
- **Optimistic** (UPDATE): UI оновлюється одразу. Якщо помилка — rollback.
  Відчуття швидкості, але складніше.
- **Pessimistic** (CREATE, DELETE): чекаємо відповідь сервера.
  Безпечніше — не показуємо те чого ще немає або вже видалено.

### Крок 4 — Product Form Component (Create + Edit)

**Задача:** одна форма для обох операцій. Якщо є `id` в route — edit, якщо немає — create.

```typescript
// src/app/admin/products/pages/product-form/product-form.component.ts
@Component({
  selector: 'app-product-form',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, AsyncPipe, NgIf],
  template: `
    <form [formGroup]="form" (ngSubmit)="onSubmit()">
      <div class="form-group">
        <label>Назва</label>
        <input formControlName="name" type="text" class="form-control">
        <div *ngIf="form.get('name')?.errors?.['required'] && form.get('name')?.touched">
          Назва обов'язкова
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <label>Ціна закупки</label>
          <input formControlName="purchasePrice" type="number" step="0.01" class="form-control">
        </div>
        <div class="col-md-6">
          <label>Ціна продажу</label>
          <input formControlName="salePrice" type="number" step="0.01" class="form-control">
          <div *ngIf="form.errors?.['salePriceTooLow']">
            Ціна продажу має бути більшою за ціну закупки
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <label>SKU</label>
          <input formControlName="sku" type="text" class="form-control" style="text-transform:uppercase">
        </div>
        <div class="col-md-6">
          <label>Кількість</label>
          <input formControlName="stock" type="number" class="form-control">
        </div>
      </div>

      <div class="form-group">
        <label>Категорія</label>
        <select formControlName="categoryId" class="form-control">
          <option *ngFor="let cat of categories$ | async" [value]="cat.id">
            {{ cat.name }}
          </option>
        </select>
      </div>

      <button type="submit" [disabled]="form.invalid || (saving$ | async)">
        {{ isEdit ? 'Зберегти' : 'Створити' }}
      </button>
      <button type="button" routerLink="/admin/products">Скасувати</button>
    </form>
  `
})
export class ProductFormComponent implements OnInit {
  private store  = inject(Store);
  private route  = inject(ActivatedRoute);
  private router = inject(Router);
  private fb     = inject(FormBuilder);

  productId = this.route.snapshot.paramMap.get('id');
  isEdit    = !!this.productId;

  categories$ = this.store.select(selectAdminCategories);
  saving$     = this.store.select(selectSavingId);

  form = this.fb.group({
    name:          ['', [Validators.required, Validators.minLength(2)]],
    description:   ['', Validators.required],
    purchasePrice: [0,  [Validators.required, Validators.min(0.01)]],
    salePrice:     [0,  [Validators.required, Validators.min(0.01)]],
    stock:         [0,  [Validators.required, Validators.min(0)]],
    sku:           ['', [Validators.required, Validators.pattern(/^[A-Z0-9\-]{2,50}$/)]],
    categoryId:    [null, Validators.required],
  }, { validators: this.salePriceValidator });

  // Validator дублює бізнес-правило з Domain (план 02/03)
  private salePriceValidator(group: AbstractControl) {
    const purchase = group.get('purchasePrice')?.value;
    const sale     = group.get('salePrice')?.value;
    return sale > purchase ? null : { salePriceTooLow: true };
  }

  ngOnInit(): void {
    this.store.dispatch(AdminCategoriesActions.loadCategories());

    if (this.isEdit && this.productId) {
      // Завантажити дані продукту для редагування
      this.store.select(selectProductById(+this.productId))
        .pipe(take(1), filter(Boolean))
        .subscribe(product => this.form.patchValue({
          name:          product.name,
          description:   product.description,
          purchasePrice: product.purchasePrice,
          salePrice:     product.salePrice,
          stock:         product.stock,
          sku:           product.sku,
          categoryId:    product.categoryId,
        }));
    }
  }

  onSubmit(): void {
    if (this.form.invalid) return;

    const data = this.form.value as ProductFormData;

    if (this.isEdit) {
      this.store.dispatch(AdminProductsActions.updateProduct({
        id: +this.productId!, data
      }));
    } else {
      this.store.dispatch(AdminProductsActions.createProduct({ data }));
    }
  }
}
```

**Навіщо validator в формі дублює серверне правило:**
Валідація на фронті = UX (миттєва відповідь без round-trip).
Валідація на бекенді = безпека (ніхто не обходить через API).
Обидва рівні потрібні.

### Крок 5 — Effect: після CREATE/UPDATE редірект на список

```typescript
// src/app/admin/products/store/admin-products.effects.ts
@Injectable()
export class AdminProductsEffects {
  createProduct$ = createEffect(() =>
    this.actions$.pipe(
      ofType(AdminProductsActions.createProduct),
      exhaustMap(({ data }) =>
        this.service.createProduct(data).pipe(
          map(product => AdminProductsActions.createProductSuccess({ product })),
          catchError(err => of(AdminProductsActions.createProductFailure({
            error: err.error?.message ?? err.message
          })))
        )
      )
    )
  );

  // Після успішного create/update — редірект
  redirectAfterSave$ = createEffect(() =>
    this.actions$.pipe(
      ofType(
        AdminProductsActions.createProductSuccess,
        AdminProductsActions.updateProductSuccess,
      ),
      tap(() => this.router.navigate(['/admin/products']))
    ),
    { dispatch: false }  // не диспетчимо нову action
  );
}
```

### Крок 6 — Routes

```typescript
// src/app/admin/products/products.routes.ts
export const ADMIN_PRODUCTS_ROUTES: Routes = [
  { path: '',     component: ProductListComponent },
  { path: 'new',  component: ProductFormComponent },
  { path: ':id',  component: ProductDetailComponent },
  { path: ':id/edit', component: ProductFormComponent },
];

// src/app/admin/admin.routes.ts
export const ADMIN_ROUTES: Routes = [
  {
    path: '',
    component: AdminComponent,
    canActivate: [adminGuard],
    children: [
      { path: 'orders',     loadChildren: () => import('./orders/orders.routes').then(m => m.ADMIN_ORDERS_ROUTES) },
      { path: 'products',   loadChildren: () => import('./products/products.routes').then(m => m.ADMIN_PRODUCTS_ROUTES) },
      { path: 'categories', loadChildren: () => import('./categories/categories.routes').then(m => m.ADMIN_CATEGORIES_ROUTES) },
      { path: '',           redirectTo: 'orders', pathMatch: 'full' },
    ]
  }
];
```

### Крок 7 — Admin Guard

```typescript
// src/app/admin/guards/admin.guard.ts
export const adminGuard: CanActivateFn = () => {
  const router = inject(Router);
  const store  = inject(Store);

  return store.select(selectCurrentUser).pipe(
    take(1),
    map(user => {
      if (user?.role === 'admin') return true;
      router.navigate(['/login']);
      return false;
    })
  );
};
```

---

## Що перевірити після реалізації

- [ ] `/admin/products` → таблиця продуктів з пошуком і фільтром по категорії
- [ ] `/admin/products/new` → форма, `salePrice < purchasePrice` → помилка в UI
- [ ] Успішне створення → редірект на список, продукт з'явився
- [ ] `/admin/products/:id/edit` → форма заповнена поточними даними
- [ ] Кнопка "Видалити" → confirm dialog → продукт зникає зі списку
- [ ] Неадмін на `/admin/products` → редірект на login

---

## Зв'язок з іншими планами
- **План 01** (Command Bus) — `createProduct` → `CreateProductCommand`
- **План 02** (Value Objects) — валідатор форми дублює VO правила
- **План 07** (Admin Orders) — той самий admin layout і guard
- **План 09** (Admin Warehouse) — той самий NgRx патерн
