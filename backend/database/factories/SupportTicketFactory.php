<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        $customer = Customer::inRandomOrder()->first() ?? Customer::factory()->create();
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $license = License::inRandomOrder()->first() ?? License::factory()->create();

        $ticketNumber = 'TKT-' . now()->format('Y') . '-' . str_pad((string) $this->faker->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'ticket_number' => $ticketNumber,
            'customer_id' => $customer->id,
            'license_id' => $license->id,
            'product_id' => $product->id,
            'subject' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'resolved', 'closed']),
            'category' => $this->faker->randomElement(['technical', 'billing', 'feature_request', 'bug_report']),
            'assigned_to' => null,
            'resolved_at' => null,
        ];
    }
}


