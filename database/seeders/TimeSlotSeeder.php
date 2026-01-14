<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create hourly time slots from 9:00 to 17:00
        $startHour = 9;
        $endHour = 17;

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $startTime = sprintf('%02d:00:00', $hour);
            $endTime = sprintf('%02d:00:00', $hour + 1);

            TimeSlot::create([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_active' => true,
            ]);
        }
    }
}
