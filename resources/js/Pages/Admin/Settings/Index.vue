<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({ opening_time: '09:00', closing_time: '18:00', operating_days: [1, 2, 3, 4, 5, 6] }),
    },
    exceptions: { type: Array, default: () => [] },
});

const page = usePage();
const toast = useToast();

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 3200 });
        }
    }
);

const days = [
    { value: 1, label: 'Lun', full: 'Lunes' },
    { value: 2, label: 'Mar', full: 'Martes' },
    { value: 3, label: 'Mié', full: 'Miércoles' },
    { value: 4, label: 'Jue', full: 'Jueves' },
    { value: 5, label: 'Vie', full: 'Viernes' },
    { value: 6, label: 'Sáb', full: 'Sábado' },
    { value: 0, label: 'Dom', full: 'Domingo' },
];

const settingsForm = useForm({
    opening_time: props.settings.opening_time,
    closing_time: props.settings.closing_time,
    operating_days: [...props.settings.operating_days],
});

const toggleDay = (value) => {
    settingsForm.operating_days = settingsForm.operating_days.includes(value)
        ? settingsForm.operating_days.filter((day) => day !== value)
        : [...settingsForm.operating_days, value];
};

const saveSettings = () => {
    settingsForm.put(route('admin.settings.update'), { preserveScroll: true });
};

const operatingSummary = computed(() => {
    const selected = days.filter((day) => settingsForm.operating_days.includes(day.value)).map((day) => day.full);

    if (!selected.length) return 'Sin días configurados';
    if (selected.length === 7) return 'Todos los días';

    return selected.join(', ');
});

const exceptionForm = useForm({
    date: '',
    label: '',
    is_closed: true,
    start_time: '',
    end_time: '',
});

const editingId = ref(null);

const editException = (exception) => {
    editingId.value = exception.id;
    exceptionForm.date = exception.date;
    exceptionForm.label = exception.label ?? '';
    exceptionForm.is_closed = exception.is_closed;
    exceptionForm.start_time = exception.start_time ?? '';
    exceptionForm.end_time = exception.end_time ?? '';
};

const setExceptionMode = (closed) => {
    exceptionForm.is_closed = closed;

    if (!closed) {
        if (!exceptionForm.start_time) exceptionForm.start_time = settingsForm.opening_time;
        if (!exceptionForm.end_time) exceptionForm.end_time = settingsForm.closing_time;
    }
};

const cancelException = () => {
    editingId.value = null;
    exceptionForm.reset();
};

const submitException = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => cancelException(),
    };

    if (editingId.value) {
        exceptionForm.put(route('admin.settings.exceptions.update', editingId.value), options);
        return;
    }

    exceptionForm.post(route('admin.settings.exceptions.store'), options);
};

