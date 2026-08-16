<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\RateLimiter;

/**
 * LogThrottleAttempts Middleware
 * 
 * Logs attempts that exceed rate limiting to audit channel.
 * Helps detect abuse patterns and suspicious activity.
 */
class LogThrottleAttempts
{
    public function __construct(private RateLimiter $rateLimiter)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Check if request was throttled (HTTP 429)
        if ($response->status() === 429) {
            Log::channel('audit')->warning('Rate limit exceeded (throttle violation)', [
                'user_id'    => auth()->id(),
                'ip_address' => $request->ip(),
                'path'       => $request->path(),
                'method'     => $request->method(),
                'user_agent' => $request->userAgent(),
                'timestamp'  => now()->toIso8601String(),
            ]);
        }

        return $response;
    }
}
