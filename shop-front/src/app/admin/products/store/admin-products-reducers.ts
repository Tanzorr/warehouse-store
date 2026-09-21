import { createReducer, on } from '@ngrx/store';
import { AdminProduct, AdminProductsResponse } from '../../shared/models/admin-product.model';
import {
  createAdminProduct,
  createAdminProductFailure,
  createAdminProductSuccess,
  deleteAdminProduct,
  deleteAdminProductFailure,
  deleteAdminProductSuccess,
  getAdminProduct,
  getAdminProductFailure,
  getAdminProductSuccess,
  getAdminProducts,
  getAdminProductsFailure,
  getAdminProductsSuccess,
  updateAdminProduct,
  updateAdminProductFailure,
  updateAdminProductSuccess,
} from './admin-products-actions';

export interface AdminProductsStateModel {
  productsResponse: AdminProductsResponse | null;
  selectedProduct: AdminProduct | null;
  loading: boolean;
  error: any;
}

const initialState: AdminProductsStateModel = {
  productsResponse: null,
  selectedProduct: null,
  loading: false,
  error: null,
};

export const adminProductsReducer = createReducer(
  initialState,

  on(getAdminProducts, (state) => ({ ...state, loading: true })),
  on(getAdminProductsSuccess, (state, { response }) => ({
    ...state,
    productsResponse: response,
    loading: false,
    error: null,
  })),
  on(getAdminProductsFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(getAdminProduct, (state) => ({ ...state, loading: true })),
  on(getAdminProductSuccess, (state, { product }) => ({
    ...state,
    selectedProduct: product,
    loading: false,
    error: null,
  })),
  on(getAdminProductFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(createAdminProduct, (state) => ({ ...state, loading: true })),
  on(createAdminProductSuccess, (state) => ({ ...state, loading: false, error: null })),
  on(createAdminProductFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(updateAdminProduct, (state) => ({ ...state, loading: true })),
  on(updateAdminProductSuccess, (state, { product }) => ({
    ...state,
    selectedProduct: product,
    loading: false,
    error: null,
  })),
  on(updateAdminProductFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(deleteAdminProduct, (state) => ({ ...state, loading: true })),
  on(deleteAdminProductSuccess, (state, { id }) => ({
    ...state,
    productsResponse: state.productsResponse
      ? {
          ...state.productsResponse,
          data: state.productsResponse.data.filter((p) => p.id !== id),
        }
      : null,
    loading: false,
    error: null,
  })),
  on(deleteAdminProductFailure, (state, { error }) => ({ ...state, loading: false, error }))
);
