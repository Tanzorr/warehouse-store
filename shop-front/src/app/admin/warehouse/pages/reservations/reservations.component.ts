import { ChangeDetectionStrategy, Component, inject, OnInit } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { Store } from '@ngrx/store';
import { RootState } from '../../../../models/root-state';
import { cancelReservation, getReservations } from '../../store/admin-warehouse-actions';
import { adminWarehouseLoadingSelector, adminWarehouseReservationsSelector } from '../../store/admin-warehouse-selectors';

@Component({
  selector: 'app-reservations',
  standalone: true,
  imports: [AsyncPipe],
  templateUrl: './reservations.component.html',
  styleUrl: './reservations.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ReservationsComponent implements OnInit {
  private store: Store<RootState> = inject(Store);

  reservations$ = this.store.select(adminWarehouseReservationsSelector);
  loading$ = this.store.select(adminWarehouseLoadingSelector);

  ngOnInit(): void {
    this.store.dispatch(getReservations());
  }

  cancel(id: number): void {
    if (confirm('Cancel this reservation?')) {
      this.store.dispatch(cancelReservation(id, {}));
      this.store.dispatch(getReservations());
    }
  }
}
