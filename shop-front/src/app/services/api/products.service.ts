import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {Observable} from 'rxjs';
import {ProductsResponse} from '../../models/products';
import {environment} from '../../../environments/environments-local';

@Injectable({
  providedIn: 'root'
})
export class ProductsService {
  private basUrl = `${environment.apiUrl}/products`;
  private http = inject(HttpClient);

  public getAll(params?:string): Observable<ProductsResponse> {
    return this.http.get<ProductsResponse>(`${this.basUrl}?${params ? params : ''}`);
  }

  public getProductById(id: number): Observable<any> {
    return this.http.get<any>(`${this.basUrl}/${id}`);
  }
}
