import { ApplicationConfig, provideBrowserGlobalErrorListeners, provideZonelessChangeDetection } from '@angular/core';
import {provideRouter, withComponentInputBinding} from '@angular/router';

import {routes } from './app.routes';

import {provideStore} from '@ngrx/store';
import {productsReducer} from './store/products/products-reducers';
import {provideEffects} from '@ngrx/effects';

import {provideHttpClient, withInterceptors} from '@angular/common/http';
import {authInterceptor} from './auth/interceptors/auth.interceptor';
import {provideStoreDevtools} from '@ngrx/store-devtools';
import {categoriesReducer} from './store/categories/categories-reducers';
import {CategoriesEffects} from './store/categories/categories-effects';
import {ProductsEffects} from './store/products/products-effects';
import {productReducer} from './store/product/product-reducers';
import {ProductEffects} from './store/product/product-effect';
import {provideRouterStore, RouterStateSerializer} from '@ngrx/router-store';
import {CustomSerializer} from './store/route/router-sate-serializer';
import {adminOrdersReducer} from './admin/orders/store/admin-orders-reducers';
import {AdminOrdersEffects} from './admin/orders/store/admin-orders-effects';
import {adminProductsReducer} from './admin/products/store/admin-products-reducers';
import {AdminProductsEffects} from './admin/products/store/admin-products-effects';
import {adminCategoriesReducer} from './admin/categories/store/admin-categories-reducers';
import {AdminCategoriesEffects} from './admin/categories/store/admin-categories-effects';
import {adminWarehouseReducer} from './admin/warehouse/store/admin-warehouse-reducers';
import {AdminWarehouseEffects} from './admin/warehouse/store/admin-warehouse-effects';



export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideZonelessChangeDetection(),
    provideRouter(routes, withComponentInputBinding()),
    provideHttpClient(withInterceptors([authInterceptor])),
    provideStore({
      productsReducer,
      categoriesReducer,
      productReducer,
      adminOrdersReducer,
      adminProductsReducer,
      adminCategoriesReducer,
      adminWarehouseReducer,
    }),
    provideEffects([
      ProductsEffects,
      CategoriesEffects,
      ProductEffects,
      AdminOrdersEffects,
      AdminProductsEffects,
      AdminCategoriesEffects,
      AdminWarehouseEffects
    ]),
    provideStoreDevtools({
      maxAge: 25, // Optional: number of actions to retain
      logOnly: false // true = log-only mode (for production)
    }),
    provideRouterStore({
      serializer: CustomSerializer,
    }),

  ]
};
