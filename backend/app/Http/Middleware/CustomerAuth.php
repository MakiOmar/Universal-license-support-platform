<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuth
{
    /**
     * Handle an incoming request.
     *
     * Validates customer token from Authorization header
     * and attaches the authenticated customer to the request.
     * 
     * Security considerations:
     * - Validates token from cache (can be moved to database in production)
     * - Checks customer status before allowing access
     * - Uses secure token extraction
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated. Token required.',
            ], 401);
        }

        // Performance: Use cache for token lookup (O(1) lookup)
        // In production, consider using database with indexes for better scalability
        $customerId = \Illuminate\Support\Facades\Cache::get("customer_token_{$token}");

        if (! $customerId) {
            return response()->json([
                'message' => 'Invalid or expired token.',
            ], 401);
        }

        // Performance: Use find() with primary key (indexed lookup)
        $customer = Customer::find($customerId);

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found.',
            ], 404);
        }

        // Security: Check account status before allowing access
        if ($customer->status !== 'active') {
            return response()->json([
                'message' => 'Account is not active.',
            ], 403);
        }

        // Attach customer to request for use in controllers
        $request->merge(['customer' => $customer]);
        $request->setUserResolver(function () use ($customer) {
            return $customer;
        });

        return $next($request);
    }

    /**
     * Extract token from Authorization header
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header) {
            return null;
        }

        // Support both "Bearer {token}" and "{token}" formats
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return $header;
    }
}

