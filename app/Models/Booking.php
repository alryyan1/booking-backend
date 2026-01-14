<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'phone_number',
        'notes',
        'accessories',
        'payment_status',
        'total_amount',
        'deposit_amount',
        'remaining_balance',
        'category_id',
        'booking_date',
        'return_date',
        'time_slot_id',
        'user_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected $casts = [
        'booking_date' => 'date',
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Item::class)
            ->withPivot('price_at_booking')
            ->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
