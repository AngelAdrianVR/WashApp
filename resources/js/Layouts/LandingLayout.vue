<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Link, Head, usePage } from '@inertiajs/vue3';

// --- Props ---
const props = defineProps({
    title: String,
});

// --- SEO Metadata ---
const siteName = 'WashApp';
const pageTitle = props.title || 'Lavado de autos a domicilio';
const socialTitle = `${pageTitle} · ${siteName}`;
const description =
    'Lavado de autos a domicilio 100% digital: ecológico, sin efectivo y con operadores verificados. Reserva en línea y recupera tu tiempo.';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user ?? null);
const firstName = computed(() => currentUser.value?.name?.split(' ')[0] ?? '');

// --- Navigation Links ---
const navigation = ref([
    { name: 'Inicio', routeName: 'landing.index' },
    { name: 'Servicios', routeName: 'landing.servicios' },
    { name: 'Agendar', routeName: 'landing.agendar' },
    { name: 'Premios', routeName: 'landing.premios' },
    { name: 'Contacto', routeName: 'landing.contacto' },
]);

const isMobileMenuOpen = ref(false);
const isScrolled = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 12;
};

let revealObserver = null;
let mutationObserver = null;

// Muestra con una transición suave los bloques marcados con [data-reveal].
const observeReveals = () => {
    revealObserver?.disconnect();

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    document.querySelectorAll('[data-reveal]:not(.is-revealed)').forEach((el) => revealObserver.observe(el));
};

// Los bloques que aparecen después (pasos del flujo, confirmaciones…) también se revelan.
const observeNewNodes = (node) => {
    if (node.nodeType !== Node.ELEMENT_NODE) return;

    if (node.matches?.('[data-reveal]:not(.is-revealed)')) {
        revealObserver.observe(node);
    }

    node.querySelectorAll?.('[data-reveal]:not(.is-revealed)').forEach((el) => revealObserver.observe(el));
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
    nextTick(() => {
        observeReveals();

        mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => mutation.addedNodes.forEach(observeNewNodes));
        });
        mutationObserver.observe(document.body, { childList: true, subtree: true });
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    revealObserver?.disconnect();
    mutationObserver?.disconnect();
});

// Al navegar con Inertia se montan elementos nuevos: se vuelven a observar.
watch(
    () => page.url,
    () => {
        isMobileMenuOpen.value = false;
        nextTick(observeReveals);
    }
);
</script>

