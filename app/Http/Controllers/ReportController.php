<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function bookings(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Booking::with(['category', 'timeSlot', 'user'])
            ->whereBetween('booking_date', [$request->date_from, $request->date_to]);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $bookings = $query->orderBy('booking_date')->get();

        // Calculate summary
        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum('payment_amount');
        $paidRevenue = $bookings->where('payment_status', 'paid')->sum('payment_amount');
        $pendingRevenue = $bookings->where('payment_status', 'pending')->sum('payment_amount');
        $partialRevenue = $bookings->where('payment_status', 'partial')->sum('payment_amount');

        return response()->json([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'summary' => [
                'total_bookings' => $totalBookings,
                'total_revenue' => (float) $totalRevenue,
                'paid_revenue' => (float) $paidRevenue,
                'pending_revenue' => (float) $pendingRevenue,
                'partial_revenue' => (float) $partialRevenue,
            ],
            'bookings' => $bookings,
        ]);
    }

    public function categoryWise(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categories = Category::with(['bookings' => function ($query) use ($request) {
            $query->whereBetween('booking_date', [$request->date_from, $request->date_to]);
        }])->get();

        $report = $categories->map(function ($category) {
            $bookings = $category->bookings;
            return [
                'category_id' => $category->id,
                'category_name_en' => $category->name_en,
                'category_name_ar' => $category->name_ar,
                'total_bookings' => $bookings->count(),
                'total_revenue' => (float) $bookings->sum('payment_amount'),
                'paid_revenue' => (float) $bookings->where('payment_status', 'paid')->sum('payment_amount'),
                'pending_revenue' => (float) $bookings->where('payment_status', 'pending')->sum('payment_amount'),
                'partial_revenue' => (float) $bookings->where('payment_status', 'partial')->sum('payment_amount'),
            ];
        });

        return response()->json([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'categories' => $report,
        ]);
    }

    public function revenue(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $groupBy = $request->get('group_by', 'day');
        $dateFrom = Carbon::parse($request->date_from);
        $dateTo = Carbon::parse($request->date_to);

        $bookings = Booking::whereBetween('booking_date', [$request->date_from, $request->date_to])
            ->get();

        $report = [];

        if ($groupBy === 'day') {
            $currentDate = $dateFrom->copy();
            while ($currentDate <= $dateTo) {
                $dayBookings = $bookings->where('booking_date', $currentDate->format('Y-m-d'));
                $report[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'date_formatted' => $currentDate->format('M d, Y'),
                    'total_bookings' => $dayBookings->count(),
                    'total_revenue' => (float) $dayBookings->sum('payment_amount'),
                    'paid_revenue' => (float) $dayBookings->where('payment_status', 'paid')->sum('payment_amount'),
                ];
                $currentDate->addDay();
            }
        } elseif ($groupBy === 'week') {
            $currentDate = $dateFrom->copy()->startOfWeek();
            while ($currentDate <= $dateTo) {
                $weekEnd = $currentDate->copy()->endOfWeek();
                $weekBookings = $bookings->filter(function ($booking) use ($currentDate, $weekEnd) {
                    $bookingDate = Carbon::parse($booking->booking_date);
                    return $bookingDate >= $currentDate && $bookingDate <= $weekEnd;
                });
                $report[] = [
                    'week_start' => $currentDate->format('Y-m-d'),
                    'week_end' => $weekEnd->format('Y-m-d'),
                    'week_label' => $currentDate->format('M d') . ' - ' . $weekEnd->format('M d, Y'),
                    'total_bookings' => $weekBookings->count(),
                    'total_revenue' => (float) $weekBookings->sum('payment_amount'),
                    'paid_revenue' => (float) $weekBookings->where('payment_status', 'paid')->sum('payment_amount'),
                ];
                $currentDate->addWeek();
            }
        } elseif ($groupBy === 'month') {
            $currentDate = $dateFrom->copy()->startOfMonth();
            while ($currentDate <= $dateTo) {
                $monthEnd = $currentDate->copy()->endOfMonth();
                $monthBookings = $bookings->filter(function ($booking) use ($currentDate, $monthEnd) {
                    $bookingDate = Carbon::parse($booking->booking_date);
                    return $bookingDate >= $currentDate && $bookingDate <= $monthEnd;
                });
                $report[] = [
                    'month' => $currentDate->format('Y-m'),
                    'month_label' => $currentDate->format('F Y'),
                    'total_bookings' => $monthBookings->count(),
                    'total_revenue' => (float) $monthBookings->sum('payment_amount'),
                    'paid_revenue' => (float) $monthBookings->where('payment_status', 'paid')->sum('payment_amount'),
                ];
                $currentDate->addMonth();
            }
        }

        return response()->json([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'group_by' => $groupBy,
            'data' => $report,
        ]);
    }
}
