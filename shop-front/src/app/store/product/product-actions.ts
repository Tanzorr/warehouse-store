import {createAction} from '@ngrx/store';
import {Product} from '../../models/products';

export const getProduct = createAction(
  '[ProductDetailComponent] Get Product',
  (productId: number) => ({productId})
);

export const getProductSuccess = createAction(
  '[ProductDetailComponent] Get Product Success',
  (product: Product) => ({product}) // Replace 'any' with the actual product type
);

export const getProductFailure = createAction(
  '[ProductDetailComponent] Get Product Failure',
  (error: any) => ({error}) // Replace 'any' with the actual error type if available
);
