import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { catchError, exhaustMap, map, of, switchMap } from 'rxjs';
import { AdminOrdersService } from '../../shared/services/admin-orders.service';
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

@Injectable()
export class AdminOrdersEffects {
  private actions$ = inject(Actions);
  private ordersService = inject(AdminOrdersService);

  getOrders$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getAdminOrders),
      exhaustMap(() =>
        this.ordersService.getAll().pipe(
          map((orders) => getAdminOrdersSuccess(orders)),
          catchError((error) => of(getAdminOrdersFailure(error)))
        )
      )
    )
  );

  getOrder$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getAdminOrder),
      switchMap(({ id }) =>
        this.ordersService.getById(id).pipe(
          map((order) => getAdminOrderSuccess(order)),
          catchError((error) => of(getAdminOrderFailure(error)))
        )
      )
    )
  );

  updateOrder$ = createEffect(() =>
    this.actions$.pipe(
      ofType(updateAdminOrder),
      switchMap(({ id, payload }) =>
        this.ordersService.update(id, payload).pipe(
          map((order) => updateAdminOrderSuccess(order)),
          catchError((error) => of(updateAdminOrderFailure(error)))
        )
      )
    )
  );

  payOrder$ = createEffect(() =>
    this.actions$.pipe(
      ofType(payAdminOrder),
      switchMap(({ id }) =>
        this.ordersService.pay(id).pipe(
          map((response) => payAdminOrderSuccess(response.order)),
          catchError((error) => of(payAdminOrderFailure(error)))
        )
      )
    )
  );
}
