import { createReducer, on } from '@ngrx/store';
import { AdminCategoriesResponse, AdminCategory } from '../../shared/models/admin-product.model';
import {
  createAdminCategory,
  createAdminCategoryFailure,
  createAdminCategorySuccess,
  deleteAdminCategory,
  deleteAdminCategoryFailure,
  deleteAdminCategorySuccess,
  getAdminCategories,
  getAdminCategoriesFailure,
  getAdminCategoriesSuccess,
  getAdminCategory,
  getAdminCategoryFailure,
  getAdminCategorySuccess,
  updateAdminCategory,
  updateAdminCategoryFailure,
  updateAdminCategorySuccess,
} from './admin-categories-actions';

export interface AdminCategoriesStateModel {
  categoriesResponse: AdminCategoriesResponse | null;
  selectedCategory: AdminCategory | null;
  loading: boolean;
  error: any;
}

const initialState: AdminCategoriesStateModel = {
  categoriesResponse: null,
  selectedCategory: null,
  loading: false,
  error: null,
};

export const adminCategoriesReducer = createReducer(
  initialState,

  on(getAdminCategories, (state) => ({ ...state, loading: true })),
  on(getAdminCategoriesSuccess, (state, { response }) => ({
    ...state,
    categoriesResponse: response,
    loading: false,
    error: null,
  })),
  on(getAdminCategoriesFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(getAdminCategory, (state) => ({ ...state, loading: true })),
  on(getAdminCategorySuccess, (state, { category }) => ({
    ...state,
    selectedCategory: category,
    loading: false,
    error: null,
  })),
  on(getAdminCategoryFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(createAdminCategory, (state) => ({ ...state, loading: true })),
  on(createAdminCategorySuccess, (state) => ({ ...state, loading: false, error: null })),
  on(createAdminCategoryFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(updateAdminCategory, (state) => ({ ...state, loading: true })),
  on(updateAdminCategorySuccess, (state) => ({ ...state, loading: false, error: null })),
  on(updateAdminCategoryFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(deleteAdminCategory, (state) => ({ ...state, loading: true })),
  on(deleteAdminCategorySuccess, (state, { id }) => ({
    ...state,
    categoriesResponse: state.categoriesResponse
      ? {
          ...state.categoriesResponse,
          data: state.categoriesResponse.data.filter((c) => c.id !== id),
        }
      : null,
    loading: false,
    error: null,
  })),
  on(deleteAdminCategoryFailure, (state, { error }) => ({ ...state, loading: false, error }))
);
