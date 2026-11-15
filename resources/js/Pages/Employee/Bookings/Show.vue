<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import VehicleInspection from '@/Components/Bookings/VehicleInspection.vue';
import VehicleCard from '@/Components/Bookings/VehicleCard.vue';
import BookingChat from '@/Components/Bookings/BookingChat.vue';

const props = defineProps({
    booking: { type: Object, required: true },
});

const toast = useToast();

const booking = computed(() => props.booking);
const client = computed(() => props.booking.client ?? {});
const inspection = computed(() => props.booking.inspection ?? null);
const employeeInitials = computed(() =>
    (client.value?.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
);

const isRouteDialogOpen = ref(false);
const etaMinutes = ref(20);
const etaPresets = [5, 10, 15, 20, 30, 45, 60];
const inspectionBusy = ref(false);
const statusBusy = ref(false);
const isChatOpen = ref(false);

const isCancelDialogOpen = ref(false);
const cancelStep = ref(1);
const cancelReason = ref('');
const cancelBusy = ref(false);
const cancelError = ref(null);

const chatPeer = computed(() => ({
    name: booking.value.client_name,
    avatar: client.value.avatar ?? null,
    role: client.value.is_guest ? 'Cliente invitado' : 'Cliente',
}));

const statusMeta = computed(
    () =>
        ({
            pending: { label: 'Por asignar', classes: 'bg-amber-100 text-amber-700' },
            confirmed: { label: 'Confirmada', classes: 'bg-cloud-100 text-cloud-700' },
            on_way: { label: 'En camino', classes: 'bg-indigo-100 text-indigo-700' },
            in_progress: { label: 'En progreso', classes: 'bg-violet-100 text-violet-700' },
            completed: { label: 'Finalizada', classes: 'bg-sage-100 text-sage-700' },
            cancelled: { label: 'Cancelada', classes: 'bg-rose-100 text-rose-700' },
        })[props.booking.status] ?? { label: props.booking.status, classes: 'bg-mist-100 text-mist-600' }
);

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
};

const formatTime = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(Number(value || 0));

const mapsLink = computed(() => {
    if (booking.value.latitude && booking.value.longitude) {
        return `https://www.google.com/maps?q=${booking.value.latitude},${booking.value.longitude}`;
    }

    return booking.value.address
        ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(booking.value.address)}`
        : null;
});

const updateStatus = (status, extra = {}) => {
    statusBusy.value = true;

    router.patch(route('employee.bookings.updateStatus', booking.value.id), { status, ...extra }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Servicio actualizado', detail: 'El cliente fue notificado.', life: 2800 }),
        onError: (errors) => toast.add({
            severity: 'error',
            summary: 'No se pudo actualizar',
            detail: Object.values(errors ?? {})[0] || 'Revisa los datos e inténtalo de nuevo.',
            life: 3600,
        }),
        onFinish: () => {
            statusBusy.value = false;
            isRouteDialogOpen.value = false;
        },
    });
};

const startRoute = () => {
    if (!etaMinutes.value || etaMinutes.value < 1) return;
    updateStatus('on_way', { eta_minutes: Number(etaMinutes.value) });
};

const markArrived = () => updateStatus('in_progress');
const markCompleted = () => updateStatus('completed');

const saveInspection = (payload) => {
    inspectionBusy.value = true;

    router.post(route('employee.bookings.inspection.store', booking.value.id), payload, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Inspección guardada', detail: 'El cliente debe confirmarla.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: 'Inténtalo de nuevo.', life: 3200 }),
        onFinish: () => {
            inspectionBusy.value = false;
        },
    });
};

const inspectionConfirmed = computed(() => Boolean(client.value.is_guest) || !inspection.value || inspection.value.confirmed);

const now = ref(Date.now());
let clockTimer = null;

onMounted(() => {
    clockTimer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onBeforeUnmount(() => {
    if (clockTimer) clearInterval(clockTimer);
});

const completionAvailableAt = computed(() =>
    booking.value.completion_available_at ? new Date(booking.value.completion_available_at).getTime() : null
);

const lockRemainingMs = computed(() =>
    completionAvailableAt.value ? Math.max(0, completionAvailableAt.value - now.value) : 0
);

const lockActive = computed(() => lockRemainingMs.value > 0);

const lockCountdown = computed(() => {
    const totalSeconds = Math.ceil(lockRemainingMs.value / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${String(seconds).padStart(2, '0')}`;
});

