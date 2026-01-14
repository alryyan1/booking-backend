<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function getWeeks(Request $request, $month, $year)
    {
        $month = (int) $month;
        $year = (int) $year;

        // Validate month
        if ($month < 1 || $month > 12) {
            return response()->json(['error' => 'Invalid month'], 400);
        }

        // Get the first day of the month
        $firstDay = Carbon::create($year, $month, 1);
        
        // Get the last day of the month
        $lastDay = Carbon::create($year, $month, 1)->endOfMonth();

        // Start from the first Sunday of the month or the first day if it's already Sunday
        $startDate = $firstDay->copy()->startOfWeek(Carbon::SUNDAY);
        if ($startDate->month !== $month) {
            $startDate = $firstDay->copy();
        }

        $weeks = [];
        $currentDate = $startDate->copy();
        $weekNumber = 1;

        while ($currentDate->lte($lastDay) || $weekNumber <= 4) {
            $weekStart = $currentDate->copy();
            $weekEnd = $currentDate->copy()->endOfWeek(Carbon::SATURDAY);

            // Only include weeks that have at least one day in the target month
            if ($weekEnd->month === $month || $weekStart->month === $month) {
                $weeks[] = [
                    'week_number' => $weekNumber,
                    'start_date' => $weekStart->format('Y-m-d'),
                    'end_date' => $weekEnd->format('Y-m-d'),
                    'start_date_formatted' => $weekStart->format('M d'),
                    'end_date_formatted' => $weekEnd->format('M d'),
                ];
                $weekNumber++;
            }

            $currentDate->addWeek();
            
            // Safety check to prevent infinite loop
            if ($weekNumber > 6) {
                break;
            }
        }

        return response()->json([
            'month' => $month,
            'year' => $year,
            'month_name' => $firstDay->format('F'),
            'weeks' => $weeks,
        ]);
    }
}
