import { ChangeDetectionStrategy, Component, DestroyRef, EventEmitter, inject, Input, OnInit, Output, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Store } from '@ngrx/store';
import { Actions, ofType } from '@ngrx/effects';
import { take } from 'rxjs';
import { RootState } from '../../../../models/root-state';
import { adjustStock, adjustStockFailure, adjustStockSuccess } from '../../store/admin-warehouse-actions';
import { extractErrorMessage } from '../../../shared/utils/extract-error-message';

@Component({
  selector: 'app-adjust-stock-modal',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './adjust-stock-modal.component.html',
  styleUrl: './adjust-stock-modal.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdjustStockModalComponent implements OnInit {
  @Input() productId!: number;
  @Input() warehouseId!: number;
  @Output() closed = new EventEmitter<boolean>();

  private store: Store<RootState> = inject(Store);
  private fb = inject(FormBuilder);
  private actions$ = inject(Actions);
  private destroyRef = inject(DestroyRef);

  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);

  form = this.fb.group({
    quantity: [0, [Validators.required, Validators.pattern(/^-?[1-9][0-9]*$/)]],
    comment: [''],
  });

  ngOnInit(): void {
    this.form.patchValue({ quantity: 0 });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const value = this.form.getRawValue();
    this.error.set(null);
    this.submitting.set(true);

    this.actions$
      .pipe(ofType(adjustStockSuccess, adjustStockFailure), take(1), takeUntilDestroyed(this.destroyRef))
      .subscribe((action) => {
        this.submitting.set(false);

        if (action.type === adjustStockSuccess.type) {
          this.closed.emit(true);
          return;
        }

        this.error.set(extractErrorMessage((action as ReturnType<typeof adjustStockFailure>).error));
      });

    this.store.dispatch(
      adjustStock({
        productId: this.productId,
        warehouseId: this.warehouseId,
        quantity: Number(value.quantity),
        comment: value.comment || null,
      })
    );
  }

  cancel(): void {
    this.closed.emit(false);
  }
}
