<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PricingTier;
use App\Models\Product;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTierBillingCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_time_tier_issues_license_without_expiration(): void
    {
        $product = Product::query()->create([
            'name' => 'Private Classes Manager',
            'slug' => 'private-classes-manager',
            'type' => 'mobile_app',
            'key_prefix' => 'ULSPPRV',
            'status' => 'active',
        ]);

        $tier = PricingTier::query()->create([
            'product_id' => $product->id,
            'name' => 'One-time',
            'price' => 49.00,
            'currency' => 'USD',
            'max_activations' => 2,
            'billing_cycle' => PricingTier::BILLING_ONE_TIME,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $license = app(LicenseService::class)->issue($customer, $product, $tier);

        $this->assertNull($license->expires_at);
        $this->assertTrue($tier->isOneTimePayment());
        $this->assertFalse($tier->isRecurring());
    }

    public function test_yearly_tier_issues_license_with_expiration(): void
    {
        $product = Product::query()->create([
            'name' => 'Demo Product',
            'slug' => 'demo-product',
            'type' => 'web_app',
            'key_prefix' => 'DEMO',
            'status' => 'active',
        ]);

        $tier = PricingTier::query()->create([
            'product_id' => $product->id,
            'name' => 'Yearly',
            'price' => 99.00,
            'currency' => 'USD',
            'max_activations' => 3,
            'billing_cycle' => PricingTier::BILLING_YEARLY,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $license = app(LicenseService::class)->issue($customer, $product, $tier);

        $this->assertNotNull($license->expires_at);
        $this->assertTrue($license->expires_at->between(now()->addYear()->subDay(), now()->addYear()->addDay()));
        $this->assertTrue($tier->isRecurring());
    }
}
