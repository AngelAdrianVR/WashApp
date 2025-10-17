<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $promotions = Promotion::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($promotion) => [
                'id' => $promotion->id,
                'code' => $promotion->code,
                'description' => $promotion->description,
                'discount_value' => $promotion->discount_value,
                'discount_type' => $promotion->discount_type,
                'expires_at' => $promotion->expires_at ? $promotion->expires_at->format('d/m/Y H:i') : null,
                'max_uses' => $promotion->max_uses,
                'current_uses' => $promotion->current_uses,
                'is_active' => $promotion->is_active,
            ]);

        return Inertia::render('Admin/Promotions/Index', [
            'promotions' => $promotions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Promotions/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:promotions,code',
            'description' => 'nullable|string',
            'discount_type' => ['required', Rule::in(['Fijo', 'Porcentaje'])],
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);

        Promotion::create($validated);

        // Se podría añadir un toast de notificación desde el backend si se desea
        return redirect()->route('admin.promotions.index');
    }

    public function show(Promotion $promotion)
    {
        //
    }

    public function edit(Promotion $promotion)
    {
        //
    }

    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('promotions')->ignore($promotion->id)],
            'description' => 'nullable|string',
            'discount_type' => ['required', Rule::in(['Fijo', 'Porcentaje'])],
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);

        $promotion->update($validated);

        return redirect()->back();
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return redirect()->back();
    }
}
