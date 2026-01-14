<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['category', 'timeSlot', 'user', 'items', 'customer']);

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

        // Search by invoice number, phone number or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
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
        $query = Booking::with(['category', 'timeSlot', 'user', 'customer', 'items']);

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
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->orderBy('booking_date', 'desc')->get();

        // Generate CSV
        $filename = 'bookings_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bookings) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Invoice Number',
                'Customer Name',
                'Phone Number',
                'Items',
                'Category',
                'Booking Date',
                'Time Slot',
                'Return Date',
                'Payment Status',
                'Total Amount',
                'Deposit',
                'Balance',
                'Notes',
                'Accessories',
                'Created At'
            ]);

            // CSV Data
            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->invoice_number,
                    $booking->customer->name ?? 'Walk-in',
                    $booking->customer->phone_number ?? $booking->phone_number,
                    $booking->items->pluck('name')->join(', '),
                    $booking->category->name_en ?? 'N/A',
                    $booking->booking_date,
                    $booking->timeSlot ? $booking->timeSlot->start_time . '-' . $booking->timeSlot->end_time : 'N/A',
                    $booking->return_date,
                    $booking->payment_status,
                    $booking->total_amount,
                    $booking->deposit_amount,
                    $booking->remaining_balance,
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
            'customer_id' => 'nullable|exists:customers,id',
            'phone_number' => 'required|string',
            'notes' => 'nullable|string',
            'accessories' => 'nullable|string',
            'payment_status' => 'required|string|in:pending,partial,paid',
            'deposit_amount' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'booking_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:booking_date',
            'time_slot_id' => 'required|exists:time_slots,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:items,id',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate total amount from items
        $totalAmount = 0;
        foreach ($request->items as $item) {
            $totalAmount += $item['price'];
        }

        $remainingBalance = $totalAmount - $request->deposit_amount;

        $booking = Booking::create([
            'invoice_number' => $request->invoice_number,
            'customer_id' => $request->customer_id,
            'phone_number' => $request->phone_number,
            'notes' => $request->notes,
            'accessories' => $request->accessories,
            'payment_status' => $request->payment_status,
            'total_amount' => $totalAmount,
            'deposit_amount' => $request->deposit_amount,
            'remaining_balance' => $remainingBalance,
            'category_id' => $request->category_id,
            'booking_date' => $request->booking_date,
            'return_date' => $request->return_date,
            'time_slot_id' => $request->time_slot_id,
            'user_id' => $request->user()->id,
        ]);

        // Attach items with price at booking
        $itemsData = [];
        foreach ($request->items as $item) {
            $itemsData[$item['id']] = ['price_at_booking' => $item['price']];
        }
        $booking->items()->sync($itemsData);

        $booking->load(['category', 'timeSlot', 'user', 'items', 'customer']);

        return response()->json($booking, 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['category', 'timeSlot', 'user', 'items', 'customer'])->findOrFail($id);

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'invoice_number' => 'sometimes|required|string|unique:bookings,invoice_number,' . $id,
            'customer_id' => 'nullable|exists:customers,id',
            'phone_number' => 'sometimes|required|string',
            'notes' => 'nullable|string',
            'accessories' => 'nullable|string',
            'payment_status' => 'sometimes|required|string|in:pending,partial,paid',
            'deposit_amount' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'booking_date' => 'sometimes|required|date',
            'return_date' => 'nullable|date|after_or_equal:booking_date',
            'time_slot_id' => 'sometimes|required|exists:time_slots,id',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'sometimes|required|exists:items,id',
            'items.*.price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'invoice_number',
            'customer_id',
            'phone_number',
            'notes',
            'accessories',
            'payment_status',
            'category_id',
            'booking_date',
            'return_date',
            'time_slot_id',
        ]);

        if ($request->has('deposit_amount')) {
            $data['deposit_amount'] = $request->deposit_amount;
        }

        if ($request->has('items')) {
            $totalAmount = 0;
            $itemsData = [];
            foreach ($request->items as $item) {
                $totalAmount += $item['price'];
                $itemsData[$item['id']] = ['price_at_booking' => $item['price']];
            }
            $data['total_amount'] = $totalAmount;
            $booking->items()->sync($itemsData);
        } else {
            $totalAmount = $booking->total_amount;
        }

        // Recalculate balance
        $deposit = $request->has('deposit_amount') ? $request->deposit_amount : $booking->deposit_amount;
        $data['remaining_balance'] = $totalAmount - $deposit;

        $booking->update($data);

        $booking->load(['category', 'timeSlot', 'user', 'items', 'customer']);

        return response()->json($booking);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
