<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['category', 'timeSlot', 'user']);

        // Filter by month
        if ($request->has('month') && $request->has('year')) {
            $query->whereYear('booking_date', $request->year)
                  ->whereMonth('booking_date', $request->month);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by week (date range)
        if ($request->has('week_start') && $request->has('week_end')) {
            $query->whereBetween('booking_date', [$request->week_start, $request->week_end]);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        // Filter by specific date
        if ($request->has('date')) {
            $query->where('booking_date', $request->date);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by time slot
        if ($request->has('time_slot_id')) {
            $query->where('time_slot_id', $request->time_slot_id);
        }

        // Search by invoice number, phone number, or attire name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('attire_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'booking_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort column
        $allowedSortColumns = ['booking_date', 'created_at', 'invoice_number', 'payment_amount', 'payment_status'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'booking_date';
        }
        
        $query->orderBy($sortBy, $sortOrder);
        
        // If sorting by booking_date, add secondary sort by time_slot_id
        if ($sortBy === 'booking_date') {
            $query->orderBy('time_slot_id');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100

        $bookings = $query->paginate($perPage);

        return response()->json($bookings);
    }

    public function export(Request $request)
    {
        $query = Booking::with(['category', 'timeSlot', 'user']);

        // Apply same filters as index
        if ($request->has('month') && $request->has('year')) {
            $query->whereYear('booking_date', $request->year)
                  ->whereMonth('booking_date', $request->month);
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('week_start') && $request->has('week_end')) {
            $query->whereBetween('booking_date', [$request->week_start, $request->week_end]);
        }
        if ($request->has('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('attire_name', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('booking_date', 'desc')->get();

        // Generate CSV
        $filename = 'bookings_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($bookings) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Invoice Number',
                'Attire Name',
                'Category',
                'Phone Number',
                'Booking Date',
                'Time Slot',
                'Payment Status',
                'Payment Amount',
                'Balance',
                'Notes',
                'Accessories',
                'Created At'
            ]);

            // CSV Data
            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->invoice_number,
                    $booking->attire_name,
                    $booking->category->name_en ?? 'N/A',
                    $booking->phone_number,
                    $booking->booking_date,
                    $booking->timeSlot ? $booking->timeSlot->start_time . '-' . $booking->timeSlot->end_time : 'N/A',
                    $booking->payment_status,
                    $booking->payment_amount,
                    $booking->balance,
                    $booking->notes ?? '',
                    $booking->accessories ?? '',
                    $booking->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|string|unique:bookings,invoice_number',
            'attire_name' => 'required|string',
            'phone_number' => 'required|string',
            'notes' => 'nullable|string',
            'accessories' => 'nullable|string',
            'payment_status' => 'required|string|in:pending,partial,paid',
            'payment_amount' => 'required|numeric|min:0',
            'balance' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'booking_date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking = Booking::create([
            'invoice_number' => $request->invoice_number,
            'attire_name' => $request->attire_name,
            'phone_number' => $request->phone_number,
            'notes' => $request->notes,
            'accessories' => $request->accessories,
            'payment_status' => $request->payment_status,
            'payment_amount' => $request->payment_amount,
            'balance' => $request->balance,
            'category_id' => $request->category_id,
            'booking_date' => $request->booking_date,
            'time_slot_id' => $request->time_slot_id,
            'user_id' => $request->user()->id,
        ]);

        $booking->load(['category', 'timeSlot', 'user']);

        return response()->json($booking, 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['category', 'timeSlot', 'user'])->findOrFail($id);

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'invoice_number' => 'sometimes|required|string|unique:bookings,invoice_number,' . $id,
            'attire_name' => 'sometimes|required|string',
            'phone_number' => 'sometimes|required|string',
            'notes' => 'nullable|string',
            'accessories' => 'nullable|string',
            'payment_status' => 'sometimes|required|string|in:pending,partial,paid',
            'payment_amount' => 'sometimes|required|numeric|min:0',
            'balance' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'booking_date' => 'sometimes|required|date',
            'time_slot_id' => 'sometimes|required|exists:time_slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking->update($request->only([
            'invoice_number',
            'attire_name',
            'phone_number',
            'notes',
            'accessories',
            'payment_status',
            'payment_amount',
            'balance',
            'category_id',
            'booking_date',
            'time_slot_id',
        ]));

        $booking->load(['category', 'timeSlot', 'user']);

        return response()->json($booking);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
