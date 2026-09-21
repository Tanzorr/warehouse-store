import { ChangeDetectionStrategy, Component, inject, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { getTransactions } from '../../store/admin-warehouse-actions';
import { adminWarehouseLoadingSelector, adminWarehouseTransactionsSelector } from '../../store/admin-warehouse-selectors';

const PER_PAGE = 50;

@Component({
  selector: 'app-transactions',
  standalone: true,
  imports: [AsyncPipe],
  templateUrl: './transactions.component.html',
  styleUrl: './transactions.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TransactionsComponent implements OnInit {
  private store: Store<RootState> = inject(Store);

  transactions$ = this.store.select(adminWarehouseTransactionsSelector);
  loading$ = this.store.select(adminWarehouseLoadingSelector);
  currentPage = 1;

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.store.dispatch(getTransactions(`page=${this.currentPage}&per_page=${PER_PAGE}`));
  }

  changePage(page: number): void {
    this.currentPage = page;
    this.load();
  }

  pageNumbers(total: number): number[] {
    const lastPage = Math.max(1, Math.ceil(total / PER_PAGE));
    return Array.from({ length: lastPage }, (_, i) => i + 1);
  }
}
