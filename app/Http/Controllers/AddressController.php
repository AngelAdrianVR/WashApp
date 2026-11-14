<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validateAddress($request);
        $user = $request->user();

        $validated['is_default'] = $request->boolean('is_default');

        if ($user->addresses()->count() === 0) {
            $validated['is_default'] = true;
        }

        $address = $user->addresses()->create($validated);

        if ($address->is_default) {
            $this->clearDefault($address);
        }

        return redirect()->back()->with('success', 'Domicilio guardado.');
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($address);

        $validated = $this->validateAddress($request);
        $validated['is_default'] = $request->boolean('is_default');

        $address->update($validated);

        if ($address->is_default) {
            $this->clearDefault($address);
        }

        return redirect()->back()->with('success', 'Domicilio actualizado.');
    }

    public function destroy(Address $address)
    {
        $this->authorizeAddress($address);

        $wasDefault = $address->is_default;
        $userId = $address->user_id;
        $address->delete();

        if ($wasDefault) {
            $next = Address::query()->where('user_id', $userId)->orderBy('id')->first();

            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect()->back()->with('success', 'Domicilio eliminado.');
    }

    public function setDefault(Address $address)
    {
        $this->authorizeAddress($address);

        $address->update(['is_default' => true]);
        $this->clearDefault($address);

        return redirect()->back()->with('success', 'Domicilio principal actualizado.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'street' => ['required', 'string', 'max:255'],
            'street_number' => ['required', 'string', 'max:30'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'digits:5'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'references' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function clearDefault(Address $address): void
    {
        Address::query()
            ->where('user_id', $address->user_id)
            ->whereKeyNot($address->getKey())
            ->update(['is_default' => false]);
    }

    private function authorizeAddress(Address $address): void
    {
        abort_unless($address->user_id === auth()->id(), 403);
    }
}
