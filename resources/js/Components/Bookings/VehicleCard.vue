<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    car: { type: Object, default: null },
    fallbackLabel: { type: String, default: '' },
    title: { type: String, default: 'Vehículo del servicio' },
});

const activePhoto = ref(null);

const label = computed(() => {
    if (!props.car) return props.fallbackLabel || 'No especificado';

    return (
        [props.car.brand, props.car.model].filter(Boolean).join(' ') ||
        props.fallbackLabel ||
        'No especificado'
    );
});

const specs = computed(() => {
    const car = props.car;
    if (!car) return [];

    return [
        car.year ? { label: 'Año', value: car.year } : null,
        car.color ? { label: 'Color', value: car.color } : null,
        car.plates ? { label: 'Placas', value: car.plates } : null,
        car.vehicle_category ? { label: 'Categoría', value: car.vehicle_category } : null,
    ].filter(Boolean);
});

const photos = computed(() => (props.car?.photos ?? []).filter((photo) => photo.url));

const openPhoto = (photo) => {
    activePhoto.value = photo;
};
</script>

<template>
    <div>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-mist-400">{{ title }}</p>
                <p class="mt-1 text-base font-semibold text-mist-900 dark:text-white">{{ label }}</p>
            </div>
            <span
                v-if="photos.length"
                class="rounded-full bg-cloud-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300"
            >
                {{ photos.length }} foto(s)
            </span>
        </div>

        <dl v-if="specs.length" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div v-for="spec in specs" :key="spec.label" class="rounded-2xl bg-mist-50 px-3 py-2 dark:bg-slate-700/40">
                <dt class="text-[10px] font-semibold uppercase tracking-wider text-mist-400">{{ spec.label }}</dt>
                <dd class="mt-0.5 truncate text-sm font-medium text-mist-700 dark:text-slate-200">{{ spec.value }}</dd>
            </div>
        </dl>

        <div v-if="photos.length" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <button
                v-for="photo in photos"
                :key="photo.side"
                type="button"
                class="group relative overflow-hidden rounded-2xl ring-1 ring-mist-200 transition hover:-translate-y-0.5 hover:shadow-soft dark:ring-slate-600"
                @click="openPhoto(photo)"
            >
                <img :src="photo.url" :alt="photo.label" class="aspect-square w-full object-cover" loading="lazy" />
                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-2 py-1 text-left text-[10px] font-semibold text-white">
                    {{ photo.label }}
                </span>
                <span class="absolute right-1.5 top-1.5 grid h-6 w-6 place-items-center rounded-full bg-white/85 text-mist-600 opacity-0 transition group-hover:opacity-100">
                    <i class="pi pi-search-plus text-[10px]"></i>
                </span>
            </button>
        </div>

        <p
            v-else
            class="mt-3 rounded-2xl border border-dashed border-mist-200 bg-mist-50/70 px-4 py-3 text-xs text-mist-500 dark:border-slate-600 dark:bg-slate-700/30 dark:text-slate-400"
        >
            <i class="pi pi-camera mr-1 text-[10px]"></i>
            El cliente no ha registrado fotos de este vehículo.
        </p>

        <Teleport to="body">
            <div
                v-if="activePhoto"
                class="fixed inset-0 z-[1250] flex flex-col items-center justify-center gap-3 bg-black/85 p-4"
                @click.self="activePhoto = null"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                    aria-label="Cerrar foto"
                    @click="activePhoto = null"
                >
                    <i class="pi pi-times text-sm"></i>
                </button>
                <img
                    :src="activePhoto.url"
                    :alt="activePhoto.label"
                    class="max-h-[80vh] w-auto max-w-full rounded-2xl object-contain shadow-2xl"
                />
                <p class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                    {{ activePhoto.label }} · {{ label }}
                </p>
            </div>
        </Teleport>
    </div>
</template>
