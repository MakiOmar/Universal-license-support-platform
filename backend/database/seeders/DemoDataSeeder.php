<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PricingTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::firstOrCreate(
            ['slug' => 'demo-product'],
            [
                'name' => 'Demo Product',
                'description' => 'A demo product for ULSP development.',
                'type' => 'wordpress_plugin',
                'version' => '1.0.0',
                'key_prefix' => 'DEMO',
                'status' => 'active',
            ],
        );

        PricingTier::firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Standard Yearly'],
            [
                'price' => 99.00,
                'currency' => 'USD',
                'max_activations' => 3,
                'billing_cycle' => 'yearly',
                'is_active' => true,
            ],
        );

        Customer::firstOrCreate(
            ['email' => 'customer@ulsp.local'],
            [
                'password' => 'password',
                'first_name' => 'Demo',
                'last_name' => 'Customer',
                'status' => 'active',
            ],
        );
    }
}
