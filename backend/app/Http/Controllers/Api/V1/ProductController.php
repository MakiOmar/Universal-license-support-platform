<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->where('status', 'active')
            ->with(['pricingTiers' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->status === 'active', 404);

        $product->load(['pricingTiers' => fn ($q) => $q->where('is_active', true)]);

        return new ProductResource($product);
    }
}
