<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Guarda (o actualiza) la opinión del cliente sobre un servicio finalizado.
     */
    public function store(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id) {
            abort(403, 'No autorizado para calificar este servicio.');
        }

        if ($booking->status !== 'completed') {
            return back()->withErrors(['rating' => 'Solo puedes calificar servicios finalizados.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:600'],
        ]);

        $review = Review::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id' => $user->id,
                'employee_id' => $booking->employee_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        if ($booking->employee_id) {
            $services = $booking->services()->pluck('name')->take(3)->implode(' + ') ?: 'Servicio WashApp';

            UserNotification::create([
                'user_id' => $booking->employee_id,
                'icon' => 'pi pi-star-fill',
                'tone' => 'sand',
                'title' => 'Nueva opinión de cliente',
                'text' => "{$user->name} · {$services} · {$review->rating}/5",
                'url' => route('employee.profile.show'),
            ]);
        }

        return redirect()->back()->with('success', '¡Gracias! Tu opinión quedó registrada.');
    }
}
