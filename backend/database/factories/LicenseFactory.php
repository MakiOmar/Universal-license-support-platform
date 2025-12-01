<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $customer = Customer::inRandomOrder()->first() ?? Customer::factory()->create();

        return [
            'license_key' => strtoupper($this->faker->bothify('LIC-####-####-####')),
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'license_type' => $this->faker->randomElement([
                'domain',
                'machine_id',
                'device_id',
                'api_key',
            ]),
            'max_activations' => $this->faker->numberBetween(1, 10),
            'status' => $this->faker->randomElement(['active', 'expired', 'pending']),
            'purchased_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'support_expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}


