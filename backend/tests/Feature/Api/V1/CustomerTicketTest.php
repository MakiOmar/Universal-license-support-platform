<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_and_list_tickets(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'ticket-user@ulsp.local',
            'password' => 'password',
        ]);

        $product = Product::query()->create([
            'name' => 'Demo Product',
            'slug' => 'demo-product',
            'description' => 'Demo',
            'type' => 'wordpress_plugin',
            'version' => '1.0.0',
            'key_prefix' => 'DEMO',
            'status' => 'active',
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/customer/tickets', [
                'subject' => 'Need help installing',
                'description' => 'Activation fails on staging.',
                'priority' => 'high',
                'category' => 'technical',
                'product_id' => $product->id,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.subject', 'Need help installing');

        $list = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customer/tickets');

        $list->assertOk()
            ->assertJsonFragment(['subject' => 'Need help installing']);
    }
}
