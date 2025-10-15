<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'address',
        'latitude',
        'longitude',
        'scheduled_at',
        'status',
        'total_price',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * El cliente que realizó la reserva (si está registrado).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * El empleado asignado a la reserva.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Los servicios incluidos en esta reserva.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'booking_service')
                    ->withPivot('price_at_booking') // Incluir el precio guardado
                    ->withTimestamps();
    }

    /**
     * La recompensa que se generó a partir de esta reserva (si aplica).
     */
    public function generatedReward(): HasOne
    {
        return $this->hasOne(UserReward::class);
    }
}
