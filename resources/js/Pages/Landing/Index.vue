<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import ScrollCarousel from '@/Components/Landing/ScrollCarousel.vue';
import ScrollSteps from '@/Components/Landing/ScrollSteps.vue';
import HeroShowcase from '@/Components/Landing/HeroShowcase.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    packages: { type: Array, default: () => [] },
    rewardsPreview: { type: Array, default: () => [] },
});

const nf = new Intl.NumberFormat('es-MX');

const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------------------------------------------------------
   Hero: ubicación animada
   --------------------------------------------------------- */
const locations = ['tu casa', 'tu oficina', 'tu edificio', 'tu estacionamiento'];
const typedLocation = ref(locations[0]);

let typingTimer = null;
let locationIndex = 0;
let charIndex = 0;
let isDeleting = false;

const typeLoop = () => {
    const current = locations[locationIndex];

    if (!isDeleting) {
        charIndex += 1;
        typedLocation.value = current.slice(0, charIndex);

        if (charIndex === current.length) {
            isDeleting = true;
            typingTimer = setTimeout(typeLoop, 1700);
            return;
        }

        typingTimer = setTimeout(typeLoop, 72);
        return;
    }

    charIndex -= 1;
    typedLocation.value = current.slice(0, charIndex);

    if (charIndex === 0) {
        isDeleting = false;
        locationIndex = (locationIndex + 1) % locations.length;
        typingTimer = setTimeout(typeLoop, 380);
        return;
    }

    typingTimer = setTimeout(typeLoop, 34);
};

/* ---------------------------------------------------------
   Contador ecológico
   --------------------------------------------------------- */
const LITERS_SAVED_PER_WASH = 85;
const WASHES_COMPLETED = 12400;
const litersTarget = WASHES_COMPLETED * LITERS_SAVED_PER_WASH;

const litersSaved = ref(prefersReducedMotion ? litersTarget : 0);
let counterFrame = null;

const animateCounter = () => {
    if (prefersReducedMotion) return;

    const duration = 1600;
    const start = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        litersSaved.value = Math.round(litersTarget * eased);

        if (progress < 1) {
            counterFrame = requestAnimationFrame(tick);
        }
    };

    counterFrame = requestAnimationFrame(tick);
};

onMounted(() => {
    animateCounter();

    if (prefersReducedMotion) {
        typedLocation.value = locations[0];
        return;
    }

    charIndex = locations[0].length;
    isDeleting = true;
    typingTimer = setTimeout(typeLoop, 1800);
});

onUnmounted(() => {
    if (counterFrame) cancelAnimationFrame(counterFrame);
    if (typingTimer) clearTimeout(typingTimer);
});

/* ---------------------------------------------------------
   Contenido conciso de la página
   --------------------------------------------------------- */
const advantages = [
    {
        title: 'Comodidad total',
        description: 'Lavamos en tu casa, oficina o estacionamiento. Tú sigue con tu día.',
        icon: 'home',
        tone: 'cloud',
    },
    {
        title: 'Ecológico y consciente',
        description: 'Solo 15 litros de agua y productos biodegradables: hasta 85 L menos por lavado.',
        icon: 'leaf',
        tone: 'sage',
    },
    {
        title: 'Simple y digital',
        description: 'Agenda, paga y recibe el reporte fotográfico desde tu celular. Sin efectivo.',
        icon: 'phone',
        tone: 'sand',
    },
];

const steps = [
    {
        title: 'Elige tu paquete',
        description: 'Lavado en seco o con agua: compara tiempos, precios y extras desde la app.',
        icon: 'fa-solid fa-layer-group',
        tone: 'bg-cloud-100 text-cloud-600',
    },
    {
        title: 'Agenda en segundos',
        description: 'Elige fecha, hora y dirección. La confirmación llega al instante, sin llamadas.',
        icon: 'fa-regular fa-calendar-check',
        tone: 'bg-sage-100 text-sage-600',
    },
    {
        title: 'Llegamos a ti',
        description: 'Un operador verificado lava donde estés con productos biodegradables.',
        icon: 'fa-solid fa-location-dot',
        tone: 'bg-sand-100 text-sand-600',
    },
    {
        title: 'Acumula y canjea',
        description: 'Cada servicio suma puntos que cambias por lavados y extras gratis.',
        icon: 'fa-solid fa-gift',
        tone: 'bg-cloud-100 text-cloud-600',
    },
];

