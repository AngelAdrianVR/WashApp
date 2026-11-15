<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserNotification;

class BookingNotifier
{
    /**
     * La reserva se registró y entra a la cola de asignación de personal.
     */
    public static function bookingCreated(Booking $booking, ?int $actorId = null): void
    {
        $booking->loadMissing(['services', 'client', 'employee']);

        $rows = self::bookingRows($booking);
        $clientEmail = $booking->client?->email ?? $booking->guest_email;
        $clientUrl = self::clientUrl($booking);

        self::push(self::clientRecipients($booking, [
            'icon' => 'pi pi-calendar-plus',
            'tone' => 'sand',
            'title' => 'Reserva recibida',
            'text' => 'Estamos asignando a tu colaborador. Te avisaremos en cuanto quede confirmado.',
            'url' => $clientUrl,
        ]), $actorId, $booking->user_id);

        BookingMailer::send(
            $clientEmail,
            'Recibimos tu reserva · WashApp',
            'Reserva registrada',
            'Tu lavado ya está en nuestra agenda',
            'Gracias por confiar en WashApp. Tu reserva quedó registrada y en este momento estamos asignando al colaborador que realizará el servicio.',
            $rows,
            [
                'title' => 'Siguiente paso',
                'lines' => ['Estamos asignando a tu colaborador.', 'Te enviaremos otro correo en cuanto quede confirmado.'],
                'tone' => 'sand',
            ],
            $clientUrl ? ['label' => 'Ver mi reserva', 'url' => $clientUrl] : null
        );

        self::push(self::adminRecipients($actorId, [
            'icon' => 'pi pi-inbox',
            'tone' => 'sand',
            'title' => 'Reserva por asignar',
            'text' => "{$booking->clientName()} · {$booking->scheduledShortLabel()} · {$booking->fullAddress()}",
            'url' => route('admin.bookings.show', $booking),
        ]), $actorId);

        BookingMailer::sendToAdmins(
            'Reserva por asignar · WashApp',
            'Acción requerida',
            'Hay una reserva esperando colaborador',
            "{$booking->clientName()} registró un servicio que todavía no tiene personal asignado. Revisa la información del cliente y asigna al colaborador ideal.",
            array_merge(self::bookingRows($booking), [
                ['label' => 'Cliente', 'value' => $booking->clientName()],
                ['label' => 'Teléfono', 'value' => $booking->client?->phone_number ?? $booking->guest_phone ?? 'Sin teléfono'],
                ['label' => 'Correo', 'value' => $clientEmail ?? 'Sin correo'],
                ['label' => 'Notas', 'value' => $booking->notes ?: 'Sin notas'],
            ]),
            [
                'title' => 'Pendiente de asignación',
                'lines' => ['La reserva queda marcada como "Por asignar" hasta que elijas al colaborador.'],
                'tone' => 'cloud',
            ],
            ['label' => 'Asignar colaborador', 'url' => route('admin.bookings.show', $booking)]
        );
    }

    /**
     * Se confirmó el colaborador que realizará el servicio.
     */
    public static function employeeAssigned(Booking $booking, ?int $actorId = null): void
    {
        $booking->loadMissing(['client', 'employee']);

        if (! $booking->employee_id || ! $booking->employee) {
            return;
        }

        $employee = $booking->employee;
        $clientUrl = self::clientUrl($booking);
        $employeeName = $employee->name;
        $employeePhone = $employee->phone_number ?? 'Por confirmar';
        $employeeRating = self::employeeRating($employee);

        UserNotification::create([
            'user_id' => $booking->employee_id,
            'icon' => 'pi pi-briefcase',
            'tone' => 'sage',
            'title' => 'Nuevo servicio asignado',
            'text' => "{$booking->clientName()} · {$booking->scheduledShortLabel()} · {$booking->fullAddress()}",
            'url' => route('employee.bookings.show', $booking),
        ]);

        BookingMailer::send(
            $employee->email,
            'Nuevo servicio asignado · WashApp',
            'Servicio asignado',
            'Tienes un nuevo servicio en tu agenda',
            'Revisa los detalles del servicio y avísanos cuando salgas hacia el domicilio para notificar al cliente.',
            array_merge(self::bookingRows($booking), [
                ['label' => 'Cliente', 'value' => $booking->clientName()],
                ['label' => 'Teléfono', 'value' => $booking->client?->phone_number ?? $booking->guest_phone ?? 'Sin teléfono'],
            ]),
            null,
            ['label' => 'Ver el servicio', 'url' => route('employee.bookings.show', $booking)],
            'Recibes este correo porque se te asignó un servicio en WashApp.'
        );

        self::push(self::clientRecipients($booking, [
            'icon' => 'pi pi-verified',
            'tone' => 'sage',
            'title' => 'Colaborador confirmado',
            'text' => "{$employeeName} realizará tu servicio. Puedes ver su información en el detalle.",
            'url' => $clientUrl,
        ]), $actorId);

        BookingMailer::send(
            $booking->client?->email ?? $booking->guest_email,
            'Tu colaborador está confirmado · WashApp',
            'Asignación confirmada',
            "{$employeeName} realizará tu servicio",
            'Ya asignamos al colaborador que se encargará de tu lavado. Esta es su información general:',
            array_merge(self::bookingRows($booking), [
                ['label' => 'Colaborador', 'value' => $employeeName],
                ['label' => 'Teléfono', 'value' => $employeePhone],
                ['label' => 'Valoración', 'value' => $employeeRating ? "{$employeeRating} / 5" : 'Nuevo en WashApp'],
            ]),
            [
                'title' => 'Tu colaborador',
                'lines' => [$employeeName, "Contacto: {$employeePhone}"],
                'tone' => 'sage',
            ],
            $clientUrl ? ['label' => 'Ver mi reserva', 'url' => $clientUrl] : null
        );
    }

