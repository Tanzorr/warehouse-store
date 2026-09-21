import {DestroyRef, inject, Injectable} from '@angular/core';
import {Store} from '@ngrx/store';
import {RootState} from '../../../../models/root-state';
import {productSelector} from '../../../../store/product/product-reduce-selectors';
import {getProduct} from '../../../../store/product/product-actions';
import {ActivatedRoute} from '@angular/router';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';

@Injectable({
  providedIn: 'root',
})
export class ProductService {
  private _destroyRef = inject(DestroyRef);
  private _store: Store<RootState> = inject(Store);
  private _route: ActivatedRoute = inject(ActivatedRoute);
  public product$ = this._store.select(productSelector);

  getProduct(productId: number): void {
    if (productId) {
      this._store.dispatch(getProduct(productId));
    } else {
      console.error('Product ID is null or undefined');
    }

  }
}
