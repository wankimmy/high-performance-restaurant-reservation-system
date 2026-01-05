<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@restaurant.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $this->command->info('Default admin user created:');
        $this->command->info('Email: admin@restaurant.com');
        $this->command->info('Password: password');
        $this->command->warn('⚠️  Please change the password after first login!');
    }
}

