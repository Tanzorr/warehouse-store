import {DestroyRef, inject, Injectable, signal} from '@angular/core';
import {Store} from '@ngrx/store';
import {getProducts} from '../../../../store/products/products-actions';
import {productsResponseSelector} from '../../../../store/products/products-response-selectors';
import {PageQueryParams} from '../../../../models/pagination';
import {selectedCategoryIdsSelector} from '../../../../store/categories/categories-response-selector';
import {takeUntilDestroyed} from '@angular/core/rxjs-interop';
import {RootState} from '../../../../models/root-state';

@Injectable({
  providedIn: 'root'
})
export class ProductsService {
  private  _destroyRef = inject(DestroyRef);
  private _store: Store<RootState> = inject(Store);
  public productsResponse$ = this._store.select(productsResponseSelector);
  public selectedProductIds$ = this._store.select(selectedCategoryIdsSelector);
  public selectedCategoriesIds = signal<number[]>([]);
  constructor() {
    this.selectedProductIds$
      .pipe(takeUntilDestroyed(this._destroyRef))
      .subscribe((selectedCategoriesIds) => {
        this.getProducts({category_ids: selectedCategoriesIds});
        this.selectedCategoriesIds.set(selectedCategoriesIds);
      });
  }
  public getProducts(pageQueryParams: PageQueryParams) {

    const stringParams = Object.entries(pageQueryParams)
      .map(([key, val]) => `${key}=${val}`)
      .join('&');
    this._store.dispatch(getProducts(stringParams))
  }

  public searchValue(searchValue: string | null): void {
    const params = {search: searchValue, category_ids: this.selectedCategoriesIds()};
    this.getProducts(params);
  }
  public changePage(url: string | null): void {
    if(url) {
      const urlArr = url.toString().trim().split('=');
      const pageNumber = urlArr[urlArr.length - 1];
      this.getProducts({page: pageNumber,  category_ids:this.selectedCategoriesIds()})
    }
  }
}
