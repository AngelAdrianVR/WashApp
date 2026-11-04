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
    topPages: { type: Array, default: () => [] },
    devices: { type: Array, default: () => [] },
    referrers: { type: Array, default: () => [] },
    generatedAt: { type: String, default: null },
});

const refreshing = ref(false);

const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(`${value}T00:00:00`);
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatChange = (change) => (change === null || change === undefined ? null : Math.abs(change));
const changeClasses = (change) =>
    change >= 0
        ? 'bg-sage-50 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300'
        : 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-300';

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
        route('admin.analytics.index'),
        { ...filter, ...overrides },
        { preserveState: true, preserveScroll: true, replace: true, onStart: () => (refreshing.value = true), onFinish: () => (refreshing.value = false) }
    );
};

const applyPreset = (preset) => {
    // Cada preset deja que el servidor elija la mejor agrupación para el rango.
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
        label: 'Vistas totales',
        value: formatNumber(props.summary.views),
        change: props.summary.viewsChange,
        hint: `${formatNumber(props.summary.previousViews)} en el periodo anterior`,
        classes: 'bg-cloud-50 text-cloud-600 dark:bg-cloud-900/40 dark:text-cloud-300',
        icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
    },
    {
        label: 'Visitantes únicos',
        value: formatNumber(props.summary.visitors),
        change: props.summary.visitorsChange,
        hint: `${formatNumber(props.summary.previousVisitors)} en el periodo anterior`,
        classes: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        label: 'Vistas por visitante',
        value: Number(props.summary.viewsPerVisitor).toFixed(2),
        change: null,
        hint: `${formatNumber(props.summary.pages)} páginas distintas`,
        classes: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
        icon: 'M8 13v-1m4 1v-3m4 3V8M8 21l4-4 4 4M4 4h16a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z',
    },
    {
        label: 'Día con más vistas',
        value: props.summary.busiestDay ? formatDate(props.summary.busiestDay) : 'Sin datos',
        change: null,
        hint: `${formatNumber(props.summary.busiestDayViews)} vistas`,
        classes: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
]);

// ---------------------------------------------------------------- Gráficas
const trafficChartData = computed(() => ({
    labels: props.timeseries.labels,
    datasets: [
        {
            type: 'bar',
            label: 'Vistas',
            data: props.timeseries.views,
            backgroundColor: 'rgba(11, 107, 155, 0.75)',
            hoverBackgroundColor: 'rgba(11, 107, 155, 1)',
            borderRadius: 6,
            maxBarThickness: 26,
            order: 2,
        },
        {
            type: 'line',
            label: 'Visitantes únicos',
            data: props.timeseries.visitors,
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

const trafficChartOptions = computed(() => ({
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
            callbacks: { label: (context) => ` ${context.dataset.label}: ${formatNumber(context.parsed.y)}` },
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
            ticks: { color: '#a3b8c8', font: { size: 10 }, precision: 0 },
            border: { display: false },
        },
    },
}));

const deviceColors = ['#0b6b9b', '#3fae45', '#f59e0b', '#a3b8c8'];

const deviceChartData = computed(() => ({
    labels: props.devices.map((device) => device.device),
    datasets: [
        {
            data: props.devices.map((device) => device.views),
            backgroundColor: props.devices.map((_, index) => deviceColors[index % deviceColors.length]),
            borderWidth: 0,
            hoverOffset: 6,
        },
    ],
}));

const deviceChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(58, 68, 78, 0.94)',
            padding: 10,
            cornerRadius: 10,
            callbacks: { label: (context) => ` ${context.label}: ${formatNumber(context.parsed)}` },
        },
    },
}));

const deviceLabel = (device) => ({ desktop: 'Escritorio', mobile: 'Móvil', tablet: 'Tablet' })[device] ?? device;
</script>

