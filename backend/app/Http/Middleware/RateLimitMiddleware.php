<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (Cache::has($key)) {
            $attempts = Cache::get($key);
            if ($attempts >= $maxAttempts) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $decayMinutes * 60,
                ], 429);
            }
            Cache::increment($key);
        } else {
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
        }

        $response = $next($request);

        $remaining = $maxAttempts - Cache::get($key, 0);
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));

        return $response;
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $apiKey = $request->attributes->get('api_key');
        if ($apiKey) {
            return 'rate_limit:api_key:' . $apiKey->id;
        }

        return 'rate_limit:ip:' . $request->ip();
    }
}

