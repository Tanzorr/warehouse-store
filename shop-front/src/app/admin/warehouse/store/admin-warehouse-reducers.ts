import { createReducer, on } from '@ngrx/store';
import { ReservationsResponse, StockLevel, TransactionsResponse, WarehouseSummary } from '../../shared/models/warehouse.model';
import {
  adjustStock,
  adjustStockFailure,
  adjustStockSuccess,
  cancelReservation,
  cancelReservationFailure,
  cancelReservationSuccess,
  getReservations,
  getReservationsFailure,
  getReservationsSuccess,
  getStockLevels,
  getStockLevelsFailure,
  getStockLevelsSuccess,
  getTransactions,
  getTransactionsFailure,
  getTransactionsSuccess,
  getWarehouseSummary,
  getWarehouseSummaryFailure,
  getWarehouseSummarySuccess,
  transferStock,
  transferStockFailure,
  transferStockSuccess,
} from './admin-warehouse-actions';

export interface AdminWarehouseStateModel {
  stockLevels: StockLevel[];
  reservations: ReservationsResponse | null;
  transactions: TransactionsResponse | null;
  summary: WarehouseSummary | null;
  loading: boolean;
  error: any;
}

const initialState: AdminWarehouseStateModel = {
  stockLevels: [],
  reservations: null,
  transactions: null,
  summary: null,
  loading: false,
  error: null,
};

export const adminWarehouseReducer = createReducer(
  initialState,

  on(getStockLevels, (state) => ({ ...state, loading: true })),
  on(getStockLevelsSuccess, (state, { stockLevels }) => ({ ...state, stockLevels, loading: false, error: null })),
  on(getStockLevelsFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(getReservations, (state) => ({ ...state, loading: true })),
  on(getReservationsSuccess, (state, { response }) => ({ ...state, reservations: response, loading: false, error: null })),
  on(getReservationsFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(getTransactions, (state) => ({ ...state, loading: true })),
  on(getTransactionsSuccess, (state, { response }) => ({ ...state, transactions: response, loading: false, error: null })),
  on(getTransactionsFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(getWarehouseSummary, (state) => ({ ...state, loading: true })),
  on(getWarehouseSummarySuccess, (state, { summary }) => ({ ...state, summary, loading: false, error: null })),
  on(getWarehouseSummaryFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(cancelReservation, (state) => ({ ...state, loading: true })),
  on(cancelReservationSuccess, (state) => ({ ...state, loading: false, error: null })),
  on(cancelReservationFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(adjustStock, (state) => ({ ...state, loading: true })),
  on(adjustStockSuccess, (state) => ({ ...state, loading: false, error: null })),
  on(adjustStockFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(transferStock, (state) => ({ ...state, loading: true })),
  on(transferStockSuccess, (state) => ({ ...state, loading: false, error: null })),
  on(transferStockFailure, (state, { error }) => ({ ...state, loading: false, error }))
);
