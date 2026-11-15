<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    markers: { type: Array, default: () => [] },
    notes: { type: String, default: '' },
    editable: { type: Boolean, default: false },
    confirmable: { type: Boolean, default: false },
    confirmed: { type: Boolean, default: false },
    confirmedAt: { type: String, default: null },
    registeredBy: { type: String, default: null },
    busy: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'confirm']);

const damageTypes = [    { key: 'scratch', label: 'Rayón', dot: '#f59e0b', chip: 'border-amber-200 bg-amber-50 text-amber-700' },
    { key: 'dent', label: 'Golpe', dot: '#e11d48', chip: 'border-rose-200 bg-rose-50 text-rose-700' },
    { key: 'chip', label: 'Despostillado', dot: '#1585bd', chip: 'border-cloud-200 bg-cloud-50 text-cloud-700' },
    { key: 'crack', label: 'Cristal', dot: '#7c3aed', chip: 'border-violet-200 bg-violet-50 text-violet-700' },
    { key: 'stain', label: 'Mancha', dot: '#64748b', chip: 'border-slate-200 bg-slate-50 text-slate-700' },
    { key: 'other', label: 'Otro', dot: '#8c7a62', chip: 'border-sand-200 bg-sand-50 text-sand-600' },
];

const views = [
    { key: 'front', label: 'Frontal' },
    { key: 'rear', label: 'Trasera' },
    { key: 'left', label: 'Lateral izquierdo' },
    { key: 'right', label: 'Lateral derecho' },
];

const localMarkers = ref([...(props.markers ?? [])]);
const localNotes = ref(props.notes ?? '');
const selectedType = ref('scratch');

// Una vez que el cliente confirma, la inspección queda bloqueada y plegada.
const collapsed = ref(props.confirmed === true);
const isEditable = computed(() => props.editable && !props.confirmed);

watch(
    () => props.confirmed,
    (value) => {
        collapsed.value = value === true;
    }
);

watch(
    () => props.markers,
    (value) => {
        localMarkers.value = [...(value ?? [])];
    },
    { deep: true }
);

watch(
    () => props.notes,
    (value) => {
        localNotes.value = value ?? '';
    }
);

const typeMeta = (key) => damageTypes.find((type) => type.key === key) ?? damageTypes[5];

const marksFor = (view) => localMarkers.value.filter((marker) => marker.view === view);

const handleSurfaceClick = (view, event) => {
    if (!isEditable.value) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const x = Math.min(97, Math.max(3, ((event.clientX - rect.left) / rect.width) * 100));
    const y = Math.min(95, Math.max(5, ((event.clientY - rect.top) / rect.height) * 100));

    localMarkers.value.push({
        view,
        x: Number(x.toFixed(2)),
        y: Number(y.toFixed(2)),
        type: selectedType.value,
        note: null,
    });
};

const removeMarker = (index) => {
    if (!isEditable.value) return;
    localMarkers.value.splice(index, 1);
};

const save = () => {
    emit('save', {
        markers: localMarkers.value.map((marker) => ({
            view: marker.view,
            x: marker.x,
            y: marker.y,
            type: marker.type,
            note: marker.note ?? null,
        })),
        notes: localNotes.value || null,
    });
};

const totalMarks = computed(() => localMarkers.value.length);

