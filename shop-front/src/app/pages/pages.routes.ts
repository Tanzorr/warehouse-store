import {Routes} from '@angular/router';
import {PagesComponent} from './pages.component';


export const routes: Routes = [
  {
    path: '',
    component: PagesComponent,
    children: [
      {
        path: 'home',
        loadChildren: () => import('./home/home.routes').then(m => m.routes),
      },
      {
        path: 'products',
        loadChildren: () => import('./products/products.routes').then(m => m.routes),
      },
      {
        path: 'about',
        loadChildren: () => import('./about/about.routes').then(m => m.routes),
      },
      {
        path: 'contacts',
        loadChildren: () => import('./contacts/contacts.routes').then(m => m.routes),
      }
    ]
  },
];
