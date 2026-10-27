import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () => import('./pages/order-list/order-list.component').then((m) => m.OrderListComponent),
  },
  {
    path: ':id',
    loadComponent: () => import('./pages/order-detail/order-detail.component').then((m) => m.OrderDetailComponent),
  },
];
