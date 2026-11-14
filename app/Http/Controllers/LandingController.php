<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\Service;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingController extends Controller
{
    /**
     * Pantalla de carga que se muestra en la raíz antes de entrar a la landing.
     *
     * @return \Inertia\Response
     */
    public function splash()
    {
        return Inertia::render('Landing/Splash');
    }

    /**
     * Display the landing page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Landing/Index', [
            'packages' => $this->presentPackages(),
            'rewardsPreview' => Reward::active()->orderBy('points_cost')->limit(3)->get(),
        ]);
    }

    /**
     * Display the services page.
     *
     * @return \Inertia\Response
     */
    public function services()
    {
        return Inertia::render('Landing/Servicios', [
            'vehicleCategories' => $this->presentCategories(),
            'packages' => $this->presentPackages(),
            'addons' => $this->presentAddons(),
        ]);
    }

    /**
     * Display the appointment booking page.
     *
     * @return \Inertia\Response
     */
    public function appointment(Request $request)
    {
        // Pasamos el parámetro 'service' de la URL como un prop llamado 'serviceId' a la vista
        return Inertia::render('Landing/Agendar', [
            'serviceId' => $request->query('service'),
            'addonId' => $request->query('addon'),
            'vehicleCategoryId' => $request->query('vehicle'),
            'vehicleCategories' => $this->presentCategories(),
            'packages' => $this->presentPackages(),
            'addons' => $this->presentAddons(),
        ]);
    }

    /**
     * Display the promotions page.
     *
     * @return \Inertia\Response
     */
    public function promotions()
    {
        return Inertia::render('Landing/Promociones');
    }

    /**
     * Display the rewards (loyalty) page.
     *
     * @return \Inertia\Response
     */
    public function rewards()
    {
        return Inertia::render('Landing/Premios', [
            'rewards' => Reward::active()->orderBy('points_cost')->get(),
        ]);
    }

    /**
     * Display the contact page.
     *
     * @return \Inertia\Response
     */
    public function contact()
    {
        return Inertia::render('Landing/Contacto');
    }

    /**
     * Categorías de vehículo activas.
     */
    private function presentCategories()
    {
        return VehicleCategory::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (VehicleCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values();
    }

    /**
     * Paquetes de lavado con su precio y duración por categoría.
     */
    private function presentPackages()
    {
        return Service::active()
            ->packages()
            ->with('vehicleCategories')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Service $service) => $this->presentService($service))
            ->values();
    }

    /**
     * Servicios adicionales (upselling).
     */
    private function presentAddons()
    {
        return Service::active()
            ->addons()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Service $service) => $this->presentService($service))
            ->values();
    }

    /**
     * Estructura que consume el frontend.
     */
    private function presentService(Service $service): array
    {
        $variants = $service->vehicleCategories->mapWithKeys(fn (VehicleCategory $category) => [
            $category->id => [
                'price' => (float) $category->pivot->price,
                'duration_minutes' => (int) $category->pivot->duration_minutes,
            ],
        ]);

        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'type' => $service->type,
            'points' => $service->points,
            'features' => $service->features ?? [],
            'price' => (float) $service->price,
            'duration_minutes' => (int) $service->duration_minutes,
            'min_price' => $variants->isNotEmpty() ? $variants->min('price') : (float) $service->price,
            'variants' => $variants,
        ];
    }
}
