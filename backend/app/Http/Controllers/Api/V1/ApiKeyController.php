<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of API keys.
     * Performance: Uses eager loading and pagination
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 25), 100);

        $query = ApiKey::with(['customer', 'product'])
            ->select('id', 'customer_id', 'product_id', 'api_key', 'rate_limit', 'status', 'last_used_at', 'expires_at', 'created_at', 'updated_at');

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('api_key', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $apiKeys = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($apiKeys);
    }

    /**
     * Store a newly created API key.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:10000', 'default:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'status' => ['nullable', 'string', 'in:active,inactive', 'default:active'],
        ]);

        // Generate API key and secret
        $apiKey = Str::random(64);
        $apiSecret = Str::random(64);

        // Ensure uniqueness
        while (ApiKey::where('api_key', $apiKey)->exists()) {
            $apiKey = Str::random(64);
        }

        $data['api_key'] = $apiKey;
        $data['api_secret'] = hash('sha256', $apiSecret); // Store hashed secret

        $apiKeyModel = ApiKey::create($data);
        $apiKeyModel->load(['customer', 'product']);

        // Return API key and secret (only shown once)
        return response()->json([
            'api_key' => $apiKeyModel,
            'api_secret' => $apiSecret, // Only returned on creation
        ], 201);
    }

    /**
     * Display the specified API key.
     */
    public function show(ApiKey $apiKey)
    {
        $apiKey->load(['customer', 'product']);

        return response()->json($apiKey);
    }

    /**
     * Update the specified API key.
     */
    public function update(Request $request, ApiKey $apiKey)
    {
        $data = $request->validate([
            'rate_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $apiKey->update($data);
        $apiKey->load(['customer', 'product']);

        return response()->json($apiKey);
    }

    /**
     * Remove the specified API key.
     */
    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();

        return response()->json(null, 204);
    }

    /**
     * Regenerate API secret for an existing key.
     */
    public function regenerateSecret(ApiKey $apiKey)
    {
        $newSecret = Str::random(64);
        $apiKey->api_secret = hash('sha256', $newSecret);
        $apiKey->save();

        return response()->json([
            'api_key' => $apiKey->load(['customer', 'product']),
            'api_secret' => $newSecret, // Only returned on regeneration
        ]);
    }
}
