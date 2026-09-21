import {Actions, createEffect, ofType} from '@ngrx/effects';
import {catchError, map, of, switchMap} from 'rxjs';
import {CategoriesService} from '../../services/api/categories.service';
import {getCategories, getCategoriesFailure, getCategoriesSuccess} from './categories-actions';
import {inject, Injectable} from '@angular/core';

@Injectable()
export class CategoriesEffects {
  private  actions$: Actions = inject(Actions);
  private categoriesService = inject(CategoriesService);


  loadCategories$ = createEffect(() =>
    this.actions$.pipe(
      ofType(getCategories),
      switchMap(() =>
        this.categoriesService.getAll().pipe(
          map(categories => getCategoriesSuccess(categories)),
          catchError(error => of(getCategoriesFailure({ error })))
        )
      )
    )
  );
}
