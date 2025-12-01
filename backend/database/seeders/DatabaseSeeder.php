<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\Product;
use App\Models\SupportTicket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = Product::factory(5)->create();
        $customers = Customer::factory(10)->create();

        License::factory(20)->create();

        SupportTicket::factory(30)->create();

        // One API key per customer for the first product
        $primaryProduct = $products->first();

        foreach ($customers as $customer) {
            ApiKey::factory()->create([
                'customer_id' => $customer->id,
                'product_id' => $primaryProduct?->id ?? $products->first()->id,
            ]);
        }
    }
}
