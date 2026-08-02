<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Product;
use App\Notifications\LicenseIssuedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerDeviceAndActivationTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKey $apiKey;

    protected Customer $customer;

    protected License $license;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'mobile_app',
            'key_prefix' => 'TST',
            'status' => 'active',
        ]);

        $this->customer = Customer::factory()->create();

        $this->license = License::create([
            'license_key' => 'TST-AAAA-BBBB-CCCC-DDDD',
            'product_id' => $product->id,
            'customer_id' => $this->customer->id,
            'max_activations' => 1,
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
            'rate_limit' => 1000,
        ]);
    }

    public function test_customer_can_list_and_deactivate_activation(): void
    {
        $activation = LicenseActivation::create([
            'license_id' => $this->license->id,
            'activation_type' => 'device_id',
            'activation_value' => 'old-phone',
            'activation_hash' => LicenseActivation::hashActivation('device_id', 'old-phone'),
            'status' => LicenseActivation::STATUS_ACTIVE,
            'activated_at' => now(),
            'last_check_at' => now(),
        ]);

        Sanctum::actingAs($this->customer);

        $this->getJson('/api/v1/customer/licenses/'.$this->license->id.'/activations')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson('/api/v1/customer/licenses/'.$this->license->id.'/activations/'.$activation->id)
            ->assertOk();

        $this->assertDatabaseHas('license_activations', [
            'id' => $activation->id,
            'status' => LicenseActivation::STATUS_DEACTIVATED,
        ]);
    }

    public function test_replace_oldest_frees_slot_when_full(): void
    {
        LicenseActivation::create([
            'license_id' => $this->license->id,
            'activation_type' => 'device_id',
            'activation_value' => 'old-phone',
            'activation_hash' => LicenseActivation::hashActivation('device_id', 'old-phone'),
            'status' => LicenseActivation::STATUS_ACTIVE,
            'activated_at' => now()->subDay(),
            'last_check_at' => now()->subDay(),
        ]);

        $blocked = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/activate', [
                'license_key' => $this->license->license_key,
                'activation_type' => 'device_id',
                'activation_value' => 'new-phone',
            ]);

        $blocked->assertUnprocessable();

        $replaced = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/activate', [
                'license_key' => $this->license->license_key,
                'activation_type' => 'device_id',
                'activation_value' => 'new-phone',
                'replace_oldest' => true,
                'device_name' => 'Pixel 8',
                'platform' => 'android',
                'app_version' => '1.2.0',
            ]);

        $replaced->assertSuccessful()
            ->assertJsonPath('data.activation_value', 'new-phone')
            ->assertJsonPath('data.device_name', 'Pixel 8');

        $this->assertSame(1, $this->license->fresh()->activeActivationsCount());
    }

    public function test_api_key_rate_limit_returns_429(): void
    {
        $this->apiKey->update(['rate_limit' => 1]);
        Cache::flush();

        $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/validate', [
                'license_key' => $this->license->license_key,
            ])
            ->assertOk();

        $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/validate', [
                'license_key' => $this->license->license_key,
            ])
            ->assertStatus(429);
    }
}
