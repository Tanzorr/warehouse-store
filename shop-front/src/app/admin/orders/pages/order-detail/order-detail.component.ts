import { ChangeDetectionStrategy, Component, inject, Input, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { getAdminOrder, payAdminOrder, updateAdminOrder } from '../../store/admin-orders-actions';
import { adminSelectedOrderSelector } from '../../store/admin-orders-selectors';
import { ORDER_STATUSES } from '../../../shared/models/admin-order.model';

@Component({
  selector: 'app-order-detail',
  standalone: true,
  imports: [AsyncPipe, RouterLink, ReactiveFormsModule],
  templateUrl: './order-detail.component.html',
  styleUrl: './order-detail.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class OrderDetailComponent implements OnInit {
  @Input() id!: string;

  private store: Store<RootState> = inject(Store);
  private fb = inject(FormBuilder);

  order$ = this.store.select(adminSelectedOrderSelector);
  orderStatuses = ORDER_STATUSES;

  form = this.fb.group({
    status: [''],
    shipping_address: [''],
    billing_address: [''],
    notes: [''],
  });

  ngOnInit(): void {
    this.store.dispatch(getAdminOrder(Number(this.id)));

    this.order$.subscribe((order) => {
      if (order) {
        this.form.patchValue({
          status: order.status,
          shipping_address: order.shipping_address,
          billing_address: order.billing_address ?? '',
          notes: order.notes ?? '',
        });
      }
    });
  }

  save(): void {
    const value = this.form.getRawValue();
    this.store.dispatch(
      updateAdminOrder(Number(this.id), {
        status: value.status as any,
        shipping_address: value.shipping_address ?? undefined,
        billing_address: value.billing_address ?? null,
        notes: value.notes ?? null,
      })
    );
  }

  pay(): void {
    this.store.dispatch(payAdminOrder(Number(this.id)));
  }
}