const canComplete = computed(() => inspectionConfirmed.value && !lockActive.value);

const completionBlockReason = computed(() => {
    if (!inspectionConfirmed.value) {
        return 'Esperando la confirmación de la inspección por parte del cliente.';
    }

    if (lockActive.value) {
        return `Podrás finalizar el servicio en ${lockCountdown.value} (bloqueo de ${booking.value.completion_lock_minutes ?? 15} min desde tu llegada).`;
    }

    return null;
});

const openCancelDialog = () => {
    cancelReason.value = '';
    cancelStep.value = 1;
    cancelError.value = null;
    isCancelDialogOpen.value = true;
};

const requestCancel = () => {
    if (cancelReason.value.trim().length < 5) {
        cancelError.value = 'Escribe el motivo de la cancelación (mínimo 5 caracteres).';
        return;
    }

    cancelError.value = null;
    cancelStep.value = 2;
};

const confirmCancel = () => {
    cancelBusy.value = true;

    router.patch(
        route('employee.bookings.updateStatus', booking.value.id),
        { status: 'cancelled', cancel_reason: cancelReason.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                isCancelDialogOpen.value = false;
                toast.add({ severity: 'warn', summary: 'Servicio cancelado', detail: 'El cliente fue notificado con el motivo.', life: 3200 });
            },
            onError: (errors) => {
                cancelStep.value = 1;
                cancelError.value = Object.values(errors ?? {})[0] || 'No se pudo cancelar el servicio.';
            },
            onFinish: () => {
                cancelBusy.value = false;
            },
        }
    );
};
</script>

