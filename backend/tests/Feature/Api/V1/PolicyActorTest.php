<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Policies\LicensePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_view_any_licenses(): void
    {
        $user = User::factory()->create();
        $policy = new LicensePolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_customer_can_only_view_own_license(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $product = Product::query()->create([
            'name' => 'Demo',
            'slug' => 'demo',
            'type' => 'wordpress_plugin',
            'key_prefix' => 'DEMO',
            'status' => 'active',
        ]);
        $license = License::query()->create([
            'license_key' => 'DEMO-AAAA-BBBB-CCCC-DDDD',
            'product_id' => $product->id,
            'customer_id' => $owner->id,
            'max_activations' => 1,
            'status' => 'active',
        ]);

        $policy = new LicensePolicy;

        $this->assertTrue($policy->view($owner, $license));
        $this->assertFalse($policy->view($other, $license));
    }
}
