<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * List products (public endpoint)
     * Performance: Uses select() to limit columns, supports search filtering
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 25), 100);

        $query = Product::select('id', 'name', 'slug', 'description', 'type', 'version', 'status', 'created_at', 'updated_at')
            ->where('status', 'active'); // Only show active products to public

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Performance: Order by created_at (indexed column)
        $products = $query->orderByDesc('created_at')->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(['wordpress_plugin', 'web_app', 'desktop_app', 'mobile_app', 'api_service', 'saas_product'])],
            'version' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ]);

        $product = Product::create($data);

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in(['wordpress_plugin', 'web_app', 'desktop_app', 'mobile_app', 'api_service', 'saas_product'])],
            'version' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ]);

        $product->update($data);

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}


