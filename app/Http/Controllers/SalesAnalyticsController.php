<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SalesAnalyticsController extends Controller
{
    /**
     * Análisis de ventas por rango de fechas (solo administradores).
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'granularity' => ['nullable', Rule::in(['auto', 'day', 'week', 'month'])],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $spanDays = (int) $from->diffInDays($to) + 1;
        $granularity = $validated['granularity'] ?? 'auto';

        if ($granularity === 'auto') {
            $granularity = match (true) {
                $spanDays <= 45 => 'day',
                $spanDays <= 180 => 'week',
                default => 'month',
            };
        }

        // --- Periodo actual ---
        $revenue = (float) Booking::completedBetween($from, $to)->sum('total_price');
        $completed = Booking::completedBetween($from, $to)->count();
        $expenses = (float) Expense::between($from, $to)->sum('amount');
        $netProfit = round($revenue - $expenses, 2);

        // --- Periodo anterior de la misma duración ---
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($spanDays - 1)->startOfDay();
        $previousRevenue = (float) Booking::completedBetween($previousFrom, $previousTo)->sum('total_price');
        $previousCompleted = Booking::completedBetween($previousFrom, $previousTo)->count();
        $previousExpenses = (float) Expense::between($previousFrom, $previousTo)->sum('amount');
        $previousNetProfit = round($previousRevenue - $previousExpenses, 2);

        // --- Citas abiertas / canceladas dentro del rango ---
        $pendingQuery = Booking::query()->open()->whereBetween('scheduled_at', [$from, $to]);
        $pendingCount = (clone $pendingQuery)->count();
        $pendingRevenue = (float) (clone $pendingQuery)->sum('total_price');
        $cancelledCount = Booking::query()
            ->where('status', 'cancelled')
            ->whereBetween('scheduled_at', [$from, $to])
            ->count();

        return Inertia::render('Admin/Sales/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'granularity' => $granularity,
            ],
            'summary' => [
                'revenue' => round($revenue, 2),
                'previousRevenue' => round($previousRevenue, 2),
                'revenueChange' => $this->percentageChange($revenue, $previousRevenue),
                'completedServices' => $completed,
                'previousCompletedServices' => $previousCompleted,
                'completedChange' => $this->percentageChange($completed, $previousCompleted),
                'expenses' => round($expenses, 2),
                'previousExpenses' => round($previousExpenses, 2),
                'expensesChange' => $this->percentageChange($expenses, $previousExpenses),
                'netProfit' => $netProfit,
                'previousNetProfit' => $previousNetProfit,
                'netProfitChange' => $this->percentageChange($netProfit, $previousNetProfit),
                'netMargin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : null,
                'averageTicket' => $completed > 0 ? round($revenue / $completed, 2) : 0,
                'pendingRevenue' => round($pendingRevenue, 2),
                'pendingCount' => $pendingCount,
                'cancelledCount' => $cancelledCount,
            ],
            'timeseries' => $this->timeseries($from, $to, $granularity),
            'topServices' => $this->topServices($from, $to),
            'paymentBreakdown' => $this->paymentBreakdown($from, $to),
            'topEmployees' => $this->topEmployees($from, $to),
            'topClients' => $this->topClients($from, $to),
        ]);
    }

    /**
     * Serie de ingresos, gastos y utilidad por periodo.
     *
     * @return array{labels: array<int, string>, revenue: array<int, float>, expenses: array<int, float>, profit: array<int, float>}
     */
    private function timeseries(Carbon $from, Carbon $to, string $granularity): array
    {
        $revenueExpression = match ($granularity) {
            'week' => "DATE_FORMAT(COALESCE(bookings.completed_at, bookings.updated_at), '%x-W%v')",
            'month' => "DATE_FORMAT(COALESCE(bookings.completed_at, bookings.updated_at), '%Y-%m')",
            default => "DATE_FORMAT(COALESCE(bookings.completed_at, bookings.updated_at), '%Y-%m-%d')",
        };

        $expenseExpression = match ($granularity) {
            'week' => "DATE_FORMAT(expense_date, '%x-W%v')",
            'month' => "DATE_FORMAT(expense_date, '%Y-%m')",
            default => "DATE_FORMAT(expense_date, '%Y-%m-%d')",
        };

        $revenueByBucket = Booking::query()
            ->where('status', 'completed')
            ->whereRaw('COALESCE(bookings.completed_at, bookings.updated_at) BETWEEN ? AND ?', [$from, $to])
            ->selectRaw("{$revenueExpression} as bucket, COALESCE(SUM(total_price), 0) as total")
            ->groupByRaw($revenueExpression)
            ->pluck('total', 'bucket');

        $expensesByBucket = Expense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("{$expenseExpression} as bucket, COALESCE(SUM(amount), 0) as total")
            ->groupByRaw($expenseExpression)
            ->pluck('total', 'bucket');

        $labels = [];
        $revenue = [];
        $expenses = [];
        $profit = [];

        $cursor = match ($granularity) {
            'week' => $from->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $from->copy()->startOfMonth(),
            default => $from->copy(),
        };

        while ($cursor->lte($to)) {
            $key = match ($granularity) {
                'week' => $cursor->format('o-\WW'),
                'month' => $cursor->format('Y-m'),
                default => $cursor->format('Y-m-d'),
            };

            $labels[] = match ($granularity) {
                'week' => 'Sem ' . $cursor->format('W'),
                'month' => ucfirst($cursor->copy()->locale('es')->translatedFormat('M Y')),
                default => $cursor->copy()->locale('es')->translatedFormat('j M'),
            };

            $bucketRevenue = round((float) ($revenueByBucket[$key] ?? 0), 2);
            $bucketExpenses = round((float) ($expensesByBucket[$key] ?? 0), 2);

            $revenue[] = $bucketRevenue;
            $expenses[] = $bucketExpenses;
            $profit[] = round($bucketRevenue - $bucketExpenses, 2);

            match ($granularity) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonthNoOverflow()->startOfMonth(),
                default => $cursor->addDay(),
            };
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, services_count: int, revenue: float}>
     */
    private function topServices(Carbon $from, Carbon $to): array
    {
        return DB::table('booking_service as bs')
            ->join('bookings as b', 'b.id', '=', 'bs.booking_id')
            ->join('services as s', 's.id', '=', 'bs.service_id')
            ->where('b.status', 'completed')
            ->whereRaw('COALESCE(b.completed_at, b.updated_at) BETWEEN ? AND ?', [$from, $to])
            ->selectRaw('s.id, s.name, COUNT(*) as services_count, COALESCE(SUM(bs.price_at_booking), 0) as revenue')
            ->groupBy('s.id', 's.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'services_count' => (int) $row->services_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    /**
     * @return array<int, array{method: string, bookings_count: int, revenue: float}>
     */
    private function paymentBreakdown(Carbon $from, Carbon $to): array
    {
        return Booking::completedBetween($from, $to)
            ->selectRaw("COALESCE(payment_method, 'No especificado') as method, COUNT(*) as bookings_count, COALESCE(SUM(total_price), 0) as revenue")
            ->groupBy('method')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'bookings_count' => (int) $row->bookings_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    /**
     * @return array<int, array{employee_id: int, employee_name: string, services_count: int, revenue: float}>
     */
    private function topEmployees(Carbon $from, Carbon $to): array
    {
        return Booking::completedBetween($from, $to)
            ->join('users', 'users.id', '=', 'bookings.employee_id')
            ->selectRaw('users.id as employee_id, users.name as employee_name, COUNT(*) as services_count, COALESCE(SUM(bookings.total_price), 0) as revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (int) $row->employee_id,
                'employee_name' => $row->employee_name,
                'services_count' => (int) $row->services_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    /**
     * @return array<int, array{client_name: string, services_count: int, revenue: float}>
     */
    private function topClients(Carbon $from, Carbon $to): array
    {
        return Booking::completedBetween($from, $to)
            ->leftJoin('users', 'users.id', '=', 'bookings.user_id')
            ->selectRaw("COALESCE(users.name, bookings.guest_name, 'Cliente invitado') as client_name, COUNT(*) as services_count, COALESCE(SUM(bookings.total_price), 0) as revenue")
            ->groupByRaw("COALESCE(users.name, bookings.guest_name, 'Cliente invitado')")
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'client_name' => $row->client_name,
                'services_count' => (int) $row->services_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
