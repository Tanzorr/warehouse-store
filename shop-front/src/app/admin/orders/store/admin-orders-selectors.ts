import { RootState } from '../../../models/root-state';

export const adminOrdersSelector = (state: RootState) => state.adminOrdersReducer.orders;
export const adminSelectedOrderSelector = (state: RootState) => state.adminOrdersReducer.selectedOrder;
export const adminOrdersLoadingSelector = (state: RootState) => state.adminOrdersReducer.loading;
export const adminOrdersErrorSelector = (state: RootState) => state.adminOrdersReducer.error;
