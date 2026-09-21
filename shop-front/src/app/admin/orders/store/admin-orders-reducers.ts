import { createReducer, on } from '@ngrx/store';
import { AdminOrder } from '../../shared/models/admin-order.model';
import {
  getAdminOrder,
  getAdminOrderFailure,
  getAdminOrderSuccess,
  getAdminOrders,
  getAdminOrdersFailure,
  getAdminOrdersSuccess,
  payAdminOrder,
  payAdminOrderFailure,
  payAdminOrderSuccess,
  updateAdminOrder,
  updateAdminOrderFailure,
  updateAdminOrderSuccess,
} from './admin-orders-actions';

export interface AdminOrdersStateModel {
  orders: AdminOrder[];
  selectedOrder: AdminOrder | null;
  loading: boolean;
  error: any;
}

const initialState: AdminOrdersStateModel = {
  orders: [],
  selectedOrder: null,
  loading: false,
  error: null,
};

export const adminOrdersReducer = createReducer(
  initialState,

  on(getAdminOrders, (state) => ({ ...state, loading: true })),
  on(getAdminOrdersSuccess, (state, { orders }) => ({
    ...state,
    orders,
    loading: false,
    error: null,
  })),
  on(getAdminOrdersFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(getAdminOrder, (state) => ({ ...state, loading: true })),
  on(getAdminOrderSuccess, (state, { order }) => ({
    ...state,
    selectedOrder: order,
    loading: false,
    error: null,
  })),
  on(getAdminOrderFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(updateAdminOrder, (state) => ({ ...state, loading: true })),
  on(updateAdminOrderSuccess, (state, { order }) => ({
    ...state,
    selectedOrder: order,
    orders: state.orders.map((o) => (o.id === order.id ? order : o)),
    loading: false,
    error: null,
  })),
  on(updateAdminOrderFailure, (state, { error }) => ({ ...state, loading: false, error })),

  on(payAdminOrder, (state) => ({ ...state, loading: true })),
  on(payAdminOrderSuccess, (state, { order }) => ({
    ...state,
    selectedOrder: order,
    orders: state.orders.map((o) => (o.id === order.id ? order : o)),
    loading: false,
    error: null,
  })),
  on(payAdminOrderFailure, (state, { error }) => ({ ...state, loading: false, error }))
);
