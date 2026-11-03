<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Car extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Lados del vehículo que se documentan con una foto cada uno.
     */
    public const SIDES = [
        'front' => 'Frente',
        'left' => 'Lateral izquierdo',
        'right' => 'Lateral derecho',
        'back' => 'Trasera',
    ];

    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'year',
        'color',
        'plates',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    /**
     * Máximo 4 fotos, una por cada lado del vehículo.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fotos indexadas por lado: ['front' => [...], 'left' => [...]].
     */
    public function photosBySide(): array
    {
        $photos = $this->getMedia('photos');

        return collect(self::SIDES)
            ->map(function (string $label, string $side) use ($photos) {
                $media = $photos->first(fn ($item) => $item->getCustomProperty('side') === $side);

                return [
                    'side' => $side,
                    'label' => $label,
                    'media_id' => $media?->id,
                    'url' => $media?->getUrl(),
                ];
            })
            ->values()
            ->all();
    }

    public function toCatalogArray(): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'plates' => $this->plates,
            'photos' => $this->photosBySide(),
        ];
    }
}
