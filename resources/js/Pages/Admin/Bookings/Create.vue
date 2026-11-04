<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue
import Button from 'primevue/button';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    services: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    defaults: { type: Object, default: () => ({ state: 'Jalisco', employee_id: null }) },
});

const toast = useToast();

const MEXICAN_STATES = [
    'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
    'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Guanajuato', 'Guerrero', 'Hidalgo',
    'Jalisco', 'México', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
    'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas',
    'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
];

const GUADALAJARA = { lat: 20.6597, lng: -103.3496 };

const pad = (value) => String(value).padStart(2, '0');

const defaultSchedule = () => {
    const date = new Date();
    date.setSeconds(0, 0);
    date.setMinutes(0);
    date.setHours(date.getHours() + 1);
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const clientMode = ref(props.clients.length ? 'registered' : 'guest');

const form = useForm({
    user_id: null,
    guest_name: '',
    guest_phone: '',
    guest_email: '',
    employee_id: props.defaults.employee_id ?? null,
    services: [],
    scheduled_at: defaultSchedule(),
    street: '',
    street_number: '',
    neighborhood: '',
    postal_code: '',
    state: props.defaults.state || 'Jalisco',
    latitude: null,
    longitude: null,
    payment_method: 'Efectivo',
    notes: '',
});

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const selectedServices = computed(() =>
    props.services.filter((service) => form.services.includes(service.id))
);

const totalPrice = computed(() =>
    selectedServices.value.reduce((total, service) => total + Number(service.price || 0), 0)
);

const totalDuration = computed(() =>
    selectedServices.value.reduce((total, service) => total + Number(service.duration_minutes || 0), 0)
);

const composedAddress = computed(() =>
    [
        [form.street, form.street_number].filter(Boolean).join(' '),
        form.neighborhood,
        form.postal_code ? `CP ${form.postal_code}` : null,
        form.state,
    ]
        .filter(Boolean)
        .join(', ')
);

const mapsLink = computed(() => {
    if (form.latitude && form.longitude) {
        return `https://www.google.com/maps?q=${form.latitude},${form.longitude}`;
    }

    if (composedAddress.value) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(composedAddress.value)}`;
    }

    return null;
});

// ---------------------------------------------------------------- Mapa
const mapElement = ref(null);
let map = null;
let marker = null;
let markerIcon = null;

const setCoordinates = (lat, lng) => {
    form.latitude = Number(Number(lat).toFixed(6));
    form.longitude = Number(Number(lng).toFixed(6));

    if (!map) return;

    const position = [form.latitude, form.longitude];

    if (marker) {
        marker.setLatLng(position);
    } else if (markerIcon) {
        marker = L.marker(position, { draggable: true, icon: markerIcon }).addTo(map);
        marker.on('dragend', () => {
            const next = marker.getLatLng();
            form.latitude = Number(next.lat.toFixed(6));
            form.longitude = Number(next.lng.toFixed(6));
        });
    }
};

const clearCoordinates = () => {
    form.latitude = null;
    form.longitude = null;

    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
};

const locating = ref(false);

const locateAddress = async () => {
    const query = [composedAddress.value, 'México'].filter(Boolean).join(', ');

    if (!query.trim()) {
        toast.add({ severity: 'warn', summary: 'Falta la dirección', detail: 'Escribe calle y colonia primero.', life: 3000 });
        return;
    }

    locating.value = true;

    try {
        const { data } = await axios.get('https://nominatim.openstreetmap.org/search', {
            params: { q: query, format: 'json', limit: 1 },
        });

        if (data?.length) {
            const { lat, lon } = data[0];
            setCoordinates(parseFloat(lat), parseFloat(lon));
            map?.setView([form.latitude, form.longitude], 16);
        } else {
            toast.add({ severity: 'warn', summary: 'Sin resultados', detail: 'Ajusta la dirección o marca el punto en el mapa.', life: 4000 });
        }
    } catch {
        toast.add({ severity: 'error', summary: 'No se pudo ubicar', detail: 'Marca el punto manualmente en el mapa.', life: 4000 });
    } finally {
        locating.value = false;
    }
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

    if (form.latitude && form.longitude) {
        setCoordinates(form.latitude, form.longitude);
    }

    map.on('click', (event) => setCoordinates(event.latlng.lat, event.latlng.lng));
});

onUnmounted(() => {
    map?.remove();
    map = null;
});

// ---------------------------------------------------------------- Envío
const submit = () => {
    if (clientMode.value === 'guest') {
        form.user_id = null;
    } else {
        form.guest_name = '';
        form.guest_phone = '';
        form.guest_email = '';
    }

    form.post(route('admin.bookings.store'), {
        preserveScroll: true,
        onSuccess: () =>
            toast.add({
                severity: 'success',
                summary: 'Servicio agendado',
                detail: 'La cita se registró y asignó correctamente.',
                life: 3500,
            }),
        onError: (errors) =>
            toast.add({
                severity: 'error',
                summary: 'Revisa el formulario',
                detail: Object.values(errors)[0] || 'No se pudo agendar la cita.',
                life: 4500,
            }),
    });
};
</script>

<template>
    <Head title="Agendar cita" />

    <AppLayout title="Agendar cita">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Operación · WashApp
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">Agendar cita</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Registra un servicio para un cliente o para público en general.
                    </p>
                </div>
                <Link :href="route('admin.bookings.index')">
                    <Button label="Volver a servicios agendados" icon="pi pi-arrow-left" size="small" text severity="secondary" />
                </Link>
            </div>

            <form class="grid grid-cols-1 gap-4 xl:grid-cols-3" @submit.prevent="submit">
                <!-- Columna principal -->
                <div class="space-y-4 xl:col-span-2">
                    <!-- Cliente -->
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Cliente</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">Elige un cliente registrado o captura los datos del público general.</p>

                        <div class="mt-3 inline-flex rounded-xl bg-mist-100 p-1 dark:bg-slate-700">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1 text-xs font-semibold transition"
                                :class="clientMode === 'registered' ? 'bg-white text-mist-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-mist-500'"
                                @click="clientMode = 'registered'"
                            >
                                Cliente registrado
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1 text-xs font-semibold transition"
                                :class="clientMode === 'guest' ? 'bg-white text-mist-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-mist-500'"
                                @click="clientMode = 'guest'"
                            >
                                Público general
                            </button>
                        </div>

                        <div v-if="clientMode === 'registered'" class="mt-3">
                            <Select
                                v-model="form.user_id"
                                :options="clients"
                                optionLabel="name"
                                optionValue="id"
                                filter
                                showClear
                                placeholder="Selecciona un cliente"
                                class="w-full"
                            />
                            <p v-if="form.errors.user_id" class="mt-1 text-xs text-red-500">{{ form.errors.user_id }}</p>
                        </div>
                        <div v-else class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <input
                                    v-model="form.guest_name"
                                    type="text"
                                    placeholder="Nombre completo"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <p v-if="form.errors.guest_name" class="mt-1 text-xs text-red-500">{{ form.errors.guest_name }}</p>
                            </div>
                            <input
                                v-model="form.guest_phone"
                                type="tel"
                                placeholder="Teléfono"
                                class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                            <input
                                v-model="form.guest_email"
                                type="email"
                                placeholder="Correo (opcional)"
                                class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                    </div>

                    <!-- Servicios -->
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Servicios</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">Puedes combinar paquetes y servicios adicionales.</p>

                        <div class="mt-3">
                            <MultiSelect
                                v-model="form.services"
                                :options="services"
                                optionLabel="name"
                                optionValue="id"
                                filter
                                display="chip"
                                placeholder="Selecciona uno o más servicios"
                                class="w-full"
                            />
                            <p v-if="form.errors.services" class="mt-1 text-xs text-red-500">{{ form.errors.services }}</p>
                        </div>

                        <div
                            v-if="selectedServices.length"
                            class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-mist-50 px-3 py-2 text-xs dark:bg-slate-700/40"
                        >
                            <span class="text-mist-500 dark:text-slate-300">{{ totalDuration }} min estimados</span>
                            <span class="font-semibold text-mist-800 dark:text-slate-100">Total: {{ formatCurrency(totalPrice) }}</span>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h2 class="text-base font-semibold text-mist-900 dark:text-white">Dirección del servicio</h2>
                                <p class="text-xs text-mist-400 dark:text-slate-500">
                                    Captura la dirección completa. Las coordenadas son opcionales.
                                </p>
                            </div>
                            <a
                                v-if="mapsLink"
                                :href="mapsLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-cloud-600 hover:underline dark:text-cloud-300"
                            >
                                <i class="pi pi-map-marker"></i> Ver en Google Maps
                            </a>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Calle</label>
                                <input
                                    v-model="form.street"
                                    type="text"
                                    placeholder="Nombre de la calle"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <p v-if="form.errors.street" class="mt-1 text-xs text-red-500">{{ form.errors.street }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Número</label>
                                <input
                                    v-model="form.street_number"
                                    type="text"
                                    placeholder="123"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Colonia</label>
                                <input
                                    v-model="form.neighborhood"
                                    type="text"
                                    placeholder="Colonia o fraccionamiento"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <p v-if="form.errors.neighborhood" class="mt-1 text-xs text-red-500">{{ form.errors.neighborhood }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Código postal</label>
                                <input
                                    v-model="form.postal_code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="5"
                                    placeholder="00000"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Estado</label>
                                <Select v-model="form.state" :options="MEXICAN_STATES" filter class="w-full" />
                                <p v-if="form.errors.state" class="mt-1 text-xs text-red-500">{{ form.errors.state }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">
                                    Ubicación exacta (opcional)
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        label="Ubicar dirección"
                                        icon="pi pi-search"
                                        size="small"
                                        text
                                        severity="secondary"
                                        :loading="locating"
                                        type="button"
                                        @click="locateAddress"
                                    />
                                    <Button
                                        v-if="form.latitude && form.longitude"
                                        label="Quitar punto"
                                        icon="pi pi-times"
                                        size="small"
                                        text
                                        severity="danger"
                                        type="button"
                                        @click="clearCoordinates"
                                    />
                                </div>
                            </div>

                            <div
                                ref="mapElement"
                                class="mt-2 h-64 w-full overflow-hidden rounded-2xl border border-mist-200 dark:border-slate-700"
                                style="z-index: 0"
                            ></div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Latitud</label>
                                    <input
                                        v-model="form.latitude"
                                        type="number"
                                        step="any"
                                        placeholder="20.659700"
                                        class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    <p v-if="form.errors.latitude" class="mt-1 text-xs text-red-500">{{ form.errors.latitude }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Longitud</label>
                                    <input
                                        v-model="form.longitude"
                                        type="number"
                                        step="any"
                                        placeholder="-103.349600"
                                        class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    <p v-if="form.errors.longitude" class="mt-1 text-xs text-red-500">{{ form.errors.longitude }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] text-mist-400 dark:text-slate-500">
                                Haz clic en el mapa o arrastra el marcador para fijar la ubicación exacta.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="space-y-4">
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Programación</h2>

                        <div class="mt-3 space-y-3">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Empleado</label>
                                <Select
                                    v-model="form.employee_id"
                                    :options="employees"
                                    optionLabel="name"
                                    optionValue="id"
                                    filter
                                    placeholder="Selecciona un empleado"
                                    class="w-full"
                                />
                                <p v-if="form.errors.employee_id" class="mt-1 text-xs text-red-500">{{ form.errors.employee_id }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Fecha y hora</label>
                                <input
                                    v-model="form.scheduled_at"
                                    type="datetime-local"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <p v-if="form.errors.scheduled_at" class="mt-1 text-xs text-red-500">{{ form.errors.scheduled_at }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Método de pago</label>
                                <Select
                                    v-model="form.payment_method"
                                    :options="['Efectivo', 'Tarjeta', 'Transferencia']"
                                    placeholder="Selecciona"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Notas</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Indicaciones especiales"
                                    class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                        <h2 class="text-sm font-semibold text-mist-900 dark:text-white">Resumen</h2>
                        <dl class="mt-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-mist-400">Servicios</dt>
                                <dd class="font-semibold text-mist-700 dark:text-slate-200">{{ selectedServices.length }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-mist-400">Duración</dt>
                                <dd class="font-semibold text-mist-700 dark:text-slate-200">{{ totalDuration }} min</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-mist-100 pt-2 dark:border-slate-700">
                                <dt class="text-mist-400">Total</dt>
                                <dd class="text-lg font-semibold text-cloud-600 dark:text-cloud-300">{{ formatCurrency(totalPrice) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-col gap-2">
                            <Button
                                label="Agendar servicio"
                                icon="pi pi-calendar-plus"
                                type="submit"
                                :loading="form.processing"
                            />
                            <Link :href="route('admin.bookings.index')">
                                <Button label="Cancelar" text severity="secondary" type="button" class="w-full" />
                            </Link>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
