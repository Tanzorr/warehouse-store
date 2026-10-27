import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { catchError, exhaustMap, map, of, switchMap } from 'rxjs';
import { AdminCategoriesService } from '../../shared/services/admin-categories.service';
import {
  createAdminCategory,
  createAdminCategoryFailure,
  createAdminCategorySuccess,
  deleteAdminCategory,
  deleteAdminCategoryFailure,
  deleteAdminCategorySuccess,
  getAdminCategories,
  getAdminCategoriesFailure,
  getAdminCategoriesSuccess,
  getAdminCategory,
  getAdminCategoryFailure,
  getAdminCategorySuccess,
  updateAdminCategory,
  updateAdminCategoryFailure,
  updateAdminCategorySuccess,
} from './admin-categories-actions';

@Injectable()
export class AdminCategoriesEffects {
  private actions$ = inject(Actions);
  private categoriesService = inject(AdminCategoriesService);

  getCategories$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getAdminCategories),
      exhaustMap(() =>
        this.categoriesService.getAll().pipe(
          map((response) => getAdminCategoriesSuccess(response)),
          catchError((error) => of(getAdminCategoriesFailure(error)))
        )
      )
    )
  );

  getCategory$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getAdminCategory),
      switchMap(({ id }) =>
        this.categoriesService.getById(id).pipe(
          map((category) => getAdminCategorySuccess(category)),
          catchError((error) => of(getAdminCategoryFailure(error)))
        )
      )
    )
  );

  createCategory$ = createEffect(() =>
    this.actions$.pipe(
      ofType(createAdminCategory),
      switchMap(({ payload }) =>
        this.categoriesService.create(payload).pipe(
          map(() => createAdminCategorySuccess()),
          catchError((error) => of(createAdminCategoryFailure(error)))
        )
      )
    )
  );

  updateCategory$ = createEffect(() =>
    this.actions$.pipe(
      ofType(updateAdminCategory),
      switchMap(({ id, payload }) =>
        this.categoriesService.update(id, payload).pipe(
          map(() => updateAdminCategorySuccess()),
          catchError((error) => of(updateAdminCategoryFailure(error)))
        )
      )
    )
  );

  deleteCategory$ = createEffect(() =>
    this.actions$.pipe(
      ofType(deleteAdminCategory),
      switchMap(({ id }) =>
        this.categoriesService.delete(id).pipe(
          map(() => deleteAdminCategorySuccess(id)),
          catchError((error) => of(deleteAdminCategoryFailure(error)))
        )
      )
    )
  );
}
