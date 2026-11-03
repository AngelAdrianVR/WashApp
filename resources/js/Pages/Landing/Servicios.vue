<script setup>
import { ref, computed } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    vehicleCategories: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    addons: { type: Array, default: () => [] },
});

const nf = new Intl.NumberFormat('es-MX');

// Tipo de vehículo activo para mostrar precios y tiempos de referencia
const activeCategory = ref(props.vehicleCategories[0]?.id ?? null);
const activeCategoryName = computed(
    () => props.vehicleCategories.find((category) => category.id === activeCategory.value)?.name ?? ''
);

const typeMeta = {
    Seco: { icon: 'leaf', tone: 'sage', water: '0 L de agua', label: 'Lavado en seco' },
    Agua: { icon: 'drop', tone: 'cloud', water: '≈ 15 L de agua', label: 'Lavado con agua' },
};

const addonIcons = ['sparkle', 'seat', 'fabric', 'hand', 'wind', 'engine'];

const iconPaths = {
    leaf: 'M20 4c0 8-5 12-11 12H5c0-7 5-11 11-11h4z|M5 20c1-4 4-7 8-9',
    drop: 'M12 3s6 6.2 6 10.5A6 6 0 016 13.5C6 9.2 12 3 12 3z',
    sparkle: 'M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z|M18 16l.9 2.1L21 19l-2.1.9L18 22l-.9-2.1L15 19l2.1-.9L18 16z',
    seat: 'M7 4h6a3 3 0 013 3v7H7V4z|M5 14h12a2 2 0 012 2v4H7a2 2 0 01-2-2v-4z',
    fabric: 'M4 6h16v4a4 4 0 01-4 4H8a4 4 0 01-4-4V6z|M8 14v6M16 14v6',
    hand: 'M8 11V6a1.5 1.5 0 013 0v5|M11 11V4.5a1.5 1.5 0 013 0V11|M14 11V6.5a1.5 1.5 0 013 0V14a6 6 0 01-6 6h-1a5 5 0 01-5-5v-3a2 2 0 114 0',
    wind: 'M4 8h9a3 3 0 100-6|M4 12h13a3 3 0 110 6H9|M4 16h5',
    engine: 'M6 9h3V7h6v2h2l2 3h2v4h-2l-2 3H9l-2-2H6a2 2 0 01-2-2v-4a2 2 0 012-2z|M10 12h4',
};

const durationLabel = (minutes) => {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    if (hours === 0) return `${rest} min`;
    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
};

// Precio y duración del paquete según la categoría seleccionada
const variantFor = (service) => service.variants?.[activeCategory.value] ?? null;
const priceFor = (service) => Number(variantFor(service)?.price ?? service.price ?? 0);
const minutesFor = (service) => Number(variantFor(service)?.duration_minutes ?? service.duration_minutes ?? 0);
</script>

