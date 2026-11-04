<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminExpenseController extends Controller
{
    /**
     * Listado y resumen financiero de los gastos del negocio.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'category' => ['nullable', Rule::in(Expense::CATEGORIES)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->endOfMonth()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $filters = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'category' => $validated['category'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $baseQuery = Expense::query()
            ->between($from, $to)
            ->when($filters['category'], fn ($query, $category) => $query->where('category', $category))
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('concept', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%");
                });
            });

        $filteredTotal = (float) (clone $baseQuery)->sum('amount');
        $filteredCount = (clone $baseQuery)->count();

        $categoryBreakdown = (clone $baseQuery)
            ->selectRaw('category, COUNT(*) as expenses_count, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'expenses_count' => (int) $row->expenses_count,
                'total' => round((float) $row->total, 2),
            ]);

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $expenses = (clone $baseQuery)
            ->with('creator:id,name')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Expense $expense) => [
                'id' => $expense->id,
                'concept' => $expense->concept,
                'category' => $expense->category,
                'amount' => round((float) $expense->amount, 2),
                'expense_date' => $expense->expense_date->toDateString(),
                'payment_method' => $expense->payment_method,
                'supplier' => $expense->supplier,
                'notes' => $expense->notes,
                'created_by' => $expense->creator?->name,
            ]);

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => Expense::CATEGORIES,
            'paymentMethods' => Expense::PAYMENT_METHODS,
            'summary' => [
                'filteredTotal' => round($filteredTotal, 2),
                'filteredCount' => $filteredCount,
                'average' => $filteredCount > 0 ? round($filteredTotal / $filteredCount, 2) : 0,
                'monthTotal' => round((float) Expense::between($monthStart, $monthEnd)->sum('amount'), 2),
                'monthCount' => Expense::between($monthStart, $monthEnd)->count(),
                'topCategory' => $categoryBreakdown->first()['category'] ?? null,
                'topCategoryTotal' => $categoryBreakdown->first()['total'] ?? 0,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'monthlyTrend' => $this->monthlyTrend(),
        ]);
    }

    public function store(Request $request)
    {
        $expense = Expense::create([
            ...$this->validated($request),
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', "Gasto «{$expense->concept}» registrado correctamente.");
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return back()->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Gasto eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'concept' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in(Expense::PAYMENT_METHODS)],
            'supplier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * Totales de gastos de los últimos seis meses.
     *
     * @return array<int, array{label: string, total: float}>
     */
    private function monthlyTrend(): array
    {
        $start = now()->copy()->subMonthsNoOverflow(5)->startOfMonth();
        $totals = Expense::between($start, now()->copy()->endOfMonth())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month, COALESCE(SUM(amount), 0) as total")
            ->groupByRaw("DATE_FORMAT(expense_date, '%Y-%m')")
            ->pluck('total', 'month');

        $trend = [];

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $month = now()->copy()->subMonthsNoOverflow($monthsAgo);

            $trend[] = [
                'label' => ucfirst($month->copy()->locale('es')->translatedFormat('M Y')),
                'total' => round((float) ($totals[$month->format('Y-m')] ?? 0), 2),
            ];
        }

        return $trend;
    }
}
