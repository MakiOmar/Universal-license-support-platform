<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKey $apiKey;

    protected License $license;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'wordpress_plugin',
            'key_prefix' => 'TST',
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create();

        $this->license = License::create([
            'license_key' => 'TST-AAAA-BBBB-CCCC-DDDD',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'max_activations' => 2,
            'status' => License::STATUS_ACTIVE,
            'purchased_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->apiKey = ApiKey::create([
            'name' => 'Test Key',
            'key' => 'test_api_key_123',
            'secret_hash' => bcrypt('secret'),
            'status' => ApiKey::STATUS_ACTIVE,
            'product_id' => $product->id,
        ]);
    }

    public function test_can_validate_active_license(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/validate', [
                'license_key' => $this->license->license_key,
            ]);

        $response->assertOk()
            ->assertJsonPath('valid', true);
    }

    public function test_can_activate_license(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/activate', [
                'license_key' => $this->license->license_key,
                'activation_type' => 'domain',
                'activation_value' => 'example.com',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.activation_type', 'domain')
            ->assertJsonPath('data.activation_value', 'example.com');

        $this->assertDatabaseHas('license_activations', [
            'license_id' => $this->license->id,
            'activation_value' => 'example.com',
            'status' => 'active',
        ]);
    }

    public function test_activate_accepts_camel_case_device_meta(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/activate', [
                'license_key' => $this->license->license_key,
                'activation_type' => 'device_id',
                'activation_value' => 'android-id-abc',
                'deviceName' => 'Pixel 8',
                'platform' => 'android',
                'appVersion' => '1.0.0',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.device_name', 'Pixel 8')
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.app_version', '1.0.0');

        $this->assertDatabaseHas('license_activations', [
            'license_id' => $this->license->id,
            'activation_value' => 'android-id-abc',
            'device_name' => 'Pixel 8',
            'platform' => 'android',
            'app_version' => '1.0.0',
        ]);
    }

    public function test_rejects_request_without_api_key(): void
    {
        $response = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $this->license->license_key,
        ]);

        $response->assertUnauthorized();
    }

    public function test_rejects_license_for_different_product(): void
    {
        $otherProduct = Product::create([
            'name' => 'Other Product',
            'slug' => 'other-product',
            'type' => 'mobile_app',
            'key_prefix' => 'OTH',
            'status' => 'active',
        ]);

        $otherLicense = License::create([
            'license_key' => 'OTH-AAAA-BBBB-CCCC-DDDD',
            'product_id' => $otherProduct->id,
            'customer_id' => $this->license->customer_id,
            'max_activations' => 2,
            'status' => License::STATUS_ACTIVE,
            'purchased_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // API key is scoped to Test Product; other product license must fail.
        $validate = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/validate', [
                'license_key' => $otherLicense->license_key,
            ]);

        $validate->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'license_product_mismatch');

        $activate = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/activate', [
                'license_key' => $otherLicense->license_key,
                'activation_type' => 'device_id',
                'activation_value' => 'device-123',
            ]);

        $activate->assertUnprocessable()
            ->assertJsonValidationErrors(['license_key']);
    }
}
