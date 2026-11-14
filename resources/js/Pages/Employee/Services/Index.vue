<script setup>
import { computed, reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';
import debounce from 'lodash.debounce';

const props = defineProps({
    bookings: { type: Object, required: true },
    filters: { type: Object, default: () => ({ month: null, year: null, search: null }) },
    summary: { type: Object, default: () => ({}) },
    monthOptions: { type: Array, default: () => [] },
    yearOptions: { type: Array, default: () => [] },
});

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
};

const summaryCards = computed(() => [
    { label: 'Servicios realizados', value: formatNumber(props.summary.total), icon: 'pi pi-check-circle', tone: 'sage' },
    { label: 'Ingresos históricos', value: formatCurrency(props.summary.revenue), icon: 'pi pi-wallet', tone: 'cloud' },
    { label: 'Horas de servicio', value: `${formatNumber(props.summary.hours)} h`, icon: 'pi pi-clock', tone: 'mist' },
    {
        label: 'Puntuación media',
        value: props.summary.average_rating ? props.summary.average_rating.toFixed(1) : '—',
        icon: 'pi pi-star-fill',
        tone: 'sand',
        hint: `${formatNumber(props.summary.reviews_count)} opiniones`,
    },
    { label: 'Este mes', value: formatNumber(props.summary.this_month), icon: 'pi pi-calendar', tone: 'cloud' },
]);

const toneClasses = {
    sage: 'bg-sage-50 text-sage-600 ring-sage-100 dark:bg-sage-500/15 dark:text-sage-300 dark:ring-sage-500/20',
    cloud: 'bg-cloud-50 text-cloud-600 ring-cloud-100 dark:bg-cloud-500/15 dark:text-cloud-300 dark:ring-cloud-500/20',
    sand: 'bg-amber-50 text-amber-600 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/20',
    mist: 'bg-mist-100 text-mist-600 ring-mist-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600',
};

// ---------------------------------------------------------------- Filtros
const filterState = reactive({
    month: props.filters.month ?? null,
    year: props.filters.year ?? null,
    search: props.filters.search ?? '',
});

