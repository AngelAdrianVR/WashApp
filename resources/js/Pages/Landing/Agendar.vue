<script setup>
import { ref, computed, reactive, watch } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    serviceId: { type: [String, Number], default: null },
    addonId: { type: [String, Number], default: null },
    vehicleCategoryId: { type: [String, Number], default: null },
    vehicleCategories: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    addons: { type: Array, default: () => [] },
});

const nf = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 });

/* =========================================================
   Catálogo (viene de la base de datos)
   ========================================================= */
const timeBlocks = ['08:00 – 10:00', '10:00 – 12:00', '12:00 – 14:00', '14:00 – 16:00', '16:00 – 18:00'];

const steps = [
    { id: 1, label: 'Vehículo' },
    { id: 2, label: 'Dirección' },
    { id: 3, label: 'Servicio' },
    { id: 4, label: 'Horario' },
    { id: 5, label: 'Pago' },
];

const typeMeta = {
    Seco: { water: '0 L de agua', tone: 'sage' },
    Agua: { water: '≈ 15 L de agua', tone: 'cloud' },
};

const categoryIcons = {
    hatchback: 'M4 13l1.6-4.2A2 2 0 017.5 7.5h9A2 2 0 0118.4 8.8L20 13v5h-3v-1H7v1H4v-5z|M7 13h10',
    sedan: 'M4 13l1.6-4.2A2 2 0 017.5 7.5h9A2 2 0 0118.4 8.8L20 13v5h-3v-1H7v1H4v-5z|M7 13h10',
    suv: 'M3 13l1.8-5A2 2 0 016.7 6.6h10.6a2 2 0 011.9 1.4L21 13v5h-3v-1H6v1H3v-5z|M7 13h10|M6 6.6V5h12v1.6',
    pickup: 'M3 13V9a2 2 0 012-2h5l2 4h7a2 2 0 012 2v4h-3v-1H6v1H3v-4z|M9 13h5',
    'van-camioneta': 'M3 15V9a2 2 0 012-2h8l4 4h2a2 2 0 012 2v2h-2v1h-3v-1H6v1H5a2 2 0 01-2-2z|M13 7v4h6',
};

const initialCategory =
    props.vehicleCategories.find((category) => String(category.id) === String(props.vehicleCategoryId)) ??
    props.vehicleCategories[0] ??
    null;

const initialPackage =
    props.packages.find((pkg) => String(pkg.id) === String(props.serviceId)) ?? props.packages[0] ?? null;

const initialAddons = props.addons.some((addon) => String(addon.id) === String(props.addonId))
    ? [Number(props.addonId)]
    : [];

/* =========================================================
   Estado del formulario
   ========================================================= */
const currentStep = ref(1);
const isConfirmed = ref(false);
const isProcessing = ref(false);
const folio = ref('');
const confirmedAt = ref('');

const form = reactive({
    brand: '',
    model: '',
    categoryId: initialCategory?.id ?? null,

    street: '',
    number: '',
    colonia: '',
    postalCode: '',
    city: '',
    references: '',
    gps: null,

    packageId: initialPackage?.id ?? null,
    addons: initialAddons,

    date: '',
    block: '',

    cardName: '',
    cardNumber: '',
    cardExpiry: '',
    cardCvv: '',
});

/* =========================================================
   Cálculos dinámicos
   ========================================================= */
const selectedCategory = computed(
    () => props.vehicleCategories.find((category) => category.id === form.categoryId) ?? null
);
const selectedPackage = computed(() => props.packages.find((pkg) => pkg.id === form.packageId) ?? null);
const selectedAddons = computed(() => props.addons.filter((addon) => form.addons.includes(addon.id)));

const variant = computed(() => selectedPackage.value?.variants?.[form.categoryId] ?? null);

const basePrice = computed(() =>
    Number(variant.value?.price ?? selectedPackage.value?.price ?? 0)
);
const baseMinutes = computed(() =>
    Number(variant.value?.duration_minutes ?? selectedPackage.value?.duration_minutes ?? 0)
);

const addonsTotal = computed(() => selectedAddons.value.reduce((sum, addon) => sum + Number(addon.price), 0));
const addonsMinutes = computed(() =>
    selectedAddons.value.reduce((sum, addon) => sum + Number(addon.duration_minutes), 0)
);
const total = computed(() => basePrice.value + addonsTotal.value);
const totalMinutes = computed(() => baseMinutes.value + addonsMinutes.value);

const pointsEarned = computed(
    () => Number(selectedPackage.value?.points ?? 0) + selectedAddons.value.reduce((sum, addon) => sum + Number(addon.points), 0)
);

const durationLabel = (minutes) => {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    if (hours === 0) return `${rest} min`;
    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
};

const totalDurationLabel = computed(() => durationLabel(totalMinutes.value));

/* =========================================================
   Calendario: navegación por semanas (sin días pasados)
   ========================================================= */
const MAX_WEEK_OFFSET = 5;
const weekOffset = ref(0);

