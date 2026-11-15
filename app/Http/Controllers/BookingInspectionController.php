<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\UserNotification;
use App\Services\BookingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingInspectionController extends Controller
{
    /**
     * El colaborador (o un administrador) registra el estado estético del vehículo.
     */
    public function store(Request $request, Booking $booking)
    {
        $user = Auth::user();

        if ($user->role === 'Empleado' && $booking->employee_id !== $user->id) {
            abort(403, 'No autorizado para inspeccionar este vehículo.');
        }

        if (! in_array($booking->status, ['confirmed', 'on_way', 'in_progress'], true)) {
            return back()->with('error', 'La inspección solo puede registrarse con el servicio activo.');
        }

        if ($booking->inspection?->isConfirmed()) {
            return back()->with('error', 'El cliente ya confirmó la inspección: no se puede modificar.');
        }

        $validated = $request->validate([
            'markers' => ['array'],
            'markers.*.view' => ['required', 'string', 'in:front,rear,left,right'],
            'markers.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'markers.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
            'markers.*.type' => ['required', 'string', 'in:scratch,dent,chip,crack,stain,other'],
            'markers.*.note' => ['nullable', 'string', 'max:140'],
            'notes' => ['nullable', 'string', 'max:600'],
        ]);

        $inspection = BookingInspection::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id' => $user->id,
                'markers' => $validated['markers'] ?? [],
                'notes' => $validated['notes'] ?? null,
                'client_confirmed_at' => null,
            ]
        );

        BookingNotifier::inspectionRequested($booking->refresh()->load('client', 'employee', 'inspection'));

        return back()->with('success', 'Inspección registrada. El cliente debe confirmarla para continuar.');
    }

    /**
     * El cliente confirma que la inspección del vehículo es correcta.
     */
    public function confirm(Request $request, Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'No autorizado para confirmar esta inspección.');
        }

        $inspection = $booking->inspection;

        if (! $inspection) {
            return back()->with('error', 'Todavía no hay una inspección registrada.');
        }

        if (! $inspection->isConfirmed()) {
            $inspection->update(['client_confirmed_at' => now()]);
        }

        BookingNotifier::inspectionConfirmed($booking->refresh()->load('employee'));

        return back()->with('success', '¡Gracias! La inspección quedó confirmada.');
    }
}
