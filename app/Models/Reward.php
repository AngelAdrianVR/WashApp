<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Reward extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'is_active',
    ];

    /**
     * Atributos calculados que siempre se envían al frontend.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * La imagen del premio se gestiona con Spatie Media Library.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    /**
     * Ruta absoluta de la imagen lista para usarse en el frontend.
     */
    public function getImageUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('image');

        return $url !== '' ? $url : null;
    }

    /**
     * Premios que pueden mostrarse en la landing.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
