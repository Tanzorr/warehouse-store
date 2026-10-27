import {inject, Injectable} from '@angular/core';
import {Actions, createEffect, ofType} from '@ngrx/effects';
import {ProductsService} from '../../services/api/products.service';
import {catchError, exhaustMap,  map, of, } from 'rxjs';
import {getProduct, getProductFailure, getProductSuccess} from './product-actions';
import {Store} from '@ngrx/store';
import {RootState} from '../../models/root-state';


@Injectable()
export class ProductEffects {
  private _actions$ = inject(Actions);
  private _productsService = inject(ProductsService);
  private _store = inject(Store<RootState>);


  getProduct$ = createEffect(() => {
    return this._actions$.pipe(
      ofType(getProduct),
      exhaustMap((action) => {
        return this._productsService.getProductById(action.productId).pipe(
            map(product => getProductSuccess(product)),
            catchError(error => of(getProductFailure(error)))
          )
        }
      )
    );
  });
}
