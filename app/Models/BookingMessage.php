<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingMessage extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'body',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(BookingMessageImage::class);
    }

    /**
     * Representación del mensaje para el chat de la reserva.
     *
     * @return array<string, mixed>
     */
    public function toChatArray(int $viewerId): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender_id' => $this->user_id,
            'sender_name' => $this->user?->name ?? 'Usuario',
            'sender_avatar' => $this->user?->avatar_url ?: $this->user?->profile_photo_url,
            'sender_role' => $this->user?->role,
            'mine' => $this->user_id === $viewerId,
            'images' => $this->images->map(fn (BookingMessageImage $image) => [
                'id' => $image->id,
                'url' => $image->url(),
            ])->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'time_label' => $this->created_at?->format('H:i'),
            'date_label' => $this->created_at
                ? $this->created_at->locale('es')->translatedFormat('j M')
                : null,
        ];
    }
}
