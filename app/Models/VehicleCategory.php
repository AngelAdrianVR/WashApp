<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VehicleCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Paquetes de lavado que tienen precio para esta categoría.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_vehicle_category')
            ->withPivot(['price', 'duration_minutes'])
            ->withTimestamps();
    }

    /**
     * Categorías activas ordenadas para mostrar en la landing y el panel.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
