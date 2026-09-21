import { Routes } from '@angular/router';
import { AdminComponent } from './admin.component';
import { adminGuard } from './guards/admin.guard';

export const routes: Routes = [
  {
    path: '',
    component: AdminComponent,
    canActivate: [adminGuard],
    children: [
      {
        path: '',
        redirectTo: 'orders',
        pathMatch: 'full',
      },
      {
        path: 'orders',
        loadChildren: () => import('./orders/orders.routes').then((m) => m.routes),
      },
      {
        path: 'products',
        loadChildren: () => import('./products/products.routes').then((m) => m.routes),
      },
      {
        path: 'categories',
        loadChildren: () => import('./categories/categories.routes').then((m) => m.routes),
      },
      {
        path: 'warehouse',
        loadChildren: () => import('./warehouse/warehouse.routes').then((m) => m.routes),
      },
    ],
  },
];
