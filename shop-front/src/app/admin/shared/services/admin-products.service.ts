import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environments-local';
import { AdminProduct, AdminProductsResponse, ProductPayload } from '../models/admin-product.model';

@Injectable({
  providedIn: 'root',
})
export class AdminProductsService {
  private baseUrl = `${environment.apiUrl}/products`;
  private http = inject(HttpClient);

  public getAll(params?: string): Observable<AdminProductsResponse> {
    return this.http.get<AdminProductsResponse>(`${this.baseUrl}${params ? `?${params}` : ''}`);
  }

  public getById(id: number): Observable<AdminProduct> {
    return this.http.get<AdminProduct>(`${this.baseUrl}/${id}`);
  }

  public create(payload: ProductPayload): Observable<AdminProduct> {
    return this.http.post<AdminProduct>(this.baseUrl, payload);
  }

  public update(id: number, payload: ProductPayload): Observable<AdminProduct> {
    return this.http.put<AdminProduct>(`${this.baseUrl}/${id}`, payload);
  }

  public delete(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.baseUrl}/${id}`);
  }
}
