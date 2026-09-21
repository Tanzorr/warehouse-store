import { ChangeDetectionStrategy, Component, DestroyRef, inject, OnInit } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AsyncPipe } from '@angular/common';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { getStockLevels } from '../../store/admin-warehouse-actions';
import { adminWarehouseLoadingSelector, adminWarehouseStockLevelsSelector } from '../../store/admin-warehouse-selectors';
import { StockLevel } from '../../../shared/models/warehouse.model';
import { AdjustStockModalComponent } from '../../modals/adjust-stock-modal/adjust-stock-modal.component';
import { TransferStockModalComponent } from '../../modals/transfer-stock-modal/transfer-stock-modal.component';

@Component({
  selector: 'app-stock-levels',
  standalone: true,
  imports: [AsyncPipe, ReactiveFormsModule, AdjustStockModalComponent, TransferStockModalComponent],
  templateUrl: './stock-levels.component.html',
  styleUrl: './stock-levels.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StockLevelsComponent implements OnInit {
  private store: Store<RootState> = inject(Store);
  private fb = inject(FormBuilder);
  private destroyRef = inject(DestroyRef);

  readonly pageSize = 20;
  currentPage = 1;

  stockLevels$ = this.store.select(adminWarehouseStockLevelsSelector);
  loading$ = this.store.select(adminWarehouseLoadingSelector);

  filterForm = this.fb.group({
    search: [''],
    warehouseId: [null as number | null],
    lowStockOnly: [false],
  });

  adjustTarget: StockLevel | null = null;
  transferTarget: StockLevel | null = null;
  allStockLevels: StockLevel[] = [];

  ngOnInit(): void {
    this.refresh();
    this.stockLevels$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((levels) => {
      if (levels.length) {
        this.allStockLevels = levels;
      }
      // Keep the current page within bounds when the dataset changes.
      this.currentPage = Math.min(this.currentPage, this.totalPages(levels));
    });
  }

  refresh(): void {
    this.currentPage = 1;
    this.store.dispatch(getStockLevels());
  }

  applyFilters(): void {
    const value = this.filterForm.getRawValue();
    const params = new URLSearchParams();

    if (value.search) {
      params.set('search', value.search);
    }
    if (value.warehouseId) {
      params.set('warehouse_id', String(value.warehouseId));
    }
    if (value.lowStockOnly) {
      params.set('low_stock_only', '1');
    }

    this.currentPage = 1;
    this.store.dispatch(getStockLevels(params.toString() || undefined));
  }

  pagedLevels(levels: StockLevel[]): StockLevel[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return levels.slice(start, start + this.pageSize);
  }

  totalPages(levels: StockLevel[]): number {
    return Math.max(1, Math.ceil(levels.length / this.pageSize));
  }

  goToPage(page: number, total: number): void {
    this.currentPage = Math.min(Math.max(1, page), total);
  }

  uniqueWarehouses(levels: StockLevel[]): { id: number; name: string }[] {
    const map = new Map<number, string>();
    levels.forEach((l) => map.set(l.warehouseId, l.warehouseName));

    return Array.from(map.entries()).map(([id, name]) => ({ id, name }));
  }

  openAdjust(level: StockLevel): void {
    this.adjustTarget = level;
  }

  closeAdjust(success: boolean): void {
    this.adjustTarget = null;

    if (success) {
      this.refresh();
    }
  }

  openTransfer(level: StockLevel): void {
    this.transferTarget = level;
  }

  closeTransfer(success: boolean): void {
    this.transferTarget = null;

    if (success) {
      this.refresh();
    }
  }
}
