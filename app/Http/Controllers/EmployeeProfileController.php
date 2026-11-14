<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Services\BookingSummaryPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeProfileController extends Controller
{
    /**
     * Perfil del empleado: foto, estadísticas, puntuaciones y opiniones por servicio.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $completed = Booking::query()
            ->where('employee_id', $user->id)
            ->where('status', 'completed')
            ->with('services')
            ->get();

        $reviews = Review::query()
            ->forEmployee($user->id)
            ->with(['client', 'booking.services'])
            ->orderByDesc('created_at')
            ->get();

        $averageRating = $reviews->isNotEmpty() ? round((float) $reviews->avg('rating'), 2) : null;

        $ratingBreakdown = collect([5, 4, 3, 2, 1])
            ->map(fn (int $star) => [
                'stars' => $star,
                'count' => $reviews->where('rating', $star)->count(),
            ])
            ->map(function (array $row) use ($reviews) {
                $row['share'] = $reviews->count() > 0
                    ? (int) round($row['count'] / $reviews->count() * 100)
                    : 0;

                return $row;
            })
            ->all();

        $recentServices = Booking::query()
            ->where('employee_id', $user->id)
            ->where('status', 'completed')
            ->with(['services', 'client', 'review'])
            ->orderByRaw('COALESCE(completed_at, updated_at) DESC')
            ->limit(5)
            ->get()
            ->map(fn (Booking $booking) => BookingSummaryPresenter::make($booking))
            ->values();

        $upcoming = Booking::query()
            ->where('employee_id', $user->id)
            ->open()
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->with(['services', 'client'])
            ->orderBy('scheduled_at')
            ->limit(4)
            ->get()
            ->map(fn (Booking $booking) => BookingSummaryPresenter::make($booking))
            ->values();

        return Inertia::render('Employee/Profile/Show', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
                'photo_url' => $user->profile_photo_url,
                'member_since' => optional($user->created_at)->translatedFormat('F Y'),
                'is_active' => (bool) $user->is_active,
            ],
            'stats' => [
                'completed' => $completed->count(),
                'revenue' => round((float) $completed->sum('total_price'), 2),
                'hours' => round($completed->sum(fn (Booking $booking) => $booking->durationMinutes()) / 60, 1),
                'average_rating' => $averageRating,
                'reviews_count' => $reviews->count(),
                'five_star' => $reviews->where('rating', 5)->count(),
                'clients_served' => $completed
                    ->map(fn (Booking $booking) => $booking->user_id ?? $booking->guest_phone ?? $booking->guest_email ?? 'guest-' . $booking->id)
                    ->unique()
                    ->count(),
                'this_month' => Booking::query()
                    ->where('employee_id', $user->id)
                    ->completedBetween(now()->startOfMonth(), now()->endOfMonth())
                    ->count(),
            ],
            'ratingBreakdown' => $ratingBreakdown,
            'reviews' => $reviews->take(20)->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'date_label' => $review->created_at?->locale('es')->translatedFormat('d M Y'),
                'client_name' => $review->client?->name ?? $review->booking?->clientName() ?? 'Cliente',
                'client_avatar' => $review->client?->avatar_url ?: $review->client?->profile_photo_url,
                'booking_id' => $review->booking_id,
                'service_date' => $review->booking?->completed_at?->locale('es')->translatedFormat('d M Y'),
                'services' => $review->booking?->services->pluck('name')->values()->all() ?? [],
            ])->values(),
            'recentServices' => $recentServices,
            'upcoming' => $upcoming,
        ]);
    }

    /**
     * Datos básicos del empleado.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($validated);

        return redirect()->back()->with('success', 'Datos actualizados.');
    }

    /**
     * Foto de perfil gestionada con Spatie Media Library.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        $request->user()
            ->addMediaFromRequest('photo')
            ->toMediaCollection('avatar');

        return redirect()->back()->with('success', 'Foto de perfil actualizada.');
    }

    public function destroyAvatar(Request $request)
    {
        $request->user()->clearMediaCollection('avatar');

        return redirect()->back()->with('success', 'Foto de perfil eliminada.');
    }
}