const startOfToday = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
};

const firstBookableDay = () => {
    const tomorrow = startOfToday();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow;
};

const toIsoDate = (date) =>
    [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');

const weekdayFormat = new Intl.DateTimeFormat('es-MX', { weekday: 'short' });
const monthFormat = new Intl.DateTimeFormat('es-MX', { month: 'short' });

const weekDays = computed(() => {
    const monday = startOfToday();
    monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7) + weekOffset.value * 7);

    const limit = firstBookableDay();

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(monday);
        date.setDate(monday.getDate() + index);

        return {
            value: toIsoDate(date),
            weekday: weekdayFormat.format(date).replace('.', ''),
            day: date.getDate(),
            month: monthFormat.format(date).replace('.', ''),
            disabled: date < limit,
        };
    });
});

const weekLabel = computed(() => {
    const days = weekDays.value;
    const first = new Date(`${days[0].value}T00:00:00`);
    const last = new Date(`${days[6].value}T00:00:00`);
    const sameMonth = first.getMonth() === last.getMonth();

    const startLabel = `${first.getDate()} ${sameMonth ? '' : monthFormat.format(first).replace('.', '')}`.trim();
    const endLabel = `${last.getDate()} ${monthFormat.format(last).replace('.', '')}`;

    return `${startLabel} – ${endLabel}`;
});

const canGoPrevWeek = computed(() => weekOffset.value > 0);
const canGoNextWeek = computed(() => weekOffset.value < MAX_WEEK_OFFSET);

const changeWeek = (direction) => {
    const next = weekOffset.value + direction;
    if (next < 0 || next > MAX_WEEK_OFFSET) return;

    weekOffset.value = next;

    // Si la fecha elegida ya no está visible, se limpia la selección.
    const visible = weekDays.value.some((day) => day.value === form.date && !day.disabled);
    if (!visible) {
        form.date = '';
        form.block = '';
    }
};

const selectDay = (day) => {
    if (day.disabled) return;
    form.date = day.value;
    form.block = '';
};

// La disponibilidad es estable por día: se deriva un hash simple de fecha y bloque.
const isBlockAvailable = (block) => {
    if (!form.date) return true;

    const seed = `${form.date}${block}`;
    let hash = 0;
    for (let i = 0; i < seed.length; i++) {
        hash = (hash * 31 + seed.charCodeAt(i)) % 9973;
    }

    return hash % 5 !== 0;
};

const selectBlock = (block) => {
    if (!isBlockAvailable(block)) return;
    form.block = block;
};

const formattedDate = computed(() => {
    if (!form.date) return '';
    const [year, month, day] = form.date.split('-').map(Number);
    return new Intl.DateTimeFormat('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }).format(
        new Date(year, month - 1, day)
    );
});

/* =========================================================
   Dirección / GPS
   ========================================================= */
const isLocating = ref(false);

const useGps = () => {
    isLocating.value = true;
    let settled = false;

    const finish = (coords) => {
        if (settled) return;
        settled = true;
        form.gps = coords;
        isLocating.value = false;
    };

    const applyReferenceLocation = () => {
        form.street = form.street || 'Av. Américas';
        form.number = form.number || '1435';
        form.colonia = form.colonia || 'Country Club';
        form.postalCode = form.postalCode || '44620';
        form.city = form.city || 'Guadalajara, Jalisco';
        finish({ lat: '20.67674', lng: '-103.40478' });
    };

    // Red de seguridad si el navegador no responde al permiso de ubicación.
    const fallbackTimer = setTimeout(applyReferenceLocation, 8000);

    if (typeof navigator !== 'undefined' && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                clearTimeout(fallbackTimer);
                finish({
                    lat: position.coords.latitude.toFixed(5),
                    lng: position.coords.longitude.toFixed(5),
                });
            },
            () => {
                clearTimeout(fallbackTimer);
                applyReferenceLocation();
            },
            { timeout: 6000 }
        );
    } else {
        clearTimeout(fallbackTimer);
        setTimeout(applyReferenceLocation, 800);
    }
};

const fullAddress = computed(() =>
    [form.street, form.number, form.colonia, form.city, form.postalCode ? `CP ${form.postalCode}` : '']
        .filter(Boolean)
        .join(', ')
);

/* =========================================================
   Pago (modo de prueba, sin pasarela real)
   ========================================================= */
const cardDigits = computed(() => form.cardNumber.replace(/\D/g, ''));

const isPaymentValid = computed(() => {
    const expiryOk = /^\d{2}\/\d{2}$/.test(form.cardExpiry);
    return (
        form.cardName.trim().length > 3 &&
        cardDigits.value.length >= 15 &&
        expiryOk &&
        /^\d{3,4}$/.test(form.cardCvv)
    );
});

const onCardNumberInput = (event) => {
    const digits = event.target.value.replace(/\D/g, '').slice(0, 16);
    form.cardNumber = digits.replace(/(.{4})/g, '$1 ').trim();
};

