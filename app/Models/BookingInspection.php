<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInspection extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'markers',
        'notes',
        'client_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'markers' => 'array',
            'client_confirmed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return $this->client_confirmed_at !== null;
    }
}
