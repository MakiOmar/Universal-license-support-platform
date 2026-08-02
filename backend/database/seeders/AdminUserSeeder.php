<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@ulsp.local'],
            [
                'name' => 'ULSP Admin',
                'password' => 'admin123',
            ],
        );

        $admin->assignRole('super_admin');
    }
}
