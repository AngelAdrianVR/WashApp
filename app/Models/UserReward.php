<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promotion_id',
        'booking_id',
        'is_claimed',
        'claimed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }

    /**
     * El usuario que posee esta recompensa.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La promoción asociada a esta recompensa (define el descuento).
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
    
    /**
     * La reserva que originó esta recompensa.
     */
    public function originatingBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
