<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    bookings: { type: Object, required: true },
    filters: { type: Object, default: () => ({ status: null, sort: 'recent', hide_completed: false }) },
});

const toast = useToast();

const statusOptions = [
    { value: '', label: 'Todas' },
    { value: 'pending', label: 'Por asignar' },
    { value: 'confirmed', label: 'Confirmadas' },
    { value: 'on_way', label: 'En camino' },
    { value: 'in_progress', label: 'En progreso' },
    { value: 'completed', label: 'Completadas' },
    { value: 'cancelled', label: 'Canceladas' },
];

const showFilters = ref(false);

const filtersState = reactive({
    status: props.filters?.status ?? '',
    sort: props.filters?.sort ?? 'recent',
    hide_completed: props.filters?.hide_completed ?? false,
});

const hasActiveFilters = computed(
    () => filtersState.status !== '' || filtersState.sort !== 'recent' || filtersState.hide_completed
);

watch(
    () => props.filters,
    (value) => {
        filtersState.status = value?.status ?? '';
        filtersState.sort = value?.sort ?? 'recent';
        filtersState.hide_completed = value?.hide_completed ?? false;
    },
    { deep: true }
);

const applyFilters = () => {
    const params = { sort: filtersState.sort };

    if (filtersState.status) {
        params.status = filtersState.status;
    }

    if (filtersState.hide_completed) {
        params.hide_completed = 1;
    }

    router.get(route('client.bookings.index'), params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const setStatus = (status) => {
    filtersState.status = status;

    if (status === 'completed') {
        filtersState.hide_completed = false;
    }

    applyFilters();
};

const toggleHideCompleted = () => {
    if (filtersState.hide_completed && filtersState.status === 'completed') {
        filtersState.status = '';
    }

    applyFilters();
};

const clearFilters = () => {
    filtersState.status = '';
    filtersState.sort = 'recent';
    filtersState.hide_completed = false;
    applyFilters();
};

const isCancelModalVisible = ref(false);
const selectedBooking = ref(null);

const cancelForm = useForm({ reason: '' });

const openCancelModal = (booking) => {
    selectedBooking.value = booking;
    cancelForm.reset();
    isCancelModalVisible.value = true;
};

const closeCancelModal = () => {
    isCancelModalVisible.value = false;
    selectedBooking.value = null;
};

const submitCancellation = () => {
    if (!selectedBooking.value) return;

    cancelForm.patch(route('client.bookings.cancel', selectedBooking.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Reserva cancelada', detail: 'La reserva se canceló correctamente.', life: 3200 });
            closeCancelModal();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'No se pudo cancelar', detail: 'Revisa el motivo e intenta de nuevo.', life: 3200 });
        },
    });
};

// ---------------------------------------------------------------- Opiniones
const isReviewModalVisible = ref(false);
const reviewBooking = ref(null);

const reviewForm = useForm({ rating: 5, comment: '' });

const openReviewModal = (booking) => {
    reviewBooking.value = booking;
    reviewForm.rating = booking.review?.rating ?? 5;
    reviewForm.comment = booking.review?.comment ?? '';
    reviewForm.clearErrors();
    isReviewModalVisible.value = true;
};

const closeReviewModal = () => {
    isReviewModalVisible.value = false;
    reviewBooking.value = null;
    reviewForm.reset();
};

const submitReview = () => {
    if (!reviewBooking.value) return;

    reviewForm.post(route('client.bookings.review', reviewBooking.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: '¡Gracias por tu opinión!',
                detail: 'Tu calificación ayuda a mejorar el servicio.',
                life: 3200,
            });
            closeReviewModal();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'No se pudo enviar', detail: 'Revisa tu calificación e intenta de nuevo.', life: 3200 });
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));

