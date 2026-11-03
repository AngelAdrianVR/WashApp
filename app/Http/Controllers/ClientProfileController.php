<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProfileController extends Controller
{
    /**
     * Perfil del cliente: foto, puntos, datos y vehículos.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('cars');

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
            'cars' => $user->cars->map->toCatalogArray()->values(),
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
