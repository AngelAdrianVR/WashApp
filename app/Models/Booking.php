<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'car_id',
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
        'assignment_requested_at',
        'arrival_eta_minutes',
        'en_route_at',
        'arrival_window_start',
        'arrival_window_end',
        'arrived_at',
        'client_confirmed_at',
        'air_freshener',
        'air_freshener_accepted',
        'tip_amount',
        'tip_method',
        'points_awarded',
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
            'assignment_requested_at' => 'datetime',
            'en_route_at' => 'datetime',
            'arrival_window_start' => 'datetime',
            'arrival_window_end' => 'datetime',
            'arrived_at' => 'datetime',
            'client_confirmed_at' => 'datetime',
            'arrival_eta_minutes' => 'integer',
            'points_awarded' => 'integer',
            'tip_amount' => 'decimal:2',
            'air_freshener_accepted' => 'boolean',
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
     * El vehículo registrado con el que se agendó la reserva.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
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
     * Opinión y puntuación que dejó el cliente al finalizar el servicio.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Inspección visual del vehículo antes del lavado.
     */
    public function inspection(): HasOne
    {
        return $this->hasOne(BookingInspection::class);
    }

    /**
     * Mensajes del chat entre el cliente y el colaborador asignado.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BookingMessage::class);
    }

    /**
     * Citas que todavía no se finalizan ni se cancelan.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    /**
     * Indica si la cita está esperando que un administrador asigne al personal.
     */
    public function isAwaitingAssignment(): bool
    {
        return $this->employee_id === null && in_array($this->status, ['pending', 'confirmed'], true);
    }

    /**
     * Ventana de llegada estimada (hora indicada por el operador + 7 minutos).
     */
    public function arrivalWindowLabel(): ?string
    {
        if (! $this->arrival_window_start) {
            return null;
        }

        $format = fn (Carbon $moment) => $moment->format('g:i a');
        $start = $this->arrival_window_start;
        $end = $this->arrival_window_end ?? $this->arrival_window_start->copy()->addMinutes(7);

        return $format($start) . ' – ' . $format($end);
    }

    /**
     * Minutos mínimos desde la llegada antes de poder finalizar el servicio.
     */
    public const COMPLETION_LOCK_MINUTES = 15;

    /**
     * Momento a partir del cual el colaborador puede finalizar el servicio.
     */
    public function completionAvailableAt(): ?Carbon
    {
        if (! $this->arrived_at) {
            return null;
        }

        return $this->arrived_at->copy()->addMinutes(self::COMPLETION_LOCK_MINUTES);
    }

    /**
     * Indica si ya se puede marcar el servicio como finalizado.
     */
    public function canBeCompleted(?Carbon $reference = null): bool
    {
        $availableAt = $this->completionAvailableAt();

        if (! $availableAt) {
            return true;
        }

        return ($reference ? $reference->copy() : now())->gte($availableAt);
    }

    /**
     * Minutos reales que tardó el servicio (desde la llegada al domicilio).
     */
    public function actualDurationMinutes(): ?int
    {
        if (! $this->completed_at) {
            return null;
        }

        $start = $this->arrived_at ?? $this->started_at ?? $this->scheduled_at;

        if (! $start) {
            return null;
        }

        return max(1, (int) $start->diffInMinutes($this->completed_at));
    }

    /**
     * Puntos que otorga esta reserva por los servicios contratados.
     */
    public function servicePoints(): int
    {
        return (int) $this->services->sum(fn (Service $service) => (int) $service->points);
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
     * Folio legible del servicio (WA-00001).
     */
    public function folio(): string
    {
        return 'WA-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Fecha corta para notificaciones (ej. "mar 18 nov").
     */
    public function scheduledShortLabel(): string
    {
        return $this->scheduled_at
            ? $this->scheduled_at->locale('es')->translatedFormat('D j M · H:i')
            : 'Sin fecha';
    }

    /**
     * Fecha larga para correos (ej. "martes 18 de noviembre").
     */
    public function scheduledLongLabel(): string
    {
        return $this->scheduled_at
            ? $this->scheduled_at->locale('es')->translatedFormat('l j \d\e F')
            : 'Sin fecha';
    }

    /**
     * Marca y modelo del vehículo.
     */
    public function vehicleLabel(): string
    {
        return trim(implode(' ', array_filter([$this->vehicle_brand, $this->vehicle_model]))) ?: '';
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