const statusInfo = (status) => {
    const statuses = {
        pending: { text: 'Por asignar', class: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300', dot: 'bg-amber-500' },
        confirmed: { text: 'Confirmada', class: 'bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300', dot: 'bg-cloud-500' },
        on_way: { text: 'En camino', class: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300', dot: 'bg-indigo-500' },
        in_progress: { text: 'En progreso', class: 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300', dot: 'bg-violet-500' },
        completed: { text: 'Completada', class: 'bg-sage-50 text-sage-700 dark:bg-sage-500/15 dark:text-sage-300', dot: 'bg-sage-500' },
        cancelled: { text: 'Cancelada', class: 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', dot: 'bg-rose-500' },
    };

    return statuses[status] || { text: 'Desconocido', class: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300', dot: 'bg-mist-400' };
};

const canCancel = (booking) => ['pending', 'confirmed'].includes(booking.status);

const bookingVehicle = (booking) => {
    const name = [booking.vehicle_brand, booking.vehicle_model].filter(Boolean).join(' ');
    return name || null;
};
</script>

<template>
    <AppLayout title="Mis reservas">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-6xl space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Mi cuenta</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white">Mis reservas</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Consulta el estado de tus servicios y filtra por lo que necesites.
                    </p>
                </div>
                <Link :href="route('client.bookings.create')" class="wa-btn-primary !px-4 !py-2.5 w-fit">
                    <i class="pi pi-calendar-plus text-xs"></i>
                    Agendar cita
                </Link>
            </div>

            <!-- Filtros -->
            <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] text-mist-400">
                            Mostrando {{ bookings.from ?? 0 }}–{{ bookings.to ?? 0 }} de {{ bookings.total }} reserva{{ bookings.total === 1 ? '' : 's' }}
                        </p>
                        <p v-if="hasActiveFilters" class="mt-1 flex items-center gap-1.5 text-[11px] font-semibold text-cloud-600 dark:text-cloud-300">
                            <i class="pi pi-filter-fill text-[9px]"></i>
                            Filtros activos
                            <button type="button" class="ml-1 font-medium text-mist-400 underline-offset-2 hover:text-rose-500 hover:underline" @click="clearFilters">
                                Limpiar
                            </button>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="wa-btn-soft !px-4 !py-2.5"
                        :aria-expanded="showFilters"
                        @click="showFilters = !showFilters"
                    >
                        <i class="pi pi-filter text-xs"></i>
                        Filtros
                        <i :class="['text-[10px] transition-transform', showFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down']"></i>
                    </button>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showFilters" class="mt-4 space-y-4 border-t border-mist-100 pt-4 dark:border-slate-700">
                        <div>
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-mist-400">Estado</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-2xl px-3.5 py-2 text-xs font-semibold transition"
                                    :class="filtersState.status === option.value
                                        ? 'bg-cloud-600 text-white shadow-soft'
                                        : 'bg-mist-50 text-mist-500 hover:bg-mist-100 hover:text-mist-700 dark:bg-slate-700/40 dark:text-slate-300 dark:hover:bg-slate-700'"
                                    @click="setStatus(option.value)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-mist-600 dark:text-slate-300">
                                <input
                                    v-model="filtersState.hide_completed"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-mist-300 text-cloud-600 focus:ring-cloud-400 dark:border-slate-600 dark:bg-slate-800"
                                    @change="toggleHideCompleted"
                                />
                                Ocultar completadas
                            </label>

                            <div class="flex items-center gap-2">
                                <i class="pi pi-sort-alt text-xs text-mist-400"></i>
                                <select
                                    v-model="filtersState.sort"
                                    class="rounded-2xl border border-mist-200 bg-white px-3 py-2 text-xs font-medium text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                    @change="applyFilters"
                                >
                                    <option value="recent">Más recientes primero</option>
                                    <option value="oldest">Más antiguas primero</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Vacío -->
            <div
                v-if="!bookings.data.length"
                class="rounded-3xl border border-dashed border-mist-300 bg-white/60 px-6 py-14 text-center dark:border-slate-600 dark:bg-gray-800/60"
            >
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-3xl bg-mist-100 text-mist-400 dark:bg-slate-700">
                    <i class="pi pi-book text-xl"></i>
                </span>
                <h3 class="mt-4 text-lg font-semibold text-mist-900 dark:text-white">
                    {{ hasActiveFilters ? 'No hay reservas con esos filtros' : 'Aún no tienes reservas' }}
                </h3>
                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                    {{ hasActiveFilters
                        ? 'Prueba con otros filtros o agenda un nuevo servicio.'
                        : 'Agenda tu primer lavado y aparecerá aquí.' }}
                </p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="wa-btn-soft !px-4 !py-2.5"
                        @click="clearFilters"
                    >
                        Limpiar filtros
                    </button>
                    <Link :href="route('client.bookings.create')" class="wa-btn-primary !px-4 !py-2.5">
                        <i class="pi pi-calendar-plus text-xs"></i>
                        Agendar cita
                    </Link>
                </div>
            </div>

            <!-- Listado -->
            <div v-else class="space-y-3">
                <article
                    v-for="booking in bookings.data"
                    :key="booking.id"
                    class="group cursor-pointer rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md transition hover:-translate-y-0.5 hover:shadow-lift dark:border-slate-700 dark:bg-gray-800"
                    @click="router.visit(route('client.bookings.show', booking.id))"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-cloud-100 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300">
                                <i class="pi pi-calendar text-base"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold first-letter:uppercase text-mist-900 dark:text-white">
                                    {{ formatDate(booking.scheduled_at) }}
                                </p>
                                <p class="mt-0.5 text-xs text-mist-400">
                                    <i class="pi pi-clock mr-1 text-[10px]"></i>{{ formatTime(booking.scheduled_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                :class="statusInfo(booking.status).class"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusInfo(booking.status).dot"></span>
                                {{ statusInfo(booking.status).text }}
                            </span>
                            <span class="text-lg font-semibold text-mist-900 dark:text-white">
                                {{ formatCurrency(booking.total_price) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 border-t border-mist-100 pt-4 dark:border-slate-700 sm:grid-cols-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Ubicación</p>
                            <p class="mt-1 flex items-start gap-1.5 text-sm text-mist-600 dark:text-slate-300">
                                <i class="pi pi-map-marker mt-0.5 text-xs text-cloud-500"></i>
                                <span class="min-w-0">{{ booking.address || 'Sin dirección registrada' }}</span>
                            </p>
                            <p v-if="bookingVehicle(booking)" class="mt-1 text-xs text-mist-400">
                                <i class="pi pi-car mr-1 text-[10px]"></i>
                                {{ bookingVehicle(booking) }}
                            </p>
                        </div>

                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">Servicios</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span
                                    v-for="service in booking.services"
                                    :key="service.id"
                                    class="rounded-full bg-mist-100 px-2.5 py-1 text-[11px] font-medium text-mist-600 dark:bg-slate-700 dark:text-slate-300"
                                >
                                    {{ service.name }}
                                </span>
                                <span v-if="!booking.services.length" class="text-xs text-mist-400">Sin servicios registrados</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <Link
                                :href="route('client.bookings.show', booking.id)"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-mist-200 px-3 py-1.5 text-xs font-semibold text-mist-600 transition hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:text-slate-300"
                                @click.stop
                            >
                                <i class="pi pi-eye text-[11px]"></i>
                                Ver detalle
                            </Link>
                            <button
                                v-if="canCancel(booking)"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                @click.stop="openCancelModal(booking)"
                            >
                                <i class="pi pi-times-circle text-[11px]"></i>
                                Cancelar
                            </button>
                            <button
                                v-if="booking.status === 'completed'"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                                :class="booking.review
                                    ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300'
                                    : 'border border-mist-200 text-mist-600 hover:border-amber-300 hover:text-amber-700 dark:border-slate-600 dark:text-slate-300'"
                                @click.stop="openReviewModal(booking)"
                            >
                                <i class="pi pi-star-fill text-[11px]"></i>
                                {{ booking.review ? `Tu calificación: ${booking.review.rating}/5` : 'Calificar servicio' }}
                            </button>
                        </div>
                        <span class="text-[10px] uppercase tracking-wider text-mist-300 group-hover:text-cloud-400">
                            Reserva #{{ booking.id }}
                        </span>
                    </div>
                </article>

                <!-- Paginación -->
                <div v-if="bookings.last_page > 1" class="flex flex-wrap items-center justify-center gap-1.5 pt-4">
                    <template v-for="(link, index) in bookings.links" :key="index">
                        <span
                            v-if="link.url === null"
                            class="grid h-9 min-w-9 place-items-center rounded-xl border border-mist-100 px-2 text-xs text-mist-300 dark:border-slate-700 dark:text-slate-600"
                            v-html="link.label"
                        ></span>
                        <Link
                            v-else
                            :href="link.url"
                            preserve-scroll
                            class="grid h-9 min-w-9 place-items-center rounded-xl border px-2 text-xs font-semibold transition"
                            :class="link.active
                                ? 'border-cloud-600 bg-cloud-600 text-white shadow-soft'
                                : 'border-mist-200 bg-white text-mist-500 hover:border-cloud-300 hover:text-cloud-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal cancelación -->
        <Dialog v-model:visible="isCancelModalVisible" modal header="Cancelar reserva" :style="{ width: '90vw', maxWidth: '500px' }">
            <div class="flex flex-col gap-4">
                <p class="text-sm text-mist-600 dark:text-slate-300">
                    Estás a punto de cancelar tu reserva del
                    <span class="font-semibold text-mist-900 dark:text-white">
                        {{ selectedBooking ? formatDate(selectedBooking.scheduled_at) : '' }}
                    </span>.
                    Cuéntanos el motivo.
                </p>

                <div class="flex flex-col gap-2">
                    <label for="reason" class="text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-slate-400">
                        Motivo de la cancelación
                    </label>
                    <Textarea id="reason" v-model="cancelForm.reason" rows="4" cols="30" :invalid="!!cancelForm.errors.reason" />
                    <small v-if="cancelForm.errors.reason" class="text-xs font-medium text-rose-600">{{ cancelForm.errors.reason }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Volver" icon="pi pi-times" text @click="closeCancelModal" />
                <Button
                    label="Confirmar cancelación"
                    icon="pi pi-check"
                    severity="danger"
                    :loading="cancelForm.processing"
                    @click="submitCancellation"
                />
            </template>
        </Dialog>

        <!-- Modal de opinión -->
        <Dialog v-model:visible="isReviewModalVisible" modal header="Calificar servicio" :style="{ width: '90vw', maxWidth: '520px' }">
            <div v-if="reviewBooking" class="flex flex-col gap-5">
                <div>
                    <p class="text-sm text-mist-600 dark:text-slate-300">
                        ¿Cómo fue tu experiencia con el servicio del
                        <span class="font-semibold text-mist-900 dark:text-white">{{ formatDate(reviewBooking.scheduled_at) }}</span>?
                    </p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span
                            v-for="service in reviewBooking.services"
                            :key="service.id"
                            class="rounded-full bg-cloud-50 px-2.5 py-1 text-[11px] font-medium text-cloud-700 dark:bg-cloud-500/10 dark:text-cloud-300"
                        >
                            {{ service.name }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-2 rounded-2xl border border-mist-100 bg-mist-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-mist-400">Tu calificación</p>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="star in 5"
                            :key="star"
                            type="button"
                            class="transition hover:scale-110 focus:outline-none"
                            :aria-label="`${star} estrella${star === 1 ? '' : 's'}`"
                            @click="reviewForm.rating = star"
                        >
                            <i
                                class="pi pi-star-fill text-3xl"
                                :class="star <= reviewForm.rating ? 'text-amber-400' : 'text-mist-200 dark:text-slate-600'"
                            ></i>
                        </button>
                    </div>
                    <p class="text-xs font-semibold text-mist-500 dark:text-slate-400">
                        {{ reviewForm.rating }}/5 · {{ ['Muy mala', 'Mala', 'Regular', 'Buena', 'Excelente'][reviewForm.rating - 1] }}
                    </p>
                    <small v-if="reviewForm.errors.rating" class="text-xs font-medium text-rose-600">{{ reviewForm.errors.rating }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="review-comment" class="text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-slate-400">
                        Cuéntanos más (opcional)
                    </label>
                    <Textarea
                        id="review-comment"
                        v-model="reviewForm.comment"
                        rows="4"
                        maxlength="600"
                        placeholder="Puntualidad, trato, resultado del lavado…"
                        :invalid="!!reviewForm.errors.comment"
                    />
                    <small v-if="reviewForm.errors.comment" class="text-xs font-medium text-rose-600">{{ reviewForm.errors.comment }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" text severity="secondary" @click="closeReviewModal" />
                <Button
                    label="Enviar opinión"
                    icon="pi pi-send"
                    :loading="reviewForm.processing"
                    :disabled="!reviewBooking"
                    @click="submitReview"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
