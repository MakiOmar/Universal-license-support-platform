<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->sentence(12),
            'type' => $this->faker->randomElement([
                'wordpress_plugin',
                'web_app',
                'desktop_app',
                'mobile_app',
                'api_service',
                'saas',
            ]),
            'version' => '1.0.' . $this->faker->numberBetween(0, 50),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}


