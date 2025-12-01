<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $customer = Customer::inRandomOrder()->first() ?? Customer::factory()->create();
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();

        return [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'api_key' => 'sk_' . Str::random(32),
            'api_secret' => 'ss_' . Str::random(48),
            'rate_limit' => 1000,
            'status' => 'active',
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}


