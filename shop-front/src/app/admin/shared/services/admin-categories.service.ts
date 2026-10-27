import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environments-local';
import { AdminCategoriesResponse, AdminCategory, CategoryPayload } from '../models/admin-product.model';

@Injectable({
  providedIn: 'root',
})
export class AdminCategoriesService {
  private baseUrl = `${environment.apiUrl}/categories`;
  private http = inject(HttpClient);

  public getAll(): Observable<AdminCategoriesResponse> {
    return this.http.get<AdminCategoriesResponse>(this.baseUrl);
  }

  public getById(id: number): Observable<AdminCategory> {
    return this.http.get<AdminCategory>(`${this.baseUrl}/${id}`);
  }

  public create(payload: CategoryPayload): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(this.baseUrl, payload);
  }

  public update(id: number, payload: CategoryPayload): Observable<{ message: string }> {
    return this.http.put<{ message: string }>(`${this.baseUrl}/${id}`, payload);
  }

  public delete(id: number): Observable<null> {
    return this.http.delete<null>(`${this.baseUrl}/${id}`);
  }
}
