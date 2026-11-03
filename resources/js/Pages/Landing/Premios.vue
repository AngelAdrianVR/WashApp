<script setup>
import { computed } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    rewards: { type: Array, default: () => [] },
});

const nf = new Intl.NumberFormat('es-MX');

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);

const loyaltySteps = [
    {
        title: 'Agenda y lava',
        description: 'Cada servicio completado suma puntos automáticamente a tu cuenta.',
        icon: 'car',
    },
    {
        title: 'Acumula puntos',
        description: 'Sumas puntos por el lavado base y por cada servicio adicional que agregues.',
        icon: 'star',
    },
    {
        title: 'Canjea tu premio',
        description: 'Elige la recompensa y canjéala desde tu panel; se agenda en tu siguiente cita.',
        icon: 'gift',
    },
];

const iconPaths = {
    car: 'M4 13l1.6-4.2A2 2 0 017.5 7.5h9A2 2 0 0118.4 8.8L20 13v5h-3v-1H7v1H4v-5z|M7 13h10',
    star: 'M12 3l2.5 5.6 6.1.7-4.5 4.2 1.2 6-5.3-3-5.3 3 1.2-6L3.4 9.3l6.1-.7L12 3z',
    gift: 'M4 9h16v4H4z|M5 13h14v8H5z|M12 9v12|M12 9C10 9 7.5 8.4 7.5 6.3A2.3 2.3 0 0112 5.5 2.3 2.3 0 0116.5 6.3C16.5 8.4 14 9 12 9z',
};
</script>

<template>
    <LandingLayout title="Premios">
        <div class="px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <!-- ==================== ENCABEZADO ==================== -->
                <header class="pt-8 text-center sm:pt-10" data-reveal>
                    <span class="wa-eyebrow">
                        <i class="fa-solid fa-gift"></i> Programa de fidelidad
                    </span>
                    <h1 class="wa-title mx-auto mt-5 max-w-3xl text-4xl leading-tight sm:text-5xl">
                        Tus lavados se convierten en
                        <span class="block bg-gradient-to-r from-cloud-600 to-sage-500 bg-clip-text text-transparent">
                            premios reales
                        </span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-mist-500">
                        Cada servicio suma puntos a tu cuenta. Acumúlalos y canjéalos por lavados gratis, tratamientos
                        y membresías completas.
                    </p>

                    <div class="mt-7 flex flex-wrap justify-center gap-2">
                        <template v-if="currentUser">
                            <Link :href="route('dashboard')" class="wa-btn-primary">Ver mis puntos</Link>
                            <Link :href="route('landing.agendar')" class="wa-btn-soft">Agendar servicio</Link>
                        </template>
                        <template v-else>
                            <Link :href="route('register')" class="wa-btn-primary">Registrarse</Link>
                            <Link :href="route('login')" class="wa-btn-soft">Ingresar</Link>
                        </template>
                    </div>
                </header>

                <!-- ==================== CÓMO FUNCIONA ==================== -->
                <section class="mt-12 grid gap-4 sm:grid-cols-3" data-reveal>
                    <div v-for="(step, index) in loyaltySteps" :key="step.title" class="wa-panel p-6">
                        <div class="flex items-center justify-between">
                            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-cloud-100 text-cloud-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                    <template v-for="(d, i) in iconPaths[step.icon].split('|')" :key="i">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                    </template>
                                </svg>
                            </span>
                            <span class="text-xs font-semibold tracking-[0.2em] text-sage-500">0{{ index + 1 }}</span>
                        </div>
                        <h2 class="mt-4 text-base font-semibold text-mist-900">{{ step.title }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-mist-500">{{ step.description }}</p>
                    </div>
                </section>

                <!-- ==================== CATÁLOGO DE PREMIOS ==================== -->
                <section class="mt-16">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                        <div>
                            <h2 class="wa-title text-2xl sm:text-3xl">Catálogo de premios</h2>
                            <p class="mt-2 text-sm text-mist-500">
                                {{ rewards.length }} recompensas disponibles · ordenadas por puntos
                            </p>
                        </div>
                        <p class="text-xs uppercase tracking-[0.18em] text-mist-400">
                            Canje disponible desde tu panel
                        </p>
                    </div>

                    <div v-if="rewards.length" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="(reward, index) in rewards"
                            :key="reward.id"
                            class="group flex flex-col overflow-hidden rounded-3xl border border-white/70 bg-white/75 shadow-soft backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:shadow-lift"
                            data-reveal
                            :style="{ transitionDelay: `${index * 60}ms` }"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden bg-mist-100">
                                <img
                                    :src="reward.image_url"
                                    :alt="reward.name"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <span
                                    class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-cloud-700 shadow-sm backdrop-blur"
                                >
                                    <i class="fa-solid fa-star text-sand-400"></i>
                                    {{ nf.format(reward.points_cost) }} pts
                                </span>
                            </div>

                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="text-base font-semibold text-mist-900">{{ reward.name }}</h3>
                                <p class="mt-2 flex-1 text-sm leading-relaxed text-mist-500">{{ reward.description }}</p>

                                <Link v-if="currentUser" :href="route('dashboard')" class="wa-btn-sage mt-5 w-full">
                                    Canjear
                                </Link>
                                <Link v-else :href="route('login')" class="wa-btn-sage mt-5 w-full">
                                    Ingresar para canjear
                                </Link>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="mt-8 rounded-3xl border border-dashed border-mist-300 bg-white/60 p-10 text-center text-sm text-mist-500"
                    >
                        Estamos preparando nuevos premios. Vuelve pronto.
                    </div>

                    <p class="mt-6 text-center text-xs text-mist-400">
                        <i class="fa-solid fa-lock mr-1 text-mist-300"></i>
                        Necesitas una cuenta para canjear. El historial de puntos y los canjes se gestionan en tu panel.
                    </p>
                </section>

                <!-- ==================== CTA ==================== -->
                <section class="mt-16" data-reveal>
                    <div
                        class="relative overflow-hidden rounded-3xl border border-white/70 bg-gradient-to-br from-white/90 via-cloud-50/80 to-sage-50/80 p-8 text-center shadow-soft backdrop-blur-md sm:p-12"
                    >
                        <div class="pointer-events-none absolute -left-16 -top-16 h-52 w-52 rounded-full bg-sage-200/40 blur-3xl"></div>
                        <div class="relative mx-auto max-w-2xl">
                            <h2 class="wa-title text-2xl sm:text-3xl">Empieza a acumular desde tu próximo lavado</h2>
                            <p class="mt-3 text-sm text-mist-500">
                                Agenda en línea, paga seguro y recibe los puntos en tu cuenta al terminar el servicio.
                            </p>
                            <Link :href="route('landing.agendar')" class="wa-btn-primary mt-6">
                                Agendar ahora
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </LandingLayout>
</template>
