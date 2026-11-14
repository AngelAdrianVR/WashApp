<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::query()
            ->withCount('vehicleCategories')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('is_addon')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'type' => $service->type,
                'is_addon' => $service->is_addon,
                'points' => $service->points,
                'categories_count' => $service->vehicle_categories_count,
                'is_active' => $service->is_active,
            ]);

        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Services/Create', [
            'vehicleCategories' => $this->vehicleCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $variants = $validated['variants'] ?? [];
        unset($validated['variants'], $validated['images'], $validated['image']);

        $service = Service::create($this->withFallbackPricing($validated, $variants));

        $this->syncVariants($service, $variants);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $service->addMedia($file)->toMediaCollection('services');
            }
        }

        return redirect()->route('admin.services.index')->with('success', 'Servicio creado exitosamente.');
    }

    public function show(Service $service)
    {
        $service->load('media', 'vehicleCategories');

        return Inertia::render('Admin/Services/Show', [
            'service' => $this->present($service),
        ]);
    }

    public function edit(Service $service)
    {
        $service->load('media', 'vehicleCategories');

        return Inertia::render('Admin/Services/Edit', [
            'service' => $this->present($service),
            'vehicleCategories' => $this->vehicleCategories(),
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $this->validated($request);
        $variants = $validated['variants'] ?? [];
        unset($validated['variants'], $validated['images'], $validated['image']);

        $service->update($this->withFallbackPricing($validated, $variants));

        $this->syncVariants($service, $variants);

        $request->validate(['images_to_delete' => 'nullable|array']);
        if ($request->filled('images_to_delete')) {
            Media::whereIn('id', $request->input('images_to_delete'))->delete();
        }

        $request->validate([
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|max:2048',
        ]);
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                $service->addMedia($file)->toMediaCollection('services');
            }
        }

        return redirect()->route('admin.services.index')->with('success', 'Servicio actualizado exitosamente.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('admin.services.index');
    }

    public function toggleActive(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        return redirect()->back();
    }

    public function addImages(Request $request, Service $service)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|max:2048',
        ]);

        foreach ($request->file('images') as $file) {
            $service->addMedia($file)->toMediaCollection('services');
        }

        return redirect()->back()->with('success', 'Imágenes agregadas correctamente.');
    }

    /**
     * Reglas de validación compartidas entre crear y editar.
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'type' => 'required|in:Seco,Agua',
            'is_addon' => 'required|boolean',
            'points' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'variants' => 'nullable|array',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.duration_minutes' => 'nullable|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);
    }

    /**
     * Si el precio base viene vacío se toma el mínimo de las categorías.
     */
    private function withFallbackPricing(array $validated, array $variants): array
    {
        $prices = collect($variants)->pluck('price')->filter(fn ($price) => $price !== null && $price !== '');
        $durations = collect($variants)->pluck('duration_minutes')->filter(fn ($value) => $value !== null && $value !== '');

        $validated['price'] = $validated['price'] ?? $prices->min() ?? 0;
        $validated['duration_minutes'] = $validated['duration_minutes'] ?? $durations->max() ?? 0;
        $validated['features'] = array_values(array_filter($validated['features'] ?? []));
        $validated['points'] = $validated['points'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }

    /**
     * Guarda precio y duración por categoría de vehículo.
     */
    private function syncVariants(Service $service, array $variants): void
    {
        $sync = [];

        foreach ($variants as $categoryId => $values) {
            if (($values['price'] ?? null) === null || ($values['duration_minutes'] ?? null) === null) {
                continue;
            }

            $sync[$categoryId] = [
                'price' => $values['price'],
                'duration_minutes' => $values['duration_minutes'],
            ];
        }

        $service->vehicleCategories()->sync($sync);
    }

    /**
     * Categorías disponibles para el formulario.
     */
    private function vehicleCategories()
    {
        return VehicleCategory::orderBy('sort_order')->get(['id', 'name', 'slug', 'is_active']);
    }

    /**
     * Estructura del servicio para las vistas de detalle y edición.
     */
    private function present(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'features' => $service->features ?? [],
            'price' => (float) $service->price,
            'duration_minutes' => $service->duration_minutes,
            'type' => $service->type,
            'is_addon' => $service->is_addon,
            'points' => $service->points,
            'sort_order' => $service->sort_order,
            'is_active' => $service->is_active,
            'variants' => $service->vehicleCategories
                ->mapWithKeys(fn (VehicleCategory $category) => [
                    $category->id => [
                        'price' => (float) $category->pivot->price,
                        'duration_minutes' => (int) $category->pivot->duration_minutes,
                    ],
                ]),
            'vehicle_categories' => $service->vehicleCategories->map(fn (VehicleCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'price' => (float) $category->pivot->price,
                'duration_minutes' => (int) $category->pivot->duration_minutes,
            ])->values(),
            'media' => $service->getMedia('services')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ]),
        ];
    }
}
