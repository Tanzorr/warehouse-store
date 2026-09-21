import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { catchError, exhaustMap, map, of, switchMap } from 'rxjs';
import { WarehouseService } from '../../shared/services/warehouse.service';
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

@Injectable()
export class AdminWarehouseEffects {
  private actions$ = inject(Actions);
  private warehouseService = inject(WarehouseService);

  getStockLevels$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getStockLevels),
      exhaustMap(({ params }) =>
        this.warehouseService.getStockLevels(params).pipe(
          map((stockLevels) => getStockLevelsSuccess(stockLevels)),
          catchError((error) => of(getStockLevelsFailure(error)))
        )
      )
    )
  );

  getReservations$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getReservations),
      exhaustMap(({ params }) =>
        this.warehouseService.getReservations(params).pipe(
          map((response) => getReservationsSuccess(response)),
          catchError((error) => of(getReservationsFailure(error)))
        )
      )
    )
  );

  getTransactions$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getTransactions),
      exhaustMap(({ params }) =>
        this.warehouseService.getTransactions(params).pipe(
          map((response) => getTransactionsSuccess(response)),
          catchError((error) => of(getTransactionsFailure(error)))
        )
      )
    )
  );

  getWarehouseSummary$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getWarehouseSummary),
      switchMap(({ warehouseId }) =>
        this.warehouseService.getWarehouseSummary(warehouseId).pipe(
          map((summary) => getWarehouseSummarySuccess(summary)),
          catchError((error) => of(getWarehouseSummaryFailure(error)))
        )
      )
    )
  );

  cancelReservation$ = createEffect(() =>
    this.actions$.pipe(
      ofType(cancelReservation),
      switchMap(({ id, payload }) =>
        this.warehouseService.cancelReservation(id, payload).pipe(
          map(() => cancelReservationSuccess()),
          catchError((error) => of(cancelReservationFailure(error)))
        )
      )
    )
  );

  adjustStock$ = createEffect(() =>
    this.actions$.pipe(
      ofType(adjustStock),
      switchMap(({ payload }) =>
        this.warehouseService.adjustStock(payload).pipe(
          map(() => adjustStockSuccess()),
          catchError((error) => of(adjustStockFailure(error)))
        )
      )
    )
  );

  transferStock$ = createEffect(() =>
    this.actions$.pipe(
      ofType(transferStock),
      switchMap(({ payload }) =>
        this.warehouseService.transferStock(payload).pipe(
          map(() => transferStockSuccess()),
          catchError((error) => of(transferStockFailure(error)))
        )
      )
    )
  );
}
