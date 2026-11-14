<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const promotions = ref([
    {
        title: '¡Primer Lavado con 50% de Descuento!',
        description:
            'Regístrate hoy y obtén la mitad de precio en tu primer servicio de Lavado Ecológico en Seco. La bienvenida perfecta para tu auto.',
        cta: 'Lo quiero',
        icon: '🎉',
    },
    {
        title: 'Plan Lealtad: Tu 6º Lavado es GRATIS',
        description:
            'Después de 5 servicios de cualquier tipo, el sexto lavado ecológico corre por nuestra cuenta. Sin trucos, solo un gracias.',
        cta: 'Acumular puntos',
        icon: '🎁',
    },
    {
        title: 'Regalo de Cumpleaños',
        description:
            'Tu auto también merece celebrar. Te regalamos un encerado express en cualquier servicio durante el mes de tu cumpleaños.',
        cta: 'Celebrar mi cumpleaños',
        icon: '🎂',
    },
    {
        title: 'Refiere y Gana',
        description:
            'Invita a un amigo y ambos reciben 25% de descuento en su próximo servicio cuando tu referido complete su primer lavado.',
        cta: 'Invitar amigos',
        icon: '🤝',
    },
]);
</script>

<template>
    <LandingLayout title="Promociones">
        <div class="px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <header class="pt-8 text-center sm:pt-10" data-reveal>
                    <span class="wa-eyebrow">Promociones</span>
                    <h1 class="wa-title mx-auto mt-5 max-w-3xl text-4xl leading-tight sm:text-5xl">
                        Más beneficios,
                        <span class="block bg-gradient-to-r from-cloud-500 to-sage-400 bg-clip-text text-transparent">
                            más brillo
                        </span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-mist-500">
                        Ser parte de WashApp tiene sus recompensas. Estas son las promociones activas para miembros de
                        la comunidad.
                    </p>
                </header>

                <section class="mt-12 grid gap-5 sm:grid-cols-2">
                    <article
                        v-for="(promo, index) in promotions"
                        :key="promo.title"
                        class="wa-card flex flex-col p-7 transition-all duration-300 hover:-translate-y-1 hover:shadow-lift sm:p-8"
                        data-reveal
                        :style="{ transitionDelay: `${index * 80}ms` }"
                    >
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-mist-100 text-2xl">
                            {{ promo.icon }}
                        </span>
                        <h2 class="mt-5 text-xl font-semibold text-mist-900">{{ promo.title }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-mist-500">{{ promo.description }}</p>
                        <Link
                            :href="route('register')"
                            class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-cloud-600 transition hover:gap-3 hover:text-cloud-700"
                        >
                            {{ promo.cta }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                            </svg>
                        </Link>
                    </article>
                </section>

                <section class="mt-16" data-reveal>
                    <div
                        class="relative overflow-hidden rounded-3xl border border-white/70 bg-gradient-to-br from-white/90 via-cloud-50/80 to-sage-50/80 p-8 text-center shadow-soft backdrop-blur-md sm:p-12"
                    >
                        <div class="pointer-events-none absolute -left-16 -top-16 h-52 w-52 rounded-full bg-sage-200/40 blur-3xl"></div>
                        <div class="relative mx-auto max-w-2xl">
                            <h2 class="wa-title text-3xl sm:text-4xl">¿Aún no eres miembro?</h2>
                            <p class="mt-4 text-mist-500">
                                Regístrate en menos de un minuto y empieza a disfrutar de todos estos beneficios hoy
                                mismo. Es rápido, fácil y completamente gratis.
                            </p>
                            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                                <Link :href="route('register')" class="wa-btn-primary text-base">
                                    Crear mi cuenta gratis
                                </Link>
                                <Link :href="route('landing.agendar')" class="wa-btn-soft text-base">
                                    Agendar ahora
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </LandingLayout>
</template>
