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

    /**
     * Etiqueta legible de los días de operación, por ejemplo "Lun a Sáb".
     */
    public function daysLabel(): string
    {
        $names = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $days = $this->operatingDays();
        sort($days);

        if (count($days) === 7) {
            return 'Todos los días';
        }

        if (count($days) >= 3 && $days === range($days[0], $days[0] + count($days) - 1)) {
            return $names[$days[0]] . ' a ' . $names[$days[count($days) - 1]];
        }

        $labels = array_map(fn (int $day) => $names[$day], $days);

        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels) . ' y ' . $last;
    }

    /**
     * Horario completo listo para mostrar en el sitio público.
     */
    public function scheduleLabel(): string
    {
        return $this->daysLabel() . ' · ' . $this->openingTime() . ' – ' . $this->closingTime() . ' h';
    }
}
