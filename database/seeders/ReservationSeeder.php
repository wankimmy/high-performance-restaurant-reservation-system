<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all available tables
        $tables = Table::all();
        
        if ($tables->isEmpty()) {
            $this->command->warn('No tables found. Please create tables first before seeding reservations.');
            return;
        }

        // Sample customer data
        $customers = [
            ['name' => 'John Smith', 'email' => 'john.smith@example.com', 'phone' => '+60123456789'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah.j@example.com', 'phone' => '+60123456790'],
            ['name' => 'Michael Chen', 'email' => 'michael.chen@example.com', 'phone' => '+60123456791'],
            ['name' => 'Emily Davis', 'email' => 'emily.davis@example.com', 'phone' => '+60123456792'],
            ['name' => 'David Wilson', 'email' => 'david.w@example.com', 'phone' => '+60123456793'],
            ['name' => 'Lisa Anderson', 'email' => 'lisa.a@example.com', 'phone' => '+60123456794'],
            ['name' => 'Robert Brown', 'email' => 'robert.brown@example.com', 'phone' => '+60123456795'],
            ['name' => 'Jennifer Lee', 'email' => 'jennifer.lee@example.com', 'phone' => '+60123456796'],
            ['name' => 'James Taylor', 'email' => 'james.t@example.com', 'phone' => '+60123456797'],
            ['name' => 'Maria Garcia', 'email' => 'maria.garcia@example.com', 'phone' => '+60123456798'],
        ];

        // Sample time slots
        $timeSlots = ['16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00'];

        // Sample notes
        $notes = [
            null,
            'Window seat preferred',
            'Anniversary celebration',
            'Birthday party',
            'Business dinner',
            'Vegetarian options needed',
            'Quiet table please',
            'High chair needed',
            null,
            'Special dietary requirements',
        ];

        // Track used date/time/table combinations to avoid conflicts
        $usedSlots = [];
        
        // Generate 10 reservations
        for ($i = 0; $i < 10; $i++) {
            $attempts = 0;
            $maxAttempts = 50;
            
            // Try to find a unique slot
            do {
                // Random date between today and 30 days from now
                $daysFromNow = rand(0, 30);
                $reservationDate = Carbon::today()->addDays($daysFromNow);
                
                // Random table
                $table = $tables->random();
                
                // Random time slot
                $reservationTime = $timeSlots[array_rand($timeSlots)];

                $reservationStartAt = Carbon::parse($reservationDate->format('Y-m-d') . ' ' . $reservationTime);
                $reservationEndAt = $reservationStartAt->copy()->addMinutes(105);
                
                $slotKey = "{$reservationDate->format('Y-m-d')}_{$reservationTime}_{$table->id}";
                $attempts++;
            } while (isset($usedSlots[$slotKey]) && $attempts < $maxAttempts);
            
            // If we couldn't find a unique slot after many attempts, use it anyway
            $usedSlots[$slotKey] = true;
            
            // Random customer
            $customer = $customers[$i];
            
            // Random pax (between 1 and table capacity, but not more than 10)
            $maxPax = min($table->capacity, 10);
            $pax = rand(1, $maxPax);
            
            // Random status (mostly confirmed, some pending, some cancelled)
            $statusOptions = ['confirmed', 'confirmed', 'confirmed', 'confirmed', 'pending', 'cancelled'];
            $status = $statusOptions[array_rand($statusOptions)];
            
            // Calculate deposit (assuming RM 10 per person for demo)
            $depositPerPax = 10.00;
            $depositAmount = $pax * $depositPerPax;
            
            // Random notes
            $note = $notes[$i] ?? null;
            
            // Create reservation
            Reservation::create([
                'table_id' => $table->id,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'pax' => $pax,
                'deposit_amount' => $depositAmount,
                'reservation_date' => $reservationDate,
                'reservation_time' => $reservationTime,
                'reservation_start_at' => $reservationStartAt,
                'reservation_end_at' => $reservationEndAt,
                'notes' => $note,
                'status' => $status,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'otp_verified' => $status === 'confirmed' ? true : false,
                'has_arrived' => false,
            ]);
        }

        $this->command->info('Successfully seeded 10 dummy reservations.');
    }
}
