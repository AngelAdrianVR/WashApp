<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookingMessageImage extends Model
{
    protected $fillable = [
        'booking_message_id',
        'path',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(BookingMessage::class, 'booking_message_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
