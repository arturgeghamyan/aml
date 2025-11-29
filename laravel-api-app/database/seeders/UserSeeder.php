<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        // Seller
        User::updateOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name'     => 'Seller User',
                'password' => Hash::make('password'),
                'role'     => 'seller',
            ]
        );

        // Buyer
        User::updateOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name'     => 'Buyer User',
                'password' => Hash::make('password'),
                'role'     => 'buyer',
            ]
        );
    }
}
