<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
