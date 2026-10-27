import { createAction } from '@ngrx/store';
import { AdminOrder, UpdateOrderPayload } from '../../shared/models/admin-order.model';

export const getAdminOrders = createAction('[Admin Orders] Get Orders');
export const getAdminOrdersSuccess = createAction(
  '[Admin Orders] Get Orders Success',
  (orders: AdminOrder[]) => ({ orders })
);
export const getAdminOrdersFailure = createAction(
  '[Admin Orders] Get Orders Failure',
  (error: any) => ({ error })
);

export const getAdminOrder = createAction(
  '[Admin Orders] Get Order',
  (id: number) => ({ id })
);
export const getAdminOrderSuccess = createAction(
  '[Admin Orders] Get Order Success',
  (order: AdminOrder) => ({ order })
);
export const getAdminOrderFailure = createAction(
  '[Admin Orders] Get Order Failure',
  (error: any) => ({ error })
);

export const updateAdminOrder = createAction(
  '[Admin Orders] Update Order',
  (id: number, payload: UpdateOrderPayload) => ({ id, payload })
);
export const updateAdminOrderSuccess = createAction(
  '[Admin Orders] Update Order Success',
  (order: AdminOrder) => ({ order })
);
export const updateAdminOrderFailure = createAction(
  '[Admin Orders] Update Order Failure',
  (error: any) => ({ error })
);

export const payAdminOrder = createAction(
  '[Admin Orders] Pay Order',
  (id: number) => ({ id })
);
export const payAdminOrderSuccess = createAction(
  '[Admin Orders] Pay Order Success',
  (order: AdminOrder) => ({ order })
);
export const payAdminOrderFailure = createAction(
  '[Admin Orders] Pay Order Failure',
  (error: any) => ({ error })
);
