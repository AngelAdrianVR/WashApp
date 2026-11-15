<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue
import Button from 'primevue/button';
import Select from 'primevue/select';

const props = defineProps({
    filters: { type: Object, required: true },
    summary: { type: Object, required: true },
    timeseries: { type: Object, required: true },
    topServices: { type: Array, default: () => [] },
    paymentBreakdown: { type: Array, default: () => [] },
    topEmployees: { type: Array, default: () => [] },
    topClients: { type: Array, default: () => [] },
});

const refreshing = ref(false);

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(`${value}T00:00:00`).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatChange = (change) => (change === null || change === undefined ? null : Math.abs(change));

const changeClasses = (change, invert = false) => {
    const positive = invert ? change <= 0 : change >= 0;

    return positive
        ? 'bg-sage-50 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300'
        : 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300';
};

// ---------------------------------------------------------------- Filtros
const filter = reactive({
    from: props.filters.from,
    to: props.filters.to,
    granularity: props.filters.granularity,
});

const today = () => new Date().toISOString().slice(0, 10);
const startOfMonth = () => {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
};
const startOfYear = () => `${new Date().getFullYear()}-01-01`;
const daysAgo = (days) => {
    const date = new Date();
    date.setDate(date.getDate() - days);
    return date.toISOString().slice(0, 10);
};

const presets = computed(() => [
    { key: 'today', label: 'Hoy', from: today(), to: today() },
    { key: 'week', label: '7 días', from: daysAgo(6), to: today() },
    { key: 'month30', label: '30 días', from: daysAgo(29), to: today() },
    { key: 'month', label: 'Este mes', from: startOfMonth(), to: today() },
    { key: 'year', label: 'Este año', from: startOfYear(), to: today() },
]);

const isPresetActive = (preset) => filter.from === preset.from && filter.to === preset.to;

const apply = (overrides = {}) => {
    router.get(
        route('admin.sales.index'),
        { ...filter, ...overrides },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (refreshing.value = true),
            onFinish: () => (refreshing.value = false),
        }
    );
};

const applyPreset = (preset) => {
    filter.from = preset.from;
    filter.to = preset.to;
    filter.granularity = 'auto';
    apply({ from: preset.from, to: preset.to, granularity: 'auto' });
};

watch(
    () => props.filters,
    (value) => {
        filter.from = value.from;
        filter.to = value.to;
        filter.granularity = value.granularity;
    },
    { deep: true }
);

