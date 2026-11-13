<?php

namespace App\Http\Controllers;

use App\Models\BusinessException;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BusinessSettingsController extends Controller
{
    public function index()
    {
        $settings = BusinessSetting::current();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'opening_time' => $settings->openingTime(),
                'closing_time' => $settings->closingTime(),
                'operating_days' => $settings->operatingDays(),
            ],
            'exceptions' => BusinessException::query()
                ->orderBy('date')
                ->get()
                ->map(fn (BusinessException $exception) => $exception->toScheduleArray())
                ->values(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'operating_days' => ['required', 'array', 'min:1'],
            'operating_days.*' => ['integer', 'between:0,6'],
        ]);

        BusinessSetting::current()->update([
            'opening_time' => $validated['opening_time'] . ':00',
            'closing_time' => $validated['closing_time'] . ':00',
            'operating_days' => array_values(array_unique(array_map('intval', $validated['operating_days']))),
        ]);

        return redirect()->back()->with('success', 'Horario de operación actualizado.');
    }

    public function storeException(Request $request)
    {
        BusinessException::create($this->validateException($request));

        return redirect()->back()->with('success', 'Excepción de horario agregada.');
    }

    public function updateException(Request $request, BusinessException $exception)
    {
        $exception->update($this->validateException($request));

        return redirect()->back()->with('success', 'Excepción de horario actualizada.');
    }

    public function destroyException(BusinessException $exception)
    {
        $exception->delete();

        return redirect()->back()->with('success', 'Excepción de horario eliminada.');
    }

    private function validateException(Request $request): array
    {
        $isClosed = $request->boolean('is_closed');
        $requiresTimes = ! $isClosed;

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'label' => ['nullable', 'string', 'max:120'],
            'is_closed' => ['required', 'boolean'],
            'start_time' => [Rule::requiredIf($requiresTimes), 'nullable', 'date_format:H:i'],
            'end_time' => [Rule::requiredIf($requiresTimes), 'nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        return [
            'date' => $validated['date'],
            'label' => $validated['label'] ?? null,
            'is_closed' => $isClosed,
            'start_time' => $isClosed ? null : ($validated['start_time'] ?? null) . ':00',
            'end_time' => $isClosed ? null : ($validated['end_time'] ?? null) . ':00',
        ];
    }
}
