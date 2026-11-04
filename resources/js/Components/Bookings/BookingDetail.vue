<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    booking: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    statusRoute: { type: String, required: true },
    assignRoute: { type: String, default: null },
});

const emit = defineEmits(['changed']);
const toast = useToast();

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
};

const formatTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const statusMeta = (status) =>
    ({
        pending: { label: 'Pendiente', classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
        confirmed: { label: 'Confirmada', classes: 'bg-cloud-100 text-cloud-700 dark:bg-cloud-900/40 dark:text-cloud-300' },
        on_way: { label: 'En camino', classes: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' },
        in_progress: { label: 'En servicio', classes: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300' },
        completed: { label: 'Finalizada', classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' },
        cancelled: { label: 'Cancelada', classes: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
    })[status] ?? { label: status, classes: 'bg-mist-100 text-mist-600' };

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

const mapsLink = computed(() => {
    const booking = props.booking;

    if (booking.latitude && booking.longitude) {
        return `https://www.google.com/maps?q=${booking.latitude},${booking.longitude}`;
    }

    if (booking.address) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(booking.address)}`;
    }

    return null;
});

const nextStatus = computed(
    () =>
        ({
            pending: { value: 'confirmed', label: 'Confirmar', icon: 'pi pi-check', severity: 'info' },
            confirmed: { value: 'on_way', label: 'En camino', icon: 'pi pi-send', severity: 'info' },
            on_way: { value: 'in_progress', label: 'Iniciar', icon: 'pi pi-play', severity: 'warn' },
            in_progress: { value: 'completed', label: 'Finalizar', icon: 'pi pi-check-circle', severity: 'success' },
        })[props.booking.status] ?? null
);

const canCancel = computed(() => ['pending', 'confirmed', 'on_way', 'in_progress'].includes(props.booking.status));
const canAssign = computed(() => props.canManage && props.assignRoute && props.employees.length > 0 && canCancel.value);

const changeStatus = (status) => {
    router.patch(
        route(props.statusRoute, props.booking.id),
        { status },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Cita actualizada', detail: statusMeta(status).label, life: 2500 });
                emit('changed');
            },
            onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la cita.', life: 3500 }),
        }
    );
};

const assignEmployee = (employeeId) => {
    if (!props.assignRoute || !employeeId || employeeId === props.booking.employee_id) return;

    router.patch(
        route(props.assignRoute, props.booking.id),
        { employee_id: employeeId },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Empleado asignado', life: 2500 });
                emit('changed');
            },
            onError: (errors) =>
                toast.add({ severity: 'error', summary: 'No se pudo asignar', detail: errors.employee_id || '', life: 3500 }),
        }
    );
};
</script>

<template>
    <div class="space-y-4 text-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-lg font-semibold text-mist-900 dark:text-white">{{ booking.client_name }}</p>
                <p class="text-xs text-mist-400 dark:text-slate-500">
                    {{ booking.client_phone || 'Sin teléfono' }}<span v-if="booking.client_email"> · {{ booking.client_email }}</span>
                </p>
            </div>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusMeta(booking.status).classes">
                {{ statusMeta(booking.status).label }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3 rounded-2xl bg-mist-50 p-3 text-xs dark:bg-slate-700/40">
            <div>
                <p class="text-[10px] uppercase tracking-wide text-mist-400">Programada</p>
                <p class="mt-0.5 font-semibold text-mist-700 dark:text-slate-200">{{ formatDate(booking.scheduled_at) }}</p>
                <p class="text-mist-500 dark:text-slate-400">
                    {{ formatTime(booking.scheduled_at) }} – {{ formatTime(booking.ends_at) }}
                </p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wide text-mist-400">Empleado</p>
                <p class="mt-0.5 font-semibold text-mist-700 dark:text-slate-200">
                    {{ booking.employee_name || 'Sin asignar' }}
                </p>
                <p class="text-mist-500 dark:text-slate-400">{{ booking.duration_minutes }} min</p>
            </div>
            <div class="col-span-2">
                <p class="text-[10px] uppercase tracking-wide text-mist-400">Dirección</p>
                <p v-for="line in addressLines" :key="line" class="text-mist-600 dark:text-slate-300">{{ line }}</p>
                <a
                    v-if="mapsLink"
                    :href="mapsLink"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-cloud-600 hover:underline dark:text-cloud-300"
                >
                    <i class="pi pi-map-marker"></i> Ver en Google Maps
                </a>
            </div>
        </div>

        <div v-if="canAssign">
            <p class="mb-1 text-[10px] uppercase tracking-wide text-mist-400">Reasignar empleado</p>
            <Select
                :modelValue="booking.employee_id"
                :options="employees"
                optionLabel="name"
                optionValue="id"
                filter
                placeholder="Sin asignar"
                class="w-full"
                size="small"
                @change="(event) => assignEmployee(event.value)"
            />
        </div>

        <div>
            <p class="mb-2 text-[10px] uppercase tracking-wide text-mist-400">Servicios</p>
            <ul class="space-y-1.5">
                <li
                    v-for="service in booking.services"
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
                <p class="font-medium text-mist-600 dark:text-slate-300">{{ booking.payment_method || 'No especificado' }}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-wide text-mist-400">Total</p>
                <p class="text-xl font-semibold text-mist-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</p>
            </div>
        </div>

        <p v-if="booking.notes" class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
            <i class="pi pi-comment mr-1"></i>{{ booking.notes }}
        </p>

        <div v-if="canManage" class="flex flex-wrap justify-end gap-2">
            <Button
                v-if="canCancel"
                label="Cancelar cita"
                icon="pi pi-times"
                size="small"
                text
                severity="danger"
                @click="changeStatus('cancelled')"
            />
            <Button
                v-if="nextStatus"
                :label="nextStatus.label"
                :icon="nextStatus.icon"
                :severity="nextStatus.severity"
                size="small"
                @click="changeStatus(nextStatus.value)"
            />
        </div>
    </div>
</template>
