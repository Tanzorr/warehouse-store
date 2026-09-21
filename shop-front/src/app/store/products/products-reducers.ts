import { createReducer, on } from '@ngrx/store';

import { ProductsResponse} from '../../models/products';
import {getProductsFailure, getProductsSuccess} from './products-actions';

export interface ProductsStateModel {
  productsResponse: ProductsResponse | null; // Changed to allow null for initial state
  loading: boolean;
  error: any;
  selectedCategoryIds?: number[]; // Optional property to hold selected category IDs
}


const initialState: ProductsStateModel = {
  productsResponse: null,
  loading: false,
  error: null,
};

export const productsReducer = createReducer(
  initialState,
  on(getProductsSuccess, (state, {products}) => ({
    ...state,
    productsResponse: products,
    loading: false,
    error: null,
  })),

  on(getProductsFailure, (state, {error}) => ({
    ...state,
    loading: false,
    error,
  }))
);
