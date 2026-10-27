import { ChangeDetectionStrategy, Component, inject, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { getAdminOrders } from '../../store/admin-orders-actions';
import {
  adminOrdersLoadingSelector,
  adminOrdersSelector,
} from '../../store/admin-orders-selectors';
import { PAYMENT_STATUSES, PaymentStatus } from '../../../shared/models/admin-order.model';

@Component({
  selector: 'app-order-list',
  standalone: true,
  imports: [AsyncPipe, RouterLink],
  templateUrl: './order-list.component.html',
  styleUrl: './order-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class OrderListComponent implements OnInit {
  private store: Store<RootState> = inject(Store);

  orders$ = this.store.select(adminOrdersSelector);
  loading$ = this.store.select(adminOrdersLoadingSelector);

  paymentStatuses = PAYMENT_STATUSES;
  selectedStatus: PaymentStatus | null = null;

  ngOnInit(): void {
    this.store.dispatch(getAdminOrders());
  }

  filterByStatus(status: PaymentStatus | null): void {
    this.selectedStatus = status;
  }
}