<template>
    <Head title="Analítica de visitas" />

    <AppLayout title="Analítica de visitas">
        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Tráfico del sitio
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">Analítica de visitas</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        {{ formatDate(filters.from) }} – {{ formatDate(filters.to) }}
                        <span v-if="generatedAt" class="text-mist-400 dark:text-slate-500">
                            · Actualizado {{ new Date(generatedAt).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) }}
                        </span>
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

            <!-- Filtros personalizados -->
            <div
                class="flex flex-wrap items-end gap-3 rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
            >
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                            :class="changeClasses(card.change)"
                        >
                            <i class="pi" :class="card.change >= 0 ? 'pi-arrow-up' : 'pi-arrow-down'" style="font-size: 0.6rem"></i>
                            {{ formatChange(card.change) }}%
                        </span>
                        <span class="text-mist-400 dark:text-slate-500">{{ card.hint }}</span>
                    </div>
                </div>
            </div>

            <!-- Gráfica principal -->
            <div
                class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Vistas y visitantes</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">Evolución del tráfico en el periodo seleccionado</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wide text-mist-400">Total</p>
                        <p class="text-sm font-semibold text-cloud-600 dark:text-cloud-300">{{ formatNumber(summary.views) }} vistas</p>
                    </div>
                </div>

                <div class="mt-4" style="height: 320px">
                    <Chart type="bar" :data="trafficChartData" :options="trafficChartOptions" style="height: 100%" />
                </div>

                <p v-if="summary.views === 0" class="mt-2 text-center text-xs text-mist-400 dark:text-slate-500">
                    Aún no hay visitas registradas en este periodo. Las visitas de clientes se registran automáticamente.
                </p>
            </div>

            <!-- Páginas + dispositivos + referencias -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90 xl:col-span-2"
                >
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Páginas más visitadas</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Top 10 de rutas en el periodo</p>

                    <div v-if="topPages.length" class="mt-5 space-y-4">
                        <div v-for="page in topPages" :key="page.path">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-mist-700 dark:text-slate-200">{{ page.path }}</span>
                                <span class="whitespace-nowrap text-xs font-semibold text-mist-500 dark:text-slate-300">
                                    {{ formatNumber(page.views) }} vistas · {{ formatNumber(page.visitors) }} visitantes
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-cloud-500 to-sage-400"
                                    :style="{ width: `${Math.max(page.percentage, 3)}%` }"
                                ></div>
                            </div>
                            <p class="mt-1 text-[11px] text-mist-400 dark:text-slate-500">{{ page.percentage }}% del total</p>
                        </div>
                    </div>

                    <div v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">
                        <i class="pi pi-chart-bar mb-2 block text-2xl"></i>
                        Sin páginas registradas en este rango.
                    </div>
                </div>

                <div class="space-y-4">
                    <div
                        class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
                    >
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Dispositivos</h2>

                        <div v-if="devices.length" class="mt-4 grid grid-cols-1 items-center gap-3 sm:grid-cols-2">
                            <div style="height: 150px">
                                <Chart type="doughnut" :data="deviceChartData" :options="deviceChartOptions" style="height: 100%" />
                            </div>
                            <ul class="space-y-2 text-xs">
                                <li v-for="(device, index) in devices" :key="device.device" class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-mist-600 dark:text-slate-300">
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: deviceColors[index % deviceColors.length] }"
                                        ></span>
                                        {{ deviceLabel(device.device) }}
                                    </span>
                                    <span class="font-semibold text-mist-700 dark:text-slate-200">{{ device.percentage }}%</span>
                                </li>
                            </ul>
                        </div>
                        <p v-else class="mt-6 text-center text-xs text-mist-400 dark:text-slate-500">Sin datos de dispositivos.</p>
                    </div>

                    <div
                        class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
                    >
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Origen del tráfico</h2>

                        <ul v-if="referrers.length" class="mt-4 space-y-3 text-xs">
                            <li v-for="source in referrers" :key="source.source">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-mist-600 dark:text-slate-300">{{ source.source }}</span>
                                    <span class="whitespace-nowrap font-semibold text-mist-700 dark:text-slate-200">
                                        {{ formatNumber(source.views) }} · {{ source.percentage }}%
                                    </span>
                                </div>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                    <div class="h-full rounded-full bg-cloud-400" :style="{ width: `${Math.max(source.percentage, 3)}%` }"></div>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-6 text-center text-xs text-mist-400 dark:text-slate-500">Sin referencias externas.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
