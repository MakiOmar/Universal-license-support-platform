<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('X-API-Key', '');
        $auth = (string) $request->header('Authorization', '');

        $token = $header;

        if (! $token && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
        }

        if (! $token) {
            return response()->json(['message' => 'API key required'], 401);
        }

        $apiKey = ApiKey::where('api_key', $token)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid or expired API key'], 401);
        }

        // You can optionally update last_used_at for analytics
        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        // Make api key available on the request for controllers if needed
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_customer_id', $apiKey->customer_id);
        $request->attributes->set('api_product_id', $apiKey->product_id);

        return $next($request);
    }
}


