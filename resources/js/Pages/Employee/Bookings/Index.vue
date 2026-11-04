<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BookingDetail from '@/Components/Bookings/BookingDetail.vue';
import debounce from 'lodash.debounce';

// PrimeVue
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    bookings: { type: Object, required: true },
    filters: { type: Object, required: true },
    summary: { type: Object, required: true },
});

const toast = useToast();

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

const statusMeta = (status) =>
    ({
        pending: { label: 'Pendiente', classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', dot: 'bg-amber-500' },
        confirmed: { label: 'Confirmada', classes: 'bg-cloud-100 text-cloud-700 dark:bg-cloud-900/40 dark:text-cloud-300', dot: 'bg-cloud-500' },
        on_way: { label: 'En camino', classes: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', dot: 'bg-indigo-500' },
        in_progress: { label: 'En servicio', classes: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300', dot: 'bg-sage-500' },
        completed: { label: 'Finalizada', classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', dot: 'bg-emerald-500' },
        cancelled: { label: 'Cancelada', classes: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', dot: 'bg-red-500' },
    })[status] ?? { label: status, classes: 'bg-mist-100 text-mist-600', dot: 'bg-mist-400' };

const nextStatus = (status) =>
    ({
        pending: { value: 'confirmed', label: 'Confirmar', icon: 'pi pi-check', severity: 'info' },
        confirmed: { value: 'on_way', label: 'En camino', icon: 'pi pi-send', severity: 'info' },
        on_way: { value: 'in_progress', label: 'Iniciar', icon: 'pi pi-play', severity: 'warn' },
        in_progress: { value: 'completed', label: 'Finalizar', icon: 'pi pi-check-circle', severity: 'success' },
    })[status] ?? null;

const statusOptions = [
    { label: 'Pendiente', value: 'pending' },
    { label: 'Confirmada', value: 'confirmed' },
    { label: 'En camino', value: 'on_way' },
    { label: 'En servicio', value: 'in_progress' },
    { label: 'Finalizada', value: 'completed' },
    { label: 'Cancelada', value: 'cancelled' },
];

// ---------------------------------------------------------------- Filtros
const filterForm = reactive({
    status: props.filters.status,
    from: props.filters.from,
    to: props.filters.to,
    search: props.filters.search,
});

const applyFilters = () => {
    router.get(
        route('employee.bookings.index'),
        {
            status: filterForm.status || undefined,
            from: filterForm.from || undefined,
            to: filterForm.to || undefined,
            search: filterForm.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const resetFilters = () => {
    filterForm.status = null;
    filterForm.from = null;
    filterForm.to = null;
    filterForm.search = '';
    applyFilters();
};

watch(
    () => filterForm.search,
    debounce(() => applyFilters(), 450)
);

// ---------------------------------------------------------------- Acciones
const changeStatus = (booking, status) => {
    router.patch(
        route('employee.bookings.updateStatus', booking.id),
        { status },
        {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Servicio actualizado', detail: statusMeta(status).label, life: 2500 }),
            onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el servicio.', life: 3500 }),
        }
    );
};

const showDetail = ref(false);
const selectedBooking = ref(null);

const openDetail = (booking) => {
    selectedBooking.value = booking;
    showDetail.value = true;
};
</script>

<template>
    <Head title="Mis servicios" />

    <AppLayout title="Mis servicios">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                    Operación · WashApp
                </p>
                <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">Mis servicios</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                    Todos los servicios que tienes asignados, con su estado y detalles.
                </p>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Asignados</p>
                    <p class="mt-1 text-2xl font-semibold text-mist-900 dark:text-white">{{ formatNumber(summary.assigned) }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Por realizar</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-300">{{ formatNumber(summary.open) }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Hoy</p>
                    <p class="mt-1 text-2xl font-semibold text-cloud-600 dark:text-cloud-300">{{ formatNumber(summary.today) }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Completados</p>
                    <p class="mt-1 text-2xl font-semibold text-sage-600 dark:text-sage-300">{{ formatNumber(summary.completed) }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Generado</p>
                    <p class="mt-1 truncate text-2xl font-semibold text-mist-900 dark:text-white">{{ formatCurrency(summary.earnings) }}</p>
                </div>
            </div>

            <!-- Listado -->
            <div class="rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                <div class="flex flex-col gap-3 border-b border-mist-100 px-5 py-4 dark:border-slate-700">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Listado de servicios asignados</h2>
                            <p class="text-xs text-mist-400 dark:text-slate-500">Filtra por estado, fecha o cliente.</p>
                        </div>
                        <Button label="Limpiar filtros" icon="pi pi-times" size="small" text severity="secondary" @click="resetFilters" />
                    </div>

                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-[9.5rem]">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Estado</label>
                            <Select
                                v-model="filterForm.status"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todos"
                                showClear
                                size="small"
                                class="w-full"
                                @change="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Desde</label>
                            <input
                                v-model="filterForm.from"
                                type="date"
                                class="rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Hasta</label>
                            <input
                                v-model="filterForm.to"
                                type="date"
                                class="rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div class="min-w-[12rem] flex-1">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Buscar</label>
                            <input
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Cliente o dirección"
                                class="w-full rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <Button label="Aplicar" icon="pi pi-filter" size="small" @click="applyFilters" />
                    </div>
                </div>

                <div v-if="bookings.data.length">
                    <div class="hidden overflow-x-auto xl:block">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-mist-50/60 text-[11px] uppercase tracking-wide text-mist-400 dark:bg-slate-800/60 dark:text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Cita</th>
                                    <th class="px-4 py-3 font-semibold">Cliente</th>
                                    <th class="px-4 py-3 font-semibold">Servicios</th>
                                    <th class="px-4 py-3 font-semibold">Estado</th>
                                    <th class="px-4 py-3 text-right font-semibold">Total</th>
                                    <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-100 dark:divide-slate-700">
                                <tr
                                    v-for="booking in bookings.data"
                                    :key="booking.id"
                                    class="transition hover:bg-mist-50/60 dark:hover:bg-slate-800/40"
                                >
                                    <td class="whitespace-nowrap px-4 py-3.5 align-top">
                                        <p class="font-semibold text-mist-800 dark:text-slate-100">{{ formatDate(booking.scheduled_at) }}</p>
                                        <p class="text-xs text-mist-400 dark:text-slate-500">
                                            {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <p class="font-medium text-mist-800 dark:text-slate-100">{{ booking.client_name }}</p>
                                        <p class="text-xs text-mist-400 dark:text-slate-500">{{ booking.client_phone || 'Sin teléfono' }}</p>
                                    </td>
                                    <td class="max-w-[15rem] px-4 py-3.5 align-top">
                                        <p class="truncate text-mist-600 dark:text-slate-300">
                                            {{ booking.services.map((service) => service.name).join(' + ') }}
                                        </p>
                                        <p class="text-xs text-mist-400 dark:text-slate-500">{{ booking.duration_minutes }} min</p>
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                            :class="statusMeta(booking.status).classes"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta(booking.status).dot"></span>
                                            {{ statusMeta(booking.status).label }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right align-top font-semibold text-mist-800 dark:text-slate-100">
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
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div class="divide-y divide-mist-100 xl:hidden dark:divide-slate-700">
                        <div v-for="booking in bookings.data" :key="booking.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">{{ booking.client_name }}</p>
                                    <p class="text-xs text-mist-400 dark:text-slate-500">
                                        {{ formatDate(booking.scheduled_at) }} · {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }}
                                    </p>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusMeta(booking.status).classes">
                                    {{ statusMeta(booking.status).label }}
                                </span>
                            </div>

                            <p class="mt-2 text-xs text-mist-500 dark:text-slate-400">
                                {{ booking.services.map((service) => service.name).join(' + ') }}
                            </p>
                            <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">{{ booking.address }}</p>
                            <p class="mt-2 font-semibold text-mist-800 dark:text-slate-100">{{ formatCurrency(booking.total_price) }}</p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <Button label="Detalle" icon="pi pi-eye" text size="small" severity="secondary" @click="openDetail(booking)" />
                                <Button
                                    v-if="nextStatus(booking.status)"
                                    :label="nextStatus(booking.status).label"
                                    :icon="nextStatus(booking.status).icon"
                                    :severity="nextStatus(booking.status).severity"
                                    size="small"
                                    @click="changeStatus(booking, nextStatus(booking.status).value)"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="bookings.total > 0" class="border-t border-mist-100 px-5 py-4 dark:border-slate-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-mist-500 dark:text-slate-400">
                                Mostrando de <span class="font-medium">{{ bookings.from }}</span> a
                                <span class="font-medium">{{ bookings.to }}</span> de
                                <span class="font-medium">{{ bookings.total }}</span> servicios
                            </p>
                            <div v-if="bookings.links.length > 3" class="flex flex-wrap gap-1">
                                <template v-for="(link, key) in bookings.links" :key="key">
                                    <span
                                        v-if="link.url === null"
                                        class="rounded-lg bg-mist-100 px-3 py-1.5 text-xs text-mist-400 dark:bg-slate-700 dark:text-slate-500"
                                        v-html="link.label"
                                    />
                                    <Link
                                        v-else
                                        class="rounded-lg border px-3 py-1.5 text-xs transition"
                                        :class="
                                            link.active
                                                ? 'border-cloud-600 bg-cloud-600 text-white'
                                                : 'border-mist-200 bg-white text-mist-600 hover:bg-mist-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                        "
                                        :href="link.url"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="px-5 py-16 text-center text-sm text-mist-400 dark:text-slate-500">
                    <i class="pi pi-inbox mb-2 block text-2xl"></i>
                    No tienes servicios asignados con estos filtros.
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showDetail" modal header="Detalle del servicio" :style="{ width: '34rem' }" :breakpoints="{ '640px': '96vw' }">
            <BookingDetail
                v-if="selectedBooking"
                :booking="selectedBooking"
                can-manage
                status-route="employee.bookings.updateStatus"
                @changed="showDetail = false"
            />
            <div class="mt-4 flex justify-end">
                <Button label="Cerrar" text severity="secondary" size="small" @click="showDetail = false" />
            </div>
        </Dialog>
    </AppLayout>
</template>