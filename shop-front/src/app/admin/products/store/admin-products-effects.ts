import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { catchError, exhaustMap, map, of, switchMap } from 'rxjs';
import { AdminProductsService } from '../../shared/services/admin-products.service';
import {
  createAdminProduct,
  createAdminProductFailure,
  createAdminProductSuccess,
  deleteAdminProduct,
  deleteAdminProductFailure,
  deleteAdminProductSuccess,
  getAdminProduct,
  getAdminProductFailure,
  getAdminProductSuccess,
  getAdminProducts,
  getAdminProductsFailure,
  getAdminProductsSuccess,
  updateAdminProduct,
  updateAdminProductFailure,
  updateAdminProductSuccess,
} from './admin-products-actions';

@Injectable()
export class AdminProductsEffects {
  private actions$ = inject(Actions);
  private productsService = inject(AdminProductsService);

  getProducts$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getAdminProducts),
      exhaustMap(({ params }) =>
        this.productsService.getAll(params).pipe(
          map((response) => getAdminProductsSuccess(response)),
          catchError((error) => of(getAdminProductsFailure(error)))
        )
      )
    )
  );

  getProduct$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getAdminProduct),
      switchMap(({ id }) =>
        this.productsService.getById(id).pipe(
          map((product) => getAdminProductSuccess(product)),
          catchError((error) => of(getAdminProductFailure(error)))
        )
      )
    )
  );

  createProduct$ = createEffect(() =>
    this.actions$.pipe(
      ofType(createAdminProduct),
      switchMap(({ payload }) =>
        this.productsService.create(payload).pipe(
          map((product) => createAdminProductSuccess(product)),
          catchError((error) => of(createAdminProductFailure(error)))
        )
      )
    )
  );

  updateProduct$ = createEffect(() =>
    this.actions$.pipe(
      ofType(updateAdminProduct),
      switchMap(({ id, payload }) =>
        this.productsService.update(id, payload).pipe(
          map((product) => updateAdminProductSuccess(product)),
          catchError((error) => of(updateAdminProductFailure(error)))
        )
      )
    )
  );

  deleteProduct$ = createEffect(() =>
    this.actions$.pipe(
      ofType(deleteAdminProduct),
      switchMap(({ id }) =>
        this.productsService.delete(id).pipe(
          map(() => deleteAdminProductSuccess(id)),
          catchError((error) => of(deleteAdminProductFailure(error)))
        )
      )
    )
  );
}
