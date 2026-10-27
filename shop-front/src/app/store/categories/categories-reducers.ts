import {createReducer, on} from '@ngrx/store';
import {
  getCategoriesFailure,
  getCategoriesSuccess,
  setSelectedCategoryIds
} from './categories-actions';
import {CategoryResponse} from '../../models/category';

export interface CategoriesStateModel {
  categoriesResponse: CategoryResponse | null; // Replace 'any' with the actual type of your categories response
  loading: boolean;
  selectedCategoryIds?: number[]; // Optional property to hold selected category IDs
  error: any;
}

export interface CategoriesState {
  categories: CategoriesStateModel;
}

const initialState: CategoriesStateModel = {
  categoriesResponse: null,
  loading: false,
  selectedCategoryIds: [], // Initialize with an empty array
  error: null,
};

export const categoriesReducer = createReducer(
  initialState,

  on(getCategoriesSuccess, (state, { categories }) => ({
    ...state,
    categoriesResponse: categories,
    loading: false,
    error: null,
  })),
  on(getCategoriesFailure, (state, { error }) => ({
    ...state,
    loading: false,
    error,
  })),

  on(setSelectedCategoryIds, (state , { selectedCategoryIds }) => ({
    ...state,
   selectedCategoryIds: selectedCategoryIds || [], // Ensure it's always an array
  }))
);
