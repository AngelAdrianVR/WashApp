<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Service extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'features',
        'price',
        'duration_minutes',
        'type', // 'Seco' o 'Agua'
        'is_addon',
        'points',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_addon' => 'boolean',
        'points' => 'integer',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Las reservas que incluyen este servicio.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_service')
                    ->withPivot('price_at_booking') // Incluir el precio guardado
                    ->withTimestamps();
    }

    /**
     * Precio y duración por categoría de vehículo.
     */
    public function vehicleCategories(): BelongsToMany
    {
        return $this->belongsToMany(VehicleCategory::class, 'service_vehicle_category')
            ->withPivot(['price', 'duration_minutes'])
            ->withTimestamps();
    }

    /**
     * Paquetes de lavado (no son extras).
     */
    public function scopePackages($query)
    {
        return $query->where('is_addon', false);
    }

    /**
     * Servicios adicionales de upselling (encerado, detallado…).
     */
    public function scopeAddons($query)
    {
        return $query->where('is_addon', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Precio para una categoría concreta, con el precio base como respaldo.
     */
    public function priceFor(?int $vehicleCategoryId): float
    {
        $variant = $this->vehicleCategories->firstWhere('id', $vehicleCategoryId);

        return (float) ($variant?->pivot->price ?? $this->price);
    }

    /**
     * Duración en minutos para una categoría concreta.
     */
    public function durationFor(?int $vehicleCategoryId): int
    {
        $variant = $this->vehicleCategories->firstWhere('id', $vehicleCategoryId);

        return (int) ($variant?->pivot->duration_minutes ?? $this->duration_minutes);
    }
}