// ---------------------------------------------------------------- KPIs
const kpiCards = computed(() => [
    {
        label: 'Ingresos del periodo',
        value: formatCurrency(props.summary.revenue),
        change: props.summary.revenueChange,
        hint: `${formatCurrency(props.summary.previousRevenue)} en el periodo anterior`,
        classes: 'bg-cloud-50 text-cloud-600 dark:bg-cloud-900/40 dark:text-cloud-300',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 0c-1.11 0-2.08-.402-2.599-1M12 4V3m0 18v-1',
    },
    {
        label: 'Gastos del periodo',
        value: formatCurrency(props.summary.expenses),
        change: props.summary.expensesChange,
        invertChange: true,
        hint: `${formatCurrency(props.summary.previousExpenses)} en el periodo anterior`,
        classes: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    },
    {
        label: 'Utilidad neta',
        value: formatCurrency(props.summary.netProfit),
        change: props.summary.netProfitChange,
        hint: props.summary.netMargin !== null ? `Margen del ${props.summary.netMargin}%` : 'Aún no hay ingresos en el periodo',
        classes: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
        icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    },
    {
        label: 'Servicios finalizados',
        value: formatNumber(props.summary.completedServices),
        change: props.summary.completedChange,
        hint: `${formatNumber(props.summary.previousCompletedServices)} en el periodo anterior`,
        classes: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        label: 'Ticket promedio',
        value: formatCurrency(props.summary.averageTicket),
        change: null,
        hint: `${formatNumber(props.summary.completedServices)} servicios cobrados`,
        classes: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
        icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
    },
    {
        label: 'Por facturar',
        value: formatCurrency(props.summary.pendingRevenue),
        change: null,
        hint: `${formatNumber(props.summary.pendingCount)} citas abiertas · ${formatNumber(props.summary.cancelledCount)} canceladas`,
        classes: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
]);

// ---------------------------------------------------------------- Gráficas
const salesChartData = computed(() => ({
    labels: props.timeseries.labels,
    datasets: [
        {
            type: 'bar',
            label: 'Ingresos',
            data: props.timeseries.revenue,
            backgroundColor: 'rgba(11, 107, 155, 0.8)',
            hoverBackgroundColor: 'rgba(11, 107, 155, 1)',
            borderRadius: 6,
            maxBarThickness: 26,
            order: 3,
        },
        {
            type: 'bar',
            label: 'Gastos',
            data: props.timeseries.expenses,
            backgroundColor: 'rgba(244, 63, 94, 0.7)',
            hoverBackgroundColor: 'rgba(244, 63, 94, 1)',
            borderRadius: 6,
            maxBarThickness: 26,
            order: 2,
        },
        {
            type: 'line',
            label: 'Utilidad neta',
            data: props.timeseries.profit,
            borderColor: '#3fae45',
            backgroundColor: 'rgba(63, 174, 69, 0.12)',
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 0,
            pointHoverRadius: 5,
            fill: false,
            order: 1,
        },
    ],
}));

const salesChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, color: '#8098ac', font: { size: 11 } },
        },
        tooltip: {
            backgroundColor: 'rgba(58, 68, 78, 0.94)',
            padding: 12,
            cornerRadius: 12,
            callbacks: { label: (context) => ` ${context.dataset.label}: ${formatCurrency(context.parsed.y)}` },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#a3b8c8', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 14 },
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

const topServicesMax = computed(() => Math.max(...props.topServices.map((service) => service.revenue), 1));
const paymentTotal = computed(() => props.paymentBreakdown.reduce((total, row) => total + row.revenue, 0));
const topEmployeesMax = computed(() => Math.max(...props.topEmployees.map((employee) => employee.revenue), 1));
</script>

<template>
    <Head title="Análisis de ventas" />

    <AppLayout title="Análisis de ventas">
        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Finanzas · WashApp
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">Análisis de ventas</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        {{ formatDate(filters.from) }} – {{ formatDate(filters.to) }} · Ingresos, gastos y utilidad del periodo.
                    </p>
                </div>

                <div class="flex flex-wrap gap-1 rounded-2xl bg-mist-50 p-1 dark:bg-slate-700/40">
                    <button
                        v-for="preset in presets"
                        :key="preset.key"
                        type="button"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                        :class="
                            isPresetActive(preset)
                                ? 'bg-white text-mist-900 shadow-sm dark:bg-slate-800 dark:text-white'
                                : 'text-mist-500 hover:text-mist-800 dark:text-slate-400 dark:hover:text-white'
                        "
                        @click="applyPreset(preset)"
                    >
                        {{ preset.label }}
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-end gap-3 rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Desde</label>
                    <input
                        v-model="filter.from"
                        type="date"
                        class="rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Hasta</label>
                    <input
                        v-model="filter.to"
                        type="date"
                        class="rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>
                <div class="min-w-[11rem]">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Agrupar por</label>
                    <Select
                        v-model="filter.granularity"
                        :options="[
                            { label: 'Automático', value: 'auto' },
                            { label: 'Por día', value: 'day' },
                            { label: 'Por semana', value: 'week' },
                            { label: 'Por mes', value: 'month' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        size="small"
                        class="w-full"
                    />
                </div>
                <Button label="Aplicar" icon="pi pi-filter" size="small" :loading="refreshing" @click="apply()" />
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mist-400 dark:text-slate-500">
                                {{ card.label }}
                            </p>
                            <p class="mt-2 truncate text-3xl font-semibold text-mist-900 dark:text-white">{{ card.value }}</p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl" :class="card.classes">
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
                            {{ formatChange(card.change) }}%
                        </span>
                        <span class="text-mist-400 dark:text-slate-500">{{ card.hint }}</span>
                    </div>
                </div>
            </div>

            <!-- Gráfica -->
            <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Ingresos, gastos y utilidad</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">Evolución del periodo seleccionado</p>
                    </div>
                    <div class="flex gap-4 text-right text-xs">
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-mist-400">Utilidad</p>
                            <p class="font-semibold" :class="summary.netProfit >= 0 ? 'text-sage-600 dark:text-sage-300' : 'text-rose-600 dark:text-rose-300'">
                                {{ formatCurrency(summary.netProfit) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-mist-400">Margen</p>
                            <p class="font-semibold text-mist-700 dark:text-slate-200">
                                {{ summary.netMargin !== null ? `${summary.netMargin}%` : '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4" style="height: 340px">
                    <Chart type="bar" :data="salesChartData" :options="salesChartOptions" style="height: 100%" />
                </div>

                <p v-if="summary.revenue === 0 && summary.expenses === 0" class="mt-2 text-center text-xs text-mist-400 dark:text-slate-500">
                    Sin movimientos en este periodo.
                </p>
            </div>

            <!-- Servicios + pagos -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90 xl:col-span-2">
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Servicios más vendidos</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Por ingresos en el periodo</p>

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
                                    :style="{ width: `${Math.max((service.revenue / topServicesMax) * 100, 4)}%` }"
                                ></div>
                            </div>
                            <p class="mt-1 text-[11px] text-mist-400 dark:text-slate-500">{{ service.services_count }} vendidos</p>
                        </div>
                    </div>

                    <p v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">Sin servicios vendidos en el periodo.</p>
                </div>

                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Métodos de pago</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Distribución de ingresos</p>

                    <ul v-if="paymentBreakdown.length" class="mt-5 space-y-3 text-xs">
                        <li v-for="row in paymentBreakdown" :key="row.method">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-mist-600 dark:text-slate-300">
                                    <span class="h-2 w-2 rounded-full bg-cloud-400"></span>
                                    {{ row.method }}
                                    <span class="text-mist-400">({{ row.bookings_count }})</span>
                                </span>
                                <span class="font-semibold text-mist-700 dark:text-slate-200">{{ formatCurrency(row.revenue) }}</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-cloud-400"
                                    :style="{ width: `${paymentTotal ? Math.max((row.revenue / paymentTotal) * 100, 3) : 0}%` }"
                                ></div>
                            </div>
                        </li>
                    </ul>

                    <p v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">Sin pagos registrados.</p>
                </div>
            </div>

            <!-- Empleados + clientes -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Desempeño por empleado</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Servicios finalizados en el periodo</p>

                    <div v-if="topEmployees.length" class="mt-4 space-y-3">
                        <div
                            v-for="employee in topEmployees"
                            :key="employee.employee_id"
                            class="flex items-center justify-between gap-3 rounded-xl border border-mist-100 px-3 py-2 dark:border-slate-700"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-mist-800 dark:text-slate-100">{{ employee.employee_name }}</p>
                                <p class="text-[11px] text-mist-400 dark:text-slate-500">{{ employee.services_count }} servicios</p>
                            </div>
                            <div class="w-24 shrink-0">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                    <div
                                        class="h-full rounded-full bg-sage-400"
                                        :style="{ width: `${Math.max((employee.revenue / topEmployeesMax) * 100, 4)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <span class="whitespace-nowrap text-sm font-semibold text-mist-800 dark:text-slate-100">
                                {{ formatCurrency(employee.revenue) }}
                            </span>
                        </div>
                    </div>

                    <p v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">Sin servicios finalizados.</p>
                </div>

                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Mejores clientes</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Mayor consumo en el periodo</p>

                    <div v-if="topClients.length" class="mt-4 divide-y divide-mist-100 dark:divide-slate-700">
                        <div
                            v-for="client in topClients"
                            :key="client.client_name"
                            class="flex items-center justify-between gap-3 py-2 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-mist-800 dark:text-slate-100">{{ client.client_name }}</p>
                                <p class="text-[11px] text-mist-400 dark:text-slate-500">{{ client.services_count }} servicios</p>
                            </div>
                            <span class="whitespace-nowrap font-semibold text-mist-800 dark:text-slate-100">
                                {{ formatCurrency(client.revenue) }}
                            </span>
                        </div>
                    </div>

                    <p v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">Sin clientes en el periodo.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
