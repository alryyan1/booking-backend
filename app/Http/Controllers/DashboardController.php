<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        // Total bookings
        $totalBookingsToday = Booking::whereDate('booking_date', $today)->count();
        $totalBookingsWeek = Booking::where('booking_date', '>=', $weekStart)->count();
        $totalBookingsMonth = Booking::where('booking_date', '>=', $monthStart)->count();
        $totalBookingsAll = Booking::count();

        // Revenue statistics
        $totalRevenue = Booking::sum('payment_amount');
        $pendingRevenue = Booking::where('payment_status', 'pending')
            ->sum('payment_amount');
        $paidRevenue = Booking::where('payment_status', 'paid')
            ->sum('payment_amount');
        $partialRevenue = Booking::where('payment_status', 'partial')
            ->sum('payment_amount');

        // Bookings by category
        $bookingsByCategory = Category::withCount('bookings')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name_en' => $category->name_en,
                    'name_ar' => $category->name_ar,
                    'count' => $category->bookings_count,
                ];
            });

        // Bookings by status
        $bookingsByStatus = [
            'pending' => Booking::where('payment_status', 'pending')->count(),
            'partial' => Booking::where('payment_status', 'partial')->count(),
            'paid' => Booking::where('payment_status', 'paid')->count(),
        ];

        // Monthly trends (last 6 months)
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthlyTrends[] = [
                'month' => $month->format('M Y'),
                'month_number' => $month->month,
                'year' => $month->year,
                'bookings' => Booking::whereBetween('booking_date', [$monthStart, $monthEnd])->count(),
                'revenue' => Booking::whereBetween('booking_date', [$monthStart, $monthEnd])
                    ->sum('payment_amount'),
            ];
        }

        return response()->json([
            'bookings' => [
                'today' => $totalBookingsToday,
                'week' => $totalBookingsWeek,
                'month' => $totalBookingsMonth,
                'total' => $totalBookingsAll,
            ],
            'revenue' => [
                'total' => (float) $totalRevenue,
                'pending' => (float) $pendingRevenue,
                'paid' => (float) $paidRevenue,
                'partial' => (float) $partialRevenue,
            ],
            'bookings_by_category' => $bookingsByCategory,
            'bookings_by_status' => $bookingsByStatus,
            'monthly_trends' => $monthlyTrends,
        ]);
    }

    public function recentBookings(Request $request)
    {
        $limit = $request->get('limit', 10);

        $bookings = Booking::with(['category', 'timeSlot', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($bookings);
    }
}