<template>
    <LandingLayout title="Servicios">
        <div class="px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <!-- ==================== ENCABEZADO ==================== -->
                <header class="pt-8 text-center sm:pt-10" data-reveal>
                    <span class="wa-eyebrow">Servicios</span>
                    <h1 class="wa-title mx-auto mt-5 max-w-3xl text-4xl leading-tight sm:text-5xl">
                        Limpieza profesional, donde estés
                        <span class="block bg-gradient-to-r from-cloud-600 to-sage-500 bg-clip-text text-transparent">
                            y con el agua justa
                        </span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-mist-500">
                        Elige la modalidad que mejor se adapte a tu espacio y suma los adicionales que tu auto
                        necesita. Precios y tiempos dependen de tu tipo de vehículo.
                    </p>
                </header>

                <!-- ==================== MODALIDADES ==================== -->
                <section class="mt-14">
                    <div class="flex flex-col items-center justify-between gap-5 sm:flex-row" data-reveal>
                        <h2 class="text-2xl font-semibold tracking-tight text-mist-900">Modalidades principales</h2>

                        <!-- Selector de categoría de vehículo -->
                        <div v-if="vehicleCategories.length" class="inline-flex flex-wrap rounded-2xl border border-mist-200 bg-white/70 p-1 shadow-sm">
                            <button
                                v-for="category in vehicleCategories"
                                :key="category.id"
                                type="button"
                                @click="activeCategory = category.id"
                                class="rounded-xl px-4 py-2 text-sm font-medium transition-all duration-300"
                                :class="
                                    activeCategory === category.id
                                        ? 'bg-cloud-600 text-white shadow-sm'
                                        : 'text-mist-500 hover:text-mist-800'
                                "
                            >
                                {{ category.name }}
                            </button>
                        </div>
                    </div>

                    <div v-if="packages.length" class="mt-6 grid gap-5 lg:grid-cols-2">
                        <article
                            v-for="(service, index) in packages"
                            :key="service.id"
                            class="wa-card flex flex-col p-7 sm:p-9"
                            data-reveal
                            :style="{ transitionDelay: `${index * 100}ms` }"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <span
                                    class="grid h-12 w-12 place-items-center rounded-2xl"
                                    :class="
                                        (typeMeta[service.type] ?? typeMeta.Seco).tone === 'sage'
                                            ? 'bg-sage-100 text-sage-600'
                                            : 'bg-cloud-100 text-cloud-600'
                                    "
                                >
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <template v-for="(d, i) in iconPaths[(typeMeta[service.type] ?? typeMeta.Seco).icon].split('|')" :key="i">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                        </template>
                                    </svg>
                                </span>

                                <div class="flex flex-wrap gap-2">
                                    <span class="wa-chip">{{ (typeMeta[service.type] ?? typeMeta.Seco).water }}</span>
                                    <span class="wa-chip">{{ durationLabel(minutesFor(service)) }}</span>
                                    <span class="wa-chip border-sand-200 bg-sand-50/90 text-sand-600">
                                        <i class="fa-solid fa-star"></i> +{{ nf.format(service.points) }} pts
                                    </span>
                                </div>
                            </div>

                            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-sage-600">
                                {{ (typeMeta[service.type] ?? typeMeta.Seco).label }}
                            </p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight text-mist-900">{{ service.name }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-mist-500">{{ service.description }}</p>

                            <ul v-if="service.features.length" class="mt-6 space-y-2.5">
                                <li
                                    v-for="feature in service.features"
                                    :key="feature"
                                    class="flex items-start gap-2.5 text-sm text-mist-600"
                                >
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-sage-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>

                            <div class="mt-7 flex items-end justify-between gap-4 rounded-2xl bg-mist-50/80 p-4">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-mist-400">
                                        Precio {{ activeCategoryName }}
                                    </p>
                                    <p class="mt-1 text-2xl font-semibold text-mist-900">
                                        ${{ nf.format(priceFor(service)) }}
                                        <span class="text-sm font-normal text-mist-400">MXN</span>
                                    </p>
                                </div>
                                <Link
                                    :href="route('landing.agendar', { service: service.id, vehicle: activeCategory })"
                                    class="wa-btn-sage"
                                >
                                    Agendar
                                </Link>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-6 rounded-3xl border border-dashed border-mist-300 bg-white/60 p-10 text-center text-sm text-mist-500"
                    >
                        Estamos actualizando el catálogo de servicios. Vuelve en un momento.
                    </div>
                </section>

                <!-- ==================== ADICIONALES ==================== -->
                <section v-if="addons.length" class="mt-20">
                    <div class="mx-auto max-w-2xl text-center" data-reveal>
                        <span class="wa-eyebrow">Servicios adicionales</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Súmale lo que tu auto necesita</h2>
                        <p class="mt-4 text-mist-500">
                            Se agregan al servicio base durante el agendado y ajustan automáticamente el total, el
                            tiempo estimado y los puntos que ganas.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="(addon, index) in addons"
                            :key="addon.id"
                            class="group wa-card flex flex-col p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lift"
                            data-reveal
                            :style="{ transitionDelay: `${index * 70}ms` }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-sand-100 text-sand-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <template v-for="(d, i) in iconPaths[addonIcons[index % addonIcons.length]].split('|')" :key="i">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                        </template>
                                    </svg>
                                </span>
                                <span class="text-sm font-semibold text-mist-900">+ ${{ nf.format(addon.price) }}</span>
                            </div>

                            <h3 class="mt-4 text-base font-semibold text-mist-900">{{ addon.name }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-mist-500">{{ addon.description }}</p>

                            <div class="mt-5 flex items-center justify-between border-t border-mist-200/70 pt-4">
                                <span class="text-xs uppercase tracking-wider text-mist-400">
                                    +{{ addon.duration_minutes }} min · +{{ addon.points }} pts
                                </span>
                                <Link
                                    :href="route('landing.agendar', { addon: addon.id, vehicle: activeCategory })"
                                    class="text-sm font-semibold text-cloud-600 transition hover:text-cloud-700"
                                >
                                    Agregar →
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- ==================== QUÉ INCLUYE SIEMPRE ==================== -->
                <section class="mt-20" data-reveal>
                    <div class="wa-panel grid gap-8 p-8 sm:p-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                        <div>
                            <span class="wa-eyebrow">Incluido en todo servicio</span>
                            <h2 class="wa-title mt-5 text-2xl sm:text-3xl">El estándar WashApp no es opcional</h2>
                            <p class="mt-3 text-sm leading-relaxed text-mist-500">
                                Sin importar la modalidad o los adicionales que elijas, cada cita incluye el mismo
                                protocolo de cuidado y respaldo.
                            </p>
                            <Link :href="route('landing.agendar')" class="wa-btn-primary mt-6">
                                Comenzar mi reserva
                            </Link>
                        </div>

                        <ul class="grid gap-3 sm:grid-cols-2">
                            <li class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                                <p class="text-sm font-semibold text-mist-900">Inspección walkaround</p>
                                <p class="mt-1 text-xs leading-relaxed text-mist-500">Fotos del estado inicial y final del vehículo.</p>
                            </li>
                            <li class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                                <p class="text-sm font-semibold text-mist-900">Operador verificado</p>
                                <p class="mt-1 text-xs leading-relaxed text-mist-500">Identidad validada y capacitación interna.</p>
                            </li>
                            <li class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                                <p class="text-sm font-semibold text-mist-900">Productos biodegradables</p>
                                <p class="mt-1 text-xs leading-relaxed text-mist-500">Seguros para la pintura, las personas y el entorno.</p>
                            </li>
                            <li class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                                <p class="text-sm font-semibold text-mist-900">Garantía de 24 h</p>
                                <p class="mt-1 text-xs leading-relaxed text-mist-500">Retocamos cualquier detalle sin costo.</p>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </LandingLayout>
</template>
