<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    /**
     * Registra un vehículo para el cliente autenticado.
     */
    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $request->user()->cars()->create($validated);

        return redirect()->back()->with('success', 'Vehículo agregado.');
    }

    public function update(Request $request, Car $car)
    {
        $this->authorizeCar($car);

        $car->update($this->validated($request));

        return redirect()->back()->with('success', 'Vehículo actualizado.');
    }

    public function destroy(Car $car)
    {
        $this->authorizeCar($car);

        $car->clearMediaCollection('photos');
        $car->delete();

        return redirect()->back()->with('success', 'Vehículo eliminado.');
    }

    /**
     * Sube (o reemplaza) la foto de un lado del vehículo.
     * Máximo 4 fotos: una por cada lado.
     */
    public function storePhoto(Request $request, Car $car)
    {
        $this->authorizeCar($car);

        $request->validate([
            'photo' => 'required|image|max:4096',
            'side' => 'required|in:' . implode(',', array_keys(Car::SIDES)),
        ]);

        $side = $request->input('side');

        $car->getMedia('photos')
            ->filter(fn ($media) => $media->getCustomProperty('side') === $side)
            ->each
            ->delete();

        $car->addMediaFromRequest('photo')
            ->withCustomProperties(['side' => $side])
            ->toMediaCollection('photos');

        return redirect()->back()->with('success', 'Foto del vehículo actualizada.');
    }

    public function destroyPhoto(Car $car, int $media)
    {
        $this->authorizeCar($car);

        $item = $car->getMedia('photos')->firstWhere('id', $media);
        abort_if(! $item, 404);

        $item->delete();

        return redirect()->back()->with('success', 'Foto eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'brand' => 'required|string|max:80',
            'model' => 'required|string|max:80',
            'year' => 'nullable|integer|min:1950|max:' . (now()->year + 1),
            'color' => 'nullable|string|max:40',
            'plates' => 'nullable|string|max:15',
        ]);
    }

    private function authorizeCar(Car $car): void
    {
        abort_unless($car->user_id === auth()->id(), 403);
    }
}
