<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Services\BookingSummaryPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeServiceController extends Controller
{
    /**
     * Historial de servicios realizados por el empleado (del más reciente al más antiguo).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'digits:4'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $month = $validated['month'] ?? null;
        $year = $validated['year'] ?? null;

        $query = Booking::query()
            ->where('employee_id', $user->id)
            ->where('status', 'completed')
            ->with(['services', 'client', 'review'])
            ->when($month, function ($query, $month) {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

                $query->whereRaw('COALESCE(completed_at, updated_at) BETWEEN ? AND ?', [
                    $start,
                    $start->copy()->endOfMonth(),
                ]);
            })
            ->when(! $month && $year, function ($query, $year) {
                $start = Carbon::create((int) $year, 1, 1);

                $query->whereRaw('COALESCE(completed_at, updated_at) BETWEEN ? AND ?', [
                    $start,
                    $start->copy()->endOfYear(),
                ]);
            })
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('guest_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('vehicle_brand', 'like', "%{$search}%")
                        ->orWhere('vehicle_model', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('services', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            });

        $bookings = (clone $query)
            ->orderByRaw('COALESCE(completed_at, updated_at) DESC')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Booking $booking) => BookingSummaryPresenter::make($booking));

        $completed = Booking::query()
            ->where('employee_id', $user->id)
            ->where('status', 'completed')
            ->with('services')
            ->get();

        $reviews = Review::query()->forEmployee($user->id);
        $averageRating = (clone $reviews)->avg('rating');

        return Inertia::render('Employee/Services/Index', [
            'bookings' => $bookings,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'search' => $validated['search'] ?? null,
            ],
            'summary' => [
                'total' => $completed->count(),
                'revenue' => round((float) $completed->sum('total_price'), 2),
                'hours' => round($completed->sum(fn (Booking $booking) => $booking->durationMinutes()) / 60, 1),
                'average_rating' => $averageRating ? round((float) $averageRating, 2) : null,
                'reviews_count' => (clone $reviews)->count(),
                'this_month' => Booking::where('employee_id', $user->id)
                    ->completedBetween(now()->startOfMonth(), now()->endOfMonth())
                    ->count(),
            ],
            'monthOptions' => $this->monthOptions($completed),
            'yearOptions' => $this->yearOptions($completed),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Booking>  $completed
     * @return array<int, array{value: string, label: string}>
     */
    private function monthOptions($completed): array
    {
        $months = $completed
            ->map(fn (Booking $booking) => ($booking->completed_at ?? $booking->updated_at)?->format('Y-m'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->take(24)
            ->map(fn (string $month) => [
                'value' => $month,
                'label' => ucfirst(Carbon::createFromFormat('Y-m', $month)->locale('es')->translatedFormat('F Y')),
            ])
            ->values()
            ->all();

        if ($months === []) {
            $months[] = [
                'value' => now()->format('Y-m'),
                'label' => ucfirst(now()->locale('es')->translatedFormat('F Y')),
            ];
        }

        return $months;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Booking>  $completed
     * @return array<int, string>
     */
    private function yearOptions($completed): array
    {
        $years = $completed
            ->map(fn (Booking $booking) => ($booking->completed_at ?? $booking->updated_at)?->year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if ($years->isEmpty()) {
            $years->push((int) now()->year);
        }

        if (! $years->contains((int) now()->year)) {
            $years->prepend((int) now()->year);
        }

        return $years->map(fn (int $year) => (string) $year)->all();
    }
}
