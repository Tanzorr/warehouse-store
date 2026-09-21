import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {environment} from '../../../environments/environments-local';
import {Observable} from 'rxjs';
import {CategoryResponse} from '../../models/category';
import {PageQueryParams} from '../../models/pagination';

@Injectable({
  providedIn: 'root'
})
export class CategoriesService {
  private basUrl = `${environment.apiUrl}/categories`;
  private http = inject(HttpClient);

  public getAll(params?: PageQueryParams): Observable<CategoryResponse> {
    return this.http.get<CategoryResponse>(`${this.basUrl}?${params ? params : ''}`);
  }
}
