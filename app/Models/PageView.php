<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'path',
        'route_name',
        'user_id',
        'session_hash',
        'ip_hash',
        'device',
        'referrer',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Usuario autenticado que generó la vista (si aplica).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
