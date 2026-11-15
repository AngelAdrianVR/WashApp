<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import BookingSuccessOverlay from '@/Components/Bookings/BookingSuccessOverlay.vue';
import VehicleInspection from '@/Components/Bookings/VehicleInspection.vue';
import BookingChat from '@/Components/Bookings/BookingChat.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    booking: { type: Object, required: true },
});

const toast = useToast();
const page = usePage();

const isCancelModalVisible = ref(false);
const isNotesModalVisible = ref(false);

const celebration = ref(page.props.flash?.booking_created === props.booking.id);

const cancelForm = useForm({ reason: '' });
const notesForm = useForm({ notes: props.booking.notes || '' });

const completionForm = useForm({
    air_freshener_accepted:
        typeof props.booking.air_freshener_accepted === 'boolean' ? props.booking.air_freshener_accepted : null,
});
const ratingForm = useForm({
    rating: props.booking.review?.rating ?? 5,
    comment: props.booking.review?.comment ?? '',
});
const tipForm = useForm({
    amount: Number(props.booking.tip_amount ?? 0),
    method: props.booking.tip_method ?? 'digital',
});

const inspectionBusy = ref(false);
const isChatOpen = ref(false);

const confirmInspection = () => {
    inspectionBusy.value = true;

    router.patch(route('client.bookings.inspection.confirm', props.booking.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Inspección confirmada', detail: 'El colaborador ya puede continuar.', life: 3200 }),
        onError: () => toast.add({ severity: 'error', summary: 'No se pudo confirmar', detail: 'Inténtalo de nuevo.', life: 3200 }),
        onFinish: () => {
            inspectionBusy.value = false;
        },
    });
};

const confirmCompletion = () => {
    completionForm.patch(route('client.bookings.confirmCompletion', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: '¡Servicio confirmado!', detail: 'Gracias por confiar en WashApp.', life: 3500 }),
        onError: () => toast.add({ severity: 'error', summary: 'No se pudo confirmar', detail: 'Inténtalo de nuevo.', life: 3200 }),
    });
};

const submitRating = () => {
    ratingForm.post(route('client.bookings.review', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: '¡Gracias por calificar!', detail: 'Tu opinión suma puntos.', life: 3500 }),
        onError: () => toast.add({ severity: 'error', summary: 'No se pudo enviar', detail: 'Inténtalo de nuevo.', life: 3200 }),
    });
};

