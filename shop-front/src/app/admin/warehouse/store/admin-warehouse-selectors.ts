import { RootState } from '../../../models/root-state';

export const adminWarehouseStockLevelsSelector = (state: RootState) => state.adminWarehouseReducer.stockLevels;
export const adminWarehouseReservationsSelector = (state: RootState) => state.adminWarehouseReducer.reservations;
export const adminWarehouseTransactionsSelector = (state: RootState) => state.adminWarehouseReducer.transactions;
export const adminWarehouseSummarySelector = (state: RootState) => state.adminWarehouseReducer.summary;
export const adminWarehouseLoadingSelector = (state: RootState) => state.adminWarehouseReducer.loading;
export const adminWarehouseErrorSelector = (state: RootState) => state.adminWarehouseReducer.error;
