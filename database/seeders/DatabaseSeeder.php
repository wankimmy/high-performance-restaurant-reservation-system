<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $this->call(AdminUserSeeder::class);
        
        // Create sample tables
        $tables = [
            ['name' => 'Table 1', 'capacity' => 2],
            ['name' => 'Table 2', 'capacity' => 2],
            ['name' => 'Table 3', 'capacity' => 4],
            ['name' => 'Table 4', 'capacity' => 4],
            ['name' => 'Table 5', 'capacity' => 6],
            ['name' => 'Table 6', 'capacity' => 6],
            ['name' => 'Table 7', 'capacity' => 8],
            ['name' => 'Table 8', 'capacity' => 8],
            ['name' => 'Table 9', 'capacity' => 10],
            ['name' => 'Table 10', 'capacity' => 10],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
        
        // Seed dummy reservations
        $this->call(ReservationSeeder::class);
        
        $this->command->info('Database seeded successfully!');
    }
}

