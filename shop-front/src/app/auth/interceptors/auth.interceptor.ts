import { inject } from '@angular/core';
import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environments-local';
import { AuthService } from '../services/auth.service';

/**
 * Attaches the shared JWT (plan 11) to every request hitting either back-end —
 * Laravel (apiUrl) and Symfony warehouse (warehouseApiUrl) — and forces a
 * logout on 401 (expired/invalid token).
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const token = auth.getToken();

  const isApiRequest =
    req.url.startsWith(environment.apiUrl) || req.url.startsWith(environment.warehouseApiUrl);

  const authReq =
    token && isApiRequest
      ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
      : req;

  const isLogout = req.url.startsWith(`${environment.apiUrl}/logout`);

  return next(authReq).pipe(
    catchError((err: HttpErrorResponse) => {
      // Skip the logout call itself to avoid a 401 → logout → 401 loop.
      if (err.status === 401 && !isLogout) {
        auth.logout();
      }
      return throwError(() => err);
    }),
  );
};
