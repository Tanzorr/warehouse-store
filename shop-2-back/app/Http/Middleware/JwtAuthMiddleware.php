<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Validate the shared HS256 JWT and resolve the authenticated user.
     *
     * Replaces auth:sanctum on the API routes: the token is verified locally
     * with the shared secret (no DB token lookup) and the matching user is
     * placed on the request so downstream middleware (e.g. RoleMiddleware) and
     * controllers keep using $request->user() unchanged.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $payload = JWT::decode($token, new Key(config('app.jwt_shared_secret'), 'HS256'));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = User::find($payload->sub ?? null);

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('jwt', $payload);

        return $next($request);
    }
}