const onExpiryInput = (event) => {
    let digits = event.target.value.replace(/\D/g, '').slice(0, 4);
    if (digits.length >= 3) {
        digits = `${digits.slice(0, 2)}/${digits.slice(2)}`;
    }
    form.cardExpiry = digits;
};

const onCvvInput = (event) => {
    form.cardCvv = event.target.value.replace(/\D/g, '').slice(0, 4);
};

/* =========================================================
   Navegación
   ========================================================= */
const canContinue = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.brand.trim().length > 1 && form.model.trim().length > 0 && !!form.categoryId;
        case 2:
            return (
                form.street.trim().length > 2 &&
                form.number.trim().length > 0 &&
                form.colonia.trim().length > 2 &&
                /^\d{5}$/.test(form.postalCode) &&
                form.city.trim().length > 2
            );
        case 3:
            return !!form.packageId;
        case 4:
            return !!form.date && !!form.block;
        case 5:
            return isPaymentValid.value;
        default:
            return false;
    }
});

const nextStep = () => {
    if (!canContinue.value || currentStep.value >= steps.length) return;
    currentStep.value += 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevStep = () => {
    if (currentStep.value <= 1) return;
    currentStep.value -= 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const goToStep = (step) => {
    if (step < currentStep.value) {
        currentStep.value = step;
    }
};

const toggleAddon = (addon) => {
    const index = form.addons.indexOf(addon.id);
    if (index === -1) {
        form.addons.push(addon.id);
    } else {
        form.addons.splice(index, 1);
    }
};

const confirmBooking = () => {
    if (!canContinue.value || isProcessing.value) return;

    isProcessing.value = true;

    // Pago simulado: no hay pasarela conectada todavía.
    setTimeout(() => {
        isProcessing.value = false;
        isConfirmed.value = true;
        folio.value = `WA-${Math.random().toString(36).slice(2, 7).toUpperCase()}`;
        confirmedAt.value = new Intl.DateTimeFormat('es-MX', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date());
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 1600);
};

watch(
    () => currentStep.value,
    () => {
        if (isConfirmed.value) isConfirmed.value = false;
    }
);

const resetBooking = () => {
    isConfirmed.value = false;
    currentStep.value = 1;
    form.date = '';
    form.block = '';
    form.cardName = '';
    form.cardNumber = '';
    form.cardExpiry = '';
    form.cardCvv = '';
};
</script>

<template>
    <LandingLayout title="Agendar">
        <div class="px-3 sm:px-5">
            <div class="mx-auto max-w-7xl">
                <!-- ==================== ENCABEZADO ==================== -->
                <header v-if="!isConfirmed" class="pt-6 text-center sm:pt-8" data-reveal>
                    <span class="wa-eyebrow">Agendar</span>
                    <h1 class="wa-title mx-auto mt-5 max-w-3xl text-3xl leading-tight sm:text-4xl lg:text-5xl">
                        Reserva tu lavado en 5 pasos
                    </h1>
                    <p class="mx-auto mt-4 max-w-xl text-sm text-mist-500 sm:text-base">
                        Menos de 2 minutos. El pago es por adelantado y lo procesamos en modo de prueba.
                    </p>
                </header>

                <!-- ==================== CONFIRMACIÓN ==================== -->
                <section v-if="isConfirmed" class="animate-fadeUp py-10">
                    <div class="mx-auto max-w-3xl">
                        <div class="wa-card overflow-hidden">
                            <div class="bg-gradient-to-br from-sage-600 to-cloud-700 p-8 text-center text-white sm:p-10">
                                <span class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-white/15 backdrop-blur-sm">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <h2 class="mt-5 text-2xl font-semibold sm:text-3xl">¡Servicio reservado con éxito!</h2>
                                <p class="mt-2 text-sm text-sage-50/90">
                                    Folio <span class="font-semibold text-white">{{ folio }}</span> · Reservado el
                                    {{ confirmedAt }}
                                </p>
                            </div>

                            <div class="grid gap-6 p-7 sm:grid-cols-2 sm:p-9">
                                <div>
                                    <p class="wa-label">Servicio</p>
                                    <p class="text-sm font-semibold text-mist-900">{{ selectedPackage?.name }}</p>
                                    <p class="mt-1 text-sm text-mist-500">
                                        {{ form.brand }} {{ form.model }} · {{ selectedCategory?.name }}
                                    </p>
                                </div>
                                <div>
                                    <p class="wa-label">Fecha y bloque</p>
                                    <p class="text-sm font-semibold first-letter:uppercase text-mist-900">{{ formattedDate }}</p>
                                    <p class="mt-1 text-sm text-mist-500">{{ form.block }} · {{ totalDurationLabel }}</p>
                                </div>
                                <div>
                                    <p class="wa-label">Dirección</p>
                                    <p class="text-sm font-semibold text-mist-900">{{ form.street }} {{ form.number }}</p>
                                    <p class="mt-1 text-sm text-mist-500">
                                        {{ form.colonia }}, {{ form.city }} · CP {{ form.postalCode }}
                                    </p>
                                    <p v-if="form.references" class="mt-1 text-xs text-mist-400">{{ form.references }}</p>
                                </div>
                                <div>
                                    <p class="wa-label">Total pagado</p>
                                    <p class="text-2xl font-semibold text-mist-900">${{ nf.format(total) }} MXN</p>
                                    <p class="mt-1 text-xs text-mist-400">
                                        Pago de prueba aprobado · Ganaste <span class="font-semibold text-sage-600">+{{ nf.format(pointsEarned) }} pts</span>
                                    </p>
                                </div>
                            </div>

                            <div v-if="selectedAddons.length" class="border-t border-mist-200/70 px-7 pb-6 sm:px-9">
                                <p class="wa-label pt-5">Adicionales incluidos</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="addon in selectedAddons" :key="addon.id" class="wa-chip">
                                        {{ addon.name }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-mist-200/70 bg-mist-50/60 p-7 sm:p-9">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-mist-400">Qué sigue</p>
                                <ol class="mt-4 space-y-3">
                                    <li class="flex gap-3 text-sm text-mist-600">
                                        <span class="grid h-6 w-6 flex-shrink-0 place-items-center rounded-full bg-white text-xs font-semibold text-mist-700 shadow-sm">1</span>
                                        Asignamos a un operador con identidad verificada (menos de 2 h).
                                    </li>
                                    <li class="flex gap-3 text-sm text-mist-600">
                                        <span class="grid h-6 w-6 flex-shrink-0 place-items-center rounded-full bg-white text-xs font-semibold text-mist-700 shadow-sm">2</span>
                                        Recibirás su foto y la llegada estimada por WhatsApp.
                                    </li>
                                    <li class="flex gap-3 text-sm text-mist-600">
                                        <span class="grid h-6 w-6 flex-shrink-0 place-items-center rounded-full bg-white text-xs font-semibold text-mist-700 shadow-sm">3</span>
                                        Al llegar, hacemos la inspección walkaround contigo antes de comenzar.
                                    </li>
                                </ol>

                                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                    <Link :href="route('landing.index')" class="wa-btn-soft">Volver al inicio</Link>
                                    <button type="button" class="wa-btn-primary" @click="resetBooking">
                                        Agendar otro vehículo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 text-center text-xs text-mist-400">
                            <i class="fa-solid fa-flask mr-1"></i>
                            Demostración: el pago no se procesa con ninguna pasarela real.
                        </p>
                    </div>
                </section>

                <!-- ==================== FLUJO ==================== -->
                <div v-else class="mt-10">
                    <!-- Stepper -->
                    <div class="wa-card p-5 sm:p-6" data-reveal>
                        <ol class="flex items-start justify-between gap-1">
                            <li v-for="step in steps" :key="step.id" class="flex flex-1 flex-col items-center gap-2 text-center">
                                <button
                                    type="button"
                                    :disabled="step.id >= currentStep"
                                    @click="goToStep(step.id)"
                                    class="grid h-9 w-9 place-items-center rounded-full text-sm font-semibold transition-all duration-300 sm:h-10 sm:w-10"
                                    :class="[
                                        currentStep >= step.id
                                            ? 'bg-cloud-600 text-white shadow-soft'
                                            : 'border border-mist-200 bg-white text-mist-400',
                                        step.id < currentStep ? 'cursor-pointer hover:bg-cloud-700' : '',
                                    ]"
                                >
                                    <svg v-if="step.id < currentStep" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else>{{ step.id }}</span>
                                </button>
                                <span
                                    class="hidden text-[11px] font-medium uppercase tracking-wider sm:block"
                                    :class="currentStep >= step.id ? 'text-mist-700' : 'text-mist-400'"
                                >
                                    {{ step.label }}
                                </span>
                            </li>
                        </ol>
                        <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-mist-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-cloud-500 to-sage-500 transition-all duration-500"
                                :style="{ width: `${(currentStep / steps.length) * 100}%` }"
                            ></div>
                        </div>
                        <p class="mt-3 text-center text-xs text-mist-400 sm:hidden">
                            Paso {{ currentStep }} de {{ steps.length }} · {{ steps[currentStep - 1].label }}
                        </p>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
                        <!-- Panel del paso -->
                        <div class="wa-card p-6 sm:p-8">
                            <!-- PASO 1: VEHÍCULO -->
                            <div v-if="currentStep === 1">
                                <h2 class="text-xl font-semibold text-mist-900">¿Qué vehículo vamos a cuidar?</h2>
                                <p class="mt-1 text-sm text-mist-500">Usamos la categoría para calcular precio y tiempo.</p>

                                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="brand" class="wa-label">Marca</label>
                                        <input id="brand" v-model="form.brand" type="text" class="wa-input" placeholder="Ej. Toyota" autocomplete="off" />
                                    </div>
                                    <div>
                                        <label for="model" class="wa-label">Modelo</label>
                                        <input id="model" v-model="form.model" type="text" class="wa-input" placeholder="Ej. Corolla 2021" autocomplete="off" />
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <p class="wa-label">Categoría</p>
                                    <div v-if="vehicleCategories.length" class="grid gap-3 sm:grid-cols-3">
                                        <button
                                            v-for="category in vehicleCategories"
                                            :key="category.id"
                                            type="button"
                                            class="wa-option"
                                            :class="{ 'wa-option-active': form.categoryId === category.id }"
                                            @click="form.categoryId = category.id"
                                        >
                                            <span class="flex items-center gap-2 text-mist-800">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                                    <template v-for="(d, i) in (categoryIcons[category.slug] ?? categoryIcons.sedan).split('|')" :key="i">
                                                        <path stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                                    </template>
                                                </svg>
                                                <span class="text-sm font-semibold">{{ category.name }}</span>
                                            </span>
                                        </button>
                                    </div>
                                    <p v-else class="text-sm text-mist-400">No hay categorías activas por ahora.</p>
                                </div>

                                <div v-if="selectedPackage" class="mt-6 rounded-2xl border border-cloud-200/70 bg-cloud-50/70 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-wider text-cloud-700/70">Precio base estimado</p>
                                            <p class="mt-1 text-2xl font-semibold text-mist-900">${{ nf.format(basePrice) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs uppercase tracking-wider text-cloud-700/70">Tiempo estimado</p>
                                            <p class="mt-1 text-lg font-semibold text-mist-900">{{ durationLabel(baseMinutes) }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs text-cloud-700/80">
                                        Se recalcula al elegir modalidad y adicionales.
                                    </p>
                                </div>
                            </div>

                            <!-- PASO 2: DIRECCIÓN -->
                            <div v-else-if="currentStep === 2">
                                <h2 class="text-xl font-semibold text-mist-900">¿Dónde lavamos tu auto?</h2>
                                <p class="mt-1 text-sm text-mist-500">
                                    Necesitamos la dirección exacta para que el operador llegue sin contratiempos.
                                </p>

                                <button
                                    type="button"
                                    class="wa-btn-soft mt-5 w-full sm:w-auto"
                                    :disabled="isLocating"
                                    @click="useGps"
                                >
                                    <svg v-if="!isLocating" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z" />
                                        <circle cx="12" cy="10" r="2.5" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" d="M12 3a9 9 0 109 9" />
                                    </svg>
                                    {{ isLocating ? 'Detectando ubicación…' : 'Usar mi ubicación actual' }}
                                </button>

                                <div v-if="form.gps" class="mt-4 flex items-start gap-3 rounded-2xl border border-sage-200 bg-sage-50/70 p-4">
                                    <span class="grid h-8 w-8 flex-shrink-0 place-items-center rounded-full bg-sage-500 text-white">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-sage-800">Ubicación detectada</p>
                                        <p class="mt-0.5 text-xs text-sage-700/80">
                                            {{ form.gps.lat }}, {{ form.gps.lng }} · Zona con cobertura
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-4 sm:grid-cols-6">
                                    <div class="sm:col-span-4">
                                        <label for="street" class="wa-label">Calle *</label>
                                        <input id="street" v-model="form.street" type="text" class="wa-input" placeholder="Ej. Av. Américas" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="number" class="wa-label">Número *</label>
                                        <input id="number" v-model="form.number" type="text" class="wa-input" placeholder="1435 int. 402" />
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="colonia" class="wa-label">Colonia *</label>
                                        <input id="colonia" v-model="form.colonia" type="text" class="wa-input" placeholder="Country Club" />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="postalCode" class="wa-label">Código postal *</label>
                                        <input
                                            id="postalCode"
                                            v-model="form.postalCode"
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="5"
                                            class="wa-input"
                                            placeholder="44620"
                                            @input="form.postalCode = $event.target.value.replace(/\D/g, '').slice(0, 5)"
                                        />
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label for="city" class="wa-label">Ciudad y estado *</label>
                                        <input id="city" v-model="form.city" type="text" class="wa-input" placeholder="Guadalajara, Jalisco" />
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label for="references" class="wa-label">Referencias para el operador</label>
                                        <input id="references" v-model="form.references" type="text" class="wa-input" placeholder="Torre, piso, color de portón, número de cajón…" />
                                    </div>
                                </div>

                                <p class="mt-4 rounded-2xl bg-mist-50 p-4 text-xs leading-relaxed text-mist-500">
                                    Cubrimos la zona metropolitana. Si tu dirección queda fuera del área, te
                                    contactamos antes de confirmar para ajustar la ruta.
                                </p>
                            </div>

                            <!-- PASO 3: MODALIDAD + ADICIONALES -->
                            <div v-else-if="currentStep === 3">
                                <h2 class="text-xl font-semibold text-mist-900">Modalidad y adicionales</h2>
                                <p class="mt-1 text-sm text-mist-500">Elige el tipo de lavado y súmale lo que necesites.</p>

                                <div v-if="packages.length" class="mt-6 space-y-3">
                                    <button
                                        v-for="pkg in packages"
                                        :key="pkg.id"
                                        type="button"
                                        class="wa-option flex items-start gap-3"
                                        :class="{ 'wa-option-active': form.packageId === pkg.id }"
                                        @click="form.packageId = pkg.id"
                                    >
                                        <span
                                            class="mt-0.5 grid h-5 w-5 flex-shrink-0 place-items-center rounded-full border-2 transition"
                                            :class="form.packageId === pkg.id ? 'border-sage-500 bg-sage-500 text-white' : 'border-mist-300'"
                                        >
                                            <span v-if="form.packageId === pkg.id" class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                        </span>
                                        <span class="flex-1">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-mist-900">{{ pkg.name }}</span>
                                                <span class="wa-chip !px-2.5 !py-0.5 !text-[10px]">
                                                    {{ (typeMeta[pkg.type] ?? typeMeta.Seco).water }}
                                                </span>
                                                <span class="wa-chip !px-2.5 !py-0.5 !text-[10px]">+{{ pkg.points }} pts</span>
                                            </span>
                                            <span class="mt-1 block text-xs leading-relaxed text-mist-500">{{ pkg.description }}</span>
                                        </span>
                                        <span class="whitespace-nowrap text-sm font-semibold text-mist-900">
                                            ${{ nf.format(pkg.variants?.[form.categoryId]?.price ?? pkg.price) }}
                                        </span>
                                    </button>
                                </div>

                                <div class="mt-8">
                                    <div class="flex items-center justify-between">
                                        <p class="wa-label !mb-0">Servicios adicionales</p>
                                        <span class="text-xs text-mist-400">Opcional</span>
                                    </div>

                                    <div v-if="addons.length" class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <button
                                            v-for="addon in addons"
                                            :key="addon.id"
                                            type="button"
                                            class="wa-option flex items-center justify-between gap-3 !py-3"
                                            :class="{ 'wa-option-active': form.addons.includes(addon.id) }"
                                            @click="toggleAddon(addon)"
                                        >
                                            <span class="flex items-center gap-2.5">
                                                <span
                                                    class="grid h-5 w-5 flex-shrink-0 place-items-center rounded-md border-2 transition"
                                                    :class="form.addons.includes(addon.id) ? 'border-sage-500 bg-sage-500 text-white' : 'border-mist-300'"
                                                >
                                                    <svg v-if="form.addons.includes(addon.id)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                                <span class="text-sm font-medium text-mist-800">{{ addon.name }}</span>
                                            </span>
                                            <span class="whitespace-nowrap text-xs font-semibold text-mist-600">+ ${{ nf.format(addon.price) }}</span>
                                        </button>
                                    </div>
                                    <p v-else class="mt-3 text-sm text-mist-400">No hay adicionales disponibles.</p>
                                </div>
                            </div>

                            <!-- PASO 4: FECHA Y BLOQUE -->
                            <div v-else-if="currentStep === 4">
                                <h2 class="text-xl font-semibold text-mist-900">Fecha y bloque horario</h2>
                                <p class="mt-1 text-sm text-mist-500">
                                    Elige el bloque de inicio de 2 horas. La duración estimada de tu servicio es
                                    {{ totalDurationLabel }}.
                                </p>

                                <div class="mt-6">
                                    <div class="flex items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-full border border-mist-200 bg-white text-mist-600 transition enabled:hover:border-cloud-300 enabled:hover:text-cloud-600 disabled:opacity-40"
                                            :disabled="!canGoPrevWeek"
                                            aria-label="Semana anterior"
                                            @click="changeWeek(-1)"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
                                            </svg>
                                        </button>

                                        <div class="text-center">
                                            <p class="text-sm font-semibold capitalize text-mist-900">{{ weekLabel }}</p>
                                            <p class="text-[11px] uppercase tracking-wider text-mist-400">
                                                Semana {{ weekOffset + 1 }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-full border border-mist-200 bg-white text-mist-600 transition enabled:hover:border-cloud-300 enabled:hover:text-cloud-600 disabled:opacity-40"
                                            :disabled="!canGoNextWeek"
                                            aria-label="Semana siguiente"
                                            @click="changeWeek(1)"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mt-4 grid grid-cols-7 gap-1 sm:gap-2">
                                        <button
                                            v-for="day in weekDays"
                                            :key="day.value"
                                            type="button"
                                            :disabled="day.disabled"
                                            class="rounded-xl border p-1.5 text-center transition-all duration-300 sm:rounded-2xl sm:p-3"
                                            :class="[
                                                form.date === day.value
                                                    ? 'border-cloud-600 bg-cloud-600 text-white shadow-soft'
                                                    : 'border-mist-200 bg-white/70 text-mist-600 hover:border-cloud-300 hover:bg-white',
                                                day.disabled ? 'cursor-not-allowed opacity-35 hover:border-mist-200 hover:bg-white/70' : '',
                                            ]"
                                            @click="selectDay(day)"
                                        >
                                            <span class="block text-[9px] uppercase tracking-wider opacity-70 sm:text-[10px]">{{ day.weekday }}</span>
                                            <span class="mt-0.5 block text-sm font-semibold sm:mt-1 sm:text-lg">{{ day.day }}</span>
                                            <span class="block text-[9px] uppercase tracking-wider opacity-70 sm:text-[10px]">{{ day.month }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <p class="wa-label">Bloque disponible</p>
                                    <div v-if="!form.date" class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-400">
                                        Selecciona un día para ver los bloques disponibles.
                                    </div>
                                    <div v-else class="grid gap-2 sm:grid-cols-2">
                                        <button
                                            v-for="block in timeBlocks"
                                            :key="block"
                                            type="button"
                                            :disabled="!isBlockAvailable(block)"
                                            class="flex items-center justify-between rounded-2xl border px-4 py-3 text-sm transition-all duration-300"
                                            :class="[
                                                form.block === block
                                                    ? 'border-sage-400 bg-sage-50 text-sage-800 shadow-soft ring-2 ring-sage-200'
                                                    : 'border-mist-200 bg-white/70 text-mist-600 hover:border-cloud-300 hover:bg-white',
                                                !isBlockAvailable(block) ? 'cursor-not-allowed opacity-45 hover:border-mist-200 hover:bg-white/70' : '',
                                            ]"
                                            @click="selectBlock(block)"
                                        >
                                            <span class="font-medium">{{ block }}</span>
                                            <span class="text-[10px] uppercase tracking-wider">
                                                {{ isBlockAvailable(block) ? 'Disponible' : 'Ocupado' }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- PASO 5: PAGO -->
                            <div v-else-if="currentStep === 5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-semibold text-mist-900">Pago por adelantado</h2>
                                    <span class="wa-chip !px-2.5 !py-0.5 !text-[10px]">
                                        <i class="fa-solid fa-flask text-cloud-500"></i> Modo de prueba
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-mist-500">
                                    El cobro se realiza ahora para garantizar tu bloque. No hay pasarela conectada:
                                    los datos no se envían a ningún procesador.
                                </p>

                                <div class="mt-6 grid gap-4">
                                    <div>
                                        <label for="cardName" class="wa-label">Nombre en la tarjeta</label>
                                        <input id="cardName" v-model="form.cardName" type="text" class="wa-input" placeholder="Como aparece en la tarjeta" autocomplete="cc-name" />
                                    </div>
                                    <div>
                                        <label for="cardNumber" class="wa-label">Número de tarjeta</label>
                                        <input
                                            id="cardNumber"
                                            :value="form.cardNumber"
                                            type="text"
                                            inputmode="numeric"
                                            class="wa-input"
                                            placeholder="0000 0000 0000 0000"
                                            autocomplete="cc-number"
                                            @input="onCardNumberInput"
                                        />
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="cardExpiry" class="wa-label">Vencimiento</label>
                                            <input
                                                id="cardExpiry"
                                                :value="form.cardExpiry"
                                                type="text"
                                                inputmode="numeric"
                                                class="wa-input"
                                                placeholder="MM/AA"
                                                autocomplete="cc-exp"
                                                @input="onExpiryInput"
                                            />
                                        </div>
                                        <div>
                                            <label for="cardCvv" class="wa-label">CVV</label>
                                            <input
                                                id="cardCvv"
                                                :value="form.cardCvv"
                                                type="password"
                                                inputmode="numeric"
                                                class="wa-input"
                                                placeholder="123"
                                                autocomplete="cc-csc"
                                                @input="onCvvInput"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span class="wa-chip">Visa</span>
                                    <span class="wa-chip">Mastercard</span>
                                    <span class="wa-chip">AMEX</span>
                                    <span class="wa-chip">Apple / Google Pay</span>
                                </div>

                                <p class="mt-5 rounded-2xl bg-mist-50 p-4 text-xs leading-relaxed text-mist-500">
                                    <i class="fa-solid fa-shield-halved mr-1 text-sage-500"></i>
                                    Ambiente de pruebas: puedes usar cualquier tarjeta con 16 dígitos, por ejemplo
                                    4242 4242 4242 4242.
                                </p>
                            </div>

                            <!-- Navegación -->
                            <div class="mt-8 flex items-center justify-between gap-3 border-t border-mist-200/70 pt-6">
                                <button
                                    type="button"
                                    class="wa-btn-ghost"
                                    :disabled="currentStep === 1"
                                    :class="{ 'pointer-events-none opacity-40': currentStep === 1 }"
                                    @click="prevStep"
                                >
                                    ← Atrás
                                </button>

                                <button
                                    v-if="currentStep < 5"
                                    type="button"
                                    class="wa-btn-primary"
                                    :class="{ 'pointer-events-none opacity-50': !canContinue }"
                                    :disabled="!canContinue"
                                    @click="nextStep"
                                >
                                    Continuar
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" />
                                    </svg>
                                </button>

                                <button
                                    v-else
                                    type="button"
                                    class="wa-btn-sage"
                                    :class="{ 'pointer-events-none opacity-50': !canContinue || isProcessing }"
                                    :disabled="!canContinue || isProcessing"
                                    @click="confirmBooking"
                                >
                                    <svg v-if="isProcessing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" d="M12 3a9 9 0 109 9" />
                                    </svg>
                                    {{ isProcessing ? 'Procesando…' : `Pagar $${nf.format(total)}` }}
                                </button>
                            </div>
                        </div>

                        <!-- Resumen -->
                        <aside class="lg:sticky lg:top-28 lg:self-start">
                            <div class="wa-panel p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-mist-400">Resumen</h3>
                                    <span class="text-[10px] uppercase tracking-wider text-mist-400">{{ currentStep }}/5</span>
                                </div>

                                <dl class="mt-5 space-y-3 text-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="text-mist-400">Vehículo</dt>
                                        <dd class="text-right font-medium text-mist-800">
                                            {{ form.brand || form.model ? `${form.brand} ${form.model}`.trim() : 'Por definir' }}
                                            <span class="block text-xs font-normal text-mist-400">{{ selectedCategory?.name ?? 'Sin categoría' }}</span>
                                        </dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="text-mist-400">Modalidad</dt>
                                        <dd class="text-right font-medium text-mist-800">
                                            {{ selectedPackage?.name ?? 'Por definir' }}
                                        </dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="text-mist-400">Dirección</dt>
                                        <dd class="max-w-[62%] text-right font-medium text-mist-800">
                                            {{ fullAddress || 'Por definir' }}
                                            <span v-if="form.references" class="block text-xs font-normal text-mist-400">{{ form.references }}</span>
                                        </dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="text-mist-400">Fecha</dt>
                                        <dd class="text-right font-medium text-mist-800">
                                            <span class="first-letter:uppercase">{{ formattedDate || 'Por definir' }}</span>
                                            <span v-if="form.block" class="block text-xs font-normal text-mist-400">{{ form.block }}</span>
                                        </dd>
                                    </div>
                                </dl>

                                <div class="mt-5 space-y-2 border-t border-mist-200/70 pt-4 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-mist-500">Servicio base</span>
                                        <span class="font-medium text-mist-800">${{ nf.format(basePrice) }}</span>
                                    </div>
                                    <div v-for="addon in selectedAddons" :key="addon.id" class="flex items-center justify-between">
                                        <span class="text-mist-500">{{ addon.name }}</span>
                                        <span class="font-medium text-mist-800">+ ${{ nf.format(addon.price) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-mist-400">
                                        <span>Tiempo estimado</span>
                                        <span>{{ totalDurationLabel }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-mist-400">
                                        <span>Puntos que ganas</span>
                                        <span class="font-semibold text-sage-600">+{{ nf.format(pointsEarned) }} pts</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-end justify-between border-t border-mist-200/70 pt-4">
                                    <span class="text-sm font-semibold text-mist-700">Total</span>
                                    <span class="text-2xl font-semibold text-mist-900">${{ nf.format(total) }}</span>
                                </div>
                                <p class="mt-1 text-right text-[11px] text-mist-400">MXN · pago único por adelantado</p>

                                <div class="mt-5 rounded-2xl border border-sage-200/70 bg-sage-50/70 p-4">
                                    <p class="text-xs font-semibold text-sage-800">Incluido siempre</p>
                                    <ul class="mt-2 space-y-1.5 text-[11px] leading-relaxed text-sage-700/90">
                                        <li>· Operador con identidad verificada</li>
                                        <li>· Inspección walkaround con fotos</li>
                                        <li>· Seguro de responsabilidad civil</li>
                                        <li>· Garantía de satisfacción 24 h</li>
                                    </ul>
                                </div>
                            </div>
                        </aside>
                    </div>

                    <!-- Fases posteriores -->
                    <div class="mt-6 grid gap-3 sm:grid-cols-3" data-reveal>
                        <div class="wa-card p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Después de pagar</p>
                            <p class="mt-2 text-sm font-semibold text-mist-900">Asignación de operador</p>
                            <p class="mt-1 text-xs leading-relaxed text-mist-500">
                                Recibes su perfil verificado y la hora estimada de llegada.
                            </p>
                        </div>
                        <div class="wa-card p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sand-500">Al llegar</p>
                            <p class="mt-2 text-sm font-semibold text-mist-900">Inspección walkaround</p>
                            <p class="mt-1 text-xs leading-relaxed text-mist-500">
                                Levantamos fotos del estado inicial del vehículo junto contigo.
                            </p>
                        </div>
                        <div class="wa-card p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sage-600">Al terminar</p>
                            <p class="mt-2 text-sm font-semibold text-mist-900">Garantía de satisfacción</p>
                            <p class="mt-1 text-xs leading-relaxed text-mist-500">
                                Cualquier detalle se corrige sin costo dentro de las 24 h.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandingLayout>
</template>
