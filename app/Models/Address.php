<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'street',
        'street_number',
        'neighborhood',
        'postal_code',
        'city',
        'state',
        'latitude',
        'longitude',
        'references',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dirección en una sola línea para resúmenes y mapas.
     */
    public function fullAddress(): string
    {
        return implode(', ', array_filter([
            trim(($this->street ?? '') . ' ' . ($this->street_number ?? '')),
            $this->neighborhood,
            $this->postal_code ? 'CP ' . $this->postal_code : null,
            $this->city,
            $this->state,
        ]));
    }

    /**
     * Estructura que consume el frontend.
     *
     * @return array<string, mixed>
     */
    public function toOptionArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'street' => $this->street,
            'street_number' => $this->street_number,
            'neighborhood' => $this->neighborhood,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'state' => $this->state,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'references' => $this->references,
            'is_default' => (bool) $this->is_default,
            'full_address' => $this->fullAddress(),
        ];
    }
}
