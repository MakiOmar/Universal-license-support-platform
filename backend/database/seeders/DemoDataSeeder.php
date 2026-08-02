<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\PricingTier;
use App\Models\Product;
use App\Services\LicenseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        $tier = PricingTier::firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Standard Yearly'],
            [
                'price' => 99.00,
                'currency' => 'USD',
                'max_activations' => 3,
                'billing_cycle' => 'yearly',
                'is_active' => true,
            ],
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'customer@ulsp.local'],
            [
                'password' => 'password',
                'first_name' => 'Demo',
                'last_name' => 'Customer',
                'status' => 'active',
            ],
        );

        ApiKey::firstOrCreate(
            ['key' => 'ulsp_demo_api_key_123456'],
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'name' => 'Demo Integration Key',
                'secret_hash' => Hash::make('demo-secret'),
                'rate_limit' => 1000,
                'status' => ApiKey::STATUS_ACTIVE,
            ],
        );

        if (! License::where('customer_id', $customer->id)->exists()) {
            app(LicenseService::class)->issue($customer, $product, $tier, [
                'status' => License::STATUS_ACTIVE,
                'purchased_at' => now(),
                'expires_at' => now()->addYear(),
                'support_expires_at' => now()->addYear(),
            ]);
        }
    }
}