<template>
    <Head title="Detalle del servicio" />

    <AppLayout title="Detalle del servicio">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-5xl space-y-5">
            <!-- Encabezado -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('employee.bookings.index')"
                        class="grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-300 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        aria-label="Volver a mis servicios"
                    >
                        <i class="pi pi-arrow-left text-sm"></i>
                    </Link>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">{{ booking.folio }}</p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-2xl">
                            Servicio #{{ booking.id }}
                        </h1>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold" :class="statusMeta.classes">
                    {{ statusMeta.label }}
                </span>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <!-- Columna principal -->
                <div class="min-w-0 space-y-5 lg:col-span-2">
                    <!-- Cliente -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="client.avatar"
                                    :src="client.avatar"
                                    :alt="client.name"
                                    class="h-12 w-12 rounded-2xl object-cover"
                                />
                                <span
                                    v-else
                                    class="grid h-12 w-12 place-items-center rounded-2xl bg-cloud-100 text-sm font-semibold text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300"
                                >
                                    {{ employeeInitials || 'CL' }}
                                </span>
                                <div>
                                    <p class="text-base font-semibold text-mist-900 dark:text-white">{{ booking.client_name }}</p>
                                    <p class="text-xs text-mist-500 dark:text-slate-400">
                                        {{ booking.client_phone || 'Sin teléfono' }}
                                        <span v-if="client.is_guest" class="ml-1 rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-mist-500">Invitado</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a v-if="booking.client_phone" :href="`tel:${booking.client_phone}`" class="wa-btn-soft !px-4 !py-2.5">
                                    <i class="pi pi-phone text-xs"></i> Llamar
                                </a>
                                <button type="button" class="wa-btn-primary !px-4 !py-2.5" @click="isChatOpen = true">
                                    <i class="pi pi-comments text-xs"></i> Mensaje
                                </button>
                                <a
                                    v-if="mapsLink"
                                    :href="mapsLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="wa-btn-soft !px-4 !py-2.5"
                                >
                                    <i class="pi pi-map-marker text-xs"></i> Ruta
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Fecha y hora</p>
                                <p class="mt-1 text-sm font-semibold capitalize text-mist-800 dark:text-slate-100">
                                    {{ formatDate(booking.scheduled_at) }}
                                </p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }} · {{ booking.duration_minutes }} min
                                </p>
                            </div>
                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Dirección</p>
                                <p class="mt-1 text-sm text-mist-700 dark:text-slate-200">{{ booking.address }}</p>
                            </div>
                        </div>

                        <p v-if="booking.notes" class="mt-3 rounded-2xl bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="pi pi-comment mr-1 text-[10px]"></i>{{ booking.notes }}
                        </p>
                    </section>

                    <!-- Vehículo -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <VehicleCard
                            :car="booking.car"
                            :fallback-label="[booking.vehicle_brand, booking.vehicle_model].filter(Boolean).join(' ')"
                        />
                    </section>

                    <!-- Inspección -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <VehicleInspection
                            :markers="inspection?.markers ?? []"
                            :notes="inspection?.notes ?? ''"
                            :editable="['on_way', 'in_progress'].includes(booking.status)"
                            :confirmed="Boolean(inspection?.confirmed)"
                            :registered-by="inspection?.registered_by"
                            :busy="inspectionBusy"
                            @save="saveInspection"
                        />

                        <p v-if="inspection && !inspection.confirmed" class="mt-3 rounded-2xl bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="pi pi-exclamation-circle mr-1 text-[10px]"></i>
                            El cliente debe confirmar la inspección desde su panel antes de finalizar el servicio.
                        </p>
                    </section>

                    <!-- Servicios -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Servicios contratados</h2>
                        <ul class="mt-3 divide-y divide-mist-100 dark:divide-slate-700">
                            <li v-for="service in booking.services" :key="service.id" class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-mist-800 dark:text-slate-100">{{ service.name }}</p>
                                    <p class="text-[11px] text-mist-400">{{ service.duration_minutes }} min</p>
                                </div>
                                <span class="text-sm font-semibold text-mist-900 dark:text-white">{{ formatCurrency(service.price) }}</span>
                            </li>
                        </ul>
                        <div class="mt-3 flex items-center justify-between border-t border-mist-100 pt-3 dark:border-slate-700">
                            <span class="text-sm font-semibold text-mist-700 dark:text-slate-200">Total</span>
                            <span class="text-xl font-semibold text-mist-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</span>
                        </div>
                    </section>
                </div>

                <!-- Panel de acciones -->
                <div class="min-w-0 space-y-5">
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Siguiente paso</h2>

                        <template v-if="booking.status === 'confirmed'">
                            <p class="mt-2 text-sm text-mist-500 dark:text-slate-400">
                                Cuando salgas hacia el domicilio, avisa al cliente con tu tiempo estimado de llegada.
                            </p>
                            <button type="button" class="wa-btn-primary mt-4 w-full" @click="isRouteDialogOpen = true">
                                <i class="pi pi-send text-xs"></i>
                                Salir hacia el domicilio
                            </button>
                        </template>

                        <template v-else-if="booking.status === 'on_way'">
                            <div class="mt-2 rounded-2xl bg-cloud-50 px-4 py-3 dark:bg-cloud-500/10">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-cloud-600 dark:text-cloud-200">Llegada estimada</p>
                                <p class="mt-1 text-lg font-semibold text-cloud-800 dark:text-cloud-100">{{ booking.arrival_window_label || '—' }}</p>
                                <p class="text-xs text-cloud-600/80 dark:text-cloud-200/80">
                                    Salió a las {{ formatTime(booking.en_route_at) }} · {{ booking.arrival_eta_minutes }} min
                                </p>
                            </div>
                            <button type="button" class="wa-btn-sage mt-4 w-full" :disabled="statusBusy" @click="markArrived">
                                <i :class="['text-xs', statusBusy ? 'pi pi-spin pi-spinner' : 'pi pi-map-marker']"></i>
                                Llegué al domicilio
                            </button>
                        </template>

                        <template v-else-if="booking.status === 'in_progress'">
                            <div class="mt-2 rounded-2xl bg-sage-50 px-4 py-3 dark:bg-sage-500/10">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-sage-600 dark:text-sage-200">Llegada registrada</p>
                                <p class="mt-1 text-lg font-semibold text-sage-800 dark:text-sage-100">{{ formatTime(booking.arrived_at) }}</p>
                                <p class="text-xs text-sage-600/80 dark:text-sage-200/80">El cliente ya fue notificado.</p>
                            </div>
                            <button
                                type="button"
                                class="wa-btn-primary mt-4 w-full"
                                :disabled="statusBusy || !canComplete"
                                :title="completionBlockReason || ''"
                                @click="markCompleted"
                            >
                                <i :class="['text-xs', statusBusy ? 'pi pi-spin pi-spinner' : lockActive ? 'pi pi-lock' : 'pi pi-check-circle']"></i>
                                {{ lockActive ? `Finalizar en ${lockCountdown}` : 'Finalizar servicio' }}
                            </button>
                            <p v-if="completionBlockReason" class="mt-2 text-[11px] leading-relaxed text-amber-600 dark:text-amber-300">
                                <i class="pi pi-info-circle mr-1 text-[10px]"></i>{{ completionBlockReason }}
                            </p>
                        </template>

                        <template v-else-if="booking.status === 'completed'">
                            <p class="mt-2 text-sm text-mist-500 dark:text-slate-400">
                                Servicio finalizado el {{ formatTime(booking.completed_at) }}.
                                <span v-if="!booking.client_confirmed_at"> Esperando la confirmación del cliente.</span>
                                <span v-else> El cliente confirmó el servicio.</span>
                            </p>
                            <div v-if="booking.review" class="mt-3 rounded-2xl bg-amber-50 px-4 py-3 dark:bg-amber-500/10">
                                <div class="flex items-center gap-1">
                                    <i
                                        v-for="star in 5"
                                        :key="`star-${star}`"
                                        class="pi pi-star-fill text-xs"
                                        :class="star <= booking.review.rating ? 'text-amber-500' : 'text-amber-200 dark:text-slate-600'"
                                    ></i>
                                    <span class="ml-1 text-xs font-semibold text-amber-700 dark:text-amber-200">{{ booking.review.rating }}/5</span>
                                </div>
                                <p v-if="booking.review.comment" class="mt-1 text-xs italic text-amber-800 dark:text-amber-200">“{{ booking.review.comment }}”</p>
                            </div>
                            <div v-if="booking.tip_amount" class="mt-3 rounded-2xl bg-sage-50 px-4 py-3 text-sm text-sage-800 dark:bg-sage-500/10 dark:text-sage-200">
                                <i class="pi pi-heart-fill mr-1 text-xs"></i>
                                Propina recibida: {{ formatCurrency(booking.tip_amount) }}
                                ({{ booking.tip_method === 'cash' ? 'efectivo' : 'digital' }})
                            </div>
                        </template>

                        <template v-else>
                            <p class="mt-2 text-sm text-mist-500 dark:text-slate-400">Este servicio no tiene acciones disponibles.</p>
                        </template>

                        <button
                            v-if="['on_way', 'in_progress'].includes(booking.status)"
                            type="button"
                            class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10"
                            @click="openCancelDialog"
                        >
                            <i class="pi pi-times-circle text-xs"></i>
                            Cancelar servicio
                        </button>
                    </section>

                    <!-- Resumen de tiempos -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Tiempos</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-mist-400">Salida</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ formatTime(booking.en_route_at) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-mist-400">Llegada</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ formatTime(booking.arrived_at) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-mist-400">Finalización</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ formatTime(booking.completed_at) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-mist-100 pt-2 dark:border-slate-700">
                                <dt class="text-mist-500">Duración real</dt>
                                <dd class="font-semibold text-mist-900 dark:text-white">
                                    {{ booking.actual_duration_minutes ? booking.actual_duration_minutes + ' min' : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>

        <!-- Dialog: tiempo estimado de llegada -->
        <Dialog v-model:visible="isRouteDialogOpen" modal header="Tiempo estimado de llegada" :style="{ width: '92vw', maxWidth: '440px' }">
            <div class="space-y-4">
                <p class="text-sm text-mist-600 dark:text-slate-300">
                    Indica en cuántos minutos llegarás. El sistema avisará al cliente con una ventana de 7 minutos.
                </p>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in etaPresets"
                        :key="`eta-${preset}`"
                        type="button"
                        class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition"
                        :class="etaMinutes === preset
                            ? 'border-cloud-400 bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-200'
                            : 'border-mist-200 text-mist-600 hover:border-cloud-300 dark:border-slate-600 dark:text-slate-300'"
                        @click="etaMinutes = preset"
                    >
                        {{ preset }} min
                    </button>
                </div>

                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-mist-400">Minutos personalizados</label>
                    <input
                        v-model.number="etaMinutes"
                        type="number"
                        min="1"
                        max="240"
                        class="w-full rounded-2xl border border-mist-200 bg-white/80 px-3.5 py-2.5 text-sm text-mist-700 focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>

                <div class="rounded-2xl bg-mist-50 px-4 py-3 text-xs text-mist-600 dark:bg-slate-700/40 dark:text-slate-300">
                    Hora estimada: <span class="font-semibold">{{ etaMinutes || 0 }} min</span> ·
                    ventana de llegada de {{ etaMinutes || 0 }} a {{ Number(etaMinutes || 0) + 7 }} min.
                </div>
            </div>

            <template #footer>
                <button type="button" class="wa-btn-soft !px-4 !py-2.5" @click="isRouteDialogOpen = false">Cancelar</button>
                <button type="button" class="wa-btn-primary !px-4 !py-2.5" :disabled="statusBusy" @click="startRoute">
                    <i :class="['text-xs', statusBusy ? 'pi pi-spin pi-spinner' : 'pi pi-send']"></i>
                    Avisar al cliente
                </button>
            </template>
        </Dialog>

        <!-- Dialog: cancelar servicio (motivo obligatorio + confirmación) -->
        <Dialog
            v-model:visible="isCancelDialogOpen"
            modal
            :header="cancelStep === 1 ? 'Cancelar servicio' : 'Confirma la cancelación'"
            :style="{ width: '92vw', maxWidth: '460px' }"
        >
            <div v-if="cancelStep === 1" class="space-y-3">
                <p class="text-sm text-mist-600 dark:text-slate-300">
                    Cuéntanos por qué no se puede realizar el servicio. El cliente recibirá el motivo por correo y en su panel.
                </p>
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-mist-400">Motivo de la cancelación</label>
                    <textarea
                        v-model="cancelReason"
                        rows="3"
                        maxlength="255"
                        class="w-full rounded-2xl border border-mist-200 bg-white/80 px-3.5 py-2.5 text-sm text-mist-700 focus:border-rose-300 focus:ring-2 focus:ring-rose-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Ej. El vehículo no estaba en el domicilio."
                    ></textarea>
                </div>
                <p v-if="cancelError" class="rounded-xl bg-rose-50 px-3 py-2 text-[11px] font-medium text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                    {{ cancelError }}
                </p>
            </div>

            <div v-else class="space-y-3">
                <div class="flex items-center gap-3 rounded-2xl bg-rose-50 px-4 py-3 dark:bg-rose-500/10">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-rose-600 text-white">
                        <i class="pi pi-exclamation-triangle text-sm"></i>
                    </span>
                    <p class="text-sm font-semibold text-rose-700 dark:text-rose-200">
                        Esta acción cancela el servicio y notifica al cliente.
                    </p>
                </div>
                <p class="rounded-2xl bg-mist-50 px-4 py-3 text-xs text-mist-600 dark:bg-slate-700/40 dark:text-slate-300">
                    <span class="font-semibold">Motivo:</span> {{ cancelReason }}
                </p>
                <p v-if="cancelError" class="rounded-xl bg-rose-50 px-3 py-2 text-[11px] font-medium text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                    {{ cancelError }}
                </p>
            </div>

            <template #footer>
                <button type="button" class="wa-btn-soft !px-4 !py-2.5" @click="isCancelDialogOpen = false">Volver</button>
                <button
                    v-if="cancelStep === 1"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700"
                    @click="requestCancel"
                >
                    Continuar
                    <i class="pi pi-arrow-right text-xs"></i>
                </button>
                <button
                    v-else
                    type="button"
                    class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-60"
                    :disabled="cancelBusy"
                    @click="confirmCancel"
                >
                    <i :class="['text-xs', cancelBusy ? 'pi pi-spin pi-spinner' : 'pi pi-check']"></i>
                    Confirmar cancelación
                </button>
            </template>
        </Dialog>

        <BookingChat
            v-model:visible="isChatOpen"
            :booking-id="booking.id"
            index-route="employee.bookings.chat.index"
            store-route="employee.bookings.chat.store"
            :peer="chatPeer"
        />
    </AppLayout>
</template>
