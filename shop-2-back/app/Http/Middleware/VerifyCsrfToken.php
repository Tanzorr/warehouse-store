<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken
{
    private $except = [

    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if ($this->isReading($request) || $this->runningUnitTests() || $this->inExceptArray($request)) {
            return $next($request);
        }

        return $next($request);
    }

    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

    protected function isReading($request): bool
    {
        return in_array($request->method(), ['HEAD', 'OPTIONS']);
    }

    protected function runningUnitTests(): bool
    {
        return app()->runningUnitTests();
    }
}
