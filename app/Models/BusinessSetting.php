<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    /**
     * Días de operación por defecto: lunes a sábado (0 = domingo).
     *
     * @var array<int, int>
     */
    public const DEFAULT_OPERATING_DAYS = [1, 2, 3, 4, 5, 6];

    protected $fillable = [
        'opening_time',
        'closing_time',
        'operating_days',
    ];

    protected function casts(): array
    {
        return [
            'operating_days' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
            'operating_days' => self::DEFAULT_OPERATING_DAYS,
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function operatingDays(): array
    {
        $days = $this->operating_days;

        if (! is_array($days) || $days === []) {
            return self::DEFAULT_OPERATING_DAYS;
        }

        return array_values(array_map('intval', $days));
    }

    public function openingTime(): string
    {
        return substr((string) $this->opening_time, 0, 5);
    }

    public function closingTime(): string
    {
        return substr((string) $this->closing_time, 0, 5);
    }
}
