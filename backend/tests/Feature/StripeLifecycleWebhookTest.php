<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\License;
use App\Models\Payment;
use App\Models\PricingTier;
use App\Models\Product;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StripeLifecycleWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_paid_renews_subscription_license(): void
    {
        Notification::fake();

        $product = Product::create([
            'name' => 'Demo',
            'slug' => 'demo',
            'type' => 'saas',
            'key_prefix' => 'DEMO',
            'status' => 'active',
        ]);

        $tier = PricingTier::create([
            'product_id' => $product->id,
            'name' => 'Monthly',
            'price' => 10,
            'currency' => 'USD',
            'max_activations' => 1,
            'billing_cycle' => PricingTier::BILLING_MONTHLY,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $license = License::create([
            'license_key' => 'DEMO-AAAA-BBBB-CCCC-DDDD',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'pricing_tier_id' => $tier->id,
            'max_activations' => 1,
            'status' => License::STATUS_ACTIVE,
            'purchased_at' => now()->subMonth(),
            'expires_at' => now()->addDays(2),
        ]);

        Payment::create([
            'customer_id' => $customer->id,
            'license_id' => $license->id,
            'pricing_tier_id' => $tier->id,
            'amount' => 10,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now()->subMonth(),
            'meta' => ['stripe_subscription_id' => 'sub_test_123'],
        ]);

        $originalExpiry = $license->expires_at->copy();

        app(PaymentService::class)->dispatchEvent('invoice.paid', (object) [
            'subscription' => 'sub_test_123',
            'billing_reason' => 'subscription_cycle',
        ]);

        $this->assertTrue($license->fresh()->expires_at->gt($originalExpiry));
    }

    public function test_subscription_deleted_suspends_license(): void
    {
        Notification::fake();

        $product = Product::create([
            'name' => 'Demo',
            'slug' => 'demo',
            'type' => 'saas',
            'key_prefix' => 'DEMO',
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create();
        $license = License::create([
            'license_key' => 'DEMO-AAAA-BBBB-CCCC-EEEE',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'max_activations' => 1,
            'status' => License::STATUS_ACTIVE,
            'purchased_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        Payment::create([
            'customer_id' => $customer->id,
            'license_id' => $license->id,
            'amount' => 10,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'status' => Payment::STATUS_COMPLETED,
            'meta' => ['stripe_subscription_id' => 'sub_delete_1'],
        ]);

        app(PaymentService::class)->dispatchEvent('customer.subscription.deleted', (object) [
            'id' => 'sub_delete_1',
        ]);

        $this->assertSame(License::STATUS_SUSPENDED, $license->fresh()->status);
    }
}
