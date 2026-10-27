import { RootState } from '../../../models/root-state';

export const adminCategoriesResponseSelector = (state: RootState) => state.adminCategoriesReducer.categoriesResponse;
export const adminSelectedCategorySelector = (state: RootState) => state.adminCategoriesReducer.selectedCategory;
export const adminCategoriesLoadingSelector = (state: RootState) => state.adminCategoriesReducer.loading;
export const adminCategoriesErrorSelector = (state: RootState) => state.adminCategoriesReducer.error;
