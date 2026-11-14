<?php

namespace App\Http\Controllers;

use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VehicleCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/VehicleCategories/Index', [
            'categories' => VehicleCategory::withCount('services')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (VehicleCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'services_count' => $category->services_count,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_categories,name',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        VehicleCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->back()->with('success', 'Categoría creada.');
    }

    public function update(Request $request, VehicleCategory $vehicleCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_categories,name,' . $vehicleCategory->id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $vehicleCategory->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(VehicleCategory $vehicleCategory)
    {
        if ($vehicleCategory->services()->exists()) {
            return redirect()->back()->withErrors([
                'category' => 'No puedes eliminar una categoría con servicios asociados. Desactívala en su lugar.',
            ]);
        }

        $vehicleCategory->delete();

        return redirect()->back()->with('success', 'Categoría eliminada.');
    }
}
