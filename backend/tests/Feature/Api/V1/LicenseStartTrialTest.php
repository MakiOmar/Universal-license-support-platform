<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LicenseStartTrialTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;

    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->product = Product::create([
            'name' => 'Mobile App',
            'slug' => 'mobile-app',
            'type' => 'mobile_app',
            'key_prefix' => 'MOB',
            'status' => 'active',
        ]);

        $this->apiKey = ApiKey::create([
            'name' => 'Mobile Key',
            'key' => 'trial_api_key_123',
            'secret_hash' => bcrypt('secret'),
            'status' => ApiKey::STATUS_ACTIVE,
            'product_id' => $this->product->id,
            'trial_days' => 14,
        ]);
    }

    public function test_can_start_trial_for_device(): void
    {
        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/start-trial', [
                'activation_type' => 'device_id',
                'activation_value' => 'device-uuid-abc',
                'device_name' => 'Pixel 8',
                'platform' => 'android',
                'app_version' => '1.0.0',
            ]);

        $response->assertCreated()
            ->assertJsonPath('license.is_trial', true)
            ->assertJsonPath('license.status', License::STATUS_ACTIVE)
            ->assertJsonPath('activation.activation_type', 'device_id')
            ->assertJsonPath('activation.activation_value', 'device-uuid-abc')
            ->assertJsonPath('activation.device_name', 'Pixel 8');

        $this->assertNotNull($response->json('license.license_key'));
        $this->assertNotNull($response->json('expires_at'));

        $this->assertDatabaseHas('licenses', [
            'product_id' => $this->product->id,
            'is_trial' => true,
            'max_activations' => 1,
            'status' => License::STATUS_ACTIVE,
        ]);
    }

    public function test_rejects_when_trials_disabled(): void
    {
        $this->apiKey->update(['trial_days' => 0]);

        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/start-trial', [
                'activation_type' => 'device_id',
                'activation_value' => 'device-uuid-abc',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trial']);
    }

    public function test_rejects_when_api_key_has_no_product(): void
    {
        $this->apiKey->update(['product_id' => null]);

        $response = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/start-trial', [
                'activation_type' => 'device_id',
                'activation_value' => 'device-uuid-abc',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trial']);
    }

    public function test_rejects_second_trial_for_same_device(): void
    {
        $payload = [
            'activation_type' => 'device_id',
            'activation_value' => 'device-uuid-abc',
        ];

        $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/start-trial', $payload)
            ->assertCreated();

        $second = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/start-trial', $payload);

        $second->assertUnprocessable()
            ->assertJsonValidationErrors(['trial']);
    }

    public function test_trial_license_validates_until_expiry(): void
    {
        $start = $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/start-trial', [
                'activation_type' => 'device_id',
                'activation_value' => 'device-uuid-abc',
            ])
            ->assertCreated();

        $licenseKey = $start->json('license.license_key');

        $this->withHeader('X-API-Key', $this->apiKey->key)
            ->postJson('/api/v1/licenses/validate', [
                'license_key' => $licenseKey,
                'activation_type' => 'device_id',
                'activation_value' => 'device-uuid-abc',
            ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('activation_valid', true);
    }
}
