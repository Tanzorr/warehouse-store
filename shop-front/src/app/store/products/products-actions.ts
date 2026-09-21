import {createAction} from '@ngrx/store';
import { ProductsResponse} from '../../models/products';

export const getProducts = createAction(
  '[Products] Get Products',
  (params?: string ) => ({params})
);
export const getProductsSuccess = createAction(
  '[Products] Get Products Success',
  (products: ProductsResponse) => ({products})
);

export const getProductsFailure = createAction(
  '[Products] Get Products Failure',
  (error: any) => ({error})
);

