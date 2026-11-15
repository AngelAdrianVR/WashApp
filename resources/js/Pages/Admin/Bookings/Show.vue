<script setup>
import { computed, ref } from 'vue';
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
    employees: { type: Array, default: () => [] },
});

const toast = useToast();

const booking = computed(() => props.booking);
const client = computed(() => props.booking.client ?? {});
const inspection = computed(() => props.booking.inspection ?? null);

const selectedEmployee = ref(props.booking.employee_id ?? null);
const isEtaDialogOpen = ref(false);
const isChatOpen = ref(false);
const etaMinutes = ref(20);
const etaPresets = [5, 10, 15, 20, 30, 45, 60];
const assignBusy = ref(false);
const statusBusy = ref(false);

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
    return new Date(value).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
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

const canAssign = computed(() => !['completed', 'cancelled'].includes(props.booking.status));
const isActive = computed(() => ['confirmed', 'on_way', 'in_progress'].includes(props.booking.status));

const assign = () => {
    if (!selectedEmployee.value) return;

    assignBusy.value = true;

    router.patch(route('admin.bookings.assignEmployee', booking.value.id), { employee_id: selectedEmployee.value }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Colaborador asignado', detail: 'Cliente y colaborador fueron notificados.', life: 3000 }),
        onError: (errors) => toast.add({ severity: 'error', summary: 'No se pudo asignar', detail: errors.employee_id || 'Inténtalo de nuevo.', life: 3400 }),
        onFinish: () => {
            assignBusy.value = false;
        },
    });
};

const updateStatus = (status, extra = {}) => {
    statusBusy.value = true;

    router.patch(route('admin.bookings.updateStatus', booking.value.id), { status, ...extra }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Servicio actualizado', detail: 'El cliente fue notificado.', life: 2800 }),
        onError: (errors) => toast.add({ severity: 'error', summary: 'No se pudo actualizar', detail: Object.values(errors)[0] || 'Inténtalo de nuevo.', life: 3400 }),
        onFinish: () => {
            statusBusy.value = false;
            isEtaDialogOpen.value = false;
        },
    });
};

const notifyRoute = () => {
    if (!etaMinutes.value) return;
    updateStatus('on_way', { eta_minutes: Number(etaMinutes.value) });
};

