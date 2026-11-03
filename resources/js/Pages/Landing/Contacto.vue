<script setup>
import { ref, reactive, computed } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';

const form = reactive({
    name: '',
    email: '',
    phone: '',
    topic: 'Duda general',
    message: '',
    accept: false,
});

const isSending = ref(false);
const isSent = ref(false);

const topics = [
    'Duda general',
    'Reportar una incidencia',
    'Membresías y planes para empresas',
    'Facturación y pagos',
    'Quiero ser operador WashApp',
];

const isValid = computed(
    () =>
        form.name.trim().length > 2 &&
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) &&
        form.message.trim().length > 9 &&
        form.accept
);

const submit = () => {
    if (!isValid.value || isSending.value) return;

    isSending.value = true;

    // Punto de integración: aquí se conectaría el endpoint de contacto.
    setTimeout(() => {
        isSending.value = false;
        isSent.value = true;
    }, 1200);
};

// Prepara el formulario para un reporte de incidencia y lleva el foco al inicio.
const reportIncident = () => {
    form.topic = 'Reportar una incidencia';
    const field = document.getElementById('name');
    field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    field?.focus({ preventScroll: true });
};

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.phone = '';
    form.topic = 'Duda general';
    form.message = '';
    form.accept = false;
    isSent.value = false;
};

const channels = [
    {
        label: 'WhatsApp',
        value: '+52 33 1234 5678',
        hint: 'Respuesta en menos de 10 min',
        faClass: 'fa-brands fa-whatsapp',
        href: 'https://wa.me/523312345678',
        tone: 'sage',
    },
    {
        label: 'Correo',
        value: 'hola@washapp.mx',
        hint: 'Atención en 24 h hábiles',
        faClass: 'fa-regular fa-envelope',
        href: 'mailto:hola@washapp.mx',
        tone: 'cloud',
    },
    {
        label: 'Teléfono',
        value: '+52 33 1234 5678',
        hint: 'Lun a Sáb · 8:00 – 19:00',
        faClass: 'fa-solid fa-phone',
        href: 'tel:+523312345678',
        tone: 'sand',
    },
];

const faqs = ref([
    {
        question: '¿Qué pasa si mi auto no queda como esperaba?',
        answer: 'La garantía de satisfacción cubre cualquier detalle durante las 24 h posteriores al servicio. Reportas la incidencia y programamos una revisión sin costo.',
        open: true,
    },
    {
        question: '¿Necesito estar presente durante el lavado?',
        answer: 'No. Solo necesitamos acceso al vehículo y tu autorización para la inspección inicial. Recibes el reporte fotográfico al finalizar.',
        open: false,
    },
    {
        question: '¿Qué métodos de pago aceptan?',
        answer: 'Todo es digital: tarjeta de crédito o débito, y wallets compatibles (Apple Pay / Google Pay). No recibimos efectivo en la puerta.',
        open: false,
    },
    {
        question: '¿Puedo cancelar o reprogramar mi cita?',
        answer: 'Sí, desde tu cuenta. Cancelaciones con más de 4 h de anticipación se reembolsan completas; después de ese tiempo se reprograma sin costo.',
        open: false,
    },
]);

const toggleFaq = (index) => {
    faqs.value = faqs.value.map((faq, i) => ({ ...faq, open: i === index ? !faq.open : false }));
};
</script>

