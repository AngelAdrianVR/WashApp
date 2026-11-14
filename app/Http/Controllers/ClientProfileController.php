<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\RewardRedemption;
use App\Models\UserReward;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProfileController extends Controller
{
    /**
     * Perfil del cliente: foto, puntos, datos y vehículos.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['cars.vehicleCategory']);
        $bookings = Booking::query()->where('user_id', $user->id);

        $completedBookings = (clone $bookings)
            ->where('status', 'completed')
            ->get(['id', 'car_id', 'vehicle_brand', 'vehicle_model', 'scheduled_at', 'completed_at']);

        $cars = $user->cars
            ->map(function (Car $car) use ($completedBookings) {
                $washed = $completedBookings->filter(function (Booking $booking) use ($car) {
                    if ($booking->car_id) {
                        return $booking->car_id === $car->id;
                    }

                    // Reservas históricas sin car_id: se comparan por marca y modelo.
                    return $booking->vehicle_brand === $car->brand
                        && $booking->vehicle_model === $car->model;
                });

                $lastWash = $washed
                    ->sortByDesc(fn (Booking $booking) => ($booking->completed_at ?? $booking->scheduled_at)?->timestamp ?? 0)
                    ->first();

                return [
                    ...$car->toCatalogArray(),
                    'wash_count' => $washed->count(),
                    'last_wash_at' => ($lastWash?->completed_at ?? $lastWash?->scheduled_at)?->translatedFormat('d M Y'),
                ];
            })
            ->values();

        $promotionRewards = UserReward::query()
            ->with('promotion')
            ->where('user_id', $user->id)
            ->where('is_claimed', true)
            ->orderByDesc('claimed_at')
            ->get()
            ->map(function (UserReward $reward) {
                $promotion = $reward->promotion;

                $discount = null;

                if ($promotion) {
                    $value = (float) $promotion->discount_value;
                    $discount = $promotion->discount_type === 'Porcentaje'
                        ? rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%'
                        : '$' . number_format($value, 2) . ' MXN';
                }

                return [
                    'id' => 'promo-' . $reward->id,
                    'code' => $promotion?->code,
                    'description' => $promotion?->description,
                    'discount' => $discount,
                    'claimed_at' => optional($reward->claimed_at)->translatedFormat('d M Y'),
                    'date_iso' => optional($reward->claimed_at)->toIso8601String(),
                ];
            });

        $catalogRewards = RewardRedemption::query()
            ->with('reward')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn (RewardRedemption $redemption) => [
                'id' => 'reward-' . $redemption->id,
                'code' => $redemption->reward?->name ?? 'Premio canjeado',
                'description' => $redemption->reward?->description,
                'discount' => $redemption->points_spent . ' pts',
                'claimed_at' => optional($redemption->redeemed_at)->translatedFormat('d M Y'),
                'date_iso' => optional($redemption->redeemed_at)->toIso8601String(),
            ]);

        $claimedRewards = $promotionRewards
            ->concat($catalogRewards)
            ->sortByDesc('date_iso')
            ->values();

        return Inertia::render('Client/Profile/Show', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'points' => $user->points,
                'avatar_url' => $user->avatar_url,
                'member_since' => optional($user->created_at)->translatedFormat('F Y'),
            ],
            'stats' => [
                'total_services' => (clone $bookings)->count(),
                'completed_services' => (clone $bookings)->where('status', 'completed')->count(),
                'upcoming_services' => (clone $bookings)
                    ->whereIn('status', Booking::OPEN_STATUSES)
                    ->where('scheduled_at', '>=', now())
                    ->count(),
                'cancelled_services' => (clone $bookings)->where('status', 'cancelled')->count(),
                'total_spent' => round((float) (clone $bookings)->where('status', 'completed')->sum('total_price'), 2),
            ],
            'cars' => $cars,
            'addresses' => $user->addresses()
                ->orderByDesc('is_default')
                ->orderBy('label')
                ->get()
                ->map(fn ($address) => $address->toOptionArray())
                ->values(),
            'claimedRewards' => $claimedRewards,
            'vehicleCategories' => VehicleCategory::active()
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),
            'sides' => Car::SIDES,
        ]);
    }

    /**
     * Datos básicos del cliente.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
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
            'photo' => 'required|image|max:4096',
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
