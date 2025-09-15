<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResponseTimeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000; // ms

        // Store average response time in cache
        $key = "response_time";
        $avg = Cache::get($key, []);
        $avg[] = $duration;

        if (count($avg) > 100) {
            array_shift($avg); // keep last 100 samples
        }

        Cache::put($key, $avg, now()->addMinutes(5));

        return $response;
    }
}
