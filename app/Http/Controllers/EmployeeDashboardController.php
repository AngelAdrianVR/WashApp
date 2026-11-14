<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Services\BookingSummaryPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Throwable;

class EmployeeDashboardController extends Controller
{
    private const STATUS_LABELS = [
        'pending' => 'Pendientes',
        'confirmed' => 'Confirmadas',
        'on_way' => 'En camino',
        'in_progress' => 'En servicio',
        'completed' => 'Finalizadas',
        'cancelled' => 'Canceladas',
    ];

    /**
     * Panel del empleado: servicios realizados por mes o año, estadísticas y agenda.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period') === 'year' ? 'year' : 'month';
        $anchor = $this->anchorDate($request->query('date'), $period);

        [$start, $end] = $this->periodRange($anchor, $period);

        $previousAnchor = $period === 'year' ? $anchor->copy()->subYearNoOverflow() : $anchor->copy()->subMonthNoOverflow();
        [$previousStart, $previousEnd] = $this->periodRange($previousAnchor, $period);

        $base = Booking::query()->where('employee_id', $user->id);

        // --- Servicios realizados en el periodo ---
        $completedBookings = (clone $base)
            ->completedBetween($start, $end)
            ->with(['services', 'client', 'review.client'])
            ->orderByRaw('COALESCE(completed_at, updated_at) DESC')
            ->get();

        $previousCompletedCount = (clone $base)->completedBetween($previousStart, $previousEnd)->count();
        $previousRevenue = (float) (clone $base)->completedBetween($previousStart, $previousEnd)->sum('total_price');

        $completedCount = $completedBookings->count();
        $revenue = round((float) $completedBookings->sum('total_price'), 2);
        $minutesWorked = (int) $completedBookings->sum(fn (Booking $booking) => $booking->durationMinutes());
        $averageTicket = $completedCount > 0 ? round($revenue / $completedCount, 2) : 0;

        // --- Opiniones de los servicios del periodo ---
        $periodReviews = $completedBookings
            ->pluck('review')
            ->filter()
            ->sortByDesc('created_at')
            ->values();

        $averageRating = $periodReviews->isNotEmpty() ? round((float) $periodReviews->avg('rating'), 2) : null;
        $ratingBreakdown = collect([5, 4, 3, 2, 1])
            ->map(fn (int $star) => [
                'stars' => $star,
                'count' => $periodReviews->where('rating', $star)->count(),
            ])
            ->all();

        // --- Agenda del periodo (según fecha programada) ---
        $agenda = (clone $base)->whereBetween('scheduled_at', [$start, $end])->get(['status']);
        $agendaTotal = $agenda->count();
        $agendaCompleted = $agenda->where('status', 'completed')->count();
        $cancelledCount = $agenda->where('status', 'cancelled')->count();
        $completionRate = $agendaTotal > 0 ? (int) round($agendaCompleted / $agendaTotal * 100) : null;

        $statusBreakdown = collect(self::STATUS_LABELS)
            ->map(fn (string $label, string $status) => [
                'status' => $status,
                'label' => $label,
                'count' => $agenda->where('status', $status)->count(),
            ])
            ->values()
            ->all();

        // --- Próximos servicios asignados ---
        $upcomingBookings = (clone $base)
            ->open()
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->with(['services', 'client'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        // --- Servicios por tipo en el periodo ---
        $serviceStats = $completedBookings
            ->flatMap(fn (Booking $booking) => $booking->services->map(fn ($service) => [
                'name' => $service->name,
                'price' => (float) ($service->pivot->price_at_booking ?? $service->price),
            ]))
            ->groupBy('name')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()['name'],
                'count' => $rows->count(),
                'revenue' => round((float) $rows->sum('price'), 2),
            ])
            ->sortByDesc('count')
            ->values();

        $totalServiceLines = max((int) $serviceStats->sum('count'), 1);
        $topServices = $serviceStats->take(5)->map(fn (array $row) => [
            ...$row,
            'share' => (int) round($row['count'] / $totalServiceLines * 100),
        ])->values()->all();

        // --- Días con más actividad ---
        $byDay = $completedBookings
            ->filter(fn (Booking $booking) => $booking->completed_at !== null)
            ->groupBy(fn (Booking $booking) => $booking->completed_at->toDateString())
            ->map(fn (Collection $rows, string $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('es')->translatedFormat('D j M'),
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $clientsServed = $completedBookings
            ->map(fn (Booking $booking) => $booking->user_id ?? $booking->guest_phone ?? $booking->guest_email ?? 'guest-' . $booking->id)
            ->unique()
            ->count();

        $activeDays = max($byDay->count(), 1);

        return Inertia::render('Employee/Dashboard', [
            'employee' => [
                'name' => $user->name,
                'avatar_url' => $user->avatar_url ?: $user->profile_photo_url,
                'role' => $user->role,
                'member_since' => optional($user->created_at)->translatedFormat('F Y'),
            ],
            'period' => $this->periodPayload($start, $end, $period),
            'years' => $this->yearOptions($user),
            'kpis' => [
                'completed' => $completedCount,
                'completed_change' => $this->change($completedCount, $previousCompletedCount),
                'revenue' => $revenue,
                'revenue_change' => $this->change($revenue, $previousRevenue),
                'average_ticket' => $averageTicket,
                'average_rating' => $averageRating,
                'reviews_count' => $periodReviews->count(),
                'hours' => round($minutesWorked / 60, 1),
                'completion_rate' => $completionRate,
                'cancelled' => $cancelledCount,
                'upcoming_count' => (clone $base)->open()->where('scheduled_at', '>=', now()->startOfDay())->count(),
            ],
            'highlights' => [
                'clients_served' => $clientsServed,
                'busiest_day' => $byDay->first(),
                'average_per_active_day' => round($completedCount / $activeDays, 1),
                'top_service' => $topServices[0] ?? null,
                'agenda_total' => $agendaTotal,
            ],
            'series' => $this->buildSeries($completedBookings, $start, $period),
            'statusBreakdown' => $statusBreakdown,
            'topServices' => $topServices,
            'ratingBreakdown' => $ratingBreakdown,
            'reviews' => $this->presentReviews($completedBookings->filter(fn (Booking $booking) => $booking->review !== null)->take(4)),
            'upcoming' => $upcomingBookings->map(fn (Booking $booking) => BookingSummaryPresenter::make($booking))->values(),
            'recent' => $completedBookings->take(5)->map(fn (Booking $booking) => BookingSummaryPresenter::make($booking))->values(),
            'career' => $this->careerStats($user),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodRange(Carbon $anchor, string $period): array
    {
        if ($period === 'year') {
            return [
                $anchor->copy()->startOfYear(),
                $anchor->copy()->endOfYear(),
            ];
        }

        return [
            $anchor->copy()->startOfMonth(),
            $anchor->copy()->endOfMonth(),
        ];
    }

    private function anchorDate(?string $raw, string $period): Carbon
    {
        try {
            if ($period === 'year') {
                $year = $raw !== null ? substr($raw, 0, 4) : null;

                return $year && ctype_digit($year) ? Carbon::create((int) $year, 1, 1) : now();
            }

            if ($raw && preg_match('/^\d{4}-\d{2}$/', $raw)) {
                return Carbon::createFromFormat('Y-m', $raw)->startOfMonth();
            }
        } catch (Throwable) {
            // Fecha inválida: se usa el periodo actual.
        }

        return now();
    }

    /**
     * @return array<string, mixed>
     */
    private function periodPayload(Carbon $start, Carbon $end, string $period): array
    {
        $now = now();

        return [
            'mode' => $period,
            'value' => $period === 'year' ? (string) $start->year : $start->format('Y-m'),
            'label' => ucfirst($start->copy()->locale('es')->translatedFormat($period === 'year' ? 'Y' : 'F Y')),
            'range_label' => $period === 'year'
                ? 'Enero – Diciembre ' . $start->year
                : $start->copy()->format('d M') . ' – ' . $end->copy()->format('d M Y'),
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'prev' => $period === 'year'
                ? (string) ($start->year - 1)
                : $start->copy()->subMonthNoOverflow()->format('Y-m'),
            'next' => $period === 'year'
                ? (string) ($start->year + 1)
                : $start->copy()->addMonthNoOverflow()->format('Y-m'),
            'is_current' => $period === 'year'
                ? $start->year === $now->year
                : $start->isSameMonth($now),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function yearOptions(User $user): array
    {
        $first = Booking::query()
            ->where('employee_id', $user->id)
            ->min('scheduled_at');

        $firstYear = $first ? (int) Carbon::parse($first)->year : (int) now()->year;
        $currentYear = (int) now()->year;
        $startYear = min($firstYear, $currentYear);

        return collect(range($startYear, $currentYear))
            ->sortDesc()
            ->map(fn (int $year) => (string) $year)
            ->values()
            ->all();
    }

    /**
     * @return array{labels: array<int, string>, completed: array<int, int>, revenue: array<int, float>}
     */
    private function buildSeries(Collection $bookings, Carbon $start, string $period): array
    {
        if ($period === 'year') {
            $labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            $completed = array_fill(0, 12, 0);
            $revenue = array_fill(0, 12, 0.0);

            foreach ($bookings as $booking) {
                $date = $booking->completed_at ?? $booking->updated_at;

                if (! $date) {
                    continue;
                }

                $index = (int) $date->format('n') - 1;
                $completed[$index]++;
                $revenue[$index] += (float) $booking->total_price;
            }

            return [
                'labels' => $labels,
                'completed' => $completed,
                'revenue' => array_map(fn (float $value) => round($value, 2), $revenue),
            ];
        }

        $days = (int) $start->daysInMonth;
        $completed = array_fill(0, $days, 0);
        $revenue = array_fill(0, $days, 0.0);

        foreach ($bookings as $booking) {
            $date = $booking->completed_at ?? $booking->updated_at;

            if (! $date) {
                continue;
            }

            $index = (int) $date->format('j') - 1;
            $completed[$index]++;
            $revenue[$index] += (float) $booking->total_price;
        }

        return [
            'labels' => array_map(fn (int $day) => (string) $day, range(1, $days)),
            'completed' => $completed,
            'revenue' => array_map(fn (float $value) => round($value, 2), $revenue),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presentReviews(Collection $bookings): array
    {
        return $bookings->map(function (Booking $booking) {
            $review = $booking->review;

            return [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'date_label' => $review->created_at?->locale('es')->translatedFormat('d M Y'),
                'client_name' => $booking->clientName(),
                'client_avatar' => $booking->client?->avatar_url ?: $booking->client?->profile_photo_url,
                'booking_id' => $booking->id,
                'services' => $booking->services->pluck('name')->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function careerStats(User $user): array
    {
        $completed = Booking::query()
            ->where('employee_id', $user->id)
            ->where('status', 'completed')
            ->with('services')
            ->get();

        $reviews = Review::query()->forEmployee($user->id);

        return [
            'completed' => $completed->count(),
            'revenue' => round((float) $completed->sum('total_price'), 2),
            'hours' => round($completed->sum(fn (Booking $booking) => $booking->durationMinutes()) / 60, 1),
            'average_rating' => ($average = (clone $reviews)->avg('rating')) ? round((float) $average, 2) : null,
            'reviews_count' => (clone $reviews)->count(),
            'clients_served' => $completed
                ->map(fn (Booking $booking) => $booking->user_id ?? $booking->guest_phone ?? $booking->guest_email ?? 'guest-' . $booking->id)
                ->unique()
                ->count(),
        ];
    }

    private function change(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
