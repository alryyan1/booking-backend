<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed categories
        $this->call(CategorySeeder::class);

        // Seed time slots
        $this->call(TimeSlotSeeder::class);

        // Create admin user
        User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@booking.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
