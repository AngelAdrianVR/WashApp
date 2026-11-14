<?php

namespace App\Services;

use App\Models\BusinessException;
use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BusinessScheduleService
{
    private ?BusinessSetting $settings = null;

    /**
     * @var array<string, Collection<int, BusinessException>>
     */
    private array $exceptionCache = [];

    public function settings(): BusinessSetting
    {
        return $this->settings ??= BusinessSetting::current();
    }

    /**
     * @return array<int, int>
     */
    public function operatingDays(): array
    {
        return $this->settings()->operatingDays();
    }

    public function openingTime(): string
    {
        return $this->settings()->openingTime();
    }

    public function closingTime(): string
    {
        return $this->settings()->closingTime();
    }

    public function dayStart(Carbon $date): Carbon
    {
        return $date->copy()->startOfDay()->setTimeFromTimeString($this->openingTime());
    }

    public function dayEnd(Carbon $date): Carbon
    {
        return $date->copy()->startOfDay()->setTimeFromTimeString($this->closingTime());
    }

    /**
     * @return Collection<int, BusinessException>
     */
    public function exceptionsFor(Carbon $date): Collection
    {
        $key = $date->toDateString();

        if (! array_key_exists($key, $this->exceptionCache)) {
            $this->exceptionCache[$key] = BusinessException::query()
                ->whereDate('date', $key)
                ->orderBy('start_time')
                ->get();
        }

        return $this->exceptionCache[$key];
    }

    public function isOperatingDay(Carbon $date): bool
    {
        return in_array($date->copy()->startOfDay()->dayOfWeek, $this->operatingDays(), true);
    }

    public function fullDayClosure(Carbon $date): ?BusinessException
    {
        return $this->exceptionsFor($date)->first(fn (BusinessException $exception) => $exception->is_closed);
    }

    public function isClosedOn(Carbon $date): bool
    {
        return ! $this->isOperatingDay($date) || $this->fullDayClosure($date) !== null;
    }

    /**
     * Indica si un intervalo completo cae dentro del horario de operación.
     */
    public function isOpenWindow(Carbon $start, Carbon $end): bool
    {
        $date = $start->copy()->startOfDay();

        if ($this->isClosedOn($date)) {
            return false;
        }

        if ($start->lt($this->dayStart($date)) || $end->gt($this->dayEnd($date))) {
            return false;
        }

        return ! $this->overlapsClosure($start, $end, $date);
    }

    /**
     * Indica si el intervalo choca con un descanso especial (día completo o parcial).
     */
    public function overlapsClosure(Carbon $start, Carbon $end, ?Carbon $date = null): bool
    {
        $date = ($date ?? $start)->copy()->startOfDay();

        if ($this->isClosedOn($date)) {
            return true;
        }

        foreach ($this->exceptionsFor($date) as $exception) {
            if ($exception->is_closed) {
                return true;
            }

            if (! $exception->start_time || ! $exception->end_time) {
                continue;
            }

            $closedStart = $date->copy()->setTimeFromTimeString($exception->startTime());
            $closedEnd = $date->copy()->setTimeFromTimeString($exception->endTime());

            if ($start->lt($closedEnd) && $end->gt($closedStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Horario que consume el calendario del frontend.
     *
     * @return array<string, mixed>
     */
    public function frontendSchedule(?Carbon $from = null, int $days = 120): array
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $to = $from->copy()->addDays($days);

        return [
            'opening_time' => $this->openingTime(),
            'closing_time' => $this->closingTime(),
            'operating_days' => $this->operatingDays(),
            'min_date' => $from->toDateString(),
            'max_date' => $to->toDateString(),
            'exceptions' => BusinessException::query()
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString())
                ->orderBy('date')
                ->get()
                ->map(fn (BusinessException $exception) => $exception->toScheduleArray())
                ->values()
                ->all(),
        ];
    }
}
