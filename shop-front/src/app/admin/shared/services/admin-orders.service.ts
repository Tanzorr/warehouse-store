import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environments-local';
import { AdminOrder, UpdateOrderPayload } from '../models/admin-order.model';

@Injectable({
  providedIn: 'root',
})
export class AdminOrdersService {
  private baseUrl = `${environment.apiUrl}/orders`;
  private http = inject(HttpClient);

  public getAll(): Observable<AdminOrder[]> {
    return this.http.get<AdminOrder[]>(this.baseUrl);
  }

  public getById(id: number): Observable<AdminOrder> {
    return this.http.get<AdminOrder>(`${this.baseUrl}/${id}`);
  }

  public update(id: number, payload: UpdateOrderPayload): Observable<AdminOrder> {
    return this.http.put<AdminOrder>(`${this.baseUrl}/${id}`, payload);
  }

  public pay(id: number): Observable<{ message: string; order: AdminOrder }> {
    return this.http.post<{ message: string; order: AdminOrder }>(`${this.baseUrl}/${id}/pay`, {});
  }
}
