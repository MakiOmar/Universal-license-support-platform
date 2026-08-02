<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_and_list_tickets(): void
    {
        Notification::fake();

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

        $license = License::query()->create([
            'license_key' => 'DEMO-TICK-ET01-TEST-0001',
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'max_activations' => 1,
            'status' => License::STATUS_ACTIVE,
            'purchased_at' => now(),
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/customer/tickets', [
                'subject' => 'Need help installing',
                'description' => 'Activation fails on staging.',
                'priority' => 'high',
                'category' => 'technical',
                'product_id' => $product->id,
                'license_id' => $license->id,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.subject', 'Need help installing');

        $list = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customer/tickets');

        $list->assertOk()
            ->assertJsonFragment(['subject' => 'Need help installing']);
    }

    public function test_customer_cannot_ticket_unlicensed_product(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $otherProduct = Product::query()->create([
            'name' => 'Other Product',
            'slug' => 'other-product',
            'type' => 'mobile_app',
            'key_prefix' => 'OTH',
            'status' => 'active',
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/customer/tickets', [
                'subject' => 'Help',
                'description' => 'No license for this.',
                'product_id' => $otherProduct->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id']);
    }
}
