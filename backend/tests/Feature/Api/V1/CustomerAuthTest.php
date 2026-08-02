<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'new@ulsp.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'New',
            'last_name' => 'Customer',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['customer' => ['id', 'email'], 'token']);

        $this->assertDatabaseHas('customers', ['email' => 'new@ulsp.local']);
    }

    public function test_customer_can_login(): void
    {
        Customer::factory()->create([
            'email' => 'login@ulsp.local',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@ulsp.local',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['customer', 'token']);
    }

    public function test_customer_can_get_profile(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'me@ulsp.local',
            'password' => 'password123',
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customer/me');

        $response->assertOk()
            ->assertJsonPath('data.email', 'me@ulsp.local');
    }
}
