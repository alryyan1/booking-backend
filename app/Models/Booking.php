<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
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
        'user_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'payment_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

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
