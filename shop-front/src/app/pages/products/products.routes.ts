import {Routes} from '@angular/router';
import {ProductsComponent} from './products.component';

export const routes: Routes = [
  {
    path: '',
    component: ProductsComponent
  },
  {
    path: ':id',
    loadChildren: ()=> import('./product/product.routes').then(m => m.routes),
    data: {
      title: 'Product Details',
      description: 'Detailed information about a specific product.',
      id: ':id' // This is a placeholder for the product ID in the route
    }
  }
];
