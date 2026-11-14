<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    employee: { type: Object, required: true },
    period: { type: Object, required: true },
    years: { type: Array, default: () => [] },
    kpis: { type: Object, required: true },
    highlights: { type: Object, default: () => ({}) },
    series: { type: Object, required: true },
    statusBreakdown: { type: Array, default: () => [] },
    topServices: { type: Array, default: () => [] },
    ratingBreakdown: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    career: { type: Object, default: () => ({}) },
});

const page = usePage();
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'equipo');

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
};

const formatTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const goToPeriod = (date) => {
    if (!date || date === props.period.value) return;

    router.get(
        route('dashboard'),
        { period: props.period.mode, date },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const setMode = (mode) => {
    if (mode === props.period.mode) return;

    const year = props.period.value.slice(0, 4);
    const date =
        mode === 'year'
            ? year
            : `${year}-${Number(year) === new Date().getFullYear() ? String(new Date().getMonth() + 1).padStart(2, '0') : '01'}`;

    router.get(route('dashboard'), { period: mode, date }, { preserveState: true, preserveScroll: true, replace: true });
};

// ---------------------------------------------------------------- KPIs
const changeBadge = (change) => {
    if (change === null || change === undefined) return null;

    return {
        value: `${Math.abs(change)}%`,
        positive: change >= 0,
        icon: change >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down',
    };
};

const kpiCards = computed(() => [
    {
        label: 'Servicios realizados',
        value: formatNumber(props.kpis.completed),
        hint: 'en el periodo seleccionado',
        change: changeBadge(props.kpis.completed_change),
        icon: 'pi pi-check-circle',
        tone: 'sage',
    },
    {
        label: 'Ingresos generados',
        value: formatCurrency(props.kpis.revenue),
        hint: `ticket promedio ${formatCurrency(props.kpis.average_ticket)}`,
        change: changeBadge(props.kpis.revenue_change),
        icon: 'pi pi-wallet',
        tone: 'cloud',
    },
    {
        label: 'Puntuación media',
        value: props.kpis.average_rating ? props.kpis.average_rating.toFixed(1) : '—',
        hint: `${formatNumber(props.kpis.reviews_count)} opiniones`,
        change: null,
        icon: 'pi pi-star-fill',
        tone: 'sand',
        stars: props.kpis.average_rating,
    },
    {
        label: 'Horas de servicio',
        value: `${formatNumber(props.kpis.hours)} h`,
        hint:
            props.kpis.completion_rate !== null
                ? `${props.kpis.completion_rate}% de la agenda completada`
                : 'Sin agenda en el periodo',
        change: null,
        icon: 'pi pi-clock',
        tone: 'mist',
    },
]);

const toneClasses = {
    sage: 'bg-sage-50 text-sage-600 ring-sage-100 dark:bg-sage-500/15 dark:text-sage-300 dark:ring-sage-500/20',
    cloud: 'bg-cloud-50 text-cloud-600 ring-cloud-100 dark:bg-cloud-500/15 dark:text-cloud-300 dark:ring-cloud-500/20',
    sand: 'bg-amber-50 text-amber-600 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/20',
    mist: 'bg-mist-100 text-mist-600 ring-mist-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600',
};

const highlights = computed(() => [
    { label: 'Clientes atendidos', value: formatNumber(props.highlights.clients_served), icon: 'pi pi-users' },
    { label: 'Agenda del periodo', value: `${formatNumber(props.highlights.agenda_total)} citas`, icon: 'pi pi-calendar' },
    {
        label: 'Día más activo',
        value: props.highlights.busiest_day
            ? `${props.highlights.busiest_day.label} · ${props.highlights.busiest_day.count}`
            : '—',
        icon: 'pi pi-bolt',
    },
    {
        label: 'Promedio por día activo',
        value: formatNumber(props.highlights.average_per_active_day),
        icon: 'pi pi-chart-line',
    },
    {
        label: 'Servicio más solicitado',
        value: props.highlights.top_service?.name ?? '—',
        icon: 'pi pi-star',
    },
    {
        label: 'Próximos servicios',
        value: formatNumber(props.kpis.upcoming_count),
        icon: 'pi pi-arrow-right',
    },
]);

// ---------------------------------------------------------------- Gráfica
const chartData = computed(() => ({
    labels: props.series.labels,
    datasets: [
        {
            type: 'bar',
            label: 'Servicios realizados',
            data: props.series.completed,
            backgroundColor: 'rgba(63, 174, 69, 0.75)',
            hoverBackgroundColor: 'rgba(47, 143, 55, 0.9)',
            borderRadius: 8,
            maxBarThickness: 26,
            yAxisID: 'y',
        },
        {
            type: 'line',
            label: 'Ingresos (MXN)',
            data: props.series.revenue,
            borderColor: '#0b6b9b',
            backgroundColor: 'rgba(11, 107, 155, 0.12)',
            borderWidth: 2,
            tension: 0.35,
            pointRadius: 0,
            pointHoverRadius: 4,
            fill: false,
            yAxisID: 'y1',
        },
    ],
}));

const chartOptions = computed(() => ({
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
                label: (context) =>
                    context.dataset.yAxisID === 'y1'
                        ? ` ${context.dataset.label}: ${formatCurrency(context.parsed.y)}`
                        : ` ${context.dataset.label}: ${formatNumber(context.parsed.y)}`,
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
            position: 'left',
            grid: { color: 'rgba(163, 184, 200, 0.2)' },
            ticks: { color: '#a3b8c8', font: { size: 10 }, precision: 0 },
            border: { display: false },
        },
        y1: {
            beginAtZero: true,
            position: 'right',
            grid: { display: false },
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

const statusMeta = {
    pending: { classes: 'bg-amber-500' },
    confirmed: { classes: 'bg-cloud-500' },
    on_way: { classes: 'bg-indigo-500' },
    in_progress: { classes: 'bg-violet-500' },
    completed: { classes: 'bg-sage-500' },
    cancelled: { classes: 'bg-rose-500' },
};

const statusBreakdownMax = computed(() =>
    Math.max(...props.statusBreakdown.map((row) => row.count), 1)
);

const ratingMax = computed(() => Math.max(...props.ratingBreakdown.map((row) => row.count), 1));

const reviewsWithText = computed(() => props.reviews.filter((review) => review.comment));
</script>

<template>
    <Head title="Mi panel" />

    <AppLayout title="Mi panel">
        <div class="mx-auto w-full max-w-6xl space-y-6">
            <!-- Encabezado + periodo -->
            <section class="relative overflow-hidden rounded-3xl border border-cloud-500/20 bg-gradient-to-br from-cloud-600 via-cloud-700 to-sage-600 p-6 text-white shadow-lift sm:p-8">
                <div class="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-8 h-40 w-40 rounded-full bg-sage-300/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/85">
                            <i class="pi pi-briefcase text-[10px]"></i>
                            Panel operativo
                        </span>
                        <h1 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                            Hola, {{ firstName }} 👋
                        </h1>
                        <p class="mt-2 max-w-xl text-sm text-white/85">
                            Este es el resumen de tus servicios realizados y tu desempeño.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-semibold">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 backdrop-blur-sm">
                                <i class="pi pi-check-circle text-[10px]"></i>
                                {{ formatNumber(career.completed) }} servicios históricos
                            </span>
                            <span v-if="career.average_rating" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 backdrop-blur-sm">
                                <i class="pi pi-star-fill text-[10px] text-amber-300"></i>
                                {{ career.average_rating.toFixed(1) }} · {{ formatNumber(career.reviews_count) }} opiniones
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 backdrop-blur-sm">
                                <i class="pi pi-clock text-[10px]"></i>
                                {{ formatNumber(career.hours) }} h de servicio
                            </span>
                        </div>
                    </div>

                    <!-- Selector de periodo -->
                    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-white/25 bg-white/10 p-2 backdrop-blur-md">
                        <div class="inline-flex rounded-xl bg-white/15 p-1">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="period.mode === 'month' ? 'bg-white text-cloud-700 shadow-sm' : 'text-white/85 hover:text-white'"
                                @click="setMode('month')"
                            >
                                Mes
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="period.mode === 'year' ? 'bg-white text-cloud-700 shadow-sm' : 'text-white/85 hover:text-white'"
                                @click="setMode('year')"
                            >
                                Año
                            </button>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-xl text-white/80 transition hover:bg-white/15 hover:text-white"
                                aria-label="Periodo anterior"
                                @click="goToPeriod(period.prev)"
                            >
                                <i class="pi pi-chevron-left text-xs"></i>
                            </button>

                            <input
                                v-if="period.mode === 'month'"
                                type="month"
                                :value="period.value"
                                class="rounded-xl border-0 bg-white/15 px-2 py-1.5 text-xs font-semibold text-white [color-scheme:dark] focus:ring-2 focus:ring-white/40"
                                aria-label="Seleccionar mes"
                                @change="goToPeriod($event.target.value)"
                            />
                            <select
                                v-else
                                :value="period.value"
                                class="rounded-xl border-0 bg-white/15 px-2 py-1.5 text-xs font-semibold text-white focus:ring-2 focus:ring-white/40"
                                aria-label="Seleccionar año"
                                @change="goToPeriod($event.target.value)"
                            >
                                <option v-for="year in years" :key="year" :value="year" class="text-mist-800">{{ year }}</option>
                            </select>

                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-xl text-white/80 transition hover:bg-white/15 hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                                :disabled="period.is_current"
                                aria-label="Periodo siguiente"
                                @click="goToPeriod(period.next)"
                            >
                                <i class="pi pi-chevron-right text-xs"></i>
                            </button>
                        </div>

                        <p class="w-full px-1 text-[10px] uppercase tracking-[0.14em] text-white/70">
                            {{ period.label }} · {{ period.range_label }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- KPIs -->
            <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <article
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80 sm:p-5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl ring-1 ring-inset" :class="toneClasses[card.tone]">
                            <i :class="[card.icon, 'text-sm']"></i>
                        </span>
                        <span
                            v-if="card.change"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="card.change.positive
                                ? 'bg-sage-50 text-sage-700 dark:bg-sage-500/10 dark:text-sage-300'
                                : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'"
                        >
                            <i :class="card.change.icon" style="font-size: 0.55rem"></i>
                            {{ card.change.value }}
                        </span>
                    </div>

                    <p class="mt-4 truncate text-2xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-3xl">
                        {{ card.value }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-mist-600 dark:text-slate-300">{{ card.label }}</p>
                    <div class="mt-1 flex items-center gap-1">
                        <div v-if="card.stars" class="flex">
                            <i
                                v-for="star in 5"
                                :key="star"
                                class="pi pi-star-fill text-[9px]"
                                :class="star <= Math.round(card.stars) ? 'text-amber-400' : 'text-mist-200 dark:text-slate-600'"
                            ></i>
                        </div>
                        <p class="whitespace-nowrap text-[11px] text-mist-400 dark:text-slate-500">{{ card.hint }}</p>
                    </div>
                </article>
            </section>

            <!-- Métricas rápidas -->
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                <div
                    v-for="item in highlights"
                    :key="item.label"
                    class="rounded-2xl border border-mist-100 bg-white/70 px-3 py-3 dark:border-slate-800 dark:bg-gray-800/60"
                >
                    <p class="flex items-start gap-1 text-[10px] font-semibold uppercase leading-tight tracking-wide text-mist-400 dark:text-slate-500">
                        <i :class="[item.icon, 'mt-0.5 text-[10px]']"></i>
                        <span>{{ item.label }}</span>
                    </p>
                    <p class="mt-1 truncate text-sm font-semibold text-mist-800 dark:text-slate-100">{{ item.value }}</p>
                </div>
            </section>

            <!-- Gráfica + estados -->
            <section class="grid min-w-0 gap-4 xl:grid-cols-3 [&>*]:min-w-0">
                <div class="min-w-0 rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80 xl:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Actividad del periodo</h2>
                            <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">
                                Servicios realizados e ingresos · {{ period.label }}
                            </p>
                        </div>
                        <span class="rounded-full bg-mist-100 px-3 py-1 text-[11px] font-semibold text-mist-600 dark:bg-slate-700 dark:text-slate-300">
                            {{ formatNumber(kpis.completed) }} servicios
                        </span>
                    </div>
                    <div class="mt-4 h-64 w-full min-w-0 overflow-hidden">
                        <Chart type="bar" :data="chartData" :options="chartOptions" style="height: 100%" />
                    </div>
                </div>

                <div class="min-w-0 rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80">
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Estado de la agenda</h2>
                    <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">Citas programadas en el periodo.</p>

                    <ul class="mt-4 space-y-3">
                        <li v-for="row in statusBreakdown" :key="row.status">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-mist-600 dark:text-slate-300">{{ row.label }}</span>
                                <span class="font-semibold text-mist-800 dark:text-slate-100">{{ formatNumber(row.count) }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full"
                                    :class="statusMeta[row.status]?.classes ?? 'bg-mist-400'"
                                    :style="{ width: `${Math.round((row.count / statusBreakdownMax) * 100)}%` }"
                                ></div>
                            </div>
                        </li>
                    </ul>

                    <Link
                        :href="route('employee.bookings.index')"
                        class="mt-5 inline-flex items-center gap-1.5 text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300"
                    >
                        Ver mi agenda completa
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                </div>
            </section>

            <!-- Servicios top + puntuaciones -->
            <section class="grid min-w-0 gap-4 lg:grid-cols-2 [&>*]:min-w-0">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80">
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Servicios más realizados</h2>
                    <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">Distribución de tu trabajo en el periodo.</p>

                    <ul v-if="topServices.length" class="mt-4 space-y-4">
                        <li v-for="service in topServices" :key="service.name">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="truncate text-sm font-medium text-mist-700 dark:text-slate-200">{{ service.name }}</p>
                                <p class="shrink-0 text-xs font-semibold text-mist-500 dark:text-slate-400">
                                    {{ formatNumber(service.count) }} · {{ formatCurrency(service.revenue) }}
                                </p>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-gradient-to-r from-cloud-400 to-sage-400" :style="{ width: `${service.share}%` }"></div>
                            </div>
                        </li>
                    </ul>

                    <p v-else class="mt-6 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                        Aún no hay servicios finalizados en este periodo.
                    </p>
                </div>

                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Puntuaciones</h2>
                            <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">Opiniones de los servicios del periodo.</p>
                        </div>
                        <Link
                            :href="route('employee.profile.show')"
                            class="text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300"
                        >
                            Ver todas
                        </Link>
                    </div>

                    <div class="mt-4 flex items-center gap-5">
                        <div class="text-center">
                            <p class="text-4xl font-semibold text-mist-900 dark:text-white">
                                {{ kpis.average_rating ? kpis.average_rating.toFixed(1) : '—' }}
                            </p>
                            <div class="mt-1 flex justify-center">
                                <i
                                    v-for="star in 5"
                                    :key="star"
                                    class="pi pi-star-fill text-[11px]"
                                    :class="star <= Math.round(kpis.average_rating ?? 0) ? 'text-amber-400' : 'text-mist-200 dark:text-slate-600'"
                                ></i>
                            </div>
                            <p class="mt-1 text-[10px] uppercase tracking-wider text-mist-400">{{ formatNumber(kpis.reviews_count) }} opiniones</p>
                        </div>

                        <div class="flex-1 space-y-1.5">
                            <div v-for="row in ratingBreakdown" :key="row.stars" class="flex items-center gap-2">
                                <span class="w-3 text-[11px] font-semibold text-mist-500 dark:text-slate-400">{{ row.stars }}</span>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                    <div class="h-full rounded-full bg-amber-400" :style="{ width: `${(row.count / ratingMax) * 100}%` }"></div>
                                </div>
                                <span class="w-4 text-right text-[11px] text-mist-400">{{ row.count }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!kpis.reviews_count" class="mt-5 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-3 text-xs text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                        Aún no hay opiniones de los servicios de este periodo.
                    </div>

                    <div v-if="reviewsWithText.length" class="mt-5 space-y-3 border-t border-mist-100 pt-4 dark:border-slate-700">
                        <div v-for="review in reviewsWithText.slice(0, 2)" :key="review.id" class="rounded-2xl bg-mist-50/70 p-3 dark:bg-slate-800/60">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-xs font-semibold text-mist-800 dark:text-slate-100">{{ review.client_name }}</p>
                                <div class="flex">
                                    <i v-for="star in review.rating" :key="star" class="pi pi-star-fill text-[9px] text-amber-400"></i>
                                </div>
                            </div>
                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-mist-500 dark:text-slate-400">“{{ review.comment }}”</p>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                <span v-for="service in review.services" :key="service" class="rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-mist-500 dark:bg-slate-700 dark:text-slate-300">
                                    {{ service }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Próximos + últimos servicios -->
            <section class="grid min-w-0 gap-4 lg:grid-cols-2 [&>*]:min-w-0">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Próximos servicios</h2>
                            <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">Tu agenda inmediata.</p>
                        </div>
                        <Link :href="route('employee.bookings.index')" class="text-xs font-semibold text-cloud-600 hover:text-cloud-700 dark:text-cloud-300">
                            Ver agenda
                        </Link>
                    </div>

                    <ul v-if="upcoming.length" class="mt-4 space-y-2.5">
                        <li v-for="booking in upcoming" :key="booking.id">
                            <Link
                                :href="route('employee.bookings.show', booking.id)"
                                class="flex items-center gap-3 rounded-2xl border border-mist-100 bg-white/70 p-3 transition hover:border-cloud-200 hover:bg-white dark:border-slate-700 dark:bg-slate-800/60 dark:hover:bg-slate-800"
                            >
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-cloud-50 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300">
                                    <i class="pi pi-calendar text-sm"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-mist-800 dark:text-slate-100">
                                        {{ formatDate(booking.scheduled_at) }} · {{ formatTime(booking.scheduled_at) }}
                                    </span>
                                    <span class="block truncate text-xs text-mist-400">{{ booking.client_name }} · {{ booking.services.map((service) => service.name).join(' + ') }}</span>
                                </span>
                                <i class="pi pi-chevron-right text-xs text-mist-300"></i>
                            </Link>
                        </li>
                    </ul>

                    <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                        No tienes servicios próximos asignados.
                    </p>
                </div>

                <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Últimos servicios realizados</h2>
                            <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">Del más reciente al más antiguo.</p>
                        </div>
                        <Link :href="route('employee.services.index')" class="text-xs font-semibold text-cloud-600 hover:text-cloud-700 dark:text-cloud-300">
                            Ver historial
                        </Link>
                    </div>

                    <ul v-if="recent.length" class="mt-4 space-y-2.5">
                        <li v-for="booking in recent" :key="booking.id">
                            <Link
                                :href="route('employee.bookings.show', booking.id)"
                                class="flex items-center gap-3 rounded-2xl border border-mist-100 bg-white/70 p-3 transition hover:border-sage-200 hover:bg-white dark:border-slate-700 dark:bg-slate-800/60 dark:hover:bg-slate-800"
                            >
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sage-50 text-sage-600 dark:bg-sage-500/15 dark:text-sage-300">
                                    <i class="pi pi-check text-sm"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-mist-800 dark:text-slate-100">
                                        {{ booking.services.map((service) => service.name).join(' + ') }}
                                    </span>
                                    <span class="block truncate text-xs text-mist-400">
                                        {{ formatDate(booking.completed_at) }} · {{ booking.client_name }} · {{ formatCurrency(booking.total_price) }}
                                    </span>
                                </span>
                                <span v-if="booking.review" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                    <i class="pi pi-star-fill text-[8px]"></i>
                                    {{ booking.review.rating }}
                                </span>
                                <i v-else class="pi pi-chevron-right text-xs text-mist-300"></i>
                            </Link>
                        </li>
                    </ul>

                    <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                        Todavía no tienes servicios finalizados en este periodo.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
