<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    /**
     * Panel general del administrador: ventas, equipo y citas abiertas.
     */
    public function index()
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        // --- Indicadores de ventas ---
        $monthRevenue = (float) Booking::completedBetween($monthStart, $monthEnd)->sum('total_price');
        $previousMonthRevenue = (float) Booking::completedBetween($previousMonthStart, $previousMonthEnd)->sum('total_price');
        $monthCompleted = Booking::completedBetween($monthStart, $monthEnd)->count();
        $previousMonthCompleted = Booking::completedBetween($previousMonthStart, $previousMonthEnd)->count();
        $todayRevenue = (float) Booking::completedBetween($todayStart, $todayEnd)->sum('total_price');
        $todayCompleted = Booking::completedBetween($todayStart, $todayEnd)->count();

        // --- Gastos y utilidad ---
        $monthExpenses = (float) Expense::between($monthStart, $monthEnd)->sum('amount');
        $previousMonthExpenses = (float) Expense::between($previousMonthStart, $previousMonthEnd)->sum('amount');
        $todayExpenses = (float) Expense::between($todayStart, $todayEnd)->sum('amount');
        $netProfit = round($monthRevenue - $monthExpenses, 2);
        $previousNetProfit = round($previousMonthRevenue - $previousMonthExpenses, 2);

        $topExpenseCategory = Expense::between($monthStart, $monthEnd)
            ->selectRaw('category, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        // --- Citas abiertas ---
        $openBookingsCount = Booking::open()->count();
        $todayOpenBookings = Booking::open()->whereBetween('scheduled_at', [$todayStart, $todayEnd])->count();
        $overdueOpenBookings = Booking::open()->where('scheduled_at', '<', $now)->count();
        $pendingMonthRevenue = (float) Booking::open()
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
            ->sum('total_price');

        // --- Equipo ---
        $employees = User::query()
            ->where('role', 'Empleado')
            ->with('media')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $employeeStats = Booking::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'completed')
            ->whereRaw('COALESCE(completed_at, updated_at) BETWEEN ? AND ?', [$monthStart, $monthEnd])
            ->selectRaw('employee_id, COUNT(*) as services_count, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $employeeAgenda = Booking::query()
            ->with(['services', 'client'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->open()
            ->where('scheduled_at', '>=', $todayStart->copy()->subDay())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('employee_id');

        $employeeRows = $employees->map(function (User $employee) use ($employeeAgenda, $employeeStats, $now) {
            /** @var Collection<int, Booking> $agenda */
            $agenda = $employeeAgenda->get($employee->id, collect());

            $current = $agenda->first(fn (Booking $booking) => $booking->isOngoing($now));
            $next = $agenda->first(
                fn (Booking $booking) => $booking->scheduled_at->gt($now) && $booking->id !== $current?->id
            );
            $stats = $employeeStats->get($employee->id);

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'phone_number' => $employee->phone_number,
                'avatar_url' => $employee->avatar_url ?: $employee->profile_photo_url,
                'is_active' => (bool) $employee->is_active,
                'status' => ! $employee->is_active ? 'inactive' : ($current ? 'in_service' : 'available'),
                'current_booking' => $current ? $this->employeeBookingSummary($current) : null,
                'next_booking' => $next ? $this->employeeBookingSummary($next) : null,
                'today_bookings' => $agenda->filter(fn (Booking $booking) => $booking->scheduled_at->isToday())->count(),
                'upcoming_bookings' => $agenda->filter(fn (Booking $booking) => $booking->scheduled_at->gt($now))->count(),
                'completed_this_month' => (int) ($stats->services_count ?? 0),
                'revenue_this_month' => round((float) ($stats->revenue ?? 0), 2),
            ];
        })->values();

        // --- Citas programadas que aún no se finalizan ---
        $upcomingBookings = Booking::query()
            ->with(['services', 'client', 'employee'])
            ->open()
            ->orderBy('scheduled_at')
            ->limit(60)
            ->get()
            ->map(fn (Booking $booking) => $this->bookingSummary($booking))
            ->values();

        // --- Ingresos día a día del mes en curso (con comparativo del mes anterior) ---
        $dailyCurrent = $this->dailyRevenue($monthStart, $monthEnd);
        $dailyPrevious = $this->dailyRevenue($previousMonthStart, $previousMonthEnd);
        $dailyExpenses = $this->dailyExpenses($monthStart, $monthEnd);

        $daysInMonth = (int) $monthStart->daysInMonth;
        $labels = [];
        $currentSeries = [];
        $previousSeries = [];
        $expensesSeries = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $labels[] = $monthStart->copy()->day($day)->locale('es')->translatedFormat('j M');
            $currentSeries[] = round((float) ($dailyCurrent[$day] ?? 0), 2);
            $previousSeries[] = round((float) ($dailyPrevious[$day] ?? 0), 2);
            $expensesSeries[] = round((float) ($dailyExpenses[$day] ?? 0), 2);
        }

        // --- Servicios más vendidos y métodos de pago del mes ---
        $topServices = DB::table('booking_service as bs')
            ->join('bookings as b', 'b.id', '=', 'bs.booking_id')
            ->join('services as s', 's.id', '=', 'bs.service_id')
            ->where('b.status', 'completed')
            ->whereRaw('COALESCE(b.completed_at, b.updated_at) BETWEEN ? AND ?', [$monthStart, $monthEnd])
            ->selectRaw('s.id, s.name, COUNT(*) as services_count, COALESCE(SUM(bs.price_at_booking), 0) as revenue')
            ->groupBy('s.id', 's.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'services_count' => (int) $row->services_count,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $paymentBreakdown = Booking::completedBetween($monthStart, $monthEnd)
            ->selectRaw("COALESCE(payment_method, 'No especificado') as method, COUNT(*) as bookings_count, COALESCE(SUM(total_price), 0) as revenue")
            ->groupBy('method')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'bookings_count' => (int) $row->bookings_count,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'kpis' => [
                'monthRevenue' => round($monthRevenue, 2),
                'previousMonthRevenue' => round($previousMonthRevenue, 2),
                'revenueChange' => $this->percentageChange($monthRevenue, $previousMonthRevenue),
                'completedServices' => $monthCompleted,
                'previousCompletedServices' => $previousMonthCompleted,
                'completedChange' => $this->percentageChange($monthCompleted, $previousMonthCompleted),
                'averageTicket' => $monthCompleted > 0 ? round($monthRevenue / $monthCompleted, 2) : 0,
                'todayRevenue' => round($todayRevenue, 2),
                'todayCompleted' => $todayCompleted,
                'monthExpenses' => round($monthExpenses, 2),
                'previousMonthExpenses' => round($previousMonthExpenses, 2),
                'expensesChange' => $this->percentageChange($monthExpenses, $previousMonthExpenses),
                'todayExpenses' => round($todayExpenses, 2),
                'netProfit' => $netProfit,
                'previousNetProfit' => $previousNetProfit,
                'netProfitChange' => $this->percentageChange($netProfit, $previousNetProfit),
                'netMargin' => $monthRevenue > 0 ? round(($netProfit / $monthRevenue) * 100, 1) : null,
                'topExpenseCategory' => $topExpenseCategory?->category,
                'topExpenseCategoryTotal' => round((float) ($topExpenseCategory?->total ?? 0), 2),
                'openBookings' => $openBookingsCount,
                'todayOpenBookings' => $todayOpenBookings,
                'overdueOpenBookings' => $overdueOpenBookings,
                'pendingRevenue' => round($pendingMonthRevenue, 2),
                'employeesTotal' => $employees->count(),
                'employeesActive' => $employees->where('is_active', true)->count(),
                'employeesBusy' => $employeeRows->where('status', 'in_service')->count(),
                'newClients' => User::where('role', 'Cliente')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count(),
            ],
            'revenueChart' => [
                'monthLabel' => $monthStart->copy()->locale('es')->translatedFormat('F Y'),
                'previousMonthLabel' => $previousMonthStart->copy()->locale('es')->translatedFormat('F Y'),
                'labels' => $labels,
                'current' => $currentSeries,
                'previous' => $previousSeries,
                'expenses' => $expensesSeries,
            ],
            'topServices' => $topServices,
            'paymentBreakdown' => $paymentBreakdown,
            'employees' => $employeeRows,
            'bookings' => $upcomingBookings,
            'employeeOptions' => $employeeRows->map(fn ($employee) => [
                'id' => $employee['id'],
                'name' => $employee['name'],
                'status' => $employee['status'],
                'is_active' => $employee['is_active'],
                'avatar_url' => $employee['avatar_url'],
            ])->values(),
            'generatedAt' => $now->toIso8601String(),
        ]);
    }

    /**
     * Suma de ingresos por día para un rango de fechas.
     *
     * @return array<int, float>
     */
    private function dailyRevenue(Carbon $start, Carbon $end): array
    {
        return Booking::query()
            ->where('status', 'completed')
            ->whereRaw('COALESCE(completed_at, updated_at) BETWEEN ? AND ?', [$start, $end])
            ->selectRaw('DAY(COALESCE(completed_at, updated_at)) as day, COALESCE(SUM(total_price), 0) as total')
            ->groupByRaw('DAY(COALESCE(completed_at, updated_at))')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($total, $day) => [(int) $day => (float) $total])
            ->all();
    }

    /**
     * Suma de gastos por día para un rango de fechas.
     *
     * @return array<int, float>
     */
    private function dailyExpenses(Carbon $start, Carbon $end): array
    {
        return Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DAY(expense_date) as day, COALESCE(SUM(amount), 0) as total')
            ->groupByRaw('DAY(expense_date)')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($total, $day) => [(int) $day => (float) $total])
            ->all();
    }

    /**
     * Resumen ligero de una cita usada en la ficha del empleado.
     */
    private function employeeBookingSummary(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'client_name' => $booking->clientName(),
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'ends_at' => $booking->endsAt()?->toIso8601String(),
            'status' => $booking->status,
            'services' => $booking->services->pluck('name')->all(),
            'address' => $booking->address,
        ];
    }

    /**
     * Resumen completo de una cita para la tabla del panel.
     */
    private function bookingSummary(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'client_name' => $booking->clientName(),
            'client_phone' => $booking->client?->phone_number ?? $booking->guest_phone,
            'address' => $booking->address,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'ends_at' => $booking->endsAt()?->toIso8601String(),
            'is_today' => $booking->scheduled_at?->isToday() ?? false,
            'is_overdue' => $booking->scheduled_at?->lt(now()) ?? false,
            'status' => $booking->status,
            'payment_method' => $booking->payment_method,
            'total_price' => round((float) $booking->total_price, 2),
            'notes' => $booking->notes,
            'employee_id' => $booking->employee_id,
            'employee_name' => $booking->employee?->name,
            'employee_avatar' => $booking->employee?->avatar_url ?: $booking->employee?->profile_photo_url,
            'duration_minutes' => $booking->durationMinutes(),
            'services' => $booking->services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration_minutes' => (int) $service->duration_minutes,
                'price' => round((float) ($service->pivot->price_at_booking ?? $service->price), 2),
            ])->values()->all(),
        ];
    }

    /**
     * Variación porcentual entre dos periodos.
     */
    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
