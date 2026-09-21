<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Cache;

class RateLimitJob
{
    private int $deley = 5;

    public function handle($job, $next)
    {
        $key = 'job-rate-limit'.get_class($job);
        $lock = Cache::lock($key, $this->deley);

        if ($lock->get()) {
            try {
                $next($job);
            } finally {
                $lock->release();
            }
        } else {
            $job->release($this->deley);
        }
    }
}
