import { createAction } from '@ngrx/store';
import { AdminCategoriesResponse, AdminCategory, CategoryPayload } from '../../shared/models/admin-product.model';

export const getAdminCategories = createAction('[Admin Categories] Get Categories');
export const getAdminCategoriesSuccess = createAction(
  '[Admin Categories] Get Categories Success',
  (response: AdminCategoriesResponse) => ({ response })
);
export const getAdminCategoriesFailure = createAction(
  '[Admin Categories] Get Categories Failure',
  (error: any) => ({ error })
);

export const getAdminCategory = createAction(
  '[Admin Categories] Get Category',
  (id: number) => ({ id })
);
export const getAdminCategorySuccess = createAction(
  '[Admin Categories] Get Category Success',
  (category: AdminCategory) => ({ category })
);
export const getAdminCategoryFailure = createAction(
  '[Admin Categories] Get Category Failure',
  (error: any) => ({ error })
);

export const createAdminCategory = createAction(
  '[Admin Categories] Create Category',
  (payload: CategoryPayload) => ({ payload })
);
export const createAdminCategorySuccess = createAction('[Admin Categories] Create Category Success');
export const createAdminCategoryFailure = createAction(
  '[Admin Categories] Create Category Failure',
  (error: any) => ({ error })
);

export const updateAdminCategory = createAction(
  '[Admin Categories] Update Category',
  (id: number, payload: CategoryPayload) => ({ id, payload })
);
export const updateAdminCategorySuccess = createAction('[Admin Categories] Update Category Success');
export const updateAdminCategoryFailure = createAction(
  '[Admin Categories] Update Category Failure',
  (error: any) => ({ error })
);

export const deleteAdminCategory = createAction(
  '[Admin Categories] Delete Category',
  (id: number) => ({ id })
);
export const deleteAdminCategorySuccess = createAction(
  '[Admin Categories] Delete Category Success',
  (id: number) => ({ id })
);
export const deleteAdminCategoryFailure = createAction(
  '[Admin Categories] Delete Category Failure',
  (error: any) => ({ error })
);
