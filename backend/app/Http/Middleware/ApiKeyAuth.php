<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');

        if (! $key) {
            return response()->json(['message' => 'API key required.'], 401);
        }

        $apiKey = ApiKey::where('key', $key)->first();

        if (! $apiKey || ! $apiKey->isActive()) {
            return response()->json(['message' => 'Invalid or inactive API key.'], 401);
        }

        $rateLimit = (int) ($apiKey->rate_limit ?: 1000);
        $cacheKey = 'api_key_rate:'.$apiKey->id.':'.now()->format('YmdH');
        $hits = (int) Cache::get($cacheKey, 0);

        if ($hits >= $rateLimit) {
            return response()->json([
                'message' => 'API key rate limit exceeded.',
            ], 429);
        }

        Cache::put($cacheKey, $hits + 1, now()->addHours(2));

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
