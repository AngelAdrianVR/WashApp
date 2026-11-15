<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\BookingMessageImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingChatController extends Controller
{
    /**
     * Cantidad máxima de imágenes que se pueden adjuntar en un mensaje.
     */
    public const MAX_IMAGES = 3;

    /**
     * Mensajes del chat de la reserva (cliente, colaborador asignado y administración).
     */
    public function index(Booking $booking)
    {
        $this->authorizeParticipant($booking);

        return response()->json($this->payload($booking));
    }

    /**
     * El cliente o el colaborador envían un mensaje (con hasta 3 imágenes).
     */
    public function store(Request $request, Booking $booking)
    {
        $this->authorizeParticipant($booking);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:1000', 'required_without:images'],
            'images' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'images.*' => ['image', 'max:5120'],
        ], [
            'body.required_without' => 'Escribe un mensaje o adjunta una imagen.',
            'images.max' => 'Puedes adjuntar hasta ' . self::MAX_IMAGES . ' imágenes por mensaje.',
        ]);

        $message = BookingMessage::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'] ?? null,
        ]);

        foreach ($request->file('images', []) as $file) {
            $path = $file->store("chat/{$booking->id}", 'public');

            BookingMessageImage::create([
                'booking_message_id' => $message->id,
                'path' => $path,
            ]);
        }

        return response()->json($this->payload($booking));
    }

    /**
     * Solo los participantes del servicio pueden leer o escribir en el chat.
     */
    private function authorizeParticipant(Booking $booking): void
    {
        $user = Auth::user();

        $isParticipant = $booking->user_id === $user->id
            || $booking->employee_id === $user->id
            || $user->role === 'Admin';

        if (! $isParticipant) {
            abort(403, 'No autorizado para ver este chat.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Booking $booking): array    {
        $messages = $booking->messages()
            ->with(['user', 'images'])
            ->orderBy('created_at')
            ->limit(300)
            ->get();

        return [
            'messages' => $messages->map(fn (BookingMessage $message) => $message->toChatArray(Auth::id()))->all(),
            'max_images' => self::MAX_IMAGES,
            'client' => [
                'name' => $booking->clientName(),
                'avatar' => $booking->client?->avatar_url ?: $booking->client?->profile_photo_url,
            ],
            'employee' => $booking->employee ? [
                'name' => $booking->employee->name,
                'avatar' => $booking->employee->avatar_url ?: $booking->employee->profile_photo_url,
            ] : null,
        ];
    }
}
