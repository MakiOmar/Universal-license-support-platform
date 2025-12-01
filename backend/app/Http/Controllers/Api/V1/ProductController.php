<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()->paginate(25);

        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'version' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', 'max:50'],
            'version' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}


