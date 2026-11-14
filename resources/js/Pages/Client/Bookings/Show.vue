<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    booking: { type: Object, required: true },
});

const toast = useToast();

const isCancelModalVisible = ref(false);
const isNotesModalVisible = ref(false);

const cancelForm = useForm({ reason: '' });
const notesForm = useForm({ notes: props.booking.notes || '' });

const openCancelModal = () => {
    cancelForm.reset();
    isCancelModalVisible.value = true;
};

const closeCancelModal = () => {
    isCancelModalVisible.value = false;
};

const openNotesModal = () => {
    notesForm.notes = props.booking.notes || '';
    isNotesModalVisible.value = true;
};

const closeNotesModal = () => {
    isNotesModalVisible.value = false;
};

const submitCancellation = () => {
    cancelForm.patch(route('client.bookings.cancel', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCancelModal();
            toast.add({ severity: 'success', summary: 'Reserva cancelada', detail: 'Tu reserva se canceló correctamente.', life: 3200 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'No se pudo cancelar', detail: 'Revisa el motivo e inténtalo de nuevo.', life: 3200 });
        },
    });
};

const submitNotes = () => {
    notesForm.patch(route('client.bookings.updateNotes', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeNotesModal();
            toast.add({ severity: 'success', summary: 'Notas guardadas', detail: 'El operador verá tus indicaciones.', life: 3200 });
        },
    });
};

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateShort = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatTime = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const statusMeta = computed(() => {
    const statuses = {
        pending: { text: 'Pendiente', class: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300', dot: 'bg-amber-500' },
        confirmed: { text: 'Confirmada', class: 'bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300', dot: 'bg-cloud-500' },
        on_way: { text: 'En camino', class: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300', dot: 'bg-indigo-500' },
        in_progress: { text: 'En progreso', class: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300', dot: 'bg-violet-500' },
        completed: { text: 'Completada', class: 'bg-sage-50 text-sage-700 dark:bg-sage-500/15 dark:text-sage-300', dot: 'bg-sage-500' },
        cancelled: { text: 'Cancelada', class: 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', dot: 'bg-rose-500' },
    };

    return statuses[props.booking.status] ?? {
        text: 'Desconocido',
        class: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
        dot: 'bg-mist-400',
    };
});

const statusSteps = [
    { key: 'pending', label: 'Pendiente' },
    { key: 'confirmed', label: 'Confirmada' },
    { key: 'on_way', label: 'En camino' },
    { key: 'in_progress', label: 'En progreso' },
    { key: 'completed', label: 'Completada' },
];

const statusIndex = computed(() => statusSteps.findIndex((step) => step.key === props.booking.status));

const isCancelled = computed(() => props.booking.status === 'cancelled');
const canEdit = computed(() => ['pending', 'confirmed'].includes(props.booking.status));
const canCancel = computed(() => ['pending', 'confirmed'].includes(props.booking.status));
const isFinished = computed(() => ['completed', 'cancelled'].includes(props.booking.status));

const progressWidth = computed(() => {
    if (statusIndex.value <= 0) return '0%';
    return `${(statusIndex.value / (statusSteps.length - 1)) * 100}%`;
});

const addressLines = computed(() => {
    const booking = props.booking;

    if (booking.street || booking.neighborhood) {
        return [
            [booking.street, booking.street_number].filter(Boolean).join(' '),
            [booking.neighborhood, booking.postal_code ? `CP ${booking.postal_code}` : null].filter(Boolean).join(', '),
            booking.state,
        ].filter(Boolean);
    }

    return booking.address ? [booking.address] : [];
});

const vehicleLabel = computed(() => {
    return [props.booking.vehicle_brand, props.booking.vehicle_model].filter(Boolean).join(' ');
});

const hasLocation = computed(() => Boolean(props.booking.latitude && props.booking.longitude));

const mapsLink = computed(() => {
    if (hasLocation.value) {
        return `https://www.google.com/maps?q=${props.booking.latitude},${props.booking.longitude}`;
    }

    if (props.booking.address) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(props.booking.address)}`;
    }

    return null;
});

const servicesTotal = computed(() =>
    (props.booking.services ?? []).reduce((total, service) => total + Number(service.price || 0), 0)
);

const mapElement = ref(null);
let map = null;

onMounted(() => {
    if (!hasLocation.value || !mapElement.value) return;

    const icon = L.divIcon({
        className: '',
        html: '<div style="width:20px;height:20px;border-radius:9999px;background:#0b6b9b;border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,.4)"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10],
    });

    map = L.map(mapElement.value, { scrollWheelZoom: false }).setView(
        [props.booking.latitude, props.booking.longitude],
        16
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    L.marker([props.booking.latitude, props.booking.longitude], { icon })
        .addTo(map)
        .bindPopup('Ubicación del servicio');
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});
</script>

<template>
    <Head title="Detalle de la reserva" />

    <AppLayout title="Detalle de la reserva">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-5xl space-y-5">
            <!-- Encabezado -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('client.bookings.index')"
                        class="grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-300 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        aria-label="Volver a mis reservas"
                    >
                        <i class="pi pi-arrow-left text-sm"></i>
                    </Link>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">
                            Orden #{{ booking.id }}
                        </p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-2xl">
                            Detalle de la reserva
                        </h1>
                    </div>
                </div>
                <Link :href="route('client.bookings.index')" class="wa-btn-soft !px-4 !py-2.5">
                    <i class="pi pi-list text-xs"></i>
                    Mis reservas
                </Link>
            </div>

            <!-- Hero con fecha y estado -->
            <section class="overflow-hidden rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                <div class="relative overflow-hidden bg-gradient-to-br from-cloud-700 via-cloud-600 to-sage-600 px-5 py-6 text-white sm:px-7">
                    <div class="pointer-events-none absolute -right-12 -top-14 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="relative flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cloud-100">
                                Fecha programada
                            </p>
                            <p class="mt-1 text-xl font-semibold first-letter:uppercase leading-tight sm:text-2xl">
                                {{ formatDate(booking.scheduled_at) }}
                            </p>
                            <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-cloud-50/90">
                                <span><i class="pi pi-clock mr-1 text-xs"></i>{{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }}</span>
                                <span class="text-cloud-200">·</span>
                                <span>{{ booking.duration_minutes }} min</span>
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3.5 py-1.5 text-xs font-bold text-mist-800 shadow-sm">
                            <span class="h-2 w-2 rounded-full" :class="statusMeta.dot"></span>
                            {{ statusMeta.text }}
                        </span>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    <div v-if="isCancelled" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-500/30 dark:bg-rose-500/10">
                        <p class="flex items-center gap-2 text-sm font-semibold text-rose-700 dark:text-rose-300">
                            <i class="pi pi-times-circle text-sm"></i>
                            Esta reserva fue cancelada
                        </p>
                        <p v-if="booking.cancel_reason" class="mt-1 whitespace-pre-line text-xs text-rose-600/90 dark:text-rose-300/80">
                            {{ booking.cancel_reason }}
                        </p>
                    </div>

                    <div v-else class="relative">
                        <div class="absolute left-[10%] right-[10%] top-4 h-0.5 bg-mist-100 dark:bg-slate-700"></div>
                        <div
                            class="absolute left-[10%] top-4 h-0.5 rounded-full bg-gradient-to-r from-cloud-500 to-sage-500 transition-all duration-500"
                            :style="{ width: `calc(${progressWidth} * 0.8)` }"
                        ></div>

                        <ol class="relative grid grid-cols-5 gap-1">
                            <li v-for="(step, index) in statusSteps" :key="step.key" class="flex flex-col items-center gap-1.5 text-center">
                                <span
                                    class="grid h-8 w-8 place-items-center rounded-full border-2 text-[11px] font-bold transition sm:h-9 sm:w-9"
                                    :class="index <= statusIndex
                                        ? 'border-cloud-600 bg-cloud-600 text-white shadow-soft'
                                        : 'border-mist-200 bg-white text-mist-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-500'"
                                >
                                    <i v-if="index < statusIndex" class="pi pi-check text-[10px]"></i>
                                    <span v-else>{{ index + 1 }}</span>
                                </span>
                                <span
                                    class="text-[9px] font-semibold leading-tight sm:text-[10px]"
                                    :class="index <= statusIndex ? 'text-mist-700 dark:text-slate-200' : 'text-mist-400'"
                                >
                                    {{ step.label }}
                                </span>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-3">
                <!-- Columna principal -->
                <div class="space-y-5 lg:col-span-2">
                    <!-- Datos del servicio -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Resumen del servicio</h2>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Fecha y hora</p>
                                <p class="mt-1 text-sm font-semibold first-letter:uppercase text-mist-800 dark:text-slate-100">
                                    {{ formatDate(booking.scheduled_at) }}
                                </p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Duración estimada</p>
                                <p class="mt-1 text-sm font-semibold text-mist-800 dark:text-slate-100">{{ booking.duration_minutes }} minutos</p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    Pago: {{ booking.payment_method || 'No especificado' }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Operador</p>
                                <p class="mt-1 flex items-center gap-2 text-sm font-semibold text-mist-800 dark:text-slate-100">
                                    <img
                                        v-if="booking.employee_avatar"
                                        :src="booking.employee_avatar"
                                        :alt="booking.employee_name"
                                        class="h-6 w-6 rounded-full object-cover"
                                    />
                                    <i v-else class="pi pi-user text-xs text-mist-400"></i>
                                    {{ booking.employee_name || 'Por asignar' }}
                                </p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    {{ booking.employee_name ? 'Identidad verificada' : 'Se asigna antes del servicio' }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Reservada el</p>
                                <p class="mt-1 text-sm font-semibold text-mist-800 dark:text-slate-100">{{ formatDateShort(booking.created_at) }}</p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    {{ isFinished ? 'Orden finalizada' : 'Orden activa' }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Servicios -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Servicios contratados</h2>
                            <span class="rounded-full bg-mist-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-mist-500 dark:bg-slate-700 dark:text-slate-300">
                                {{ booking.services.length }} servicio{{ booking.services.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <ul class="mt-4 divide-y divide-mist-100 dark:divide-slate-700">
                            <li
                                v-for="service in booking.services"
                                :key="service.id"
                                class="flex items-center justify-between gap-3 py-3"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-cloud-50 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300">
                                        <i class="pi pi-sparkles text-xs"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-mist-800 dark:text-slate-100">{{ service.name }}</p>
                                        <p class="text-[11px] text-mist-400">{{ service.duration_minutes }} min</p>
                                    </div>
                                </div>
                                <span class="whitespace-nowrap text-sm font-semibold text-mist-900 dark:text-white">
                                    {{ formatCurrency(service.price) }}
                                </span>
                            </li>
                        </ul>

                        <div class="mt-4 space-y-2 border-t border-mist-100 pt-4 text-sm dark:border-slate-700">
                            <div class="flex items-center justify-between text-xs text-mist-500 dark:text-slate-400">
                                <span>Subtotal servicios</span>
                                <span>{{ formatCurrency(servicesTotal) }}</span>
                            </div>
                            <div class="flex items-end justify-between">
                                <span class="font-semibold text-mist-700 dark:text-slate-200">Total</span>
                                <span class="text-2xl font-semibold text-mist-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</span>
                            </div>
                            <p class="text-right text-[10px] uppercase tracking-wider text-mist-400">MXN</p>
                        </div>
                    </section>

                    <!-- Notas -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Notas para el operador</h2>
                            <button
                                type="button"
                                class="text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300"
                                @click="openNotesModal"
                            >
                                {{ booking.notes ? 'Editar' : 'Agregar' }}
                            </button>
                        </div>
                        <p v-if="booking.notes" class="mt-3 whitespace-pre-line rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                            {{ booking.notes }}
                        </p>
                        <p v-else class="mt-3 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 px-4 py-3 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            No hay notas adicionales para esta reserva.
                        </p>
                    </section>
                </div>

                <!-- Columna lateral -->
                <div class="space-y-5">
                    <!-- Dirección -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Dirección del servicio</h2>
                        <div class="mt-3 space-y-0.5 text-sm text-mist-600 dark:text-slate-300">
                            <p v-for="line in addressLines" :key="line">{{ line }}</p>
                            <p v-if="!addressLines.length" class="text-mist-400">Sin dirección registrada</p>
                        </div>

                        <div v-if="hasLocation" class="mt-3 overflow-hidden rounded-2xl border border-mist-200 dark:border-slate-700">
                            <div ref="mapElement" class="h-52 w-full" style="z-index: 0"></div>
                        </div>
                        <div v-else class="mt-3 grid h-32 place-items-center rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 text-center dark:border-slate-600 dark:bg-slate-800/60">
                            <div>
                                <i class="pi pi-map text-xl text-mist-300"></i>
                                <p class="mt-1 text-[11px] text-mist-400">Sin ubicación exacta en el mapa</p>
                            </div>
                        </div>

                        <a
                            v-if="mapsLink"
                            :href="mapsLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-mist-200 px-4 py-2.5 text-xs font-semibold text-mist-600 transition hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:text-slate-300"
                        >
                            <i class="pi pi-external-link text-[11px]"></i>
                            Abrir en Google Maps
                        </a>
                    </section>

                    <!-- Vehículo -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Vehículo</h2>
                        <div class="mt-3 flex items-center gap-3 rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-cloud-100 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300">
                                <i class="pi pi-car text-base"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold text-mist-800 dark:text-slate-100">
                                    {{ vehicleLabel || 'No especificado' }}
                                </p>
                                <p class="text-[11px] text-mist-400">
                                    {{ vehicleLabel ? 'Vehículo del servicio' : 'Se confirmará con el operador' }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Acciones -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Acciones</h2>

                        <div class="mt-3 space-y-2">
                            <Link
                                v-if="canEdit"
                                :href="route('client.bookings.edit', booking.id)"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl border border-mist-200 px-4 py-2.5 text-sm font-semibold text-mist-700 transition hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:text-slate-200"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                                Editar reserva
                            </Link>
                            <button
                                type="button"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl border border-mist-200 px-4 py-2.5 text-sm font-semibold text-mist-700 transition hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:text-slate-200"
                                @click="openNotesModal"
                            >
                                <i class="pi pi-file-edit text-xs"></i>
                                {{ booking.notes ? 'Editar notas' : 'Agregar notas' }}
                            </button>
                            <button
                                v-if="canCancel"
                                type="button"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                @click="openCancelModal"
                            >
                                <i class="pi pi-times-circle text-xs"></i>
                                Cancelar reserva
                            </button>
                            <p v-else class="pt-1 text-center text-[11px] leading-relaxed text-mist-400">
                                {{ isFinished
                                    ? 'Esta reserva ya está finalizada.'
                                    : 'La cancelación está disponible solo para reservas pendientes o confirmadas.' }}
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Modal cancelación -->
        <Dialog v-model:visible="isCancelModalVisible" modal header="Cancelar reserva" :style="{ width: '90vw', maxWidth: '480px' }">
            <div class="space-y-4">
                <p class="text-sm text-mist-600 dark:text-slate-300">
                    ¿Seguro que quieres cancelar tu reserva del
                    <span class="font-semibold first-letter:uppercase text-mist-900 dark:text-white">{{ formatDate(booking.scheduled_at) }}</span>?
                    Indícanos el motivo.
                </p>
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-slate-400">Motivo *</label>
                    <Textarea v-model="cancelForm.reason" rows="4" class="w-full" :invalid="!!cancelForm.errors.reason" />
                    <small v-if="cancelForm.errors.reason" class="text-xs font-medium text-rose-600">{{ cancelForm.errors.reason }}</small>
                </div>
            </div>
            <template #footer>
                <button type="button" class="wa-btn-soft !px-4 !py-2.5" @click="closeCancelModal">Volver</button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-60"
                    :disabled="cancelForm.processing"
                    @click="submitCancellation"
                >
                    <i :class="['text-xs', cancelForm.processing ? 'pi pi-spin pi-spinner' : 'pi pi-check']"></i>
                    Confirmar cancelación
                </button>
            </template>
        </Dialog>

        <!-- Modal notas -->
        <Dialog v-model:visible="isNotesModalVisible" modal header="Notas para el operador" :style="{ width: '90vw', maxWidth: '480px' }">
            <div class="space-y-3">
                <p class="text-sm text-mist-600 dark:text-slate-300">
                    Agrega cualquier detalle útil: color del coche, timbre, acceso al estacionamiento, etc.
                </p>
                <Textarea
                    v-model="notesForm.notes"
                    rows="5"
                    class="w-full"
                    placeholder="Ej. El portón es negro, tocar el timbre 2."
                    :invalid="!!notesForm.errors.notes"
                />
                <small v-if="notesForm.errors.notes" class="text-xs font-medium text-rose-600">{{ notesForm.errors.notes }}</small>
            </div>
            <template #footer>
                <button type="button" class="wa-btn-soft !px-4 !py-2.5" @click="closeNotesModal">Cancelar</button>
                <button
                    type="button"
                    class="wa-btn-primary !px-4 !py-2.5"
                    :disabled="notesForm.processing"
                    @click="submitNotes"
                >
                    <i :class="['text-xs', notesForm.processing ? 'pi pi-spin pi-spinner' : 'pi pi-save']"></i>
                    Guardar notas
                </button>
            </template>
        </Dialog>
    </AppLayout>
</template>
