import { ChangeDetectionStrategy, Component, inject, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { deleteAdminCategory, getAdminCategories } from '../../store/admin-categories-actions';
import { adminCategoriesResponseSelector, adminCategoriesLoadingSelector } from '../../store/admin-categories-selectors';

@Component({
  selector: 'app-admin-category-list',
  standalone: true,
  imports: [AsyncPipe, RouterLink],
  templateUrl: './category-list.component.html',
  styleUrl: './category-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CategoryListComponent implements OnInit {
  private store: Store<RootState> = inject(Store);

  categoriesResponse$ = this.store.select(adminCategoriesResponseSelector);
  loading$ = this.store.select(adminCategoriesLoadingSelector);

  ngOnInit(): void {
    this.store.dispatch(getAdminCategories());
  }

  remove(id: number): void {
    if (confirm('Delete this category?')) {
      this.store.dispatch(deleteAdminCategory(id));
    }
  }
}