<template>
    <Head :title="pageTitle">
        <meta name="description" :content="description" />
        <meta
            name="keywords"
            content="lavado de autos a domicilio, lavado ecológico en seco, car wash, lavado sin agua, agendar lavado, WashApp"
        />
        <meta name="author" content="WashApp" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="socialTitle" />
        <meta property="og:description" :content="description" />

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:title" :content="socialTitle" />
        <meta property="twitter:description" :content="description" />
    </Head>

    <div class="wa-page min-h-screen">
        <!-- =========== Header / Navigation =========== -->
        <header class="fixed inset-x-0 top-0 z-50 px-3 pt-3 sm:px-5 sm:pt-4">
            <nav
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 rounded-3xl border px-4 py-3 transition-all duration-300 sm:px-6"
                :class="
                    isScrolled
                        ? 'border-white/70 bg-white/85 shadow-soft backdrop-blur-xl'
                        : 'border-white/40 bg-white/55 backdrop-blur-md'
                "
            >
                <!-- Logo -->
                <Link :href="route('landing.index')" class="group flex items-center" aria-label="WashApp · Inicio">
                    <img
                        src="/images/logo-washapp.png"
                        alt="WashApp · Eco Car Wash Móvil"
                        class="h-12 w-auto transition-transform duration-300 group-hover:scale-[1.03] sm:h-14"
                    />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden items-center gap-1 lg:flex">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="route(item.routeName)"
                        class="rounded-2xl px-4 py-2 text-sm font-medium transition-colors duration-300"
                        :class="
                            route().current(item.routeName)
                                ? 'bg-mist-100/80 text-mist-900'
                                : 'text-mist-600 hover:bg-white/70 hover:text-mist-900'
                        "
                    >
                        {{ item.name }}
                    </Link>
                </div>

                <div class="hidden items-center gap-2 lg:flex">
                    <template v-if="currentUser">
                        <Link :href="route('dashboard')" class="wa-btn-ghost">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-cloud-100 text-xs font-semibold uppercase text-cloud-700">
                                {{ firstName.charAt(0) }}
                            </span>
                            {{ firstName || 'Mi panel' }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="wa-btn-ghost">Iniciar sesión</Link>
                    </template>
                    <Link :href="route('landing.agendar')" class="wa-btn-sage">
                        Agendar ahora
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                        </svg>
                    </Link>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    @click="toggleMobileMenu"
                    class="grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white/80 text-mist-700 transition hover:bg-white lg:hidden"
                    :aria-expanded="isMobileMenuOpen"
                    aria-label="Abrir menú"
                >
                    <svg v-if="!isMobileMenuOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </nav>

            <!-- Mobile Menu -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-show="isMobileMenuOpen"
                    class="mx-auto mt-2 max-w-7xl rounded-3xl border border-white/70 bg-white/90 p-3 shadow-lift backdrop-blur-xl lg:hidden"
                >
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="route(item.routeName)"
                        @click="toggleMobileMenu"
                        class="block rounded-2xl px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            route().current(item.routeName)
                                ? 'bg-mist-100 text-mist-900'
                                : 'text-mist-600 hover:bg-mist-50'
                        "
                    >
                        {{ item.name }}
                    </Link>
                    <div class="my-2 h-px bg-mist-200/80"></div>
                    <div class="grid gap-2 p-1">
                        <Link v-if="currentUser" :href="route('dashboard')" class="wa-btn-soft w-full" @click="toggleMobileMenu">
                            {{ firstName || 'Mi panel' }}
                        </Link>
                        <Link v-else :href="route('login')" class="wa-btn-soft w-full" @click="toggleMobileMenu">
                            Iniciar sesión
                        </Link>
                        <Link :href="route('landing.agendar')" class="wa-btn-sage w-full" @click="toggleMobileMenu">
                            Agendar ahora
                        </Link>
                    </div>
                </div>
            </Transition>
        </header>

        <main class="pt-24 sm:pt-28">
            <slot />
        </main>

        <!-- =========== Footer =========== -->
        <footer class="mt-24 px-3 pb-6 sm:px-5">
            <div
                class="mx-auto max-w-7xl rounded-3xl border border-white/70 bg-white/70 p-8 shadow-soft backdrop-blur-md sm:p-10"
            >
                <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Marca -->
                    <div>
                        <Link :href="route('landing.index')" class="inline-flex items-center">
                            <img src="/images/logo-washapp.png" alt="WashApp · Eco Car Wash Móvil" class="h-14 w-auto" />
                        </Link>
                        <p class="mt-4 text-sm leading-relaxed text-mist-500">
                            Lavado a domicilio 100% digital. Tu auto se limpia mientras tú recuperas tu tiempo.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <span class="wa-chip">Ecológico</span>
                            <span class="wa-chip">Cashless</span>
                        </div>
                    </div>

                    <!-- Navegación -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-mist-400">Navegación</h4>
                        <ul class="mt-4 space-y-3 text-sm text-mist-600">
                            <li>
                                <Link :href="route('landing.index')" class="transition hover:text-mist-900">Inicio</Link>
                            </li>
                            <li>
                                <Link :href="route('landing.servicios')" class="transition hover:text-mist-900">Servicios</Link>
                            </li>
                            <li>
                                <Link :href="route('landing.agendar')" class="transition hover:text-mist-900">Agendar</Link>
                            </li>
                            <li>
                                <Link :href="route('landing.contacto')" class="transition hover:text-mist-900">Contacto</Link>
                            </li>
                            <li>
                                <Link :href="route('landing.premios')" class="transition hover:text-mist-900">Premios</Link>
                            </li>
                            <li>
                                <Link :href="route('landing.promociones')" class="transition hover:text-mist-900">Promociones</Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Servicios -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-mist-400">Servicios</h4>
                        <ul class="mt-4 space-y-3 text-sm text-mist-600">
                            <li>
                                <Link :href="route('landing.servicios')" class="transition hover:text-mist-900">
                                    Lavado ecológico en seco
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('landing.servicios')" class="transition hover:text-mist-900">
                                    Lavado de bajo consumo
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('landing.servicios')" class="transition hover:text-mist-900">
                                    Servicios adicionales
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('landing.premios')" class="transition hover:text-mist-900">
                                    Premios y recompensas
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('landing.index') + '#membresias'" class="transition hover:text-mist-900">
                                    Membresías mensuales
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Soporte -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-mist-400">Soporte</h4>
                        <ul class="mt-4 space-y-3 text-sm text-mist-600">
                            <li>
                                <a
                                    href="https://wa.me/523312345678"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 transition hover:text-mist-900"
                                >
                                    <i class="fa-brands fa-whatsapp text-sage-500"></i> WhatsApp
                                </a>
                            </li>
                            <li>
                                <a
                                    href="mailto:hola@washapp.mx"
                                    class="inline-flex items-center gap-2 transition hover:text-mist-900"
                                >
                                    <i class="fa-regular fa-envelope text-cloud-400"></i> hola@washapp.mx
                                </a>
                            </li>
                            <li>
                                <Link
                                    :href="route('landing.contacto') + '#incidencias'"
                                    class="inline-flex items-center gap-2 transition hover:text-mist-900"
                                >
                                    <i class="fa-regular fa-circle-question text-sand-400"></i> Reportar una incidencia
                                </Link>
                            </li>
                            <li class="text-xs text-mist-400">Lun a Sáb · 8:00 – 19:00 h</li>
                        </ul>
                    </div>
                </div>

                <div
                    class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-mist-200/70 pt-6 text-xs text-mist-400 sm:flex-row"
                >
                    <p>&copy; {{ new Date().getFullYear() }} WashApp. Todos los derechos reservados.</p>
                    <div class="flex items-center gap-4">
                        <Link :href="route('landing.contacto')" class="transition hover:text-mist-700">Soporte</Link>
                        <a
                            href="https://dtw.com.mx/"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-1.5 rounded-lg bg-cloud-700 px-2 py-1 text-white transition hover:bg-cloud-800"
                        >
                            <span>BY</span>
                            <img class="h-3 w-auto" src="/images/DTW_logo_blanco.png" alt="DTW" />
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
