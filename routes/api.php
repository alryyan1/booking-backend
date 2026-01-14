<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::middleware('admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Time Slots
    Route::get('/time-slots', [TimeSlotController::class, 'index']);
    Route::middleware('admin')->group(function () {
        Route::post('/time-slots', [TimeSlotController::class, 'store']);
        Route::post('/time-slots/bulk', [TimeSlotController::class, 'bulkCreate']);
        Route::put('/time-slots/{id}', [TimeSlotController::class, 'update']);
        Route::delete('/time-slots/{id}', [TimeSlotController::class, 'destroy']);
    });
    Route::get('/time-slots/{id}', [TimeSlotController::class, 'show']);

    // Users (Admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // Calendar
    Route::get('/calendar/weeks/{month}/{year}', [CalendarController::class, 'getWeeks']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-bookings', [DashboardController::class, 'recentBookings']);

    // Reports
    Route::get('/reports/bookings', [ReportController::class, 'bookings']);
    Route::get('/reports/category-wise', [ReportController::class, 'categoryWise']);
    Route::get('/reports/revenue', [ReportController::class, 'revenue']);

    // Items
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items/{id}', [ItemController::class, 'show']);
    Route::put('/items/{id}', [ItemController::class, 'update']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/export', [BookingController::class, 'export']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
});
