<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TrialCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('ulsp.trial_customer_email', 'trials@ulsp.local');

        Customer::query()->firstOrCreate(
            ['email' => $email],
            [
                'password' => Hash::make(Str::random(64)),
                'first_name' => 'Trial',
                'last_name' => 'Guest',
                'company' => 'ULSP System',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }
}