    /**
     * Cambios de estado del servicio.
     */
    public static function statusChanged(Booking $booking, string $status, ?int $actorId = null): void
    {
        $booking->loadMissing(['client', 'employee']);

        $labels = [
            'pending' => 'por asignar',
            'confirmed' => 'confirmada',
            'on_way' => 'con el colaborador en camino',
            'in_progress' => 'en progreso',
            'completed' => 'finalizada',
            'cancelled' => 'cancelada',
        ];

        $label = $labels[$status] ?? $status;
        $clientUrl = self::clientUrl($booking);
        $highlight = null;
        $intro = "Tu servicio del {$booking->scheduledLongLabel()} ahora está {$label}.";

        if ($status === 'on_way') {
            $window = $booking->arrivalWindowLabel();
            $highlight = [
                'title' => 'Llegada estimada',
                'lines' => array_values(array_filter([
                    $booking->employee?->name ? "{$booking->employee->name} va en camino." : 'Tu colaborador va en camino.',
                    $window ? "Llegará entre {$window}." : null,
                    $booking->arrival_eta_minutes ? "Tiempo estimado: {$booking->arrival_eta_minutes} minutos." : null,
                ])),
                'tone' => 'cloud',
            ];
            $intro = 'Tu colaborador ya salió hacia el domicilio del servicio.';
        } elseif ($status === 'in_progress') {
            $highlight = [
                'title' => 'Servicio en progreso',
                'lines' => array_values(array_filter([
                    $booking->arrived_at ? 'Llegó a las ' . $booking->arrived_at->format('g:i a') . '.' : null,
                    'El colaborador está revisando el vehículo contigo.',
                ])),
                'tone' => 'sage',
            ];
            $intro = 'Tu colaborador llegó al domicilio y el servicio ya comenzó.';
        } elseif ($status === 'completed') {
            $minutes = $booking->actualDurationMinutes();
            $highlight = [
                'title' => 'Servicio terminado',
                'lines' => array_values(array_filter([
                    $minutes ? "Tiempo total del servicio: {$minutes} minutos." : null,
                    'De cortesía, tu colaborador te regala un aromatizante para tu auto.',
                    'Confirma que quedó todo correcto y califica a tu colaborador.',
                ])),
                'tone' => 'sage',
            ];
            $intro = 'Terminamos tu servicio. Revisa tu vehículo y confirma desde tu panel si deseas el aromatizante de cortesía.';
        } elseif ($status === 'cancelled') {
            $intro = 'Tu servicio fue cancelado.';
            $highlight = $booking->cancel_reason
                ? ['title' => 'Motivo', 'lines' => [$booking->cancel_reason], 'tone' => 'sand']
                : null;
        }

        self::push(self::clientRecipients($booking, [
            'icon' => $status === 'cancelled' ? 'pi pi-times-circle' : 'pi pi-sync',
            'tone' => $status === 'cancelled' ? 'rose' : ($status === 'completed' ? 'sage' : 'cloud'),
            'title' => "Reserva {$label}",
            'text' => $highlight['lines'][0] ?? "Tu servicio del {$booking->scheduledShortLabel()} ahora está {$label}.",
            'url' => $clientUrl,
        ]), $actorId);

        BookingMailer::send(
            $booking->client?->email ?? $booking->guest_email,
            "Reserva {$label} · WashApp",
            'Actualización del servicio',
            "Tu servicio está {$label}",
            $intro,
            self::bookingRows($booking),
            $highlight,
            $clientUrl ? ['label' => 'Ver mi reserva', 'url' => $clientUrl] : null
        );

        if ($booking->employee_id && $status !== 'cancelled') {
            UserNotification::create([
                'user_id' => $booking->employee_id,
                'icon' => 'pi pi-briefcase',
                'tone' => $status === 'completed' ? 'sage' : 'cloud',
                'title' => "Servicio {$label}",
                'text' => "{$booking->clientName()} · {$booking->scheduledShortLabel()}.",
                'url' => route('employee.bookings.show', $booking),
            ]);
        }
    }

