import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    loadChildren: () => import('./pages/pages.routes').then(m => m.routes),
  },
  {
    path: 'home',
    loadChildren: () => import('./pages/home/home.routes').then(m => m.routes),
  },
  {
    path: 'login',
    loadChildren: () => import('./auth/auth.routes').then(m => m.routes),
  },
  {
    path: 'admin',
    loadChildren: () => import('./admin/admin.routes').then(m => m.routes),
  },

  { path: '**', redirectTo: 'home' },
];
