<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'image_path',
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
     * Ruta absoluta de la imagen lista para usarse en el frontend.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Str::startsWith($this->image_path, ['http://', 'https://', '/'])
            ? $this->image_path
            : asset($this->image_path);
    }

    /**
     * Premios que pueden mostrarse en la landing.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
