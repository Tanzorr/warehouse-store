import { HttpErrorResponse } from '@angular/common/http';

/**
 * Pulls a human-readable message out of a failed warehouse command. The Symfony
 * API returns `{ error: '...' }` on 422, so prefer that, then fall back to the
 * transport-level message.
 */
export function extractErrorMessage(error: unknown): string {
  if (error instanceof HttpErrorResponse) {
    return error.error?.error ?? error.error?.message ?? error.message;
  }

  return 'Something went wrong. Please try again.';
}
