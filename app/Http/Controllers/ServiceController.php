<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        // Preparamos la consulta para los servicios
        $services = Service::query()
            // Aplicamos el filtro de búsqueda si existe
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })->latest()
            // Paginamos los resultados
            ->paginate(20)
            // Mantenemos los parámetros de la URL (como la búsqueda) en la paginación
            ->withQueryString()
            // Transformamos los datos para enviarlos a la vista
            ->through(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => (float) $service->price, // Aseguramos que el precio sea un número
                'duration_minutes' => $service->duration_minutes,
                'type' => $service->type,
                'is_active' => $service->is_active,
            ]);

        // Retornamos la vista de Inertia con los datos
        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Services/Create');
    }

    public function store(Request $request)
    {
        // Validamos los datos del formulario
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:0',
            'type' => 'required|in:Seco,Agua',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|max:2048', // Añadimos validación para la imagen
        ]);
        
        // Creamos el nuevo servicio en la base de datos
        $service = Service::create($validatedData);

        // Si el request contiene un archivo de imagen, lo asociamos al servicio
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $service->addMedia($file)->toMediaCollection('services');
            }
        }

        // Redireccionamos al listado de servicios
        return redirect()->route('admin.services.index')->with('success', 'Servicio creado exitosamente.');
    }

    public function show(Service $service)
    {
        // Cargamos las imágenes asociadas al servicio
        $service->load('media');

        return Inertia::render('Admin/Services/Show', [
            // Pasamos los datos del servicio, incluyendo las URLs de las imágenes
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'type' => $service->type,
                'is_active' => $service->is_active,
                // Mapeamos la colección de medios para obtener solo el ID y la URL
                'media' => $service->getMedia('services')->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                ]),
            ],
        ]);
    }

    public function edit(Service $service)
    {
        // Retornamos la vista de edición, pasando los datos del servicio
        // y un listado de sus imágenes actuales.
        return Inertia::render('Admin/Services/Edit', [
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
                'type' => $service->type,
                'is_active' => $service->is_active,
                // Mapeamos la colección de medios para obtener solo el ID y la URL
                'media' => $service->getMedia('services')->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                ]),
            ]
        ]);
    }

    public function update(Request $request, Service $service)
    {
        // 1. Validamos los campos principales del formulario
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:0',
            'type' => 'required|in:Seco,Agua',
            'is_active' => 'required|boolean',
        ]);

        // 2. Actualizamos el servicio
        $service->update($validatedData);

        // 3. Manejamos la eliminación de imágenes existentes
        $request->validate(['images_to_delete' => 'nullable|array']);
        if ($request->filled('images_to_delete')) {
            // Buscamos los IDs de los medios y los eliminamos
            Media::whereIn('id', $request->input('images_to_delete'))->delete();
        }

        // 4. Manejamos la subida de nuevas imágenes
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
        // Lógica para eliminar el servicio
        $service->delete();
        return redirect()->route('admin.services.index');
    }

    public function toggleActive(Service $service)
    {
        // Actualizamos el campo 'is_active' al valor opuesto
        $service->update(['is_active' => !$service->is_active]);

        // Redireccionamos a la página anterior
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
}