const employeeInitials = (name) =>
    (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
</script>

<template>
    <Head title="Detalle del servicio" />

    <AppLayout title="Detalle del servicio">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-6xl space-y-5">
            <!-- Encabezado -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.bookings.index')"
                        class="grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-300 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        aria-label="Volver al listado"
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
                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                    {{ statusMeta.label }}
                </span>
            </div>

            <!-- Aviso de asignación pendiente -->
            <div
                v-if="booking.is_awaiting_assignment"
                class="flex flex-wrap items-center gap-3 rounded-3xl border border-amber-200 bg-amber-50/80 px-5 py-4 dark:border-amber-500/30 dark:bg-amber-500/10"
            >
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-500 text-white">
                    <i class="pi pi-exclamation-triangle text-sm"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Esta reserva espera un colaborador</p>
                    <p class="text-xs text-amber-700 dark:text-amber-200/80">
                        El cliente ya recibió el aviso. Revisa su historial y asigna al operador ideal.
                    </p>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <!-- Columna principal -->
                <div class="min-w-0 space-y-5 lg:col-span-2">
                    <!-- Cliente -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Información del cliente</h2>
                            <span
                                v-if="client.is_guest"
                                class="rounded-full bg-mist-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-mist-500 dark:bg-slate-700 dark:text-slate-300"
                            >
                                Invitado
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <img
                                v-if="client.avatar"
                                :src="client.avatar"
                                :alt="client.name"
                                class="h-14 w-14 rounded-2xl object-cover"
                            />
                            <span
                                v-else
                                class="grid h-14 w-14 place-items-center rounded-2xl bg-cloud-100 text-base font-semibold text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300"
                            >
                                {{ employeeInitials(client.name) || 'CL' }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-lg font-semibold text-mist-900 dark:text-white">{{ client.name }}</p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    {{ client.phone || 'Sin teléfono' }}
                                    <span v-if="client.email"> · {{ client.email }}</span>
                                </p>
                                <p v-if="client.member_since" class="text-[11px] text-mist-400">Cliente desde {{ client.member_since }}</p>
                            </div>
                            <div class="ml-auto flex flex-wrap gap-2">
                                <a v-if="client.phone" :href="`tel:${client.phone}`" class="wa-btn-soft !px-4 !py-2.5">
                                    <i class="pi pi-phone text-xs"></i> Llamar
                                </a>
                                <button type="button" class="wa-btn-primary !px-4 !py-2.5" @click="isChatOpen = true">
                                    <i class="pi pi-comments text-xs"></i> Mensaje
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl bg-mist-50 p-3 text-center dark:bg-slate-700/40">
                                <p class="text-lg font-semibold text-mist-900 dark:text-white">{{ client.total_bookings ?? 1 }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-mist-400">Reservas</p>
                            </div>
                            <div class="rounded-2xl bg-mist-50 p-3 text-center dark:bg-slate-700/40">
                                <p class="text-lg font-semibold text-sage-700 dark:text-sage-300">{{ client.completed_bookings ?? 0 }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-mist-400">Completadas</p>
                            </div>
                            <div class="rounded-2xl bg-mist-50 p-3 text-center dark:bg-slate-700/40">
                                <p class="text-lg font-semibold text-rose-600 dark:text-rose-300">{{ client.cancelled_bookings ?? 0 }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-mist-400">Canceladas</p>
                            </div>
                            <div class="rounded-2xl bg-mist-50 p-3 text-center dark:bg-slate-700/40">
                                <p class="text-lg font-semibold text-cloud-700 dark:text-cloud-300">{{ client.points ?? 0 }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-mist-400">Puntos</p>
                            </div>
                        </div>
                    </section>

                    <!-- Servicio -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Detalle del servicio</h2>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Fecha y hora</p>
                                <p class="mt-1 text-sm font-semibold capitalize text-mist-800 dark:text-slate-100">{{ formatDate(booking.scheduled_at) }}</p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">
                                    {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }} · {{ booking.duration_minutes }} min
                                </p>
                            </div>
                            <div class="rounded-2xl bg-mist-50 p-3.5 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Vehículo</p>
                                <p class="mt-1 text-sm font-semibold text-mist-800 dark:text-slate-100">
                                    {{ [booking.vehicle_brand, booking.vehicle_model].filter(Boolean).join(' ') || 'No especificado' }}
                                </p>
                                <p class="text-xs text-mist-500 dark:text-slate-400">Pago: {{ booking.payment_method || 'No especificado' }}</p>
                            </div>
                            <div class="rounded-2xl bg-mist-50 p-3.5 sm:col-span-2 dark:bg-slate-700/40">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Dirección</p>
                                <p class="mt-1 text-sm text-mist-700 dark:text-slate-200">{{ booking.address }}</p>
                                <a
                                    v-if="mapsLink"
                                    :href="mapsLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-cloud-600 hover:underline dark:text-cloud-300"
                                >
                                    <i class="pi pi-external-link text-[10px]"></i> Ver en Google Maps
                                </a>
                            </div>
                        </div>

                        <ul class="mt-4 divide-y divide-mist-100 dark:divide-slate-700">
                            <li v-for="service in booking.services" :key="service.id" class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-mist-800 dark:text-slate-100">{{ service.name }}</p>
                                    <p class="text-[11px] text-mist-400">{{ service.duration_minutes }} min · +{{ service.points }} pts</p>
                                </div>
                                <span class="text-sm font-semibold text-mist-900 dark:text-white">{{ formatCurrency(service.price) }}</span>
                            </li>
                        </ul>

                        <div class="mt-3 flex items-center justify-between border-t border-mist-100 pt-3 dark:border-slate-700">
                            <span class="text-sm font-semibold text-mist-700 dark:text-slate-200">Total</span>
                            <span class="text-xl font-semibold text-mist-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</span>
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
                    <section
                        v-if="inspection"
                        class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6"
                    >
                        <VehicleInspection
                            :markers="inspection.markers"
                            :notes="inspection.notes || ''"
                            :confirmed="Boolean(inspection.confirmed)"
                            :registered-by="inspection.registered_by"
                        />
                    </section>
                </div>

                <!-- Panel lateral -->
                <div class="min-w-0 space-y-5">
                    <!-- Asignación -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-semibold text-mist-900 dark:text-white">Colaborador</h2>
                            <span v-if="booking.employee" class="text-[10px] font-semibold uppercase tracking-wide text-sage-600">
                                Asignado
                            </span>
                        </div>

                        <div v-if="booking.employee" class="mt-3 flex items-center gap-3 rounded-2xl bg-sage-50/70 p-3 dark:bg-sage-500/10">
                            <img
                                v-if="booking.employee.avatar"
                                :src="booking.employee.avatar"
                                :alt="booking.employee.name"
                                class="h-10 w-10 rounded-xl object-cover"
                            />
                            <span
                                v-else
                                class="grid h-10 w-10 place-items-center rounded-xl bg-sage-600 text-xs font-semibold text-white"
                            >
                                {{ employeeInitials(booking.employee.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">{{ booking.employee.name }}</p>
                                <p class="text-[11px] text-mist-500 dark:text-slate-400">
                                    {{ booking.employee.rating ? booking.employee.rating + '/5' : 'Nuevo' }} ·
                                    {{ booking.employee.completed_services }} servicios
                                </p>
                            </div>
                        </div>

                        <template v-if="canAssign">
                            <p class="mt-4 text-[10px] font-semibold uppercase tracking-wider text-mist-400">
                                {{ booking.employee ? 'Reasignar' : 'Elige al colaborador' }}
                            </p>

                            <div class="mt-2 max-h-64 space-y-1.5 overflow-y-auto pr-1">
                                <button
                                    v-for="employeeOption in employees"
                                    :key="employeeOption.id"
                                    type="button"
                                    class="flex w-full items-center gap-2.5 rounded-2xl border px-3 py-2 text-left transition"
                                    :class="Number(selectedEmployee) === employeeOption.id
                                        ? 'border-cloud-400 bg-cloud-50 dark:bg-cloud-500/15'
                                        : 'border-mist-200 hover:border-cloud-300 dark:border-slate-600'"
                                    @click="selectedEmployee = employeeOption.id"
                                >
                                    <img
                                        v-if="employeeOption.avatar"
                                        :src="employeeOption.avatar"
                                        :alt="employeeOption.name"
                                        class="h-8 w-8 rounded-lg object-cover"
                                    />
                                    <span
                                        v-else
                                        class="grid h-8 w-8 place-items-center rounded-lg bg-mist-100 text-[10px] font-semibold text-mist-600 dark:bg-slate-700 dark:text-slate-200"
                                    >
                                        {{ employeeInitials(employeeOption.name) }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-mist-800 dark:text-slate-100">{{ employeeOption.name }}</span>
                                        <span class="block text-[10px] text-mist-400">
                                            {{ employeeOption.rating ? employeeOption.rating + '/5' : 'Sin calificaciones' }} ·
                                            {{ employeeOption.completed_services }} completados ·
                                            {{ employeeOption.active_services }} activos
                                        </span>
                                    </span>
                                    <i
                                        v-if="Number(selectedEmployee) === employeeOption.id"
                                        class="pi pi-check-circle text-sm text-cloud-600 dark:text-cloud-300"
                                    ></i>
                                </button>

                                <p v-if="!employees.length" class="rounded-2xl border border-dashed border-mist-200 px-3 py-4 text-center text-xs text-mist-400">
                                    No hay colaboradores activos.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="wa-btn-sage mt-3 w-full !py-2.5"
                                :disabled="assignBusy || !selectedEmployee || Number(selectedEmployee) === Number(booking.employee_id)"
                                @click="assign"
                            >
                                <i :class="['text-xs', assignBusy ? 'pi pi-spin pi-spinner' : 'pi pi-user-plus']"></i>
                                {{ booking.employee ? 'Reasignar y notificar' : 'Asignar y notificar' }}
                            </button>
                        </template>
                    </section>

                    <!-- Acciones de estado -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Estado del servicio</h2>

                        <div class="mt-3 space-y-2">
                            <button
                                v-if="booking.status === 'confirmed'"
                                type="button"
                                class="wa-btn-soft w-full !justify-start !py-2.5"
                                @click="isEtaDialogOpen = true"
                            >
                                <i class="pi pi-send text-xs text-cloud-600"></i>
                                Marcar en camino con ETA
                            </button>

                            <button
                                v-if="['confirmed', 'on_way'].includes(booking.status)"
                                type="button"
                                class="wa-btn-soft w-full !justify-start !py-2.5"
                                :disabled="statusBusy"
                                @click="updateStatus('in_progress')"
                            >
                                <i class="pi pi-map-marker text-xs text-sage-600"></i>
                                Registrar llegada
                            </button>

                            <button
                                v-if="booking.status === 'in_progress'"
                                type="button"
                                class="wa-btn-soft w-full !justify-start !py-2.5"
                                :disabled="statusBusy"
                                @click="updateStatus('completed')"
                            >
                                <i class="pi pi-check-circle text-xs text-sage-600"></i>
                                Finalizar servicio
                            </button>

                            <button
                                v-if="isActive"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                :disabled="statusBusy"
                                @click="updateStatus('cancelled', { cancel_reason: 'Cancelada por el administrador' })"
                            >
                                <i class="pi pi-times-circle text-xs"></i>
                                Cancelar servicio
                            </button>
                        </div>

                        <dl class="mt-4 space-y-2 border-t border-mist-100 pt-3 text-sm dark:border-slate-700">
                            <div class="flex justify-between gap-3">
                                <dt class="text-mist-400">Salida</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ formatTime(booking.en_route_at) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-mist-400">Ventana</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ booking.arrival_window_label || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-mist-400">Llegada</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ formatTime(booking.arrived_at) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-mist-400">Cierre</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">{{ formatTime(booking.completed_at) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-mist-400">Propina</dt>
                                <dd class="font-medium text-mist-700 dark:text-slate-200">
                                    {{ booking.tip_amount ? formatCurrency(booking.tip_amount) : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>

        <!-- Dialog ETA -->
        <Dialog v-model:visible="isEtaDialogOpen" modal header="Tiempo estimado de llegada" :style="{ width: '92vw', maxWidth: '440px' }">
            <div class="space-y-4">
                <p class="text-sm text-mist-600 dark:text-slate-300">
                    El sistema notificará al cliente una ventana de llegada de 7 minutos.
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in etaPresets"
                        :key="`admin-eta-${preset}`"
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
                <input
                    v-model.number="etaMinutes"
                    type="number"
                    min="1"
                    max="240"
                    class="w-full rounded-2xl border border-mist-200 bg-white/80 px-3.5 py-2.5 text-sm text-mist-700 focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
            </div>
            <template #footer>
                <button type="button" class="wa-btn-soft !px-4 !py-2.5" @click="isEtaDialogOpen = false">Cancelar</button>
                <button type="button" class="wa-btn-primary !px-4 !py-2.5" :disabled="statusBusy" @click="notifyRoute">
                    <i :class="['text-xs', statusBusy ? 'pi pi-spin pi-spinner' : 'pi pi-send']"></i>
                    Notificar al cliente
                </button>
            </template>
        </Dialog>

        <BookingChat
            v-model:visible="isChatOpen"
            :booking-id="booking.id"
            index-route="admin.bookings.chat.index"
            store-route="admin.bookings.chat.store"
            :peer="{ name: booking.client_name, avatar: client.avatar ?? null, role: 'Cliente' }"
        />
    </AppLayout>
</template>