    /**
     * El colaborador registró la inspección y espera la confirmación del cliente.
     */
    public static function inspectionRequested(Booking $booking): void
    {
        $booking->loadMissing(['client', 'employee']);
        $clientUrl = self::clientUrl($booking);

        self::push(self::clientRecipients($booking, [
            'icon' => 'pi pi-search',
            'tone' => 'sand',
            'title' => 'Confirma la inspección',
            'text' => 'Tu colaborador registró el estado del vehículo. Revísalo y confirma para comenzar.',
            'url' => $clientUrl,
        ]));

        BookingMailer::send(
            $booking->client?->email ?? $booking->guest_email,
            'Confirma la inspección de tu vehículo · WashApp',
            'Inspección del vehículo',
            'Revisa el estado de tu auto antes del lavado',
            'Registramos contigo los detalles estéticos del vehículo para evitar malentendidos. Confirma desde tu panel que todo está correcto para continuar.',
            self::bookingRows($booking),
            [
                'title' => 'Inspección registrada',
                'lines' => ['Revisa las marcas señaladas en las 4 vistas del vehículo.', 'Confirma para proceder con el servicio.'],
                'tone' => 'sand',
            ],
            $clientUrl ? ['label' => 'Revisar inspección', 'url' => $clientUrl] : null
        );
    }

    /**
     * El cliente confirmó la inspección.
     */
    public static function inspectionConfirmed(Booking $booking): void
    {
        if (! $booking->employee_id) {
            return;
        }

        UserNotification::create([
            'user_id' => $booking->employee_id,
            'icon' => 'pi pi-check-circle',
            'tone' => 'sage',
            'title' => 'Inspección confirmada',
            'text' => "{$booking->clientName()} confirmó el estado del vehículo. Puedes iniciar el lavado.",
            'url' => route('employee.bookings.show', $booking),
        ]);
    }

    /**
     * El cliente confirmó que el servicio terminó.
     */
    public static function serviceConfirmed(Booking $booking): void
    {
        if (! $booking->employee_id) {
            return;
        }

        UserNotification::create([
            'user_id' => $booking->employee_id,
            'icon' => 'pi pi-verified',
            'tone' => 'sage',
            'title' => 'Servicio confirmado por el cliente',
            'text' => "{$booking->clientName()} confirmó que el servicio quedó terminado.",
            'url' => route('employee.bookings.show', $booking),
        ]);
    }

    /**
     * El cliente dejó una propina.
     */
    public static function tipReceived(Booking $booking): void
    {
        $amount = number_format((float) $booking->tip_amount, 2);
        $method = $booking->tip_method === 'digital' ? 'digital' : 'en efectivo';

        if ($booking->employee_id) {
            UserNotification::create([
                'user_id' => $booking->employee_id,
                'icon' => 'pi pi-heart-fill',
                'tone' => 'sand',
                'title' => '¡Recibiste una propina!',
                'text' => "{$booking->clientName()} dejó \${$amount} MXN ({$method}).",
                'url' => route('employee.bookings.show', $booking),
            ]);
        }

        foreach (User::where('role', 'Admin')->pluck('id') as $adminId) {
            UserNotification::create([
                'user_id' => $adminId,
                'icon' => 'pi pi-wallet',
                'tone' => 'sand',
                'title' => 'Propina registrada',
                'text' => "Reserva #{$booking->id} · \${$amount} MXN ({$method}).",
                'url' => route('admin.bookings.show', $booking),
            ]);
        }
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function bookingRows(Booking $booking): array
    {
        return array_values(array_filter([
            ['label' => 'Folio', 'value' => 'WA-' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT)],
            ['label' => 'Fecha', 'value' => $booking->scheduledLongLabel()],
            ['label' => 'Horario', 'value' => ($booking->scheduled_at?->format('H:i') ?? '') . ' h'],
            ['label' => 'Servicios', 'value' => $booking->services->pluck('name')->implode(', ') ?: 'Servicio WashApp'],
            ['label' => 'Vehículo', 'value' => $booking->vehicleLabel()],
            ['label' => 'Dirección', 'value' => $booking->fullAddress()],
            ['label' => 'Total', 'value' => '$' . number_format((float) $booking->total_price, 2) . ' MXN'],
        ], fn (array $row) => filled($row['value'])));
    }

    /**
     * @param  array{icon: string, tone: string, title: string, text: string, url: ?string}  $payload
     * @return array<int, array{icon: string, tone: string, title: string, text: string, url: ?string}>
     */
    private static function clientRecipients(Booking $booking, array $payload): array
    {
        return $booking->user_id ? [$booking->user_id => $payload] : [];
    }

    /**
     * @param  array{icon: string, tone: string, title: string, text: string, url: ?string}  $payload
     * @return array<int, array{icon: string, tone: string, title: string, text: string, url: ?string}>
     */
    private static function adminRecipients(?int $actorId, array $payload): array
    {
        return User::query()
            ->where('role', 'Admin')
            ->where('is_active', true)
            ->pluck('id')
            ->reject(fn (int $adminId) => $adminId === $actorId)
            ->mapWithKeys(fn (int $adminId) => [$adminId => $payload])
            ->all();
    }

    private static function clientUrl(Booking $booking): ?string
    {
        return $booking->user_id ? route('client.bookings.show', $booking) : null;
    }

    private static function employeeRating(?User $employee): ?string
    {
        if (! $employee) {
            return null;
        }

        $average = $employee->reviewsReceived()->avg('rating');

        return $average ? number_format((float) $average, 1) : null;
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
