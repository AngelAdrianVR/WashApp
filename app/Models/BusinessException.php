<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessException extends Model
{
    protected $fillable = [
        'date',
        'label',
        'is_closed',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function startTime(): ?string
    {
        return $this->start_time ? substr((string) $this->start_time, 0, 5) : null;
    }

    public function endTime(): ?string
    {
        return $this->end_time ? substr((string) $this->end_time, 0, 5) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toScheduleArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'label' => $this->label,
            'is_closed' => (bool) $this->is_closed,
            'start_time' => $this->startTime(),
            'end_time' => $this->endTime(),
        ];
    }
}