const marqueeItems = [
    'Operadores verificados',
    'Pago digital sin efectivo',
    'Seguro de responsabilidad',
    'Productos biodegradables',
    'Puntos en cada lavado',
    'Agenda en 2 minutos',
];

const iconPaths = {
    leaf: 'M20 4c0 8-5 12-11 12H5c0-7 5-11 11-11h4z|M5 20c1-4 4-7 8-9',
    drop: 'M12 3s6 6.2 6 10.5A6 6 0 016 13.5C6 9.2 12 3 12 3z',
    home: 'M4 11l8-7 8 7|M6 10v10h12V10',
    phone: 'M7 3h10a1 1 0 011 1v16a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z|M10 18h4',
};

const typeMeta = {
    Seco: { tag: 'En seco · 0 L', icon: 'leaf', tone: 'sage' },
    Agua: { tag: 'Bajo consumo · 15 L', icon: 'drop', tone: 'cloud' },
};

const modalities = computed(() =>
    props.packages.map((service) => ({
        id: service.id,
        name: service.name,
        description: service.description,
        minPrice: service.min_price,
        ...(typeMeta[service.type] ?? typeMeta.Seco),
    }))
);

const rewards = computed(() => props.rewardsPreview.slice(0, 3));

const testimonials = [
    {
        quote: 'Agendé desde la app y llegaron puntual. Dejaron el auto impecable mientras yo trabajaba.',
        author: 'Ana L.',
        city: 'Zapopan',
    },
    {
        quote: 'El lavado en seco quedó perfecto y sin una sola gota de agua en el estacionamiento.',
        author: 'Carlos G.',
        city: 'Guadalajara',
    },
    {
        quote: 'Pagué en línea y me retocaron una mancha sin discusión al día siguiente.',
        author: 'Laura M.',
        city: 'Tlaquepaque',
    },
];

const toneClasses = {
    cloud: 'bg-cloud-100 text-cloud-600',
    sage: 'bg-sage-100 text-sage-600',
    sand: 'bg-sand-100 text-sand-600',
};
</script>

