<?php

namespace App\Services;

use App\Mail\BookingFlowMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingMailer
{
    /**
     * Envía un correo del flujo de reservas sin interrumpir la operación si falla.
     *
     * @param  array<int, array{label: string, value: string}>  $rows
     * @param  array{title: string, lines: array<int, string>, tone?: string}|null  $highlight
     * @param  array{label: string, url: string}|null  $cta
     */
    public static function send(
        ?string $to,
        string $subject,
        string $eyebrow,
        string $title,
        string $intro,
        array $rows = [],
        ?array $highlight = null,
        ?array $cta = null,
        ?string $footer = null
    ): void {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new BookingFlowMail([
                'subject' => $subject,
                'eyebrow' => $eyebrow,
                'title' => $title,
                'intro' => $intro,
                'rows' => $rows,
                'highlight' => $highlight,
                'cta' => $cta,
                'footer' => $footer,
            ]));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el correo del flujo de reservas.', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifica por correo a todos los administradores activos.
     *
     * @param  array<int, array{label: string, value: string}>  $rows
     * @param  array{title: string, lines: array<int, string>, tone?: string}|null  $highlight
     * @param  array{label: string, url: string}|null  $cta
     */
    public static function sendToAdmins(
        string $subject,
        string $eyebrow,
        string $title,
        string $intro,
        array $rows = [],
        ?array $highlight = null,
        ?array $cta = null
    ): void {
        User::query()
            ->where('role', 'Admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->each(fn (string $email) => self::send(
                $email,
                $subject,
                $eyebrow,
                $title,
                $intro,
                $rows,
                $highlight,
                $cta,
                'Recibes este correo porque eres administrador de WashApp.'
            ));
    }
}
