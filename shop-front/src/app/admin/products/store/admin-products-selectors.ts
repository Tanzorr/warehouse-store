import { RootState } from '../../../models/root-state';

export const adminProductsResponseSelector = (state: RootState) => state.adminProductsReducer.productsResponse;
export const adminSelectedProductSelector = (state: RootState) => state.adminProductsReducer.selectedProduct;
export const adminProductsLoadingSelector = (state: RootState) => state.adminProductsReducer.loading;
export const adminProductsErrorSelector = (state: RootState) => state.adminProductsReducer.error;
