import { ChangeDetectionStrategy, Component, DestroyRef, EventEmitter, inject, Input, Output, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Store } from '@ngrx/store';
import { Actions, ofType } from '@ngrx/effects';
import { take } from 'rxjs';
import { RootState } from '../../../../models/root-state';
import { transferStock, transferStockFailure, transferStockSuccess } from '../../store/admin-warehouse-actions';
import { StockLevel } from '../../../shared/models/warehouse.model';
import { extractErrorMessage } from '../../../shared/utils/extract-error-message';

@Component({
  selector: 'app-transfer-stock-modal',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './transfer-stock-modal.component.html',
  styleUrl: './transfer-stock-modal.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TransferStockModalComponent {
  @Input() productId!: number;
  @Input() fromWarehouseId!: number;
  @Input() warehouses: StockLevel[] = [];
  @Output() closed = new EventEmitter<boolean>();

  private store: Store<RootState> = inject(Store);
  private fb = inject(FormBuilder);
  private actions$ = inject(Actions);
  private destroyRef = inject(DestroyRef);

  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);

  form = this.fb.group({
    toWarehouseId: [null as number | null, Validators.required],
    quantity: [1, [Validators.required, Validators.min(1)]],
    comment: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const value = this.form.getRawValue();
    this.error.set(null);
    this.submitting.set(true);

    this.actions$
      .pipe(ofType(transferStockSuccess, transferStockFailure), take(1), takeUntilDestroyed(this.destroyRef))
      .subscribe((action) => {
        this.submitting.set(false);

        if (action.type === transferStockSuccess.type) {
          this.closed.emit(true);
          return;
        }

        this.error.set(extractErrorMessage((action as ReturnType<typeof transferStockFailure>).error));
      });

    this.store.dispatch(
      transferStock({
        productId: this.productId,
        fromWarehouseId: this.fromWarehouseId,
        toWarehouseId: Number(value.toWarehouseId),
        quantity: Number(value.quantity),
        comment: value.comment || null,
      })
    );
  }

  cancel(): void {
    this.closed.emit(false);
  }
}
