import { ChangeDetectionStrategy, Component, inject, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { deleteAdminProduct, getAdminProducts } from '../../store/admin-products-actions';
import { adminProductsResponseSelector, adminProductsLoadingSelector } from '../../store/admin-products-selectors';

@Component({
  selector: 'app-admin-product-list',
  standalone: true,
  imports: [AsyncPipe, RouterLink],
  templateUrl: './product-list.component.html',
  styleUrl: './product-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductListComponent implements OnInit {
  private store: Store<RootState> = inject(Store);

  productsResponse$ = this.store.select(adminProductsResponseSelector);
  loading$ = this.store.select(adminProductsLoadingSelector);

  ngOnInit(): void {
    this.store.dispatch(getAdminProducts());
  }

  changePage(page: number): void {
    this.store.dispatch(getAdminProducts(`page=${page}`));
  }

  pageNumbers(lastPage: number): number[] {
    return Array.from({ length: lastPage }, (_, i) => i + 1);
  }

  remove(id: number): void {
    if (confirm('Delete this product?')) {
      this.store.dispatch(deleteAdminProduct(id));
    }
  }
}