<template>
    <LandingLayout title="Contacto">
        <div class="px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <!-- ==================== ENCABEZADO ==================== -->
                <header class="pt-8 text-center sm:pt-10" data-reveal>
                    <span class="wa-eyebrow">Contacto</span>
                    <h1 class="wa-title mx-auto mt-5 max-w-3xl text-4xl leading-tight sm:text-5xl">
                        Estamos para resolverlo
                        <span class="block bg-gradient-to-r from-cloud-500 to-sage-400 bg-clip-text text-transparent">
                            sin bots ni filas
                        </span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-mist-500">
                        Dudas sobre el servicio, incidencias o planes para tu empresa: elige el canal que prefieras y
                        te respondemos con nombre y apellido.
                    </p>
                </header>

                <div class="mt-12 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <!-- ==================== FORMULARIO ==================== -->
                    <section id="formulario" class="wa-card p-6 sm:p-8" data-reveal>
                        <div v-if="isSent" class="flex h-full flex-col items-center justify-center py-10 text-center">
                            <span class="grid h-16 w-16 place-items-center rounded-3xl bg-sage-100 text-sage-600">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <h2 class="mt-5 text-2xl font-semibold text-mist-900">Mensaje enviado</h2>
                            <p class="mt-2 max-w-sm text-sm text-mist-500">
                                Gracias por escribirnos. Un miembro del equipo te responderá a
                                <span class="font-medium text-mist-700">{{ form.email }}</span> en menos de 24 horas hábiles.
                            </p>
                            <button type="button" class="wa-btn-soft mt-6" @click="resetForm">Enviar otro mensaje</button>
                        </div>

                        <form v-else @submit.prevent="submit">
                            <h2 class="text-xl font-semibold text-mist-900">Escríbenos</h2>
                            <p class="mt-1 text-sm text-mist-500">Todos los campos con * son obligatorios.</p>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="name" class="wa-label">Nombre *</label>
                                    <input id="name" v-model="form.name" type="text" class="wa-input" placeholder="Tu nombre" />
                                </div>
                                <div>
                                    <label for="phone" class="wa-label">Teléfono</label>
                                    <input id="phone" v-model="form.phone" type="tel" class="wa-input" placeholder="33 1234 5678" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="email" class="wa-label">Correo electrónico *</label>
                                    <input id="email" v-model="form.email" type="email" class="wa-input" placeholder="tu@correo.com" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="topic" class="wa-label">Motivo</label>
                                    <select id="topic" v-model="form.topic" class="wa-input">
                                        <option v-for="topic in topics" :key="topic" :value="topic">{{ topic }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="message" class="wa-label">Mensaje *</label>
                                    <textarea
                                        id="message"
                                        v-model="form.message"
                                        rows="5"
                                        class="wa-input resize-none"
                                        placeholder="Cuéntanos qué necesitas. Si es una incidencia, incluye la fecha del servicio."
                                    ></textarea>
                                </div>
                            </div>

                            <label class="mt-5 flex cursor-pointer items-start gap-3 text-xs leading-relaxed text-mist-500">
                                <input
                                    v-model="form.accept"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-mist-300 text-sage-500 focus:ring-sage-300"
                                />
                                <span>
                                    Acepto el aviso de privacidad y el tratamiento de mis datos para dar seguimiento a
                                    esta solicitud.
                                </span>
                            </label>

                            <button
                                type="submit"
                                class="wa-btn-primary mt-6 w-full sm:w-auto"
                                :class="{ 'pointer-events-none opacity-50': !isValid || isSending }"
                                :disabled="!isValid || isSending"
                            >
                                <svg v-if="isSending" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" d="M12 3a9 9 0 109 9" />
                                </svg>
                                {{ isSending ? 'Enviando…' : 'Enviar mensaje' }}
                            </button>
                        </form>
                    </section>

                    <!-- ==================== CANALES ==================== -->
                    <div class="space-y-6">
                        <section class="wa-card p-6 sm:p-7" data-reveal>
                            <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-mist-400">
                                Canales de atención
                            </h2>

                            <ul class="mt-5 space-y-3">
                                <li v-for="channel in channels" :key="channel.label">
                                    <a
                                        :href="channel.href"
                                        :target="channel.faClass.includes('whatsapp') ? '_blank' : undefined"
                                        rel="noopener"
                                        class="group flex items-center gap-4 rounded-2xl border border-mist-200/70 bg-white/70 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-cloud-300 hover:shadow-soft"
                                    >
                                        <span
                                            class="grid h-10 w-10 flex-shrink-0 place-items-center rounded-2xl"
                                            :class="{
                                                'bg-sage-100 text-sage-600': channel.tone === 'sage',
                                                'bg-cloud-100 text-cloud-600': channel.tone === 'cloud',
                                                'bg-sand-100 text-sand-600': channel.tone === 'sand',
                                            }"
                                        >
                                            <i :class="channel.faClass" class="text-base"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-xs uppercase tracking-wider text-mist-400">{{ channel.label }}</span>
                                            <span class="block truncate text-sm font-semibold text-mist-900">{{ channel.value }}</span>
                                            <span class="block text-xs text-mist-400">{{ channel.hint }}</span>
                                        </span>
                                        <svg class="h-4 w-4 flex-shrink-0 text-mist-300 transition group-hover:translate-x-0.5 group-hover:text-cloud-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            </ul>

                            <div class="mt-5 rounded-2xl bg-mist-50 p-4 text-xs leading-relaxed text-mist-500">
                                <p class="font-semibold text-mist-700">Horario de atención</p>
                                <p class="mt-1">Lunes a sábado · 8:00 a 19:00 h</p>
                                <p>Domingo · solo servicios programados</p>
                            </div>
                        </section>

                        <!-- Garantía / incidencias -->
                        <section id="incidencias" class="relative overflow-hidden rounded-3xl border border-sage-200/70 bg-gradient-to-br from-sage-600 to-mist-700 p-6 text-white shadow-lift sm:p-7" data-reveal>
                            <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider">
                                <i class="fa-solid fa-shield-heart"></i> Garantía de satisfacción
                            </span>
                            <h3 class="relative mt-4 text-xl font-semibold">¿Algo no quedó perfecto?</h3>
                            <p class="relative mt-2 text-sm leading-relaxed text-sage-50/85">
                                Tienes 24 horas para reportar cualquier detalle. Programamos una revisión sin costo y
                                con prioridad sobre la agenda regular.
                            </p>
                            <a
                                href="#formulario"
                                class="relative mt-5 inline-flex items-center gap-2 rounded-2xl bg-white/95 px-5 py-2.5 text-sm font-semibold text-mist-800 shadow-sm transition hover:bg-white"
                                @click.prevent="reportIncident"
                            >
                                Reportar incidencia
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                </svg>
                            </a>
                        </section>
                    </div>
                </div>

                <!-- ==================== FAQ ==================== -->
                <section class="mt-16">
                    <div class="mx-auto max-w-2xl text-center" data-reveal>
                        <span class="wa-eyebrow">Preguntas frecuentes</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">Antes de que preguntes</h2>
                    </div>

                    <div class="mx-auto mt-8 max-w-3xl space-y-3">
                        <div
                            v-for="(faq, index) in faqs"
                            :key="faq.question"
                            class="overflow-hidden rounded-3xl border border-white/70 bg-white/75 shadow-soft backdrop-blur-md transition"
                            data-reveal
                            :style="{ transitionDelay: `${index * 60}ms` }"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 p-5 text-left"
                                :aria-expanded="faq.open"
                                @click="toggleFaq(index)"
                            >
                                <span class="text-sm font-semibold text-mist-900">{{ faq.question }}</span>
                                <svg
                                    class="h-4 w-4 flex-shrink-0 text-mist-400 transition-transform duration-300"
                                    :class="{ 'rotate-45': faq.open }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                </svg>
                            </button>
                            <div v-show="faq.open" class="px-5 pb-5 text-sm leading-relaxed text-mist-500">
                                {{ faq.answer }}
                            </div>
                        </div>
                    </div>

                    <div class="mx-auto mt-10 max-w-3xl text-center" data-reveal>
                        <p class="text-sm text-mist-500">¿Prefieres reservar directo?</p>
                        <Link :href="route('landing.agendar')" class="wa-btn-sage mt-4">
                            Agendar mi lavado
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </LandingLayout>
</template>
