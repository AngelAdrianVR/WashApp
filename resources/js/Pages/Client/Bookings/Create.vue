<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    services: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({ name: '', phone_number: '' }) },
});

const toast = useToast();

const STEPS = [
    { id: 1, label: 'Datos' },
    { id: 2, label: 'Dirección' },
    { id: 3, label: 'Servicio' },
    { id: 4, label: 'Horario' },
    { id: 5, label: 'Pago' },
];

const MEXICAN_STATES = [
    'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
    'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Guanajuato', 'Guerrero', 'Hidalgo',
    'Jalisco', 'México', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
    'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas',
    'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
];

const GUADALAJARA = { lat: 20.6597, lng: -103.3496 };

const currentStep = ref(1);
const isSubmitting = ref(false);
const submitError = ref('');

const todayIso = () => {
    const today = new Date();
    return [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
};

const form = reactive({
    name: props.user?.name ?? '',
    phone_number: props.user?.phone_number ?? '',

    street: '',
    street_number: '',
    neighborhood: '',
    postal_code: '',
    state: 'Jalisco',
    latitude: null,
    longitude: null,

    services: [],
    date: todayIso(),
    block: '',

    payment_method: 'Efectivo',
    cardName: '',
    cardNumber: '',
    cardExpiry: '',
    cardCvv: '',
    notes: '',
});

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const formatDateLong = (value) => {
    if (!value) return '';
    return new Date(`${value}T00:00:00`).toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

const selectedServices = computed(() => props.services.filter((service) => form.services.includes(service.id)));
const totalPrice = computed(() => selectedServices.value.reduce((total, service) => total + Number(service.price), 0));
const totalDuration = computed(() =>
    selectedServices.value.reduce((total, service) => total + Number(service.duration_minutes), 0)
);

const durationLabel = computed(() => {
    const minutes = totalDuration.value;
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    if (hours === 0) return `${rest} min`;
    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
});

const toggleService = (service) => {
    const index = form.services.indexOf(service.id);
    if (index === -1) {
        form.services.push(service.id);
    } else {
        form.services.splice(index, 1);
    }
};

// ---------------------------------------------------------------- Dirección / mapa
const mapElement = ref(null);
let map = null;
let marker = null;
let markerIcon = null;

const setCoordinates = (lat, lng) => {
    form.latitude = Number(Number(lat).toFixed(6));
    form.longitude = Number(Number(lng).toFixed(6));

    if (!map || !markerIcon) return;

    const position = [form.latitude, form.longitude];

    if (marker) {
        marker.setLatLng(position);
    } else {
        marker = L.marker(position, { draggable: true, icon: markerIcon }).addTo(map);
        marker.on('dragend', () => {
            const next = marker.getLatLng();
            form.latitude = Number(next.lat.toFixed(6));
            form.longitude = Number(next.lng.toFixed(6));
        });
    }
};

const useMyLocation = () => {
    if (!navigator.geolocation) {
        toast.add({ severity: 'warn', summary: 'Ubicación no disponible', life: 3000 });
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            setCoordinates(position.coords.latitude, position.coords.longitude);
            map?.setView([form.latitude, form.longitude], 16);
        },
        () => toast.add({ severity: 'warn', summary: 'No se pudo obtener tu ubicación', life: 3000 }),
        { timeout: 8000 }
    );
};

onMounted(() => {
    if (!mapElement.value) return;

    markerIcon = L.divIcon({
        className: '',
        html: '<div style="width:18px;height:18px;border-radius:9999px;background:#0b6b9b;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35)"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });

    map = L.map(mapElement.value).setView([GUADALAJARA.lat, GUADALAJARA.lng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    map.on('click', (event) => setCoordinates(event.latlng.lat, event.latlng.lng));
});

onUnmounted(() => {
    map?.remove();
    map = null;
});

// ---------------------------------------------------------------- Horarios
const availableTimes = ref([]);
const isLoadingTimes = ref(false);

const fetchTimes = async () => {
    form.block = '';

    if (!form.date || totalDuration.value === 0) {
        availableTimes.value = [];
        return;
    }

    isLoadingTimes.value = true;

    try {
        const { data } = await axios.post(route('client.bookings.availableTimes'), {
            date: form.date,
            duration: totalDuration.value,
        });
        availableTimes.value = Array.isArray(data) ? data : [];
    } catch {
        availableTimes.value = [];
    } finally {
        isLoadingTimes.value = false;
    }
};

watch([() => form.date, () => totalDuration.value], fetchTimes);

const slotEnd = (slot) => {
    const [hours, minutes] = slot.split(':').map(Number);
    const end = new Date();
    end.setHours(hours, minutes + 60, 0, 0);
    return `${String(end.getHours()).padStart(2, '0')}:${String(end.getMinutes()).padStart(2, '0')}`;
};

// ---------------------------------------------------------------- Pago
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

// ---------------------------------------------------------------- Navegación
const canContinue = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.name.trim().length > 2;
        case 2:
            return (
                form.street.trim().length > 2 &&
                form.street_number.trim().length > 0 &&
                form.neighborhood.trim().length > 2 &&
                /^\d{5}$/.test(form.postal_code) &&
                !!form.state
            );
        case 3:
            return form.services.length > 0;
        case 4:
            return !!form.date && !!form.block;
        case 5:
            return true;
        default:
            return false;
    }
});

const nextStep = () => {
    if (!canContinue.value || currentStep.value >= STEPS.length) return;
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

const submit = () => {
    if (isSubmitting.value) return;

    submitError.value = '';
    isSubmitting.value = true;

    router.post(
        route('client.bookings.store'),
        {
            name: form.name,
            phone_number: form.phone_number,
            street: form.street,
            street_number: form.street_number,
            neighborhood: form.neighborhood,
            postal_code: form.postal_code,
            state: form.state,
            latitude: form.latitude,
            longitude: form.longitude,
            services: form.services,
            scheduled_at: `${form.date} ${form.block}`,
            payment_method: form.payment_method,
            notes: form.notes,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
            onError: (errors) => {
                submitError.value = Object.values(errors)[0] || 'Revisa los datos obligatorios.';
            },
        }
    );
};
</script>

<template>
    <Head title="Agendar cita" />

    <AppLayout title="Agendar cita">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Cliente · WashApp
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">Agendar cita</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Reserva en {{ STEPS.length }} pasos. El pago con tarjeta está en modo de prueba.
                    </p>
                </div>
                <Link :href="route('client.bookings.index')">
                    <Button label="Mis reservas" icon="pi pi-book" text size="small" severity="secondary" />
                </Link>
            </div>

            <!-- Stepper -->
            <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                <ol class="flex items-start justify-between gap-1">
                    <li v-for="step in STEPS" :key="step.id" class="flex flex-1 flex-col items-center gap-2 text-center">
                        <button
                            type="button"
                            :disabled="step.id >= currentStep"
                            class="grid h-9 w-9 place-items-center rounded-full text-sm font-semibold transition-all duration-300 sm:h-10 sm:w-10"
                            :class="[
                                currentStep >= step.id
                                    ? 'bg-cloud-600 text-white shadow-soft'
                                    : 'border border-mist-200 bg-white text-mist-400',
                                step.id < currentStep ? 'cursor-pointer hover:bg-cloud-700' : '',
                            ]"
                            @click="goToStep(step.id)"
                        >
                            <svg v-if="step.id < currentStep" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ step.id }}</span>
                        </button>
                        <span
                            class="hidden text-[11px] font-medium uppercase tracking-wider sm:block"
                            :class="currentStep >= step.id ? 'text-mist-700 dark:text-slate-200' : 'text-mist-400'"
                        >
                            {{ step.label }}
                        </span>
                    </li>
                </ol>
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-cloud-500 to-sage-500 transition-all duration-500"
                        :style="{ width: `${(currentStep / STEPS.length) * 100}%` }"
                    ></div>
                </div>
                <p class="mt-3 text-center text-xs text-mist-400 sm:hidden">
                    Paso {{ currentStep }} de {{ STEPS.length }} · {{ STEPS[currentStep - 1].label }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Panel -->
                <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft dark:border-slate-800 dark:bg-gray-800/90 lg:col-span-2">
                    <!-- PASO 1: DATOS -->
                    <div v-if="currentStep === 1">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Tus datos</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">La reserva quedará a tu nombre.</p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Nombre completo *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Teléfono</label>
                                <input
                                    v-model="form.phone_number"
                                    type="tel"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Notas (opcional)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    placeholder="Indicaciones para el operador"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 2: DIRECCIÓN -->
                    <div v-else-if="currentStep === 2">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Dirección del servicio</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Captura calle, número, colonia y código postal. La ubicación en el mapa es opcional.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Calle *</label>
                                <input v-model="form.street" type="text" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Número *</label>
                                <input v-model="form.street_number" type="text" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Colonia *</label>
                                <input v-model="form.neighborhood" type="text" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Código postal *</label>
                                <input
                                    v-model="form.postal_code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="5"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    @input="form.postal_code = $event.target.value.replace(/\D/g, '').slice(0, 5)"
                                />
                            </div>
                            <div class="sm:col-span-6">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Estado *</label>
                                <select v-model="form.state" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    <option v-for="state in MEXICAN_STATES" :key="state" :value="state">{{ state }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Ubicación exacta (opcional)</p>
                                <Button label="Usar mi ubicación" icon="pi pi-map-marker" text size="small" severity="secondary" type="button" @click="useMyLocation" />
                            </div>
                            <div ref="mapElement" class="mt-2 h-56 w-full overflow-hidden rounded-2xl border border-mist-200 dark:border-slate-700" style="z-index: 0"></div>
                            <p v-if="form.latitude" class="mt-2 text-[11px] text-mist-400">
                                {{ form.latitude }}, {{ form.longitude }}
                            </p>
                        </div>
                    </div>

                    <!-- PASO 3: SERVICIO -->
                    <div v-else-if="currentStep === 3">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Elige tus servicios</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Puedes combinar paquetes y adicionales.</p>

                        <div class="mt-6 space-y-3">
                            <button
                                v-for="service in services"
                                :key="service.id"
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-4 py-3 text-left transition"
                                :class="
                                    form.services.includes(service.id)
                                        ? 'border-cloud-500 bg-cloud-50/70 ring-2 ring-cloud-200 dark:bg-cloud-900/20'
                                        : 'border-mist-200 bg-white hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800/60'
                                "
                                @click="toggleService(service)"
                            >
                                <span
                                    class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-md border-2"
                                    :class="form.services.includes(service.id) ? 'border-cloud-600 bg-cloud-600 text-white' : 'border-mist-300'"
                                >
                                    <svg v-if="form.services.includes(service.id)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-mist-900 dark:text-white">{{ service.name }}</span>
                                        <span v-if="service.is_addon" class="rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-mist-500 dark:bg-slate-700 dark:text-slate-300">Adicional</span>
                                        <span class="rounded-full bg-cloud-50 px-2 py-0.5 text-[10px] font-semibold text-cloud-700 dark:bg-cloud-900/30 dark:text-cloud-300">{{ service.type }}</span>
                                    </span>
                                    <span class="mt-1 block text-xs text-mist-500 dark:text-slate-400">{{ service.duration_minutes }} min</span>
                                </span>
                                <span class="whitespace-nowrap text-sm font-semibold text-mist-900 dark:text-slate-100">{{ formatCurrency(service.price) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 4: HORARIO -->
                    <div v-else-if="currentStep === 4">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Fecha y horario</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Intervalos de 1 hora. Los horarios ya transcurridos no se muestran.
                        </p>

                        <div class="mt-6">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Fecha *</label>
                            <input
                                v-model="form.date"
                                type="date"
                                :min="todayIso()"
                                class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 sm:w-64"
                            />
                        </div>

                        <div class="mt-5">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-mist-400">
                                Horarios disponibles · {{ durationLabel }}
                            </p>
                            <div v-if="isLoadingTimes" class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-400 dark:border-slate-700 dark:bg-slate-800/60">
                                <i class="pi pi-spin pi-spinner mr-2"></i>Consultando horarios…
                            </div>
                            <div v-else-if="availableTimes.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button
                                    v-for="time in availableTimes"
                                    :key="time"
                                    type="button"
                                    class="rounded-xl border px-3 py-2 text-sm font-medium transition"
                                    :class="
                                        form.block === time
                                            ? 'border-cloud-600 bg-cloud-600 text-white shadow-soft'
                                            : 'border-mist-200 bg-white text-mist-600 hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                    "
                                    @click="form.block = time"
                                >
                                    {{ time }} – {{ slotEnd(time) }}
                                </button>
                            </div>
                            <div v-else class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-500 dark:border-slate-700 dark:bg-slate-800/60">
                                No hay horarios disponibles para este día. Prueba con otra fecha.
                            </div>
                        </div>
                    </div>

                    <!-- PASO 5: PAGO -->
                    <div v-else-if="currentStep === 5">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Pago</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Tu reserva se registra aunque el cobro con tarjeta aún no se procese.
                        </p>

                        <div class="mt-5 grid gap-2 sm:grid-cols-3">
                            <button
                                v-for="method in ['Efectivo', 'Tarjeta', 'Transferencia']"
                                :key="method"
                                type="button"
                                class="rounded-xl border px-3 py-2.5 text-sm font-semibold transition"
                                :class="
                                    form.payment_method === method
                                        ? 'border-cloud-600 bg-cloud-50 text-cloud-700 ring-2 ring-cloud-200 dark:bg-cloud-900/20 dark:text-cloud-300'
                                        : 'border-mist-200 bg-white text-mist-600 hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                "
                                @click="form.payment_method = method"
                            >
                                {{ method }}
                            </button>
                        </div>

                        <div v-if="form.payment_method === 'Tarjeta'" class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Nombre en la tarjeta (opcional)</label>
                                <input v-model="form.cardName" type="text" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Número de tarjeta (opcional)</label>
                                <input
                                    :value="form.cardNumber"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="0000 0000 0000 0000"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    @input="onCardNumberInput"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Vencimiento</label>
                                <input :value="form.cardExpiry" type="text" placeholder="MM/AA" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" @input="onExpiryInput" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">CVV</label>
                                <input :value="form.cardCvv" type="password" inputmode="numeric" placeholder="123" class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" @input="onCvvInput" />
                            </div>
                        </div>

                        <p class="mt-4 rounded-2xl bg-mist-50 p-4 text-xs leading-relaxed text-mist-500 dark:bg-slate-700/40 dark:text-slate-300">
                            <i class="pi pi-info-circle mr-1 text-cloud-500"></i>
                            Los datos de tarjeta son opcionales por ahora. La reserva se guarda y puedes pagar al operador.
                        </p>
                    </div>

                    <p v-if="submitError" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ submitError }}
                    </p>

                    <!-- Navegación -->
                    <div class="mt-8 flex items-center justify-between gap-3 border-t border-mist-100 pt-6 dark:border-slate-700">
                        <Button label="Atrás" icon="pi pi-arrow-left" text severity="secondary" :disabled="currentStep === 1" @click="prevStep" />
                        <Button
                            v-if="currentStep < STEPS.length"
                            label="Continuar"
                            icon="pi pi-arrow-right"
                            iconPos="right"
                            :disabled="!canContinue"
                            @click="nextStep"
                        />
                        <Button
                            v-else
                            :label="isSubmitting ? 'Guardando…' : 'Confirmar reserva'"
                            icon="pi pi-check"
                            :loading="isSubmitting"
                            @click="submit"
                        />
                    </div>
                </div>

                <!-- Resumen -->
                <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft dark:border-slate-800 dark:bg-gray-800/90">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-mist-400">Resumen</h3>
                            <span class="text-[10px] uppercase tracking-wider text-mist-400">{{ currentStep }}/{{ STEPS.length }}</span>
                        </div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-mist-400">Cliente</dt>
                                <dd class="text-right font-medium text-mist-800 dark:text-slate-200">{{ form.name || 'Por definir' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-mist-400">Dirección</dt>
                                <dd class="max-w-[62%] text-right font-medium text-mist-800 dark:text-slate-200">
                                    {{ [form.street, form.street_number].filter(Boolean).join(' ') || 'Por definir' }}
                                    <span class="block text-xs font-normal text-mist-400">
                                        {{ [form.neighborhood, form.postal_code ? `CP ${form.postal_code}` : null].filter(Boolean).join(', ') }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-mist-400">Fecha</dt>
                                <dd class="text-right font-medium text-mist-800 dark:text-slate-200">
                                    {{ form.date ? formatDateLong(form.date) : 'Por definir' }}
                                    <span v-if="form.block" class="block text-xs font-normal text-mist-400">{{ form.block }} – {{ slotEnd(form.block) }}</span>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 space-y-2 border-t border-mist-100 pt-4 text-sm dark:border-slate-700">
                            <div v-for="service in selectedServices" :key="service.id" class="flex items-center justify-between gap-2">
                                <span class="truncate text-mist-500 dark:text-slate-400">{{ service.name }}</span>
                                <span class="whitespace-nowrap font-medium text-mist-800 dark:text-slate-200">{{ formatCurrency(service.price) }}</span>
                            </div>
                            <p v-if="!selectedServices.length" class="text-xs text-mist-400">Aún no eliges servicios.</p>
                            <div class="flex items-center justify-between text-xs text-mist-400">
                                <span>Duración estimada</span>
                                <span>{{ totalDuration ? durationLabel : '—' }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-end justify-between border-t border-mist-100 pt-4 dark:border-slate-700">
                            <span class="text-sm font-semibold text-mist-700 dark:text-slate-200">Total</span>
                            <span class="text-2xl font-semibold text-mist-900 dark:text-white">{{ formatCurrency(totalPrice) }}</span>
                        </div>
                        <p class="mt-1 text-right text-[11px] text-mist-400">MXN</p>
                    </div>

                    <div class="rounded-3xl border border-sage-200/70 bg-sage-50/70 p-4 dark:border-sage-500/30 dark:bg-sage-500/10">
                        <p class="text-xs font-semibold text-sage-800 dark:text-sage-200">Incluido siempre</p>
                        <ul class="mt-2 space-y-1 text-[11px] leading-relaxed text-sage-700/90 dark:text-sage-300/80">
                            <li>· Operador con identidad verificada</li>
                            <li>· Inspección walkaround con fotos</li>
                            <li>· Garantía de satisfacción 24 h</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
