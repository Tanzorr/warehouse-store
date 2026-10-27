import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environments-local';
import { LocalStorageService } from '../../services/storage/local-storage.service';
import { User } from '../../models/user';
import { LoginCredentials, LoginResponse } from '../models/auth.model';

/**
 * Cross-cutting auth layer (plan 10): logs in against Laravel, persists the
 * shared JWT + user in localStorage and exposes the helpers the guard and
 * interceptor rely on. No NgRx store — auth is infrastructural, not a domain
 * command/query.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly storage = inject(LocalStorageService);
  private readonly router = inject(Router);

  private readonly TOKEN_KEY = 'authToken';
  private readonly USER_KEY = 'user';

  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${environment.apiUrl}/login`, credentials).pipe(
      tap(({ authToken, loggedUser }) => {
        this.storage.set(this.TOKEN_KEY, authToken);
        this.storage.set(this.USER_KEY, JSON.stringify(loggedUser));
      }),
    );
  }

  logout(): void {
    this.http.post(`${environment.apiUrl}/logout`, {}).subscribe({
      complete: () => this.clearSession(),
      error: () => this.clearSession(), // token already invalid — clear anyway
    });
  }

  getToken(): string | null {
    return this.storage.get(this.TOKEN_KEY);
  }

  getUser(): User | null {
    const raw = this.storage.get(this.USER_KEY);
    return raw ? (JSON.parse(raw) as User) : null;
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  isAdmin(): boolean {
    return this.getUser()?.role === 'admin';
  }

  private clearSession(): void {
    this.storage.delete(this.TOKEN_KEY);
    this.storage.delete(this.USER_KEY);
    this.router.navigate(['/login']);
  }
}
