import {createReducer, on} from '@ngrx/store';
import {getProductFailure, getProductSuccess} from './product-actions';
import {Product} from '../../models/products';

export interface ProductStateModel {
  productResponse: Product | null;
  loading: boolean;
  error: unknown;
}

const initialState: ProductStateModel = {
  productResponse: null,
  loading: false,
  error: null,
}


export const productReducer = createReducer(
  initialState,
  on(getProductSuccess, (state, {product}) => ({
    ...state,
    productResponse: product,
    loading: false,
    error: null,
  })),

  on(getProductFailure, (state, {error}) => ({
    ...state,
    loading: false,
    error,
  }))
);