const removeException = (exception) => {
    if (!confirm(`¿Eliminar la excepción del ${formatDate(exception.date)}?`)) return;

    router.delete(route('admin.settings.exceptions.destroy', exception.id), { preserveScroll: true });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(`${date}T00:00:00`).toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout title="Configuraciones">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-5xl space-y-6">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Super administrador</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white">Configuraciones de operación</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                    Define el horario en el que se pueden agendar servicios. Estas reglas se aplican al calendario del cliente.
                </p>
            </div>

            <!-- Horario de operación -->
            <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Horario de operación</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Días y horas base en los que se agenda el servicio.
                        </p>
                    </div>
                    <span class="rounded-full bg-cloud-50 px-3 py-1 text-xs font-semibold text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300">
                        {{ settingsForm.opening_time }}–{{ settingsForm.closing_time }}
                    </span>
                </div>

                <form class="mt-5 space-y-5" @submit.prevent="saveSettings">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="wa-label">Hora de inicio</label>
                            <input v-model="settingsForm.opening_time" type="time" class="wa-input" />
                            <InputError class="mt-1.5" :message="settingsForm.errors.opening_time" />
                        </div>
                        <div>
                            <label class="wa-label">Hora de cierre</label>
                            <input v-model="settingsForm.closing_time" type="time" class="wa-input" />
                            <InputError class="mt-1.5" :message="settingsForm.errors.closing_time" />
                        </div>
                    </div>

                    <div>
                        <label class="wa-label">Días de operación</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="day in days"
                                :key="day.value"
                                type="button"
                                class="rounded-2xl border px-4 py-2.5 text-sm font-semibold transition"
                                :class="settingsForm.operating_days.includes(day.value)
                                    ? 'border-cloud-600 bg-cloud-600 text-white shadow-soft'
                                    : 'border-mist-200 bg-white text-mist-500 hover:border-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                :title="day.full"
                                @click="toggleDay(day.value)"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                        <InputError class="mt-1.5" :message="settingsForm.errors.operating_days" />
                        <p class="mt-2 text-[11px] text-mist-400">{{ operatingSummary }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="wa-btn-primary" :disabled="settingsForm.processing">
                            <i class="pi pi-save text-xs"></i>
                            {{ settingsForm.processing ? 'Guardando…' : 'Guardar horario' }}
                        </button>
                    </div>
                </form>
            </section>

            <!-- Excepciones -->
            <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                <div>
                    <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Días y horarios especiales</h2>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Registra descansos completos o bloqueos parciales. El calendario del cliente los mostrará deshabilitados.
                    </p>
                </div>

                <form class="mt-5 grid gap-4 rounded-2xl border border-dashed border-cloud-300 bg-cloud-50/40 p-4 sm:grid-cols-2 dark:border-slate-600 dark:bg-slate-800/40" @submit.prevent="submitException">
                    <div>
                        <label class="wa-label">Fecha *</label>
                        <input v-model="exceptionForm.date" type="date" class="wa-input" />
                        <InputError class="mt-1.5" :message="exceptionForm.errors.date" />
                    </div>
                    <div>
                        <label class="wa-label">Motivo</label>
                        <input v-model="exceptionForm.label" type="text" class="wa-input" placeholder="Navidad, mantenimiento…" />
                        <InputError class="mt-1.5" :message="exceptionForm.errors.label" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="wa-label">Tipo de excepción</label>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <button
                                type="button"
                                class="rounded-2xl border px-4 py-3 text-left transition"
                                :class="exceptionForm.is_closed
                                    ? 'border-cloud-600 bg-cloud-50 text-cloud-700 ring-2 ring-cloud-200 dark:bg-cloud-900/20 dark:text-cloud-300'
                                    : 'border-mist-200 bg-white text-mist-500 hover:border-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                @click="setExceptionMode(true)"
                            >
                                <span class="flex items-center gap-2 text-sm font-semibold">
                                    <i class="pi pi-calendar-times text-xs"></i>
                                    Día completo
                                </span>
                                <span class="mt-1 block text-[11px] font-normal opacity-80">No se opera en todo el día.</span>
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl border px-4 py-3 text-left transition"
                                :class="!exceptionForm.is_closed
                                    ? 'border-cloud-600 bg-cloud-50 text-cloud-700 ring-2 ring-cloud-200 dark:bg-cloud-900/20 dark:text-cloud-300'
                                    : 'border-mist-200 bg-white text-mist-500 hover:border-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                @click="setExceptionMode(false)"
                            >
                                <span class="flex items-center gap-2 text-sm font-semibold">
                                    <i class="pi pi-clock text-xs"></i>
                                    Horario específico
                                </span>
                                <span class="mt-1 block text-[11px] font-normal opacity-80">Bloquea algunas horas del día.</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="!exceptionForm.is_closed" class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                        <div>
                            <label class="wa-label">Inicio del bloqueo *</label>
                            <input v-model="exceptionForm.start_time" type="time" class="wa-input" />
                            <InputError class="mt-1.5" :message="exceptionForm.errors.start_time" />
                        </div>
                        <div>
                            <label class="wa-label">Fin del bloqueo *</label>
                            <input v-model="exceptionForm.end_time" type="time" class="wa-input" />
                            <InputError class="mt-1.5" :message="exceptionForm.errors.end_time" />
                        </div>
                    </div>

                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" class="wa-btn-primary !px-5 !py-2.5" :disabled="exceptionForm.processing">
                            <i :class="['text-xs', editingId ? 'pi pi-save' : 'pi pi-plus']"></i>
                            {{ editingId ? 'Actualizar excepción' : 'Agregar excepción' }}
                        </button>
                        <button v-if="editingId" type="button" class="wa-btn-soft !px-5 !py-2.5" @click="cancelException">
                            Cancelar
                        </button>
                    </div>
                </form>

                <div v-if="exceptions.length" class="mt-5 space-y-3">
                    <div
                        v-for="exception in exceptions"
                        :key="exception.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4"
                        :class="exception.is_closed
                            ? 'border-rose-200 bg-rose-50/60 dark:border-rose-500/30 dark:bg-rose-500/10'
                            : 'border-amber-200 bg-amber-50/60 dark:border-amber-500/30 dark:bg-amber-500/10'"
                    >
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-sm font-semibold capitalize text-mist-900 dark:text-white">
                                {{ formatDate(exception.date) }}
                                <span
                                    class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-white"
                                    :class="exception.is_closed ? 'bg-rose-500' : 'bg-amber-500'"
                                >
                                    {{ exception.is_closed ? 'Cerrado' : 'Horario especial' }}
                                </span>
                            </p>
                            <p class="mt-1 text-xs text-mist-500 dark:text-slate-400">
                                {{ exception.label || 'Sin motivo especificado' }}
                                <span v-if="!exception.is_closed"> · {{ exception.start_time }}–{{ exception.end_time }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="grid h-9 w-9 place-items-center rounded-xl text-mist-400 transition hover:bg-white hover:text-cloud-600 dark:hover:bg-slate-700"
                                aria-label="Editar excepción"
                                @click="editException(exception)"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                            </button>
                            <button
                                type="button"
                                class="grid h-9 w-9 place-items-center rounded-xl text-mist-400 transition hover:bg-white hover:text-rose-500 dark:hover:bg-slate-700"
                                aria-label="Eliminar excepción"
                                @click="removeException(exception)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <p v-else class="mt-5 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                    No hay días ni horarios especiales registrados.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
