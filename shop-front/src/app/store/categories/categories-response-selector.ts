import {Category} from '../../models/category';
import {RootState} from '../../models/root-state';

export const selectedCategoryIdsSelector = (state:RootState ):number[] => state.categoriesReducer.selectedCategoryIds || [];

export const categoriesListSelector = (state: RootState):Category[] => {
  return  state.categoriesReducer.categoriesResponse?.data || [];
}

