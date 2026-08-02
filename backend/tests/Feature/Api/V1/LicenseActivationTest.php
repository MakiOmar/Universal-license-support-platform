<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKey $apiKey;

    protected License $license;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_rejects_request_without_api_key(): void
    {
        $response = $this->postJson('/api/v1/licenses/validate', [
            'license_key' => $this->license->license_key,
        ]);

        $response->assertUnauthorized();
    }
}
