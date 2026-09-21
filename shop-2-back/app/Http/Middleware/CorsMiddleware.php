<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isMethod('OPTIONS, HEAD, GET, POST, PUT, DELETE')) {
            return response()->json([], 200);
        }

        //  cors config should be taken from config files, so you can have control over in in entire app.
        //                $response->headers->set('Access-Control-Allow-Origin', '*');
        //                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        //                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $next($request);
    }
}