const summary = computed(() =>
    damageTypes
        .map((type) => ({
            ...type,
            count: localMarkers.value.filter((marker) => marker.type === type.key).length,
        }))
        .filter((type) => type.count > 0)
);
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-mist-400">Inspección del vehículo</p>
                <p class="mt-1 text-sm text-mist-500">
                    <template v-if="isEditable">
                        Toca el auto en cada vista para marcar detalles estéticos antes del lavado.
                    </template>
                    <template v-else-if="confirmed">
                        Inspección confirmada por el cliente: {{ totalMarks }} detalle(s) registrados.
                    </template>
                    <template v-else>
                        Detalles estéticos registrados antes del lavado.
                    </template>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                    :class="confirmed ? 'bg-sage-50 text-sage-700' : 'bg-amber-50 text-amber-700'"
                >
                    <i :class="confirmed ? 'pi pi-lock' : 'pi pi-clock'" class="text-[10px]"></i>
                    {{ confirmed ? 'Confirmada y bloqueada' : 'Pendiente de confirmar' }}
                </span>

                <button
                    v-if="confirmed"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border border-mist-200 px-3 py-1 text-[11px] font-semibold text-mist-600 transition hover:border-cloud-300 hover:text-cloud-600 dark:border-slate-600 dark:text-slate-300"
                    @click="collapsed = !collapsed"
                >
                    <i :class="['text-[10px]', collapsed ? 'pi pi-chevron-down' : 'pi pi-chevron-up']"></i>
                    {{ collapsed ? 'Ver detalles' : 'Ocultar detalles' }}
                </button>
            </div>
        </div>

        <div v-if="collapsed" class="space-y-3">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="type in summary"
                    :key="`collapsed-${type.key}`"
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold"
                    :class="type.chip"
                >
                    <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: type.dot }"></span>
                    {{ type.count }} {{ type.label }}
                </span>
                <span v-if="!summary.length" class="text-xs text-mist-500">Sin marcas registradas.</span>
            </div>

            <p class="rounded-2xl bg-mist-50 px-4 py-3 text-xs text-mist-600 dark:bg-slate-700/40 dark:text-slate-300">
                <i class="pi pi-comment mr-1 text-[10px]"></i>
                {{ notes || 'Sin notas adicionales del operador.' }}
            </p>
        </div>

        <div v-else class="space-y-4">
        <div v-if="isEditable" class="flex flex-wrap gap-2">
            <button
                v-for="type in damageTypes"
                :key="type.key"
                type="button"
                class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-semibold transition"
                :class="selectedType === type.key ? type.chip + ' ring-2 ring-offset-1 ring-mist-300' : 'border-mist-200 bg-white text-mist-500 hover:border-mist-300'"
                @click="selectedType = type.key"
            >
                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: type.dot }"></span>
                {{ type.label }}
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div
                v-for="view in views"
                :key="view.key"
                class="overflow-hidden rounded-2xl border border-mist-200/80 bg-gradient-to-br from-mist-50 via-white to-cloud-50/60"
            >
                <div class="flex items-center justify-between px-3 pt-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-mist-500">{{ view.label }}</p>
                    <span class="text-[10px] font-semibold text-mist-400">{{ marksFor(view.key).length }} marca(s)</span>
                </div>

                <div
                    class="relative m-2 aspect-[4/3] select-none rounded-xl bg-white/50"
                    :class="isEditable ? 'cursor-crosshair' : ''"
                    @click="handleSurfaceClick(view.key, $event)"
                >
                    <svg viewBox="0 0 200 150" class="absolute inset-0 h-full w-full" aria-hidden="true">
                        <template v-if="view.key === 'front'">
                            <path
                                d="M30 92c0-19 10-31 28-35h84c18 4 28 16 28 35v13c0 6-4 10-10 10H40c-6 0-10-4-10-10Z"
                                fill="#eef7fc"
                                stroke="#c7d5e0"
                                stroke-width="2"
                            />
                            <path d="M64 58c9-17 22-26 36-26s27 9 36 26Z" fill="#d9edf8" stroke="#c7d5e0" stroke-width="2" />
                            <rect x="38" y="84" width="30" height="11" rx="5.5" fill="#7fc3e5" />
                            <rect x="132" y="84" width="30" height="11" rx="5.5" fill="#7fc3e5" />
                            <rect x="84" y="88" width="32" height="13" rx="4" fill="#dfe7ee" />
                            <rect x="24" y="70" width="8" height="13" rx="4" fill="#c7d5e0" />
                            <rect x="168" y="70" width="8" height="13" rx="4" fill="#c7d5e0" />
                            <rect x="46" y="112" width="26" height="8" rx="4" fill="#c7d5e0" />
                            <rect x="128" y="112" width="26" height="8" rx="4" fill="#c7d5e0" />
                        </template>

                        <template v-else-if="view.key === 'rear'">
                            <path
                                d="M30 92c0-19 10-31 28-35h84c18 4 28 16 28 35v13c0 6-4 10-10 10H40c-6 0-10-4-10-10Z"
                                fill="#eef7fc"
                                stroke="#c7d5e0"
                                stroke-width="2"
                            />
                            <path d="M64 58c9-17 22-26 36-26s27 9 36 26Z" fill="#d9edf8" stroke="#c7d5e0" stroke-width="2" />
                            <rect x="38" y="84" width="28" height="12" rx="4" fill="#f2b3b3" />
                            <rect x="134" y="84" width="28" height="12" rx="4" fill="#f2b3b3" />
                            <rect x="84" y="86" width="32" height="16" rx="3" fill="#dfe7ee" />
                            <rect x="24" y="70" width="8" height="13" rx="4" fill="#c7d5e0" />
                            <rect x="168" y="70" width="8" height="13" rx="4" fill="#c7d5e0" />
                            <rect x="46" y="112" width="26" height="8" rx="4" fill="#c7d5e0" />
                            <rect x="128" y="112" width="26" height="8" rx="4" fill="#c7d5e0" />
                        </template>

                        <template v-else>
                            <g :transform="view.key === 'right' ? 'translate(200,0) scale(-1,1)' : ''">
                                <path
                                    d="M22 106l4-22c2-8 8-14 18-16l22-4c12-14 22-22 36-22h22c14 0 24 8 32 22l14 6c8 4 10 12 10 20v16h-14a16 16 0 0 0-32 0H86a16 16 0 0 0-32 0Z"
                                    fill="#eef7fc"
                                    stroke="#c7d5e0"
                                    stroke-width="2"
                                />
                                <path d="M84 62c8-12 16-16 24-16h16c10 0 18 6 24 16Z" fill="#d9edf8" stroke="#c7d5e0" stroke-width="2" />
                                <circle cx="70" cy="106" r="15" fill="#dfe7ee" stroke="#a3b8c8" stroke-width="2" />
                                <circle cx="150" cy="106" r="15" fill="#dfe7ee" stroke="#a3b8c8" stroke-width="2" />
                                <rect x="30" y="94" width="10" height="6" rx="3" fill="#7fc3e5" />
                            </g>
                        </template>
                    </svg>

                    <button
                        v-for="marker in marksFor(view.key)"
                        :key="`${marker.view}-${localMarkers.indexOf(marker)}`"
                        type="button"
                        class="absolute grid h-6 w-6 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-2 border-white text-[10px] font-bold text-white shadow-soft transition"
                        :style="{ left: `${marker.x}%`, top: `${marker.y}%`, backgroundColor: typeMeta(marker.type).dot }"
                        :title="`${typeMeta(marker.type).label}${marker.note ? ' · ' + marker.note : ''}`"
                        @click.stop="removeMarker(localMarkers.indexOf(marker))"
                    >
                        {{ localMarkers.indexOf(marker) + 1 }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="summary.length" class="flex flex-wrap gap-2">
            <span
                v-for="type in summary"
                :key="`summary-${type.key}`"
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold"
                :class="type.chip"
            >
                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: type.dot }"></span>
                {{ type.count }} {{ type.label }}
            </span>
        </div>
        <p v-else class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/70 px-4 py-3 text-xs text-mist-500">
            Sin marcas registradas en el vehículo.
        </p>

        <div v-if="isEditable">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-mist-400">Notas de la inspección</label>
            <textarea
                v-model="localNotes"
                rows="2"
                class="w-full rounded-2xl border border-mist-200 bg-white/80 px-3.5 py-2.5 text-sm text-mist-700 focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200"
                placeholder="Ej. Rayón en la puerta trasera derecha ya existente."
            ></textarea>
        </div>
        <p v-else-if="notes" class="rounded-2xl bg-mist-50 px-4 py-3 text-xs text-mist-600">
            <i class="pi pi-comment mr-1 text-[10px]"></i>{{ notes }}
        </p>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-mist-100 pt-3">
            <p class="text-[11px] text-mist-400">
                {{ totalMarks }} marca(s) · {{ registeredBy ? `Registrada por ${registeredBy}` : 'Inspección del operador' }}
            </p>

            <div class="flex flex-wrap gap-2">
                <button
                    v-if="isEditable"
                    type="button"
                    class="wa-btn-primary !px-4 !py-2.5"
                    :disabled="busy"
                    @click="save"
                >
                    <i :class="['text-xs', busy ? 'pi pi-spin pi-spinner' : 'pi pi-save']"></i>
                    Guardar inspección
                </button>

                <button
                    v-if="confirmable && !confirmed"
                    type="button"
                    class="wa-btn-sage !px-4 !py-2.5"
                    :disabled="busy"
                    @click="emit('confirm')"
                >
                    <i :class="['text-xs', busy ? 'pi pi-spin pi-spinner' : 'pi pi-check']"></i>
                    Confirmar inspección
                </button>
            </div>
        </div>
        </div>
    </div>
</template>
