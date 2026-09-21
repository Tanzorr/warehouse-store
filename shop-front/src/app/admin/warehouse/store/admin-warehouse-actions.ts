import { createAction } from '@ngrx/store';
import {
  AdjustStockPayload,
  CancelReservationPayload,
  ReservationsResponse,
  StockLevel,
  TransactionsResponse,
  TransferStockPayload,
  WarehouseSummary,
} from '../../shared/models/warehouse.model';

export const getStockLevels = createAction('[Admin Warehouse] Get Stock Levels', (params?: string) => ({ params }));
export const getStockLevelsSuccess = createAction(
  '[Admin Warehouse] Get Stock Levels Success',
  (stockLevels: StockLevel[]) => ({ stockLevels })
);
export const getStockLevelsFailure = createAction(
  '[Admin Warehouse] Get Stock Levels Failure',
  (error: any) => ({ error })
);

export const getReservations = createAction(
  '[Admin Warehouse] Get Reservations',
  (params?: string) => ({ params })
);
export const getReservationsSuccess = createAction(
  '[Admin Warehouse] Get Reservations Success',
  (response: ReservationsResponse) => ({ response })
);
export const getReservationsFailure = createAction(
  '[Admin Warehouse] Get Reservations Failure',
  (error: any) => ({ error })
);

export const getTransactions = createAction(
  '[Admin Warehouse] Get Transactions',
  (params?: string) => ({ params })
);
export const getTransactionsSuccess = createAction(
  '[Admin Warehouse] Get Transactions Success',
  (response: TransactionsResponse) => ({ response })
);
export const getTransactionsFailure = createAction(
  '[Admin Warehouse] Get Transactions Failure',
  (error: any) => ({ error })
);

export const getWarehouseSummary = createAction(
  '[Admin Warehouse] Get Warehouse Summary',
  (warehouseId: number) => ({ warehouseId })
);
export const getWarehouseSummarySuccess = createAction(
  '[Admin Warehouse] Get Warehouse Summary Success',
  (summary: WarehouseSummary) => ({ summary })
);
export const getWarehouseSummaryFailure = createAction(
  '[Admin Warehouse] Get Warehouse Summary Failure',
  (error: any) => ({ error })
);

export const cancelReservation = createAction(
  '[Admin Warehouse] Cancel Reservation',
  (id: number, payload: CancelReservationPayload) => ({ id, payload })
);
export const cancelReservationSuccess = createAction('[Admin Warehouse] Cancel Reservation Success');
export const cancelReservationFailure = createAction(
  '[Admin Warehouse] Cancel Reservation Failure',
  (error: any) => ({ error })
);

export const adjustStock = createAction(
  '[Admin Warehouse] Adjust Stock',
  (payload: AdjustStockPayload) => ({ payload })
);
export const adjustStockSuccess = createAction('[Admin Warehouse] Adjust Stock Success');
export const adjustStockFailure = createAction(
  '[Admin Warehouse] Adjust Stock Failure',
  (error: any) => ({ error })
);

export const transferStock = createAction(
  '[Admin Warehouse] Transfer Stock',
  (payload: TransferStockPayload) => ({ payload })
);
export const transferStockSuccess = createAction('[Admin Warehouse] Transfer Stock Success');
export const transferStockFailure = createAction(
  '[Admin Warehouse] Transfer Stock Failure',
  (error: any) => ({ error })
);
