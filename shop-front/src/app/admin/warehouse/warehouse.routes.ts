import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./pages/stock-levels/stock-levels.component').then((m) => m.StockLevelsComponent),
  },
  {
    path: 'reservations',
    loadComponent: () =>
      import('./pages/reservations/reservations.component').then((m) => m.ReservationsComponent),
  },
  {
    path: 'transactions',
    loadComponent: () =>
      import('./pages/transactions/transactions.component').then((m) => m.TransactionsComponent),
  },
];
