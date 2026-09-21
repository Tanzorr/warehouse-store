import { createAction } from '@ngrx/store';
import { AdminProduct, AdminProductsResponse, ProductPayload } from '../../shared/models/admin-product.model';

export const getAdminProducts = createAction(
  '[Admin Products] Get Products',
  (params?: string) => ({ params })
);
export const getAdminProductsSuccess = createAction(
  '[Admin Products] Get Products Success',
  (response: AdminProductsResponse) => ({ response })
);
export const getAdminProductsFailure = createAction(
  '[Admin Products] Get Products Failure',
  (error: any) => ({ error })
);

export const getAdminProduct = createAction(
  '[Admin Products] Get Product',
  (id: number) => ({ id })
);
export const getAdminProductSuccess = createAction(
  '[Admin Products] Get Product Success',
  (product: AdminProduct) => ({ product })
);
export const getAdminProductFailure = createAction(
  '[Admin Products] Get Product Failure',
  (error: any) => ({ error })
);

export const createAdminProduct = createAction(
  '[Admin Products] Create Product',
  (payload: ProductPayload) => ({ payload })
);
export const createAdminProductSuccess = createAction(
  '[Admin Products] Create Product Success',
  (product: AdminProduct) => ({ product })
);
export const createAdminProductFailure = createAction(
  '[Admin Products] Create Product Failure',
  (error: any) => ({ error })
);

export const updateAdminProduct = createAction(
  '[Admin Products] Update Product',
  (id: number, payload: ProductPayload) => ({ id, payload })
);
export const updateAdminProductSuccess = createAction(
  '[Admin Products] Update Product Success',
  (product: AdminProduct) => ({ product })
);
export const updateAdminProductFailure = createAction(
  '[Admin Products] Update Product Failure',
  (error: any) => ({ error })
);

export const deleteAdminProduct = createAction(
  '[Admin Products] Delete Product',
  (id: number) => ({ id })
);
export const deleteAdminProductSuccess = createAction(
  '[Admin Products] Delete Product Success',
  (id: number) => ({ id })
);
export const deleteAdminProductFailure = createAction(
  '[Admin Products] Delete Product Failure',
  (error: any) => ({ error })
);
