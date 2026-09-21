import {computed, inject, Injectable, signal} from '@angular/core';
import {CategoriesState} from '../../../../store/categories/categories-reducers';
import {Store} from '@ngrx/store';
import {getCategories, setSelectedCategoryIds} from '../../../../store/categories/categories-actions';
import {
  categoriesListSelector,
} from '../../../../store/categories/categories-response-selector';
import {RootState} from '../../../../models/root-state';


@Injectable({
  providedIn: 'root'
})
export class CategoriesService {
  private store:Store<RootState> =inject(Store);
  public categories$ = this.store.select(categoriesListSelector);
  public selectedCategoriesIds = signal<number[]>([]);
  readonly selectedCategoryIds$ = computed(() => this.selectedCategoriesIds());
  public getCategories() {
    this.store.dispatch(getCategories());
  }

  public setSelectedCategoryIds(selectedCategoryIds: number[]) {
    this.store.dispatch(setSelectedCategoryIds(selectedCategoryIds))
  }

 public toggleCategory(categoryId: number): void {
    const currentSelection = this.selectedCategoriesIds();
    if (currentSelection.includes(categoryId)) {
      this.selectedCategoriesIds.set(currentSelection.filter(id => id !== categoryId));
    } else {
      this.selectedCategoriesIds.set([...currentSelection, categoryId]);
    }

    this.setSelectedCategoryIds(this.selectedCategoriesIds());
  }
}
