<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    kpis: { type: Object, required: true },
    revenueChart: { type: Object, required: true },
    topServices: { type: Array, default: () => [] },
    paymentBreakdown: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    bookings: { type: Array, default: () => [] },
    employeeOptions: { type: Array, default: () => [] },
    generatedAt: { type: String, default: null },
});

const page = usePage();
const toast = useToast();

const userName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'Admin');
const refreshing = ref(false);

// ---------------------------------------------------------------- Formato
const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));

const toDate = (value) => (value ? new Date(value) : null);

const formatTime = (value) => {
    const date = toDate(value);
    return date ? date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : '—';
};

const formatDay = (value) => {
    const date = toDate(value);
    return date ? date.toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' }) : '—';
};

const formatDateTime = (value) => (value ? `${formatDay(value)} · ${formatTime(value)}` : '—');

const initials = (name) =>
    (name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

const todayLabel = new Date().toLocaleDateString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

// ---------------------------------------------------------------- KPIs
const kpiCards = computed(() => [
    {
        label: 'Ventas brutas del mes',
        value: formatCurrency(props.kpis.monthRevenue),
        hint: `${formatCurrency(props.kpis.previousMonthRevenue)} el mes anterior`,
        change: props.kpis.revenueChange,
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 0c-1.11 0-2.08-.402-2.599-1M12 4V3m0 18v-1',
        iconClasses: 'bg-cloud-50 text-cloud-600 dark:bg-cloud-900/40 dark:text-cloud-300',
    },
    {
        label: 'Gastos del mes',
        value: formatCurrency(props.kpis.monthExpenses),
        hint: props.kpis.topExpenseCategory
            ? `${props.kpis.topExpenseCategory} · ${formatCurrency(props.kpis.topExpenseCategoryTotal)}`
            : 'Sin gastos registrados',
        change: props.kpis.expensesChange,
        invertChange: true,
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        iconClasses: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
    },
    {
        label: 'Utilidad neta',
        value: formatCurrency(props.kpis.netProfit),
        hint:
            props.kpis.netMargin !== null
                ? `Margen del ${props.kpis.netMargin}% sobre ventas`
                : 'Aún no hay ventas este mes',
        change: props.kpis.netProfitChange,
        icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        iconClasses: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
    },
    {
        label: 'Servicios finalizados',
        value: formatNumber(props.kpis.completedServices),
        hint: `${formatNumber(props.kpis.previousCompletedServices)} el mes anterior`,
        change: props.kpis.completedChange,
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        iconClasses: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
    },
    {
        label: 'Citas por finalizar',
        value: formatNumber(props.kpis.openBookings),
        hint: `${formatNumber(props.kpis.todayOpenBookings)} para hoy · ${formatNumber(props.kpis.overdueOpenBookings)} vencidas`,
        change: null,
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        iconClasses: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
    },
    {
        label: 'Equipo en servicio',
        value: `${formatNumber(props.kpis.employeesBusy)}/${formatNumber(props.kpis.employeesActive)}`,
        hint: `${formatNumber(props.kpis.employeesTotal)} empleados registrados`,
        change: null,
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        iconClasses: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
    },
]);

const secondaryStats = computed(() => [
    { label: 'Ticket promedio', value: formatCurrency(props.kpis.averageTicket) },
    { label: 'Ingresos de hoy', value: formatCurrency(props.kpis.todayRevenue) },
    { label: 'Gastos de hoy', value: formatCurrency(props.kpis.todayExpenses) },
    { label: 'Por facturar (mes)', value: formatCurrency(props.kpis.pendingRevenue) },
    { label: 'Servicios de hoy', value: formatNumber(props.kpis.todayCompleted) },
    { label: 'Clientes nuevos', value: formatNumber(props.kpis.newClients) },
]);

const changeClasses = (change, invert = false) => {
    const positive = invert ? change <= 0 : change >= 0;

    return positive
        ? 'bg-sage-50 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300'
        : 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300';
};

// ---------------------------------------------------------------- Gráfica de ingresos
const revenueChartData = computed(() => ({
    labels: props.revenueChart.labels,
    datasets: [
        {
            label: props.revenueChart.monthLabel,
            data: props.revenueChart.current,
            fill: true,
            tension: 0.35,
            borderColor: '#0b6b9b',
            backgroundColor: 'rgba(11, 107, 155, 0.12)',
            pointRadius: 0,
            pointHoverRadius: 5,
            borderWidth: 2.5,
        },
        {
            label: props.revenueChart.previousMonthLabel,
            data: props.revenueChart.previous,
            fill: false,
            tension: 0.35,
            borderColor: '#a3b8c8',
            borderDash: [5, 5],
            pointRadius: 0,
            pointHoverRadius: 4,
            borderWidth: 1.5,
        },
        {
            label: `Gastos ${props.revenueChart.monthLabel}`,
            data: props.revenueChart.expenses,
            fill: false,
            tension: 0.35,
            borderColor: '#f43f5e',
            backgroundColor: 'rgba(244, 63, 94, 0.08)',
            borderDash: [4, 4],
            pointRadius: 0,
            pointHoverRadius: 4,
            borderWidth: 1.8,
        },
    ],
}));

const revenueChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                boxHeight: 8,
                color: '#8098ac',
                font: { size: 11 },
            },
        },
        tooltip: {
            backgroundColor: 'rgba(58, 68, 78, 0.94)',
            padding: 12,
            cornerRadius: 12,
            callbacks: {
                label: (context) => ` ${context.dataset.label}: ${formatCurrency(context.parsed.y)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#a3b8c8', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
            border: { display: false },
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(163, 184, 200, 0.2)' },
            ticks: {
                color: '#a3b8c8',
                font: { size: 10 },
                callback: (value) =>
                    new Intl.NumberFormat('es-MX', { notation: 'compact', maximumFractionDigits: 1 }).format(value),
            },
            border: { display: false },
        },
    },
}));

const topServicesMaxRevenue = computed(() =>
    Math.max(...props.topServices.map((service) => Number(service.revenue) || 0), 1)
);

const paymentTotal = computed(() =>
    props.paymentBreakdown.reduce((total, row) => total + Number(row.revenue || 0), 0)
);

// ---------------------------------------------------------------- Estados
const bookingStatusMeta = (status) =>
    ({
        pending: {
            label: 'Pendiente',
            classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            dot: 'bg-amber-500',
        },
        confirmed: {
            label: 'Confirmada',
            classes: 'bg-cloud-100 text-cloud-700 dark:bg-cloud-900/40 dark:text-cloud-300',
            dot: 'bg-cloud-500',
        },
        on_way: {
            label: 'En camino',
            classes: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            dot: 'bg-indigo-500',
        },
        in_progress: {
            label: 'En servicio',
            classes: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300',
            dot: 'bg-sage-500',
        },
        completed: {
            label: 'Finalizada',
            classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            dot: 'bg-emerald-500',
        },
        cancelled: {
            label: 'Cancelada',
            classes: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            dot: 'bg-red-500',
        },
    })[status] ?? {
        label: status,
        classes: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
        dot: 'bg-mist-400',
    };

const employeeStatusMeta = (status) =>
    ({
        in_service: {
            label: 'En servicio',
            classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            dot: 'bg-amber-500',
        },
        available: {
            label: 'Disponible',
            classes: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300',
            dot: 'bg-sage-500',
        },
        inactive: {
            label: 'Inactivo',
            classes: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
            dot: 'bg-mist-400',
        },
    })[status] ?? {
        label: status,
        classes: 'bg-mist-100 text-mist-600',
        dot: 'bg-mist-400',
    };

const nextStatus = (status) =>
    ({
        pending: { value: 'confirmed', label: 'Confirmar', icon: 'pi pi-check', severity: 'info' },
        confirmed: { value: 'on_way', label: 'En camino', icon: 'pi pi-send', severity: 'info' },
        on_way: { value: 'in_progress', label: 'Iniciar', icon: 'pi pi-play', severity: 'warn' },
        in_progress: { value: 'completed', label: 'Finalizar', icon: 'pi pi-check-circle', severity: 'success' },
    })[status] ?? null;

const nextOpenStatus = (status) =>
    ({
        pending: 'confirmed',
        confirmed: 'on_way',
        on_way: 'in_progress',
        in_progress: 'completed',
    })[status] ?? null;

const canCancel = (status) => ['pending', 'confirmed', 'on_way', 'in_progress'].includes(status);

// ---------------------------------------------------------------- Acciones sobre citas
const employeesForAssignment = computed(() =>
    props.employeeOptions.filter((employee) => employee.is_active)
);

const assignEmployee = (booking, employeeId) => {
    if (!employeeId || employeeId === booking.employee_id) return;

    router.patch(
        route('admin.bookings.assignEmployee', booking.id),
        { employee_id: employeeId },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.add({
                    severity: 'success',
                    summary: 'Empleado asignado',
                    detail: `${booking.client_name} actualizado.`,
                    life: 3000,
                }),
            onError: (errors) =>
                toast.add({
                    severity: 'error',
                    summary: 'No se pudo asignar',
                    detail: errors.employee_id || 'Inténtalo de nuevo.',
                    life: 4000,
                }),
        }
    );
};

const changeStatus = (booking, status) => {
    if (!status) return;

    router.patch(
        route('admin.bookings.updateStatus', booking.id),
        { status },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.add({
                    severity: 'success',
                    summary: 'Cita actualizada',
                    detail: `${booking.client_name} → ${bookingStatusMeta(status).label}`,
                    life: 3000,
                }),
            onError: () =>
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la cita.', life: 4000 }),
        }
    );
};

const cancelBooking = (booking) => changeStatus(booking, 'cancelled');

// ---------------------------------------------------------------- Filtro y detalle
const bookingFilter = ref('all');

const bookingFilters = computed(() => [
    { key: 'all', label: 'Todas', count: props.bookings.length },
    { key: 'today', label: 'Hoy', count: props.bookings.filter((booking) => booking.is_today).length },
    { key: 'overdue', label: 'Vencidas', count: props.bookings.filter((booking) => booking.is_overdue).length },
]);

const filteredBookings = computed(() => {
    if (bookingFilter.value === 'today') return props.bookings.filter((booking) => booking.is_today);
    if (bookingFilter.value === 'overdue') return props.bookings.filter((booking) => booking.is_overdue);
    return props.bookings;
});

const selectedBooking = ref(null);
const showDetail = ref(false);

const openDetail = (booking) => {
    selectedBooking.value = booking;
    showDetail.value = true;
};

// ---------------------------------------------------------------- Nueva cita
const goToCreate = (employeeId = null) => {
    router.get(route('admin.bookings.create'), employeeId ? { employee_id: employeeId } : {});
};

// ---------------------------------------------------------------- Actualización
const refresh = () => {
    router.reload({
        only: ['kpis', 'revenueChart', 'topServices', 'paymentBreakdown', 'employees', 'bookings', 'generatedAt'],
        preserveScroll: true,
        onStart: () => (refreshing.value = true),
        onFinish: () => (refreshing.value = false),
    });
};

let refreshTimer = null;

onMounted(() => {
    refreshTimer = setInterval(() => {
        if (showDetail.value) return;
        router.reload({
            only: ['kpis', 'revenueChart', 'topServices', 'paymentBreakdown', 'employees', 'bookings', 'generatedAt'],
            preserveScroll: true,
        });
    }, 60000);
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Panel de administración">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        {{ todayLabel }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">
                        Hola, {{ userName }} 👋
                    </h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Este es el pulso de WashApp: ventas, equipo y citas por finalizar.
                        <span v-if="generatedAt" class="text-mist-400 dark:text-slate-500">
                            · Actualizado {{ formatTime(generatedAt) }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Actualizar"
                        icon="pi pi-refresh"
                        severity="secondary"
                        outlined
                        size="small"
                        :loading="refreshing"
                        @click="refresh"
                    />
                    <Button label="Agendar cita" icon="pi pi-calendar-plus" size="small" @click="goToCreate()" />
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="relative overflow-hidden rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur transition hover:-translate-y-0.5 hover:shadow-lift dark:border-slate-800 dark:bg-gray-800/90"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mist-400 dark:text-slate-500">
                                {{ card.label }}
                            </p>
                            <p class="mt-2 truncate text-3xl font-semibold text-mist-900 dark:text-white">
                                {{ card.value }}
                            </p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl" :class="card.iconClasses">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                            </svg>
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                        <span
                            v-if="card.change !== null"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold"
                            :class="changeClasses(card.change, card.invertChange)"
                        >
                            <i class="pi" :class="card.change >= 0 ? 'pi-arrow-up' : 'pi-arrow-down'" style="font-size: 0.6rem"></i>
                            {{ Math.abs(card.change) }}%
                        </span>
                        <span class="text-mist-400 dark:text-slate-500">{{ card.hint }}</span>
                    </div>
                </div>
            </div>

            <!-- Métricas rápidas -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                <div
                    v-for="stat in secondaryStats"
                    :key="stat.label"
                    class="rounded-2xl border border-mist-100 bg-white/70 px-4 py-3 dark:border-slate-800 dark:bg-gray-800/60"
                >
                    <p class="text-[11px] font-medium uppercase tracking-wide text-mist-400 dark:text-slate-500">
                        {{ stat.label }}
                    </p>
                    <p class="mt-1 text-lg font-semibold text-mist-800 dark:text-slate-100">{{ stat.value }}</p>
                </div>
            </div>

            <!-- Gráfica + servicios top -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90 xl:col-span-2"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Ingresos por día</h2>
                            <p class="text-xs text-mist-400 dark:text-slate-500">
                                {{ revenueChart.monthLabel }} vs. {{ revenueChart.previousMonthLabel }}
                            </p>
                        </div>
                        <div class="flex gap-4 text-right">
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-mist-400">Hoy</p>
                                <p class="text-sm font-semibold text-mist-800 dark:text-slate-100">
                                    {{ formatCurrency(kpis.todayRevenue) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-mist-400">Mes</p>
                                <p class="text-sm font-semibold text-cloud-600 dark:text-cloud-300">
                                    {{ formatCurrency(kpis.monthRevenue) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" style="height: 320px">
                        <Chart type="line" :data="revenueChartData" :options="revenueChartOptions" style="height: 100%" />
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
                >
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Servicios más vendidos</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Ingresos del mes en curso</p>

                    <div v-if="topServices.length" class="mt-5 space-y-4">
                        <div v-for="service in topServices" :key="service.id">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-mist-700 dark:text-slate-200">{{ service.name }}</span>
                                <span class="whitespace-nowrap text-xs font-semibold text-mist-500 dark:text-slate-300">
                                    {{ formatCurrency(service.revenue) }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-cloud-500 to-sage-400"
                                    :style="{ width: `${Math.max((service.revenue / topServicesMaxRevenue) * 100, 4)}%` }"
                                ></div>
                            </div>
                            <p class="mt-1 text-[11px] text-mist-400 dark:text-slate-500">
                                {{ formatNumber(service.services_count) }} servicios vendidos
                            </p>
                        </div>
                    </div>

                    <div v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">
                        <i class="pi pi-chart-bar mb-2 block text-2xl"></i>
                        Aún no hay servicios finalizados este mes.
                    </div>

                    <div v-if="paymentBreakdown.length" class="mt-6 border-t border-mist-100 pt-4 dark:border-slate-700">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-mist-400 dark:text-slate-500">
                            Métodos de pago
                        </p>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="method in paymentBreakdown"
                                :key="method.method"
                                class="flex items-center justify-between gap-3 text-xs"
                            >
                                <span class="flex items-center gap-2 text-mist-600 dark:text-slate-300">
                                    <span class="h-2 w-2 rounded-full bg-cloud-400"></span>
                                    {{ method.method }}
                                    <span class="text-mist-400">({{ method.bookings_count }})</span>
                                </span>
                                <span class="font-semibold text-mist-700 dark:text-slate-200">
                                    {{ paymentTotal ? Math.round((method.revenue / paymentTotal) * 100) : 0 }}%
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Equipo -->
            <div
                class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Equipo de empleados</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">
                            {{ kpis.employeesActive }} activos · {{ kpis.employeesBusy }} realizando un servicio
                        </p>
                    </div>
                    <Button label="Agendar cita" icon="pi pi-calendar-plus" text size="small" @click="goToCreate()" />
                </div>

                <div v-if="employees.length" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="employee in employees"
                        :key="employee.id"
                        class="flex flex-col rounded-2xl border border-mist-100 bg-white p-4 transition hover:border-cloud-200 hover:shadow-soft dark:border-slate-700 dark:bg-gray-800/70"
                    >
                        <div class="flex items-start gap-3">
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-2xl bg-mist-100 dark:bg-slate-700">
                                <img
                                    v-if="employee.avatar_url"
                                    :src="employee.avatar_url"
                                    :alt="employee.name"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="grid h-full w-full place-items-center text-sm font-semibold text-mist-500 dark:text-slate-300"
                                >
                                    {{ initials(employee.name) }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">
                                    {{ employee.name }}
                                </p>
                                <p class="truncate text-xs text-mist-400 dark:text-slate-500">
                                    {{ employee.phone_number || 'Sin teléfono' }}
                                </p>
                                <span
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="employeeStatusMeta(employee.status).classes"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="employeeStatusMeta(employee.status).dot"></span>
                                    {{ employeeStatusMeta(employee.status).label }}
                                </span>
                                <p class="mt-1 text-[11px] text-mist-400 dark:text-slate-500">
                                    {{ employee.today_bookings }} citas hoy · {{ employee.upcoming_bookings }} próximas
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2 text-xs">
                            <div v-if="employee.current_booking" class="rounded-xl bg-amber-50 px-3 py-2 dark:bg-amber-900/20">
                                <p class="font-semibold text-amber-700 dark:text-amber-300">
                                    Atendiendo a {{ employee.current_booking.client_name }}
                                </p>
                                <p class="mt-0.5 text-amber-600/80 dark:text-amber-200/70">
                                    {{ formatTime(employee.current_booking.scheduled_at) }} –
                                    {{ formatTime(employee.current_booking.ends_at) }} ·
                                    {{ employee.current_booking.services.join(' + ') }}
                                </p>
                            </div>
                            <div v-else class="rounded-xl bg-mist-50 px-3 py-2 text-mist-500 dark:bg-slate-700/40 dark:text-slate-300">
                                Sin servicio en curso
                            </div>

                            <div v-if="employee.next_booking" class="rounded-xl bg-cloud-50/70 px-3 py-2 dark:bg-cloud-900/20">
                                <p class="font-semibold text-cloud-700 dark:text-cloud-300">
                                    Próxima: {{ formatDateTime(employee.next_booking.scheduled_at) }}
                                </p>
                                <p class="mt-0.5 text-cloud-600/80 dark:text-cloud-200/70">
                                    {{ employee.next_booking.client_name }} · {{ employee.next_booking.services.join(' + ') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2 border-t border-mist-100 pt-3 text-center dark:border-slate-700">
                            <div>
                                <p class="text-lg font-semibold text-mist-900 dark:text-white">
                                    {{ formatNumber(employee.completed_this_month) }}
                                </p>
                                <p class="text-[10px] uppercase tracking-wide text-mist-400">Servicios del mes</p>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-sage-600 dark:text-sage-300">
                                    {{ formatCurrency(employee.revenue_this_month) }}
                                </p>
                                <p class="text-[10px] uppercase tracking-wide text-mist-400">Generado</p>
                            </div>
                        </div>

                        <Button
                            label="Asignar cita"
                            icon="pi pi-calendar-plus"
                            text
                            size="small"
                            class="mt-3 w-full"
                            @click="goToCreate(employee.id)"
                        />
                    </div>
                </div>

                <div v-else class="mt-8 text-center text-sm text-mist-400 dark:text-slate-500">
                    <i class="pi pi-users mb-2 block text-2xl"></i>
                    Todavía no hay empleados registrados.
                </div>
            </div>

            <!-- Citas por finalizar -->
            <div
                class="rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-mist-100 px-5 py-4 dark:border-slate-700">
                    <div>
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Citas por finalizar</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">
                            Pendientes, confirmadas y en proceso. Asigna al empleado o avanza el estado.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-1 rounded-2xl bg-mist-50 p-1 dark:bg-slate-700/40">
                        <button
                            v-for="filter in bookingFilters"
                            :key="filter.key"
                            type="button"
                            class="rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                bookingFilter === filter.key
                                    ? 'bg-white text-mist-900 shadow-sm dark:bg-slate-800 dark:text-white'
                                    : 'text-mist-500 hover:text-mist-800 dark:text-slate-400 dark:hover:text-white'
                            "
                            @click="bookingFilter = filter.key"
                        >
                            {{ filter.label }}
                            <span class="ml-1 text-[10px] text-mist-400">{{ filter.count }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="filteredBookings.length">
                    <!-- Tabla (escritorio) -->
                    <div class="hidden overflow-x-auto xl:block">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-mist-50/60 text-[11px] uppercase tracking-wide text-mist-400 dark:bg-slate-800/60 dark:text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Cita</th>
                                    <th class="px-4 py-3 font-semibold">Cliente</th>
                                    <th class="px-4 py-3 font-semibold">Servicios</th>
                                    <th class="px-4 py-3 font-semibold">Empleado</th>
                                    <th class="px-4 py-3 font-semibold">Estado</th>
                                    <th class="hidden px-4 py-3 text-right font-semibold 2xl:table-cell">Total</th>
                                    <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-100 dark:divide-slate-700">
                                <tr
                                    v-for="booking in filteredBookings"
                                    :key="booking.id"
                                    class="transition hover:bg-mist-50/60 dark:hover:bg-slate-800/40"
                                >
                                    <td class="whitespace-nowrap px-4 py-3.5 align-top">
                                        <p class="font-semibold text-mist-800 dark:text-slate-100">
                                            {{ formatDay(booking.scheduled_at) }}
                                        </p>
                                        <p class="text-xs text-mist-400 dark:text-slate-500">
                                            {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }}
                                        </p>
                                        <span
                                            v-if="booking.is_overdue"
                                            class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600 dark:bg-red-900/30 dark:text-red-300"
                                        >
                                            Vencida
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <p class="font-medium text-mist-800 dark:text-slate-100">{{ booking.client_name }}</p>
                                        <p class="text-xs text-mist-400 dark:text-slate-500">{{ booking.client_phone || 'Sin teléfono' }}</p>
                                    </td>
                                    <td class="max-w-[13rem] px-4 py-3.5 align-top">
                                        <p class="truncate text-mist-600 dark:text-slate-300">
                                            {{ booking.services.map((service) => service.name).join(' + ') }}
                                        </p>
                                        <p class="text-xs text-mist-400 dark:text-slate-500">
                                            {{ booking.duration_minutes }} min · {{ formatCurrency(booking.total_price) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <Select
                                            :modelValue="booking.employee_id"
                                            :options="employeesForAssignment"
                                            optionLabel="name"
                                            optionValue="id"
                                            placeholder="Sin asignar"
                                            filter
                                            size="small"
                                            class="w-36"
                                            @change="(event) => assignEmployee(booking, event.value)"
                                        />
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                            :class="bookingStatusMeta(booking.status).classes"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="bookingStatusMeta(booking.status).dot"></span>
                                            {{ bookingStatusMeta(booking.status).label }}
                                        </span>
                                    </td>
                                    <td
                                        class="hidden whitespace-nowrap px-4 py-3.5 text-right align-top font-semibold text-mist-800 2xl:table-cell dark:text-slate-100"
                                    >
                                        {{ formatCurrency(booking.total_price) }}
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button
                                                icon="pi pi-eye"
                                                text
                                                rounded
                                                size="small"
                                                severity="secondary"
                                                aria-label="Ver detalle"
                                                @click="openDetail(booking)"
                                            />
                                            <Button
                                                v-if="nextStatus(booking.status)"
                                                :label="nextStatus(booking.status).label"
                                                :icon="nextStatus(booking.status).icon"
                                                :severity="nextStatus(booking.status).severity"
                                                size="small"
                                                @click="changeStatus(booking, nextStatus(booking.status).value)"
                                            />
                                            <Button
                                                v-if="canCancel(booking.status)"
                                                icon="pi pi-times"
                                                text
                                                rounded
                                                size="small"
                                                severity="danger"
                                                aria-label="Cancelar cita"
                                                @click="cancelBooking(booking)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tarjetas (móvil / tablet) -->
                    <div class="divide-y divide-mist-100 xl:hidden dark:divide-slate-700">
                        <div v-for="booking in filteredBookings" :key="booking.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-mist-900 dark:text-white">{{ booking.client_name }}</p>
                                    <p class="text-xs text-mist-400 dark:text-slate-500">
                                        {{ formatDateTime(booking.scheduled_at) }} · {{ booking.duration_minutes }} min
                                    </p>
                                </div>
                                <span
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="bookingStatusMeta(booking.status).classes"
                                >
                                    {{ bookingStatusMeta(booking.status).label }}
                                </span>
                            </div>

                            <p class="mt-2 text-xs text-mist-500 dark:text-slate-400">
                                {{ booking.services.map((service) => service.name).join(' + ') }}
                            </p>
                            <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">{{ booking.address }}</p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <Select
                                    :modelValue="booking.employee_id"
                                    :options="employeesForAssignment"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Sin asignar"
                                    size="small"
                                    class="min-w-[10rem] flex-1"
                                    @change="(event) => assignEmployee(booking, event.value)"
                                />
                                <span class="text-sm font-semibold text-mist-800 dark:text-slate-100">
                                    {{ formatCurrency(booking.total_price) }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <Button
                                    label="Detalle"
                                    icon="pi pi-eye"
                                    text
                                    size="small"
                                    severity="secondary"
                                    @click="openDetail(booking)"
                                />
                                <Button
                                    v-if="nextStatus(booking.status)"
                                    :label="nextStatus(booking.status).label"
                                    :icon="nextStatus(booking.status).icon"
                                    :severity="nextStatus(booking.status).severity"
                                    size="small"
                                    @click="changeStatus(booking, nextStatus(booking.status).value)"
                                />
                                <Button
                                    v-if="canCancel(booking.status)"
                                    label="Cancelar"
                                    icon="pi pi-times"
                                    text
                                    size="small"
                                    severity="danger"
                                    @click="cancelBooking(booking)"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="px-5 py-16 text-center text-sm text-mist-400 dark:text-slate-500">
                    <i class="pi pi-check-circle mb-2 block text-3xl text-sage-400"></i>
                    No hay citas pendientes con este filtro.
                </div>
            </div>
        </div>

        <!-- Diálogo: detalle de la cita -->
        <Dialog
            v-model:visible="showDetail"
            modal
            header="Detalle de la cita"
            :style="{ width: '34rem' }"
            :breakpoints="{ '640px': '96vw' }"
        >
            <div v-if="selectedBooking" class="space-y-4 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold text-mist-900 dark:text-white">{{ selectedBooking.client_name }}</p>
                        <p class="text-xs text-mist-400 dark:text-slate-500">{{ selectedBooking.client_phone || 'Sin teléfono' }}</p>
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="bookingStatusMeta(selectedBooking.status).classes"
                    >
                        {{ bookingStatusMeta(selectedBooking.status).label }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-2xl bg-mist-50 p-3 text-xs dark:bg-slate-700/40">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-mist-400">Programada</p>
                        <p class="mt-0.5 font-semibold text-mist-700 dark:text-slate-200">
                            {{ formatDay(selectedBooking.scheduled_at) }}
                        </p>
                        <p class="text-mist-500 dark:text-slate-400">
                            {{ formatTime(selectedBooking.scheduled_at) }} – {{ formatTime(selectedBooking.ends_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-mist-400">Empleado</p>
                        <p class="mt-0.5 font-semibold text-mist-700 dark:text-slate-200">
                            {{ selectedBooking.employee_name || 'Sin asignar' }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] uppercase tracking-wide text-mist-400">Dirección</p>
                        <p class="mt-0.5 text-mist-600 dark:text-slate-300">{{ selectedBooking.address }}</p>
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-[10px] uppercase tracking-wide text-mist-400">Servicios</p>
                    <ul class="space-y-1.5">
                        <li
                            v-for="service in selectedBooking.services"
                            :key="service.id"
                            class="flex items-center justify-between gap-3 rounded-xl border border-mist-100 px-3 py-2 dark:border-slate-700"
                        >
                            <span class="text-mist-700 dark:text-slate-200">{{ service.name }}</span>
                            <span class="text-xs text-mist-400">{{ service.duration_minutes }} min</span>
                            <span class="font-semibold text-mist-800 dark:text-slate-100">{{ formatCurrency(service.price) }}</span>
                        </li>
                    </ul>
                </div>

                <div class="flex items-center justify-between border-t border-mist-100 pt-3 dark:border-slate-700">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-mist-400">Método de pago</p>
                        <p class="font-medium text-mist-600 dark:text-slate-300">
                            {{ selectedBooking.payment_method || 'No especificado' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wide text-mist-400">Total</p>
                        <p class="text-xl font-semibold text-mist-900 dark:text-white">
                            {{ formatCurrency(selectedBooking.total_price) }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="selectedBooking.notes"
                    class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-200"
                >
                    <i class="pi pi-comment mr-1"></i>{{ selectedBooking.notes }}
                </p>

                <div class="flex flex-wrap justify-end gap-2">
                    <Button
                        v-if="nextStatus(selectedBooking.status)"
                        :label="nextStatus(selectedBooking.status).label"
                        :icon="nextStatus(selectedBooking.status).icon"
                        :severity="nextStatus(selectedBooking.status).severity"
                        size="small"
                        @click="changeStatus(selectedBooking, nextOpenStatus(selectedBooking.status))"
                    />
                    <Button label="Cerrar" text severity="secondary" size="small" @click="showDetail = false" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
