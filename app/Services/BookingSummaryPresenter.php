<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;

class BookingSummaryPresenter
{
    /**
     * Representación ligera de una cita para el panel del empleado.
     *
     * @return array<string, mixed>
     */
    public static function make(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'client_name' => $booking->clientName(),
            'client_phone' => $booking->client?->phone_number ?? $booking->guest_phone,
            'client_email' => $booking->client?->email ?? $booking->guest_email,
            'address' => $booking->fullAddress(),
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'completed_at' => ($booking->completed_at ?? $booking->updated_at)?->toIso8601String(),
            'ends_at' => $booking->endsAt()?->toIso8601String(),
            'status' => $booking->status,
            'total_price' => round((float) $booking->total_price, 2),
            'notes' => $booking->notes,
            'vehicle' => trim(implode(' ', array_filter([$booking->vehicle_brand, $booking->vehicle_model]))),
            'duration_minutes' => $booking->durationMinutes(),
            'services' => $booking->services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => round((float) ($service->pivot->price_at_booking ?? $service->price), 2),
                'duration_minutes' => (int) $service->duration_minutes,
            ])->values()->all(),
            'review' => self::review($booking),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function review(Booking $booking): ?array
    {
        if (! $booking->relationLoaded('review') || ! $booking->review) {
            return null;
        }

        return [
            'id' => $booking->review->id,
            'rating' => (int) $booking->review->rating,
            'comment' => $booking->review->comment,
            'created_at' => $booking->review->created_at?->toIso8601String(),
        ];
    }
}
