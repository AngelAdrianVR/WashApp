<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserNotification;

class BookingNotifier
{
    /**
     * Notifica a cliente, empleado y administradores cuando se registra una cita.
     */
    public static function bookingCreated(Booking $booking, ?int $actorId = null): void
    {
        $booking->loadMissing(['services', 'client', 'employee']);

        $date = $booking->scheduled_at?->locale('es')->translatedFormat('D j M');
        $time = $booking->scheduled_at?->format('H:i');
        $clientName = $booking->clientName();
        $services = $booking->services->pluck('name')->implode(', ');
        $recipients = [];

        if ($booking->user_id && $booking->client) {
            $recipients[$booking->user_id] = [
                'icon' => 'pi pi-calendar-plus',
                'tone' => 'cloud',
                'title' => 'Reserva registrada',
                'text' => "Tu servicio quedó agendado para el {$date} a las {$time}.",
                'url' => route('client.bookings.show', $booking),
            ];
        }

        if ($booking->employee_id && $booking->employee) {
            $recipients[$booking->employee_id] = [
                'icon' => 'pi pi-briefcase',
                'tone' => 'sage',
                'title' => 'Nuevo servicio asignado',
                'text' => "{$clientName} · {$date} {$time} · {$booking->fullAddress()}",
                'url' => route('employee.bookings.show', $booking),
            ];
        }

        foreach (User::where('role', 'Admin')->pluck('id') as $adminId) {
            if ($adminId === $actorId || isset($recipients[$adminId])) {
                continue;
            }

            $recipients[$adminId] = [
                'icon' => 'pi pi-inbox',
                'tone' => 'sand',
                'title' => 'Nueva reserva',
                'text' => "{$clientName} agendó {$services} para el {$date} a las {$time}.",
                'url' => route('admin.bookings.show', $booking),
            ];
        }

        self::push($recipients, $actorId, $booking->user_id);
    }

    /**
     * Notifica los cambios de estado de una cita.
     */
    public static function statusChanged(Booking $booking, string $status, ?int $actorId = null): void
    {
        $booking->loadMissing(['client', 'employee']);

        $labels = [
            'pending' => 'pendiente',
            'confirmed' => 'confirmada',
            'on_way' => 'con el operador en camino',
            'in_progress' => 'en progreso',
            'completed' => 'finalizada',
            'cancelled' => 'cancelada',
        ];

        $label = $labels[$status] ?? $status;
        $date = $booking->scheduled_at?->locale('es')->translatedFormat('D j M');
        $time = $booking->scheduled_at?->format('H:i');
        $recipients = [];

        if ($booking->user_id) {
            $recipients[$booking->user_id] = [
                'icon' => $status === 'cancelled' ? 'pi pi-times-circle' : 'pi pi-sync',
                'tone' => $status === 'cancelled' ? 'rose' : ($status === 'completed' ? 'sage' : 'cloud'),
                'title' => "Reserva {$label}",
                'text' => "Tu servicio del {$date} a las {$time} ahora está {$label}.",
                'url' => route('client.bookings.show', $booking),
            ];
        }

        if ($booking->employee_id) {
            $recipients[$booking->employee_id] = [
                'icon' => $status === 'cancelled' ? 'pi pi-times-circle' : 'pi pi-briefcase',
                'tone' => $status === 'cancelled' ? 'rose' : ($status === 'completed' ? 'sage' : 'cloud'),
                'title' => "Servicio {$label}",
                'text' => "{$booking->clientName()} · {$date} {$time}.",
                'url' => route('employee.bookings.show', $booking),
            ];
        }

        self::push($recipients, $actorId);
    }

    /**
     * Notifica al empleado cuando se le asigna una cita.
     */
    public static function employeeAssigned(Booking $booking, ?int $actorId = null): void
    {
        $booking->loadMissing(['client']);

        if (! $booking->employee_id) {
            return;
        }

        $date = $booking->scheduled_at?->locale('es')->translatedFormat('D j M');
        $time = $booking->scheduled_at?->format('H:i');

        self::push([
            $booking->employee_id => [
                'icon' => 'pi pi-briefcase',
                'tone' => 'sage',
                'title' => 'Nuevo servicio asignado',
                'text' => "{$booking->clientName()} · {$date} {$time} · {$booking->fullAddress()}",
                'url' => route('employee.bookings.show', $booking),
            ],
        ], $actorId);
    }

    /**
     * @param  array<int, array{icon: string, tone: string, title: string, text: string, url: ?string}>  $recipients
     */
    private static function push(array $recipients, ?int $actorId = null, ?int $confirmToUserId = null): void
    {
        foreach ($recipients as $userId => $payload) {
            if ($userId === $actorId && $userId !== $confirmToUserId) {
                continue;
            }

            UserNotification::create([
                'user_id' => $userId,
                ...$payload,
            ]);
        }
    }
}