const applyFilters = () => {
    router.get(
        route('employee.services.index'),
        {
            month: filterState.month || undefined,
            year: filterState.year || undefined,
            search: filterState.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const clearFilters = () => {
    filterState.month = null;
    filterState.year = null;
    filterState.search = '';
    applyFilters();
};

watch(
    () => filterState.search,
    debounce(() => applyFilters(), 450)
);

const setYear = (value) => {
    filterState.year = value;
    filterState.month = null;
    applyFilters();
};

const setMonth = (value) => {
    filterState.month = value;
    applyFilters();
};

const hasFilters = computed(() => !!filterState.month || !!filterState.year || !!filterState.search);
</script>

<template>
    <Head title="Servicios realizados" />

    <AppLayout title="Servicios realizados">
        <div class="mx-auto w-full max-w-6xl space-y-6">
            <!-- Encabezado -->
            <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Historial de trabajo
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-3xl">
                        Servicios realizados
                    </h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Todos los servicios que has completado, del más reciente al más antiguo.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('employee.bookings.index')" class="wa-btn-soft !px-4 !py-2.5">
                        <i class="pi pi-calendar text-xs"></i>
                        Mi agenda
                    </Link>
                    <Link :href="route('dashboard')" class="wa-btn-primary !px-4 !py-2.5">
                        <i class="pi pi-chart-bar text-xs"></i>
                        Ver panel
                    </Link>
                </div>
            </section>

            <!-- Resumen -->
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl ring-1 ring-inset" :class="toneClasses[card.tone]">
                            <i :class="[card.icon, 'text-xs']"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase leading-tight tracking-[0.12em] text-mist-400">{{ card.label }}</p>
                            <p class="truncate text-lg font-semibold text-mist-900 dark:text-white">{{ card.value }}</p>
                        </div>
                    </div>
                    <p v-if="card.hint" class="mt-2 text-[10px] text-mist-400">{{ card.hint }}</p>
                </div>
            </section>

            <!-- Filtros -->
            <section class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80 sm:p-5">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[10rem]">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Mes</label>
                        <Select
                            :modelValue="filterState.month"
                            :options="monthOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todos los meses"
                            showClear
                            filter
                            size="small"
                            class="w-full"
                            @update:modelValue="setMonth"
                        />
                    </div>
                    <div class="min-w-[8rem]">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Año</label>
                        <Select
                            :modelValue="filterState.year"
                            :options="yearOptions"
                            placeholder="Todos"
                            showClear
                            size="small"
                            class="w-full"
                            @update:modelValue="setYear"
                        />
                    </div>
                    <div class="min-w-[12rem] flex-1">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Buscar</label>
                        <input
                            v-model="filterState.search"
                            type="search"
                            placeholder="Cliente, servicio o dirección"
                            class="w-full rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        />
                    </div>
                    <Button
                        v-if="hasFilters"
                        label="Limpiar"
                        icon="pi pi-times"
                        size="small"
                        text
                        severity="secondary"
                        @click="clearFilters"
                    />
                </div>
            </section>

            <!-- Listado -->
            <section class="rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80">
                <div v-if="bookings.data.length" class="divide-y divide-mist-100 dark:divide-slate-700">
                    <article
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        class="flex flex-col gap-4 p-5 transition hover:bg-mist-50/60 dark:hover:bg-slate-800/40 sm:flex-row sm:items-center"
                    >
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-sage-50 text-sage-600 dark:bg-sage-500/15 dark:text-sage-300">
                            <i class="pi pi-check text-base"></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-mist-900 dark:text-white">
                                    {{ booking.services.map((service) => service.name).join(' + ') || 'Servicio WashApp' }}
                                </p>
                                <span v-if="booking.review" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                    <i class="pi pi-star-fill text-[8px]"></i>
                                    {{ booking.review.rating }}/5
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-mist-400">
                                {{ formatDate(booking.completed_at) }} · {{ booking.duration_minutes }} min
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-mist-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="pi pi-user text-[10px]"></i>
                                    {{ booking.client_name }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="pi pi-map-marker text-[10px]"></i>
                                    <span class="truncate">{{ booking.address || 'Sin dirección' }}</span>
                                </span>
                                <span v-if="booking.vehicle" class="inline-flex items-center gap-1.5">
                                    <i class="pi pi-car text-[10px]"></i>
                                    {{ booking.vehicle }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                            <p class="text-base font-semibold text-mist-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</p>
                            <Link
                                :href="route('employee.bookings.show', booking.id)"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-mist-200 px-3 py-1.5 text-xs font-semibold text-mist-600 transition hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:text-slate-300"
                            >
                                <i class="pi pi-eye text-[11px]"></i>
                                Ver detalle
                            </Link>
                        </div>
                    </article>
                </div>

                <div v-else class="px-6 py-16 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-slate-700 dark:text-slate-400">
                        <i class="pi pi-inbox text-lg"></i>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-mist-700 dark:text-slate-200">No hay servicios realizados</p>
                    <p class="mt-1 text-xs text-mist-500 dark:text-slate-400">
                        {{ hasFilters ? 'Prueba con otros filtros o limpia la búsqueda.' : 'Cuando finalices un servicio aparecerá aquí.' }}
                    </p>
                </div>

                <!-- Paginación -->
                <div v-if="bookings.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-mist-100 px-5 py-4 dark:border-slate-700">
                    <p class="text-xs text-mist-400">
                        Mostrando {{ bookings.from }}–{{ bookings.to }} de {{ bookings.total }} servicios
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, index) in bookings.links" :key="index">
                            <span
                                v-if="link.url === null"
                                class="grid h-8 min-w-8 place-items-center rounded-lg border border-mist-100 px-2 text-xs text-mist-300 dark:border-slate-700 dark:text-slate-600"
                                v-html="link.label"
                            ></span>
                            <Link
                                v-else
                                :href="link.url"
                                preserve-scroll
                                class="grid h-8 min-w-8 place-items-center rounded-lg border px-2 text-xs font-semibold transition"
                                :class="link.active
                                    ? 'border-cloud-600 bg-cloud-600 text-white'
                                    : 'border-mist-200 bg-white text-mist-500 hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
