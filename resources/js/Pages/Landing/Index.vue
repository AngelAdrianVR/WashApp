<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    packages: { type: Array, default: () => [] },
    rewardsPreview: { type: Array, default: () => [] },
});

const nf = new Intl.NumberFormat('es-MX');

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

// Membresías: requieren cuenta, o se pueden acordar por WhatsApp
const WHATSAPP_NUMBER = '523312155731';
const whatsappLink = (message) =>
    `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;

const membershipWhatsapp = (planName) =>
    whatsappLink(`Hola, me interesa la membresía ${planName} de WashApp. ¿Me pueden dar más información?`);

const fleetWhatsapp = () =>
    whatsappLink('Hola, quiero cotizar el servicio de lavado para una flotilla de varios vehículos.');

/* ---------------------------------------------------------
   Consumo de agua: manguera tradicional vs. WashApp
   --------------------------------------------------------- */
const LITERS_TRADITIONAL = 100;
const LITERS_WASHAPP = 15;
const LITERS_SAVED_PER_WASH = LITERS_TRADITIONAL - LITERS_WASHAPP; // 85 L
const WASHES_COMPLETED = 12400;

const litersTarget = WASHES_COMPLETED * LITERS_SAVED_PER_WASH;

/* ---------------------------------------------------------
   Contador ecológico: litros de agua ahorrados
   --------------------------------------------------------- */
const litersSaved = ref(0);
let counterFrame = null;
let liveTimer = null;

const animateCounter = () => {
    const duration = 1800;
    const start = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        // easeOutCubic para un conteo que desacelera al final
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
    // Simula el ahorro colectivo en tiempo real
    liveTimer = setInterval(() => {
        litersSaved.value += Math.floor(Math.random() * 4) + 1;
    }, 2600);
});

onUnmounted(() => {
    if (counterFrame) cancelAnimationFrame(counterFrame);
    if (liveTimer) clearInterval(liveTimer);
});

const garrafones = computed(() => Math.round(litersSaved.value / 20));

/* ---------------------------------------------------------
   Hero: palabra por palabra + línea de texto animada
   --------------------------------------------------------- */
const heroLineOne = ['Cuidamos', 'tu', 'auto.'];

const locations = ['tu casa', 'tu oficina', 'tu edificio', 'tu estacionamiento'];
const typedLocation = ref('');
const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

onMounted(() => {
    if (prefersReducedMotion) {
        typedLocation.value = locations[0];
        return;
    }

    typingTimer = setTimeout(typeLoop, 600);
});

onUnmounted(() => {
    if (typingTimer) clearTimeout(typingTimer);
});

/* ---------------------------------------------------------
   Contenido de la página
   --------------------------------------------------------- */
const stats = [
    { value: '4.9/5', label: 'Satisfacción' },
    { value: '50 min', label: 'Promedio por servicio' },
    { value: '15 L', label: 'Agua por lavado' },
    { value: '100%', label: 'Pagos digitales' },
];

const trustSignals = [
    {
        title: '100% digital y sin efectivo',
        description: 'Reservas, pagas y gestionas todo desde la app. Cero filas, cero cambio, cero fricción.',
        icon: 'wallet',
        tone: 'cloud',
    },
    {
        title: 'Operadores verificados',
        description: 'Cada especialista pasa por validación de identidad y capacitación antes de tocar tu auto.',
        icon: 'shield',
        tone: 'sage',
    },
    {
        title: 'Seguro de responsabilidad civil',
        description: 'Cada servicio está cubierto ante cualquier eventualidad. Tu tranquilidad está incluida.',
        icon: 'umbrella',
        tone: 'sand',
    },
];

const steps = [
    {
        number: '01',
        title: 'Elige tu vehículo',
        description: 'Marca, modelo y tipo para calcular al instante precio base y duración estimada.',
    },
    {
        number: '02',
        title: 'Programa el bloque',
        description: 'Selecciona día y bloque de 2 horas. Tú sigues con tu día, nosotros llegamos a tu ubicación.',
    },
    {
        number: '03',
        title: 'Llega tu operador',
        description: 'Un especialista con perfil verificado se presenta en la dirección que indicaste.',
    },
    {
        number: '04',
        title: 'Inspección walkaround',
        description: 'Registro fotográfico antes y después para documentar el estado de tu auto.',
    },
    {
        number: '05',
        title: 'Garantía de satisfacción',
        description: 'Si algo no quedó como esperabas, lo corregimos sin costo dentro de las 24 h.',
    },
];

// Modalidades que llegan desde la base de datos (paquetes de servicio)
const modalityStyle = {
    Seco: { tag: 'Sin una gota de agua', icon: 'leaf', tone: 'sage' },
    Agua: { tag: 'Bajo consumo', icon: 'drop', tone: 'cloud' },
};

const modalities = computed(() =>
    props.packages.map((service) => ({
        id: service.id,
        name: service.name,
        description: service.description,
        price: `Desde $${nf.format(service.min_price)}`,
        ...(modalityStyle[service.type] ?? modalityStyle.Seco),
    }))
);

const memberships = [
    {
        name: 'Esencial',
        price: '$399',
        period: '/mes',
        tagline: 'Mantenimiento constante sin pensar en ello.',
        washes: '2 lavados ecológicos al mes',
        features: [
            '2 lavados ecológicos en seco',
            'Agenda prioritaria entre semana',
            'Reprogramación sin costo',
            'Pago automático con tarjeta',
        ],
        popular: false,
    },
    {
        name: 'Equilibrio',
        price: '$699',
        period: '/mes',
        tagline: 'El favorito de quienes viven sobre ruedas.',
        washes: '4 lavados + 1 encerado',
        features: [
            '4 lavados al mes (seco o mixto)',
            '1 encerado express incluido',
            'Aspirado profundo de interiores',
            '15% de descuento en adicionales',
            'Soporte preferente por WhatsApp',
        ],
        popular: true,
    },
    {
        name: 'Empresa / Flota',
        price: 'A tu medida',
        period: '',
        tagline: 'Flotillas siempre presentables, sin logística interna.',
        washes: 'Desde 5 vehículos',
        features: [
            'Facturación mensual consolidada',
            'Panel de control por vehículo',
            'Ejecutivo de cuenta dedicado',
            'Reportes fotográficos por unidad',
        ],
        popular: false,
    },
];

const testimonials = [
    {
        quote:
            'Agendé desde la app y no tuve que mover el auto. El operador llegó puntual, tomó fotos antes y después, y lo dejó impecable mientras yo trabajaba.',
        author: 'Ana L.',
        city: 'Zapopan',
        rating: 5,
    },
    {
        quote:
            'La inspección con fotos me dio mucha confianza. El lavado en seco quedó perfecto y sin una sola gota de agua en el estacionamiento del edificio.',
        author: 'Carlos G.',
        city: 'Guadalajara',
        rating: 5,
    },
    {
        quote:
            'Pagué en línea, llegaron en el bloque que elegí y la garantía funcionó: me retocaron una mancha sin discusión al día siguiente.',
        author: 'Laura M.',
        city: 'Tlaquepaque',
        rating: 5,
    },
];

const iconPaths = {
    wallet: 'M3 8.5A2.5 2.5 0 015.5 6H19a1 1 0 011 1v10a1 1 0 01-1 1H5.5A2.5 2.5 0 013 15.5v-7z|M16 12h2.5',
    shield: 'M12 3l7 3v5.5c0 4.2-2.9 7.6-7 9.5-4.1-1.9-7-5.3-7-9.5V6l7-3z|M9 12l2 2 4-4',
    umbrella: 'M12 4a8 8 0 018 8H4a8 8 0 018-8z|M12 12v6a2 2 0 004 0',
    leaf: 'M20 4c0 8-5 12-11 12H5c0-7 5-11 11-11h4z|M5 20c1-4 4-7 8-9',
    drop: 'M12 3s6 6.2 6 10.5A6 6 0 016 13.5C6 9.2 12 3 12 3z',
};
</script>

<template>
    <LandingLayout title="Inicio">
        <!-- ==================== HERO ==================== -->
        <section class="relative overflow-hidden px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="grid items-center gap-10 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14 lg:py-16">
                    <!-- Copy -->
                    <div data-reveal>
                        <span class="wa-eyebrow">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sage-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-sage-600"></span>
                            </span>
                            Eco Car Wash Móvil · 100% ecológico
                        </span>

                        <h1 class="wa-title mt-6 text-4xl leading-[1.05] sm:text-5xl lg:text-[3.75rem]">
                            <span class="block">
                                <span
                                    v-for="(word, index) in heroLineOne"
                                    :key="`hero-l1-${index}`"
                                    class="hero-word"
                                    :style="{ animationDelay: `${index * 110}ms` }"
                                >{{ word }}&nbsp;</span>
                            </span>
                            <span class="mt-1 block">
                                <span
                                    class="hero-word bg-gradient-to-r from-cloud-600 via-cloud-500 to-sage-500 bg-clip-text text-transparent"
                                    :style="{ animationDelay: '480ms' }"
                                >Cuidamos el agua.</span>
                            </span>
                        </h1>

                        <p class="mt-5 flex flex-wrap items-baseline gap-x-2 text-lg text-mist-600 sm:text-xl">
                            <span>Llegamos a</span>
                            <span class="inline-flex items-baseline font-semibold text-cloud-700">
                                {{ typedLocation || '\u00a0' }}<span class="typing-caret" aria-hidden="true"></span>
                            </span>
                        </p>
                        <p class="mt-1 text-sm font-medium uppercase tracking-[0.16em] text-mist-400">
                            Sin que tengas que mover el auto
                        </p>

                        <p class="mt-4 max-w-xl text-base leading-relaxed text-mist-500">
                            Agenda un bloque, sigue con tu trabajo o tu descanso y nosotros lavamos tu auto en tu
                            puerta con productos biodegradables y solo 15 litros de agua.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="wa-chip border-sage-200 bg-sage-50/90 text-sage-700">
                                <i class="fa-solid fa-leaf"></i> 85 L ahorrados por lavado
                            </span>
                            <span class="wa-chip border-cloud-200 bg-cloud-50/90 text-cloud-700">
                                <i class="fa-solid fa-droplet"></i> 15 L vs 100 L tradicionales
                            </span>
                        </div>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
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

                        <ul class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-mist-500">
                            <li class="flex items-center gap-2">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-sage-100 text-sage-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                Sin efectivo
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-cloud-100 text-cloud-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                Operadores verificados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-sand-100 text-sand-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                Seguro de RC
                            </li>
                        </ul>
                    </div>

                    <!-- Visual -->
                    <div class="relative" data-reveal>
                        <div class="absolute -left-6 -top-6 h-24 w-24 rounded-full bg-sage-200/50 blur-2xl"></div>
                        <div class="absolute -bottom-8 right-2 h-32 w-32 rounded-full bg-cloud-200/60 blur-3xl"></div>

                        <div class="relative wa-card p-3">
                            <div class="overflow-hidden rounded-[1.35rem]">
                                <img
                                    src="/images/auto-limpio-1.png"
                                    alt="Vehículo recién lavado por WashApp"
                                    class="h-[300px] w-full object-cover sm:h-[380px]"
                                />
                            </div>

                            <!-- Tarjeta flotante: estado del servicio -->
                            <div class="absolute -left-2 top-6 w-[220px] rounded-2xl border border-white/70 bg-white/85 p-4 shadow-lift backdrop-blur-md sm:-left-8">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sage-400 opacity-70"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-sage-500"></span>
                                    </span>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-mist-400">
                                        En servicio
                                    </span>
                                </div>
                                <p class="mt-2 text-sm font-semibold text-mist-900">Lavado ecológico en seco</p>
                                <p class="mt-1 text-xs text-mist-500">Av. Américas 1435 · Depto 402</p>
                                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-mist-100">
                                    <div class="h-full w-2/3 rounded-full bg-gradient-to-r from-sage-400 to-cloud-400"></div>
                                </div>
                            </div>

                            <!-- Tarjeta flotante: ahorro -->
                            <div class="absolute -bottom-6 right-2 rounded-2xl border border-white/70 bg-sage-600/95 px-4 py-3 text-white shadow-lift backdrop-blur-md sm:right-6">
                                <p class="text-[11px] font-medium uppercase tracking-wider text-sage-100">Agua por lavado</p>
                                <p class="text-xl font-semibold">15 litros</p>
                                <p class="text-[10px] text-sage-100/85">vs 100 L tradicionales</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== STATS ==================== -->
        <section class="px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-2 divide-mist-200/70 rounded-3xl border border-white/70 bg-white/65 py-6 shadow-soft backdrop-blur-md sm:grid-cols-4 sm:divide-x" data-reveal>
                    <div v-for="stat in stats" :key="stat.label" class="px-6 py-3 text-center">
                        <p class="text-2xl font-semibold text-cloud-700 sm:text-3xl">{{ stat.value }}</p>
                        <p class="mt-1 text-xs uppercase tracking-wider text-mist-400">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CONTADOR ECOLÓGICO ==================== -->
        <section id="impacto" class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div
                    class="relative overflow-hidden rounded-3xl border border-sage-300/60 bg-gradient-to-br from-sage-700 via-sage-600 to-cloud-800 p-8 text-white shadow-lift sm:p-12"
                    data-reveal
                >
                    <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-sage-300/20 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-cloud-300/20 blur-3xl"></div>

                    <div class="relative grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-sage-50">
                                <i class="fa-solid fa-leaf"></i> Impacto ecológico
                            </span>
                            <h2 class="mt-5 text-3xl font-semibold tracking-tight sm:text-4xl">
                                Cada lavado ahorra agua que no vuelve a la calle
                            </h2>
                            <p class="mt-4 max-w-xl text-sm leading-relaxed text-sage-50/85 sm:text-base">
                                Un lavado tradicional con manguera consume hasta 100 litros. Nuestro servicio de bajo
                                consumo usa 15 y el lavado en seco, cero. Cada cita ahorra 85 litros: este es el
                                ahorro acumulado de la comunidad WashApp.
                            </p>

                            <div class="mt-8 flex flex-wrap items-end gap-x-8 gap-y-4">
                                <div>
                                    <p class="text-4xl font-semibold tracking-tight sm:text-5xl">
                                        {{ nf.format(litersSaved) }}
                                        <span class="text-2xl font-medium text-sage-100">L</span>
                                    </p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-sage-100/80">
                                        Litros de agua ahorrados
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                    <p class="text-lg font-semibold">{{ nf.format(garrafones) }}</p>
                                    <p class="text-[11px] uppercase tracking-wider text-sage-100/80">
                                        Garrafones de 20 L
                                    </p>
                                </div>
                                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                                    <p class="text-lg font-semibold">85 L</p>
                                    <p class="text-[11px] uppercase tracking-wider text-sage-100/80">
                                        Ahorro por lavado
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-white/20 bg-white/10 p-6 backdrop-blur-md">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sage-100/80">
                                Comparativa por lavado
                            </p>

                            <div class="mt-5 space-y-5">
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="text-sage-50/90">Manguera tradicional</span>
                                        <span class="font-semibold">100 L</span>
                                    </div>
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/15">
                                        <div class="h-full w-full rounded-full bg-gradient-to-r from-rose-300/70 to-rose-200/60"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="text-sage-50/90">WashApp bajo consumo</span>
                                        <span class="font-semibold">15 L</span>
                                    </div>
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/15">
                                        <div class="h-full w-[15%] min-w-[14px] rounded-full bg-gradient-to-r from-cloud-200 to-cloud-100"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="text-sage-50/90">WashApp en seco</span>
                                        <span class="font-semibold">0 L</span>
                                    </div>
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/15">
                                        <div class="h-full w-[2%] min-w-[10px] rounded-full bg-white/70"></div>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-6 text-xs leading-relaxed text-sage-100/75">
                                Cálculo basado en 100 L por lavado con manguera y 15 L de consumo real registrado en
                                nuestros servicios: 85 L ahorrados en cada cita.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CONFIANZA ==================== -->
        <section class="px-3 pt-16 sm:px-5 sm:pt-20">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <span class="wa-eyebrow">Confianza operativa</span>
                    <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Diseñado para que no tengas que preocuparte</h2>
                    <p class="mt-4 text-mist-500">
                        Cada detalle del servicio está pensado para proteger tu auto, tu tiempo y tu información.
                    </p>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    <div
                        v-for="(signal, index) in trustSignals"
                        :key="signal.title"
                        class="wa-card p-7 transition-transform duration-300 hover:-translate-y-1"
                        data-reveal
                        :style="{ transitionDelay: `${index * 90}ms` }"
                    >
                        <span
                            class="grid h-12 w-12 place-items-center rounded-2xl"
                            :class="{
                                'bg-cloud-100 text-cloud-600': signal.tone === 'cloud',
                                'bg-sage-100 text-sage-600': signal.tone === 'sage',
                                'bg-sand-100 text-sand-600': signal.tone === 'sand',
                            }"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <template v-for="(d, i) in iconPaths[signal.icon].split('|')" :key="i">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                </template>
                            </svg>
                        </span>
                        <h3 class="mt-5 text-lg font-semibold text-mist-900">{{ signal.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-mist-500">{{ signal.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CÓMO FUNCIONA ==================== -->
        <section class="px-3 pt-20 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                    <div class="max-w-xl">
                        <span class="wa-eyebrow">Cómo funciona</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Cinco pasos, cero fricción</h2>
                    </div>
                    <p class="max-w-sm text-sm leading-relaxed text-mist-500">
                        Reservas en menos de 2 minutos y recibes seguimiento en cada fase del servicio.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.number"
                        class="wa-panel relative p-6"
                        data-reveal
                        :style="{ transitionDelay: `${index * 70}ms` }"
                    >
                        <span class="text-xs font-semibold tracking-[0.2em] text-sage-500">{{ step.number }}</span>
                        <h3 class="mt-3 text-base font-semibold text-mist-900">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-mist-500">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== MODALIDADES ==================== -->
        <section class="px-3 pt-20 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <span class="wa-eyebrow">Modalidades</span>
                    <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Elige cómo quieres cuidar tu auto</h2>
                    <p class="mt-4 text-mist-500">
                        Dos formas de lavar con el mismo estándar: impecable, seguro y con el menor impacto posible.
                    </p>
                </div>

                <div class="mt-12 grid gap-5 lg:grid-cols-2">
                    <Link
                        v-for="(modality, index) in modalities"
                        :key="modality.name"
                        :href="route('landing.servicios')"
                        class="group wa-card flex flex-col p-7 transition-all duration-300 hover:-translate-y-1 hover:shadow-lift sm:p-9"
                        data-reveal
                        :style="{ transitionDelay: `${index * 100}ms` }"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <span
                                class="grid h-12 w-12 place-items-center rounded-2xl"
                                :class="modality.tone === 'sage' ? 'bg-sage-100 text-sage-600' : 'bg-cloud-100 text-cloud-600'"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <template v-for="(d, i) in iconPaths[modality.icon].split('|')" :key="i">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                    </template>
                                </svg>
                            </span>
                            <span class="wa-chip">{{ modality.tag }}</span>
                        </div>

                        <h3 class="mt-6 text-2xl font-semibold tracking-tight text-mist-900">{{ modality.name }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-mist-500">{{ modality.description }}</p>

                        <div class="mt-6 flex items-center justify-between border-t border-mist-200/70 pt-5">
                            <span class="text-lg font-semibold text-mist-900">{{ modality.price }}</span>
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-cloud-600 transition group-hover:gap-3">
                                Conocer detalles
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ==================== MEMBRESÍAS ==================== -->
        <section id="membresias" class="px-3 pt-20 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <span class="wa-eyebrow">Membresías</span>
                    <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Un plan mensual y tu auto siempre listo</h2>
                    <p class="mt-4 text-mist-500">
                        Suscripciones recurrentes con precio fijo, agenda prioritaria y cancelación en cualquier
                        momento desde tu cuenta.
                    </p>
                </div>

                <div class="mt-12 grid gap-5 lg:grid-cols-3">
                    <div
                        v-for="(plan, index) in memberships"
                        :key="plan.name"
                        class="relative flex flex-col rounded-3xl border p-7 transition-all duration-300 sm:p-8"
                        :class="
                            plan.popular
                                ? 'border-cloud-700/30 bg-gradient-to-br from-cloud-600 to-cloud-800 text-white shadow-lift lg:-mt-4 lg:mb-4'
                                : 'wa-card hover:-translate-y-1'
                        "
                        data-reveal
                        :style="{ transitionDelay: `${index * 90}ms` }"
                    >
                        <span
                            v-if="plan.popular"
                            class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-sage-400 px-4 py-1 text-[11px] font-semibold uppercase tracking-wider text-sage-900"
                        >
                            Más elegido
                        </span>

                        <h3 class="text-xl font-semibold" :class="plan.popular ? 'text-white' : 'text-mist-900'">
                            {{ plan.name }}
                        </h3>
                        <p class="mt-2 text-sm" :class="plan.popular ? 'text-mist-300' : 'text-mist-500'">
                            {{ plan.tagline }}
                        </p>

                        <div class="mt-6 flex items-end gap-1">
                            <span class="text-4xl font-semibold tracking-tight">{{ plan.price }}</span>
                            <span class="pb-1 text-sm" :class="plan.popular ? 'text-mist-300' : 'text-mist-500'">
                                {{ plan.period }}
                            </span>
                        </div>
                        <p
                            class="mt-2 rounded-2xl px-3 py-2 text-xs font-medium"
                            :class="plan.popular ? 'bg-white/10 text-sage-100' : 'bg-sage-50 text-sage-700'"
                        >
                            {{ plan.washes }}
                        </p>

                        <ul class="mt-6 flex-1 space-y-3 text-sm">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5">
                                <svg
                                    class="mt-0.5 h-4 w-4 flex-shrink-0"
                                    :class="plan.popular ? 'text-sage-300' : 'text-sage-500'"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.4"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span :class="plan.popular ? 'text-mist-100' : 'text-mist-600'">{{ feature }}</span>
                            </li>
                        </ul>

                        <div class="mt-7 space-y-2">
                            <Link
                                :href="isAuthenticated ? route('dashboard') : route('login')"
                                class="w-full"
                                :class="plan.popular ? 'wa-btn-sage' : 'wa-btn-primary'"
                            >
                                <i class="fa-solid fa-lock text-xs opacity-80"></i>
                                {{ isAuthenticated ? 'Gestionar mi plan' : 'Suscribirme' }}
                            </Link>
                            <a
                                :href="membershipWhatsapp(plan.name)"
                                target="_blank"
                                rel="noopener"
                                class="wa-btn-soft w-full"
                                :class="plan.popular ? '!border-white/25 !bg-white/10 !text-white hover:!bg-white/20' : ''"
                            >
                                <i class="fa-brands fa-whatsapp"></i>
                                Acordar por WhatsApp
                            </a>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-mist-400" data-reveal>
                    Las membresías requieren una cuenta WashApp · Sin plazos forzosos · Se cancela desde tu perfil
                </p>

                <!-- Flotillas -->
                <div class="mt-6 grid gap-6 rounded-3xl border border-mist-200/80 bg-white/70 p-7 shadow-soft backdrop-blur-md sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center" data-reveal>
                    <div>
                        <span class="wa-eyebrow">
                            <i class="fa-solid fa-truck-fast"></i> Flotillas y empresas
                        </span>
                        <h3 class="wa-title mt-3 text-xl sm:text-2xl">¿Tienes varios autos? Armamos un plan a tu medida</h3>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-mist-500">
                            Desde 3 vehículos: precio por volumen, facturación mensual consolidada y un solo punto de
                            contacto para toda tu flotilla.
                        </p>
                        <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-mist-600">
                            <li class="flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-sage-500"></i> Precio por volumen
                            </li>
                            <li class="flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-sage-500"></i> Factura mensual
                            </li>
                            <li class="flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-sage-500"></i> Reportes por unidad
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                        <a :href="fleetWhatsapp()" target="_blank" rel="noopener" class="wa-btn-sage whitespace-nowrap">
                            <i class="fa-brands fa-whatsapp"></i>
                            Cotizar por WhatsApp
                        </a>
                        <Link :href="route('landing.contacto')" class="wa-btn-soft whitespace-nowrap">
                            Escribir al equipo
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== FIDELIDAD (TEASER) ==================== -->
        <section id="fidelidad" class="px-3 pt-20 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="wa-panel relative overflow-hidden p-8 sm:p-10" data-reveal>
                    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-sage-200/40 blur-3xl"></div>

                    <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                        <div>
                            <span class="wa-eyebrow">
                                <i class="fa-solid fa-gift"></i> Programa de fidelidad
                            </span>
                            <h2 class="wa-title mt-4 text-2xl sm:text-3xl">
                                Por ser cliente frecuente, ganas premios
                            </h2>
                            <p class="mt-3 max-w-xl text-sm leading-relaxed text-mist-500">
                                Cada servicio suma puntos automáticamente a tu cuenta. Acumúlalos y canjéalos por
                                lavados gratis, tratamientos y hasta una membresía completa.
                            </p>

                            <ul class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm text-mist-600">
                                <li class="flex items-center gap-2">
                                    <i class="fa-solid fa-star text-sand-400"></i> Puntos en cada lavado
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fa-solid fa-gift text-sage-500"></i> Premios desde 250 pts
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fa-solid fa-mobile-screen text-cloud-500"></i> Canje desde tu panel
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-col items-start gap-3 lg:items-end">
                            <div class="flex -space-x-2">
                                <img
                                    v-for="reward in rewardsPreview"
                                    :key="reward.id"
                                    :src="reward.image_url"
                                    :alt="reward.name"
                                    class="h-12 w-12 rounded-2xl border-2 border-white bg-white object-cover shadow-soft"
                                />
                            </div>
                            <Link :href="route('landing.premios')" class="wa-btn-primary whitespace-nowrap">
                                Ver premios y recompensas
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== TESTIMONIOS ==================== -->
        <section class="px-3 pt-20 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                    <div class="max-w-xl">
                        <span class="wa-eyebrow">Testimonios</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Lo que se siente recuperar tu tiempo</h2>
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

                <div class="mt-10 grid gap-5 md:grid-cols-3">
                    <figure
                        v-for="(testimonial, index) in testimonials"
                        :key="testimonial.author"
                        class="wa-card flex flex-col p-7"
                        data-reveal
                        :style="{ transitionDelay: `${index * 90}ms` }"
                    >
                        <div class="flex text-sand-400">
                            <svg v-for="i in testimonial.rating" :key="i" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z" />
                            </svg>
                        </div>
                        <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-mist-600">
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
                </div>
            </div>
        </section>

        <!-- ==================== CTA FINAL ==================== -->
        <section class="px-3 pt-20 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <div
                    class="relative overflow-hidden rounded-3xl border border-white/70 bg-gradient-to-br from-white/90 via-cloud-50/80 to-sage-50/80 p-8 text-center shadow-soft backdrop-blur-md sm:p-14"
                    data-reveal
                >
                    <div class="pointer-events-none absolute -left-16 -top-16 h-56 w-56 rounded-full bg-sage-200/40 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-20 -right-10 h-56 w-56 rounded-full bg-cloud-200/50 blur-3xl"></div>

                    <div class="relative mx-auto max-w-2xl">
                        <h2 class="wa-title text-3xl sm:text-4xl">Tu próximo lavado cabe en cualquier hueco de tu agenda</h2>
                        <p class="mt-4 text-mist-500">
                            Elige tu vehículo, el bloque horario y listo. Nosotros nos encargamos del resto mientras
                            tú sigues con lo importante.
                        </p>
                        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
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
