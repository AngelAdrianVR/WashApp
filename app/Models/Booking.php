<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

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
        'street',
        'street_number',
        'neighborhood',
        'postal_code',
        'state',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_category_id',
        'latitude',
        'longitude',
        'scheduled_at',
        'started_at',
        'completed_at',
        'status',
        'payment_method',
        'total_price',
        'notes',
        'cancel_reason',
    ];

    /**
     * Estados en los que la cita sigue abierta (aún no se finaliza).
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = ['pending', 'confirmed', 'on_way', 'in_progress'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /**
     * Citas que todavía no se finalizan ni se cancelan.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    /**
     * Citas finalizadas dentro de un rango de fechas (según su finalización real).
     */
    public function scopeCompletedBetween($query, $start, $end)
    {
        return $query->where('status', 'completed')
            ->whereRaw('COALESCE(bookings.completed_at, bookings.updated_at) BETWEEN ? AND ?', [$start, $end]);
    }

    /**
     * Duración total del servicio en minutos (según la categoría del vehículo).
     */
    public function durationMinutes(): int
    {
        $services = $this->services;

        if ($services->isEmpty()) {
            return 60;
        }

        $variants = $this->vehicle_category_id
            ? DB::table('service_vehicle_category')
                ->whereIn('service_id', $services->pluck('id'))
                ->where('vehicle_category_id', $this->vehicle_category_id)
                ->pluck('duration_minutes', 'service_id')
            : collect();

        $duration = $services->sum(fn (Service $service) => (int) ($variants[$service->id] ?? $service->duration_minutes));

        return $duration > 0 ? (int) $duration : 60;
    }

    /**
     * Momento estimado en que termina el servicio.
     */
    public function endsAt(): ?Carbon
    {
        return $this->scheduled_at?->copy()->addMinutes($this->durationMinutes());
    }

    /**
     * Indica si el empleado está realizando esta cita en este momento.
     */
    public function isOngoing(?Carbon $reference = null): bool
    {
        if (! in_array($this->status, self::OPEN_STATUSES, true) || ! $this->scheduled_at) {
            return false;
        }

        if (in_array($this->status, ['on_way', 'in_progress'], true)) {
            return true;
        }

        $now = $reference ? $reference->copy() : now();

        return $this->scheduled_at->lte($now) && $this->endsAt()->gt($now);
    }

    /**
     * Nombre del cliente, ya sea registrado o invitado.
     */
    public function clientName(): string
    {
        return $this->client?->name ?? $this->guest_name ?? 'Cliente invitado';
    }

    /**
     * Dirección completa compuesta a partir de sus partes (o la original si no existen).
     */
    public function fullAddress(): string
    {
        if ($this->street || $this->neighborhood) {
            $parts = array_filter([
                trim(($this->street ?? '') . ' ' . ($this->street_number ?? '')),
                $this->neighborhood,
                $this->postal_code ? 'CP ' . $this->postal_code : null,
                $this->state,
            ]);

            return implode(', ', $parts);
        }

        return (string) ($this->address ?? '');
    }
}
