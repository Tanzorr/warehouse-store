import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environments-local';
import {
  AdjustStockPayload,
  CancelReservationPayload,
  ReservationsResponse,
  StockLevel,
  TransactionsResponse,
  TransferStockPayload,
  WarehouseSummary,
} from '../models/warehouse.model';

@Injectable({
  providedIn: 'root',
})
export class WarehouseService {
  private baseUrl = environment.warehouseApiUrl;
  private http = inject(HttpClient);

  public getStockLevels(params?: string): Observable<StockLevel[]> {
    return this.http.get<StockLevel[]>(`${this.baseUrl}/stock${params ? `?${params}` : ''}`);
  }

  public getReservations(params?: string): Observable<ReservationsResponse> {
    return this.http.get<ReservationsResponse>(`${this.baseUrl}/reservations${params ? `?${params}` : ''}`);
  }

  public getTransactions(params?: string): Observable<TransactionsResponse> {
    return this.http.get<TransactionsResponse>(`${this.baseUrl}/stock/transactions${params ? `?${params}` : ''}`);
  }

  public getWarehouseSummary(warehouseId: number): Observable<WarehouseSummary> {
    return this.http.get<WarehouseSummary>(`${this.baseUrl}/warehouses/${warehouseId}/summary`);
  }

  public cancelReservation(id: number, payload: CancelReservationPayload): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.baseUrl}/reservations/${id}/cancel`, payload);
  }

  public adjustStock(payload: AdjustStockPayload): Observable<{ productId: number; warehouseId: number; amount: number }> {
    return this.http.post<{ productId: number; warehouseId: number; amount: number }>(`${this.baseUrl}/stock/adjust`, payload);
  }

  public transferStock(payload: TransferStockPayload): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.baseUrl}/stock/transfer`, payload);
  }
}