const submitTip = () => {
    tipForm.post(route('client.bookings.tip', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: '¡Gracias por tu propina!', detail: 'Tu colaborador la recibirá.', life: 3200 }),
        onError: () => toast.add({ severity: 'error', summary: 'No se pudo registrar', detail: 'Revisa el monto.', life: 3200 }),
    });
};


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
        pending: { text: 'Por asignar', class: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300', dot: 'bg-amber-500' },
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
    { key: 'pending', label: 'Por asignar' },
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

const employee = computed(() => props.booking.employee ?? null);
const chatPeer = computed(() => ({
    name: employee.value?.name ?? 'WashApp',
    avatar: employee.value?.avatar ?? null,
    role: 'Colaborador asignado',
}));
const inspection = computed(() => props.booking.inspection ?? null);
const isAwaitingAssignment = computed(() => Boolean(props.booking.is_awaiting_assignment));
const clientConfirmed = computed(() => Boolean(props.booking.client_confirmed_at));
const inspectionConfirmed = computed(() => Boolean(inspection.value?.confirmed));
const canConfirmInspection = computed(
    () => Boolean(inspection.value) && !inspectionConfirmed.value && !isCancelled.value
);
const employeeInitials = computed(() =>
    (employee.value?.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
);

const tipPresets = [20, 50, 100];

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

        <BookingSuccessOverlay
            v-if="celebration"
            :show="celebration"
            :folio="booking.folio"
            @close="celebration = false"
        />

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

            <!-- ==================== FLUJO DEL SERVICIO ==================== -->
            <section
                v-if="isAwaitingAssignment"
                class="relative overflow-hidden rounded-3xl border border-cloud-200/70 bg-gradient-to-br from-cloud-50 via-white to-sage-50/70 p-5 shadow-soft sm:p-6 dark:border-slate-700 dark:bg-gradient-to-br dark:from-slate-800 dark:via-slate-800 dark:to-slate-800"
            >
                <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-cloud-200/40 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="relative grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-cloud-600 text-white">
                        <span class="absolute inset-0 animate-ping rounded-2xl bg-cloud-400/40"></span>
                        <i class="pi pi-users relative text-2xl"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Asignación en curso</p>
                        <h2 class="mt-1 text-lg font-semibold text-mist-900 dark:text-white sm:text-xl">
                            Estamos asignando a tu colaborador
                        </h2>
                        <p class="mt-1 text-sm leading-relaxed text-mist-500 dark:text-slate-400">
                            Nuestro equipo está eligiendo al operador ideal para tu servicio. Te avisaremos por correo y en este
                            detalle en cuanto quede confirmado.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-cloud-200 bg-white/80 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-cloud-700 dark:border-slate-600 dark:bg-slate-700/60 dark:text-cloud-200">
                                <i class="pi pi-envelope text-[10px]"></i> Aviso por correo
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-sage-200 bg-white/80 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-sage-700 dark:border-slate-600 dark:bg-slate-700/60 dark:text-sage-200">
                                <i class="pi pi-bell text-[10px]"></i> Notificación en el panel
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-mist-200 bg-white/80 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-mist-600 dark:border-slate-600 dark:bg-slate-700/60 dark:text-slate-300">
                                <i class="pi pi-clock text-[10px]"></i> Suele tomar pocos minutos
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="employee"
                class="overflow-hidden rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-sage-600">Tu colaborador</p>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sage-50 px-3 py-1 text-[11px] font-semibold text-sage-700 dark:bg-sage-500/15 dark:text-sage-300">
                        <i class="pi pi-verified text-[10px]"></i> Asignación confirmada
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <img
                        v-if="employee.avatar"
                        :src="employee.avatar"
                        :alt="employee.name"
                        class="h-16 w-16 rounded-2xl object-cover"
                    />
                    <span
                        v-else
                        class="grid h-16 w-16 place-items-center rounded-2xl bg-cloud-100 text-lg font-semibold text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300"
                    >
                        {{ employeeInitials || 'WA' }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-lg font-semibold text-mist-900 dark:text-white">{{ employee.name }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-mist-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1">
                                <i class="pi pi-star-fill text-[10px] text-amber-500"></i>
                                {{ employee.rating ? employee.rating + ' / 5' : 'Nuevo en WashApp' }}
                            </span>
                            <span class="text-mist-300 dark:text-slate-600">·</span>
                            <span>{{ employee.completed_services }} servicios completados</span>
                            <span class="text-mist-300 dark:text-slate-600">·</span>
                            <span>En WashApp desde {{ employee.member_since || '—' }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a v-if="employee.phone" :href="`tel:${employee.phone}`" class="wa-btn-soft !px-4 !py-2.5">
                            <i class="pi pi-phone text-xs"></i>
                            Llamar
                        </a>
                        <button type="button" class="wa-btn-primary !px-4 !py-2.5" @click="isChatOpen = true">
                            <i class="pi pi-comments text-xs"></i>
                            Mensaje
                        </button>
                    </div>
                </div>
            </section>

            <section
                v-if="booking.status === 'on_way'"
                class="relative overflow-hidden rounded-3xl border border-cloud-300/60 bg-gradient-to-br from-cloud-700 via-cloud-600 to-cloud-800 p-6 text-white shadow-lift"
            >
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-6 h-40 w-40 rounded-full bg-sage-300/20 blur-3xl"></div>

                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cloud-100">El colaborador va en camino</p>
                        <p class="mt-1.5 text-3xl font-semibold tracking-tight sm:text-4xl">
                            {{ booking.arrival_window_label || 'Calculando llegada…' }}
                        </p>
                        <p class="mt-2 text-sm text-cloud-50/90">
                            <span v-if="employee">{{ employee.name }}</span>
                            <span v-if="booking.en_route_at"> · salió a las {{ formatTime(booking.en_route_at) }}</span>
                            <span v-if="booking.arrival_eta_minutes"> · {{ booking.arrival_eta_minutes }} min estimados</span>
                        </p>
                    </div>
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/15 backdrop-blur">
                        <i class="pi pi-car text-xl"></i>
                    </span>
                </div>

                <div class="relative mt-5 h-1.5 overflow-hidden rounded-full bg-white/20">
                    <span class="arrival-track block h-full w-1/3 rounded-full bg-white/80"></span>
                </div>
                <p class="relative mt-2 text-[11px] text-cloud-100/80">
                    La ventana de llegada considera un margen de 7 minutos sobre la hora estimada.
                </p>
            </section>

            <section
                v-if="booking.arrived_at"
                class="flex flex-wrap items-center gap-3 rounded-3xl border border-sage-200 bg-sage-50/80 px-5 py-4 dark:border-sage-500/30 dark:bg-sage-500/10"
            >
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-sage-600 text-white">
                    <i class="pi pi-map-marker text-sm"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-sage-800 dark:text-sage-200">
                        El colaborador llegó a las {{ formatTime(booking.arrived_at) }}
                    </p>
                    <p class="text-xs text-sage-700/80 dark:text-sage-300/80">
                        Hora registrada automáticamente para tu servicio.
                    </p>
                </div>
            </section>

            <section
                v-if="inspection && ['in_progress', 'completed'].includes(booking.status)"
                class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6"
            >
                <VehicleInspection
                    :markers="inspection.markers"
                    :notes="inspection.notes || ''"
                    :confirmed="inspectionConfirmed"
                    :confirmable="canConfirmInspection"
                    :registered-by="inspection.registered_by"
                    :busy="inspectionBusy"
                    @confirm="confirmInspection"
                />
            </section>

            <section
                v-if="booking.status === 'completed' && !clientConfirmed"
                class="relative overflow-hidden rounded-3xl border border-sage-300/70 bg-gradient-to-br from-sage-600 via-sage-600 to-cloud-700 p-6 text-white shadow-lift sm:p-7"
            >
                <div class="pointer-events-none absolute -right-14 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15 backdrop-blur">
                            <i class="pi pi-sparkles text-lg"></i>
                        </span>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-sage-100">Casi terminamos</p>
                            <h2 class="text-xl font-semibold sm:text-2xl">¡Tu servicio terminó!</h2>
                        </div>
                    </div>

                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-sage-50/90">
                        Revisa tu vehículo junto al colaborador y confirma que todo quedó correcto.
                        Como cortesía, tu colaborador te regalará un aromatizante para tu auto, sin costo.
                    </p>

                    <div class="mt-4 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <p class="text-sm font-semibold">¿Deseas tu aromatizante de cortesía?</p>
                        <p class="mt-1 text-xs text-sage-50/80">
                            Elige un aroma a tu gusto; el colaborador te lo entregará al momento.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                                :class="completionForm.air_freshener_accepted === true
                                    ? 'border-white bg-white text-sage-700'
                                    : 'border-white/40 bg-white/10 text-white/90 hover:bg-white/20'"
                                @click="completionForm.air_freshener_accepted = true"
                            >
                                <i class="pi pi-check mr-1 text-[10px]"></i> Sí, gracias
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                                :class="completionForm.air_freshener_accepted === false
                                    ? 'border-white bg-white text-sage-700'
                                    : 'border-white/40 bg-white/10 text-white/90 hover:bg-white/20'"
                                @click="completionForm.air_freshener_accepted = false"
                            >
                                <i class="pi pi-times mr-1 text-[10px]"></i> No, gracias
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-sage-700 shadow-soft transition hover:-translate-y-0.5 disabled:opacity-60"
                        :disabled="completionForm.processing || completionForm.air_freshener_accepted === null"
                        @click="confirmCompletion"
                    >
                        <i :class="['text-xs', completionForm.processing ? 'pi pi-spin pi-spinner' : 'pi pi-check-circle']"></i>
                        Confirmar servicio terminado
                    </button>
                </div>
            </section>

            <section
                v-if="booking.status === 'completed' && clientConfirmed"
                class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800"
            >
                <div class="relative overflow-hidden bg-gradient-to-br from-sage-600 via-sage-600 to-cloud-700 px-6 py-6 text-white">
                    <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="relative flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15 backdrop-blur">
                                <i class="pi pi-verified text-lg"></i>
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-sage-100">Servicio completado</p>
                                <p class="text-xl font-semibold">{{ booking.folio }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 text-center">
                            <div class="rounded-2xl bg-white/10 px-4 py-2 backdrop-blur">
                                <p class="text-lg font-semibold leading-tight">{{ booking.actual_duration_minutes ?? booking.duration_minutes }} min</p>
                                <p class="text-[10px] uppercase tracking-wider text-sage-100/80">Tiempo total</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 px-4 py-2 backdrop-blur">
                                <p class="text-lg font-semibold leading-tight">+{{ booking.points_awarded }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-sage-100/80">Puntos</p>
                            </div>
                            <div v-if="booking.air_freshener_accepted" class="rounded-2xl bg-white/10 px-4 py-2 backdrop-blur">
                                <p class="text-lg font-semibold leading-tight">Sí</p>
                                <p class="text-[10px] uppercase tracking-wider text-sage-100/80">Aromatizante</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                    <!-- Calificación -->
                    <div class="rounded-2xl border border-mist-100 p-4 dark:border-slate-700">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-mist-400">Tu opinión</p>

                        <template v-if="!booking.review">
                            <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                                Califica a tu colaborador y gana puntos extra.
                            </p>
                            <div class="mt-3 flex items-center gap-1">
                                <button
                                    v-for="star in 5"
                                    :key="`rating-${star}`"
                                    type="button"
                                    class="text-2xl transition hover:scale-110"
                                    :class="star <= ratingForm.rating ? 'text-amber-500' : 'text-mist-300 dark:text-slate-600'"
                                    :aria-label="`${star} estrellas`"
                                    @click="ratingForm.rating = star"
                                >
                                    <i class="pi pi-star-fill"></i>
                                </button>
                                <span class="ml-2 text-sm font-semibold text-mist-600 dark:text-slate-300">{{ ratingForm.rating }}/5</span>
                            </div>
                            <textarea
                                v-model="ratingForm.comment"
                                rows="2"
                                class="mt-3 w-full rounded-2xl border border-mist-200 bg-white/80 px-3.5 py-2.5 text-sm text-mist-700 focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                placeholder="Cuéntanos cómo fue tu experiencia (opcional, suma 5 pts extra)."
                            ></textarea>
                            <button
                                type="button"
                                class="wa-btn-primary mt-3 w-full !py-2.5"
                                :disabled="ratingForm.processing"
                                @click="submitRating"
                            >
                                <i :class="['text-xs', ratingForm.processing ? 'pi pi-spin pi-spinner' : 'pi pi-send']"></i>
                                Enviar calificación
                            </button>
                        </template>

                        <div v-else class="mt-2">
                            <div class="flex items-center gap-1">
                                <i
                                    v-for="star in 5"
                                    :key="`review-${star}`"
                                    class="pi pi-star-fill text-sm"
                                    :class="star <= booking.review.rating ? 'text-amber-500' : 'text-mist-200 dark:text-slate-600'"
                                ></i>
                                <span class="ml-2 text-sm font-semibold text-mist-700 dark:text-slate-200">{{ booking.review.rating }}/5</span>
                            </div>
                            <p v-if="booking.review.comment" class="mt-2 text-sm italic text-mist-600 dark:text-slate-300">
                                “{{ booking.review.comment }}”
                            </p>
                            <p class="mt-2 text-xs text-sage-600 dark:text-sage-300">
                                <i class="pi pi-check-circle mr-1 text-[10px]"></i> Gracias por calificar este servicio.
                            </p>
                        </div>
                    </div>

                    <!-- Propina -->
                    <div class="rounded-2xl border border-mist-100 p-4 dark:border-slate-700">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-mist-400">Propina para tu colaborador</p>

                        <template v-if="!booking.tip_amount">
                            <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                                Opcional. Puedes dejarla en digital o en efectivo.
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="preset in tipPresets"
                                    :key="`tip-${preset}`"
                                    type="button"
                                    class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition"
                                    :class="Number(tipForm.amount) === preset
                                        ? 'border-cloud-400 bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-200'
                                        : 'border-mist-200 text-mist-600 hover:border-cloud-300 dark:border-slate-600 dark:text-slate-300'"
                                    @click="tipForm.amount = preset"
                                >
                                    ${{ preset }}
                                </button>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <input
                                    v-model.number="tipForm.amount"
                                    type="number"
                                    min="0"
                                    step="10"
                                    class="rounded-2xl border border-mist-200 bg-white/80 px-3 py-2 text-sm text-mist-700 focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    placeholder="Monto"
                                />
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button
                                        type="button"
                                        class="rounded-xl border px-2 py-2 text-[11px] font-semibold transition"
                                        :class="tipForm.method === 'digital'
                                            ? 'border-cloud-400 bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-200'
                                            : 'border-mist-200 text-mist-500 dark:border-slate-600 dark:text-slate-300'"
                                        @click="tipForm.method = 'digital'"
                                    >
                                        <i class="pi pi-credit-card mr-1 text-[10px]"></i>Digital
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl border px-2 py-2 text-[11px] font-semibold transition"
                                        :class="tipForm.method === 'cash'
                                            ? 'border-sage-400 bg-sage-50 text-sage-700 dark:bg-sage-500/15 dark:text-sage-200'
                                            : 'border-mist-200 text-mist-500 dark:border-slate-600 dark:text-slate-300'"
                                        @click="tipForm.method = 'cash'"
                                    >
                                        <i class="pi pi-wallet mr-1 text-[10px]"></i>Efectivo
                                    </button>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="wa-btn-sage mt-3 w-full !py-2.5"
                                :disabled="tipForm.processing"
                                @click="submitTip"
                            >
                                <i :class="['text-xs', tipForm.processing ? 'pi pi-spin pi-spinner' : 'pi pi-heart']"></i>
                                Dejar propina
                            </button>
                        </template>

                        <div v-else class="mt-2 space-y-1">
                            <p class="text-2xl font-semibold text-mist-900 dark:text-white">
                                ${{ Number(booking.tip_amount).toFixed(2) }}
                                <span class="text-xs font-medium text-mist-400">MXN</span>
                            </p>
                            <p class="text-xs text-mist-500 dark:text-slate-400">
                                Registrada como propina {{ booking.tip_method === 'cash' ? 'en efectivo' : 'digital' }}.
                            </p>
                            <p class="text-xs text-sage-600 dark:text-sage-300">
                                <i class="pi pi-heart-fill mr-1 text-[10px]"></i> ¡Gracias por tu generosidad!
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-3">
                <!-- Columna principal -->
                <div class="min-w-0 space-y-5 lg:col-span-2">
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
                <div class="min-w-0 space-y-5">
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

        <BookingChat
            v-if="employee"
            v-model:visible="isChatOpen"
            :booking-id="booking.id"
            index-route="client.bookings.chat.index"
            store-route="client.bookings.chat.store"
            :peer="chatPeer"
        />
    </AppLayout>
</template>

<style scoped>
.arrival-track {
    animation: arrival-sweep 2.4s ease-in-out infinite;
}

@keyframes arrival-sweep {
    0% {
        transform: translateX(-120%);
    }
    55%,
    100% {
        transform: translateX(320%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .arrival-track {
        animation: none;
        width: 100%;
    }
}
</style>