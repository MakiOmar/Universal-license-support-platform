<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Services\LicenseKeyGenerator;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LicenseKeyGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_generated_keys_use_product_key_prefix(): void
    {
        $product = Product::query()->create([
            'name' => 'Private Classes Manager',
            'slug' => 'private-classes-manager',
            'type' => 'mobile_app',
            'key_prefix' => 'ulspprv',
            'status' => 'active',
        ]);

        $this->assertSame('ULSPPRV', $product->key_prefix);

        $customer = Customer::factory()->create();

        $license = app(LicenseService::class)->issue($customer, $product);

        $this->assertMatchesRegularExpression('/^ULSPPRV-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license->license_key);
        $this->assertStringStartsWith('ULSPPRV-', $license->license_key);
    }

    public function test_key_generator_falls_back_to_ulsp_when_prefix_empty(): void
    {
        $product = new Product(['key_prefix' => '']);

        $key = app(LicenseKeyGenerator::class)->generate($product);

        $this->assertStringStartsWith('ULSP-', $key);
    }

    public function test_issued_keys_are_unique(): void
    {
        $product = Product::query()->create([
            'name' => 'Demo Product',
            'slug' => 'demo-product',
            'type' => 'web_app',
            'key_prefix' => 'DEMO',
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create();
        $service = app(LicenseService::class);

        $first = $service->issue($customer, $product);
        $second = $service->issue($customer, $product);

        $this->assertNotSame($first->license_key, $second->license_key);
        $this->assertStringStartsWith('DEMO-', $first->license_key);
        $this->assertStringStartsWith('DEMO-', $second->license_key);
    }
}