<template>
    <LandingLayout title="Inicio">
        <!-- ==================== HERO ==================== -->
        <section class="relative overflow-hidden px-3 pt-2 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="grid items-center gap-10 pb-8 pt-2 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12 lg:pb-10 lg:pt-6">
                    <!-- Copy -->
                    <div data-reveal>
                        <span class="wa-eyebrow">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sage-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-sage-600"></span>
                            </span>
                            Lavado ecológico a domicilio
                        </span>

                        <h1 class="wa-title mt-6 text-4xl leading-[1.05] sm:text-5xl lg:text-[3.5rem]">
                            <span class="hero-word block">Tu auto limpio,</span>
                            <span
                                class="hero-word mt-1 block bg-gradient-to-r from-cloud-600 via-cloud-500 to-sage-500 bg-clip-text text-transparent"
                                :style="{ animationDelay: '160ms' }"
                            >sin mover un dedo.</span>
                        </h1>

                        <p class="mt-5 flex flex-wrap items-baseline gap-x-2 text-lg text-mist-600 sm:text-xl">
                            <span>Llegamos a</span>
                            <span class="inline-flex items-baseline font-semibold text-cloud-700">
                                {{ typedLocation || '\u00a0' }}<span class="typing-caret" aria-hidden="true"></span>
                            </span>
                        </p>

                        <p class="mt-3 max-w-xl text-base leading-relaxed text-mist-500">
                            Agenda en línea y nosotros nos encargamos del resto: solo 15 litros de agua y productos
                            biodegradables.
                        </p>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <Link :href="route('landing.agendar')" class="wa-btn-primary text-base">
                                Agendar ahora
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </Link>
                            <Link :href="route('landing.servicios')" class="wa-btn-soft text-base">
                                Ver servicios
                            </Link>
                        </div>

                        <ul class="mt-7 flex flex-wrap gap-2">
                            <li class="wa-chip border-sage-200 bg-sage-50/90 text-sage-700">
                                <i class="fa-solid fa-leaf"></i> 85 L menos por lavado
                            </li>
                            <li class="wa-chip border-cloud-200 bg-cloud-50/90 text-cloud-700">
                                <i class="fa-solid fa-mobile-screen"></i> 100% digital
                            </li>
                            <li class="wa-chip border-sand-200 bg-sand-50/90 text-sand-600">
                                <i class="fa-solid fa-shield-halved"></i> Operadores verificados
                            </li>
                        </ul>
                    </div>

                    <!-- Visual -->
                    <div data-reveal>
                        <HeroShowcase />
                    </div>
                </div>

                <!-- Indicador de scroll -->
                <div class="flex justify-center pb-5">
                    <a
                        href="#como-funciona"
                        class="group inline-flex flex-col items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-mist-400 transition hover:text-mist-700"
                    >
                        <span>Descubre cómo funciona</span>
                        <span
                            class="grid h-9 w-9 place-items-center rounded-full border border-mist-200 bg-white/70 text-mist-500 transition group-hover:border-cloud-300 group-hover:text-cloud-600"
                        >
                            <svg class="h-4 w-4 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M6 13l6 6 6-6" />
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <!-- ==================== CINTA DE VENTAJAS ==================== -->
        <section class="mt-4">
            <div class="wa-marquee-wrap border-y border-white/60 bg-white/50 py-3 backdrop-blur-sm">
                <div class="wa-marquee gap-3">
                    <div v-for="copy in 2" :key="copy" class="flex shrink-0 items-center gap-3 pr-3" aria-hidden="true">
                        <span
                            v-for="item in marqueeItems"
                            :key="`${copy}-${item}`"
                            class="wa-chip whitespace-nowrap border-mist-200/80 bg-white/80 text-mist-600"
                        >
                            <i class="fa-solid fa-circle-check text-sage-500"></i>
                            {{ item }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== VENTAJAS ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <span class="wa-eyebrow">Por qué WashApp</span>
                    <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Menos esfuerzo, más tiempo para ti</h2>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div
                        v-for="(advantage, index) in advantages"
                        :key="advantage.title"
                        data-motion="card"
                        :data-motion-delay="index * 0.08"
                    >
                        <div class="wa-card h-full p-6 transition-transform duration-300 hover:-translate-y-1">
                            <span class="grid h-11 w-11 place-items-center rounded-2xl" :class="toneClasses[advantage.tone]">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <template v-for="(d, i) in iconPaths[advantage.icon].split('|')" :key="i">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                    </template>
                                </svg>
                            </span>
                            <h3 class="mt-5 text-lg font-semibold text-mist-900">{{ advantage.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-mist-500">{{ advantage.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CÓMO FUNCIONA ==================== -->
        <ScrollSteps id="como-funciona" :steps="steps" title="Tu auto impecable en cuatro movimientos" />

        <!-- ==================== SERVICIOS ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                    <div class="max-w-xl">
                        <span class="wa-eyebrow">Servicios</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Elige cómo cuidar tu auto</h2>
                    </div>
                    <Link :href="route('landing.servicios')" class="wa-btn-ghost w-fit">
                        Ver todos los servicios
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                        </svg>
                    </Link>
                </div>

                <ScrollCarousel v-if="modalities.length" label="Servicios WashApp" class="mt-8">
                    <Link
                        v-for="modality in modalities"
                        :key="modality.id"
                        :href="route('landing.agendar', { service: modality.id })"
                        class="wa-scroll-card group flex w-[80%] shrink-0 snap-start flex-col rounded-3xl border border-white/70 bg-white/80 p-6 shadow-soft backdrop-blur-md transition-shadow hover:shadow-lift sm:w-[46%] lg:w-[38%]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <span
                                class="grid h-11 w-11 place-items-center rounded-2xl"
                                :class="modality.tone === 'sage' ? 'bg-sage-100 text-sage-600' : 'bg-cloud-100 text-cloud-600'"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <template v-for="(d, i) in iconPaths[modality.icon].split('|')" :key="i">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                    </template>
                                </svg>
                            </span>
                            <span class="wa-chip whitespace-nowrap !px-3 !py-1 text-[10px]">{{ modality.tag }}</span>
                        </div>

                        <h3 class="mt-5 text-xl font-semibold tracking-tight text-mist-900">{{ modality.name }}</h3>
                        <div class="mt-2 flex-1">
                            <p class="line-clamp-2 text-sm leading-relaxed text-mist-500">{{ modality.description }}</p>
                        </div>

                        <div class="mt-5 flex items-end justify-between gap-3 border-t border-mist-200/70 pt-4">
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-mist-400">Desde</p>
                                <p class="text-lg font-semibold text-mist-900">${{ nf.format(modality.minPrice) }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-cloud-600 transition-all group-hover:gap-2.5">
                                Agendar
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </ScrollCarousel>

                <p
                    v-else
                    class="mt-8 rounded-3xl border border-dashed border-mist-300 bg-white/60 p-8 text-center text-sm text-mist-500"
                >
                    Estamos actualizando el catálogo. Vuelve en un momento.
                </p>
            </div>
        </section>

        <!-- ==================== IMPACTO ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div
                    class="relative overflow-hidden rounded-3xl border border-sage-300/60 bg-gradient-to-br from-sage-700 via-sage-600 to-cloud-800 p-8 text-white shadow-lift sm:p-10"
                    data-motion="rise"
                >
                    <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-sage-300/20 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-cloud-300/20 blur-3xl"></div>

                    <div class="relative grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-sage-50">
                                <i class="fa-solid fa-leaf"></i> Ecológico y consciente
                            </span>
                            <h2 class="mt-5 text-2xl font-semibold tracking-tight sm:text-3xl">
                                Cada lavado ahorra 85 litros de agua
                            </h2>
                            <p class="mt-3 max-w-lg text-sm leading-relaxed text-sage-50/85">
                                Lavar con manguera consume hasta 100 litros. Nosotros usamos 15 o cero en el lavado en
                                seco, con productos biodegradables.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-3xl font-semibold tracking-tight">{{ nf.format(litersSaved) }} L</p>
                                <p class="mt-1 text-[11px] uppercase tracking-wider text-sage-100/80">
                                    Ahorrados por la comunidad
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-xl font-semibold">15 L</p>
                                <p class="text-[11px] uppercase tracking-wider text-sage-100/80">Por lavado</p>
                            </div>
                            <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-xl font-semibold">0 L</p>
                                <p class="text-[11px] uppercase tracking-wider text-sage-100/80">En seco</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== PREMIOS ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                    <div class="max-w-xl">
                        <span class="wa-eyebrow">
                            <i class="fa-solid fa-gift"></i> Premios
                        </span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Tus lavados se vuelven recompensas</h2>
                        <p class="mt-3 text-sm text-mist-500">Cada servicio suma puntos automáticamente a tu cuenta.</p>
                    </div>
                    <Link :href="route('landing.premios')" class="wa-btn-ghost w-fit">
                        Ver catálogo de premios
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                        </svg>
                    </Link>
                </div>

                <div v-if="rewards.length" class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                    <div
                        v-for="(reward, index) in rewards"
                        :key="reward.id"
                        data-motion="card"
                        :data-motion-delay="index * 0.06"
                    >
                        <Link
                            :href="route('landing.premios')"
                            class="group block overflow-hidden rounded-2xl border border-white/70 bg-white/80 shadow-soft backdrop-blur-md transition-transform duration-300 hover:-translate-y-1 sm:rounded-3xl"
                        >
                            <div class="relative aspect-square overflow-hidden bg-mist-100 sm:aspect-[4/3]">
                                <img
                                    :src="reward.image_url"
                                    :alt="reward.name"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-cloud-700 shadow-sm backdrop-blur sm:left-3 sm:top-3 sm:px-3 sm:py-1 sm:text-xs">
                                    <i class="fa-solid fa-star text-[8px] text-sand-400 sm:text-[10px]"></i>
                                    {{ nf.format(reward.points_cost) }} pts
                                </span>
                            </div>
                            <p class="line-clamp-2 p-3 text-sm font-semibold text-mist-900 sm:p-4 sm:text-base">{{ reward.name }}</p>
                        </Link>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-8 rounded-3xl border border-dashed border-mist-300 bg-white/60 p-8 text-center text-sm text-mist-500"
                >
                    Estamos preparando nuevos premios. Vuelve pronto.
                </div>
            </div>
        </section>

        <!-- ==================== TESTIMONIOS ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                    <div class="max-w-xl">
                        <span class="wa-eyebrow">Testimonios</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Tiempo recuperado, autos impecables</h2>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-mist-500">
                        <div class="flex text-sand-400">
                            <svg v-for="i in 5" :key="i" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" />
                            </svg>
                        </div>
                        <span>4.9 de 320 reseñas</span>
                    </div>
                </div>

                <ScrollCarousel label="Testimonios de clientes" class="mt-8">
                    <figure
                        v-for="testimonial in testimonials"
                        :key="testimonial.author"
                        class="wa-scroll-card flex w-[85%] shrink-0 snap-start flex-col rounded-3xl border border-white/70 bg-white/80 p-6 shadow-soft backdrop-blur-md sm:w-[48%] lg:w-[32%]"
                    >
                        <div class="flex text-sand-400">
                            <svg v-for="i in 5" :key="i" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" />
                            </svg>
                        </div>
                        <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-mist-600">
                            “{{ testimonial.quote }}”
                        </blockquote>
                        <figcaption class="mt-5 flex items-center gap-3 border-t border-mist-200/70 pt-4">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-cloud-100 text-sm font-semibold text-cloud-700">
                                {{ testimonial.author.charAt(0) }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-mist-900">{{ testimonial.author }}</p>
                                <p class="text-xs text-mist-400">{{ testimonial.city }}</p>
                            </div>
                        </figcaption>
                    </figure>
                </ScrollCarousel>
            </div>
        </section>

        <!-- ==================== CTA FINAL ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-24">
            <div class="mx-auto max-w-7xl">
                <div
                    class="relative overflow-hidden rounded-3xl border border-white/70 bg-gradient-to-br from-white/90 via-cloud-50/80 to-sage-50/80 p-8 text-center shadow-soft backdrop-blur-md sm:p-12"
                    data-reveal
                >
                    <div class="pointer-events-none absolute -left-16 -top-16 h-56 w-56 rounded-full bg-sage-200/40 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-20 -right-10 h-56 w-56 rounded-full bg-cloud-200/50 blur-3xl"></div>

                    <div class="relative mx-auto max-w-2xl">
                        <h2 class="wa-title text-3xl sm:text-4xl">Tu próximo lavado cabe en tu agenda</h2>
                        <p class="mt-3 text-mist-500">
                            Elige tu vehículo y el horario. Nosotros llegamos a donde estés.
                        </p>
                        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                            <Link :href="route('landing.agendar')" class="wa-btn-primary text-base">
                                Agendar ahora
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </Link>
                            <Link :href="route('landing.contacto')" class="wa-btn-soft text-base">
                                Hablar con el equipo
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>

<style scoped>
.hero-word {
    display: inline-block;
    animation: hero-word-in 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes hero-word-in {
    from {
        opacity: 0;
        transform: translateY(0.55em) rotate(1.5deg);
    }
    to {
        opacity: 1;
        transform: translateY(0) rotate(0);
    }
}

.typing-caret {
    display: inline-block;
    width: 2px;
    height: 1em;
    margin-left: 4px;
    border-radius: 2px;
    background-color: currentColor;
    animation: caret-blink 1s steps(2, start) infinite;
}

@keyframes caret-blink {
    50% {
        opacity: 0;
    }
}
</style>
