<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    services: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({ name: '', phone_number: '' }) },
    cars: { type: Array, default: () => [] },
    addresses: { type: Array, default: () => [] },
    vehicleCategories: { type: Array, default: () => [] },
    schedule: { type: Object, default: () => ({}) },
});

const toast = useToast();

const STEPS = [
    { id: 1, label: 'Datos' },
    { id: 2, label: 'Dirección' },
    { id: 3, label: 'Vehículo' },
    { id: 4, label: 'Servicios' },
    { id: 5, label: 'Horario' },
    { id: 6, label: 'Pago' },
];

const MEXICAN_STATES = [
    'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
    'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Guanajuato', 'Guerrero', 'Hidalgo',
    'Jalisco', 'México', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
    'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas',
    'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
];

const GUADALAJARA = { lat: 20.6597, lng: -103.3496 };

const typeMeta = {
    Seco: { water: '0 L de agua', chip: 'bg-sage-50 text-sage-700 dark:bg-sage-500/15 dark:text-sage-300' },
    Agua: { water: '≈ 15 L de agua', chip: 'bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300' },
};

// ---------------------------------------------------------------- Helpers de fecha
const todayStart = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
};

const isoOf = (date) =>
    [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');

const parseIso = (iso) => {
    const [year, month, day] = String(iso).split('-').map(Number);
    return new Date(year, (month || 1) - 1, day || 1);
};

const addDays = (date, days) => {
    const copy = new Date(date);
    copy.setDate(copy.getDate() + days);
    return copy;
};

const startOfMonth = (date) => new Date(date.getFullYear(), date.getMonth(), 1);

const todayIso = () => isoOf(todayStart());

// ---------------------------------------------------------------- Configuración de operación
const scheduleConfig = computed(() => ({
    opening_time: props.schedule?.opening_time ?? '09:00',
    closing_time: props.schedule?.closing_time ?? '18:00',
    operating_days: props.schedule?.operating_days ?? [1, 2, 3, 4, 5, 6],
    exceptions: props.schedule?.exceptions ?? [],
    min_date: props.schedule?.min_date ?? todayIso(),
    max_date: props.schedule?.max_date ?? isoOf(addDays(todayStart(), 120)),
}));

const exceptionsByDate = computed(() => {
    return scheduleConfig.value.exceptions.reduce((map, exception) => {
        if (!map[exception.date]) map[exception.date] = [];
        map[exception.date].push(exception);
        return map;
    }, {});
});

const isDateClosed = (iso) => {
    const date = parseIso(iso);
    const exceptions = exceptionsByDate.value[iso] ?? [];

    return !scheduleConfig.value.operating_days.includes(date.getDay())
        || exceptions.some((exception) => exception.is_closed);
};

const dateExceptionLabel = (iso) => {
    const exceptions = exceptionsByDate.value[iso] ?? [];
    const closed = exceptions.find((exception) => exception.is_closed);
    if (closed) return closed.label || 'Descanso especial';
    const partial = exceptions.find((exception) => !exception.is_closed);
    if (partial) {
        return `${partial.label || 'Horario especial'} ${partial.start_time}–${partial.end_time}`;
    }
    return null;
};

const timeToMinutes = (time) => {
    const [hours, minutes] = String(time).split(':').map(Number);
    return hours * 60 + minutes;
};

// ---------------------------------------------------------------- Estado
const defaultAddress = props.addresses.find((address) => address.is_default) ?? props.addresses[0] ?? null;
const defaultCar = props.cars[0] ?? null;

const currentStep = ref(1);
const isSubmitting = ref(false);
const submitError = ref('');

const vehicleMode = ref(props.cars.length ? 'saved' : 'new');

const form = reactive({
    name: props.user?.name ?? '',
    phone_number: props.user?.phone_number ?? '',

    address_id: defaultAddress?.id ?? null,
    street: defaultAddress?.street ?? '',
    street_number: defaultAddress?.street_number ?? '',
    neighborhood: defaultAddress?.neighborhood ?? '',
    postal_code: defaultAddress?.postal_code ?? '',
    state: defaultAddress?.state ?? 'Jalisco',
    latitude: defaultAddress?.latitude ?? null,
    longitude: defaultAddress?.longitude ?? null,

    car_id: defaultCar?.id ?? null,
    vehicle_category_id: defaultCar?.vehicle_category_id ?? '',
    vehicle_brand: defaultCar?.brand ?? '',
    vehicle_model: defaultCar?.model ?? '',

    services: [],
    date: '',
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
    return parseIso(value).toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

// ---------------------------------------------------------------- Catálogo y precios
const packages = computed(() => props.services.filter((service) => !service.is_addon));
const addons = computed(() => props.services.filter((service) => service.is_addon));
const selectedServices = computed(() => props.services.filter((service) => form.services.includes(service.id)));
const selectedPackage = computed(() => packages.value.find((pkg) => form.services.includes(pkg.id)) ?? null);
const selectedAddons = computed(() => addons.value.filter((addon) => form.services.includes(addon.id)));
const selectedCar = computed(() => props.cars.find((car) => car.id === form.car_id) ?? null);
const selectedCategory = computed(
    () => props.vehicleCategories.find((category) => category.id === form.vehicle_category_id) ?? null
);

const priceFor = (service) =>
    Number(service.variants?.[form.vehicle_category_id]?.price ?? service.price ?? 0);

const durationFor = (service) =>
    Number(service.variants?.[form.vehicle_category_id]?.duration_minutes ?? service.duration_minutes ?? 0);

const totalPrice = computed(() => selectedServices.value.reduce((total, service) => total + priceFor(service), 0));
const totalDuration = computed(() => selectedServices.value.reduce((total, service) => total + durationFor(service), 0));

const durationLabel = computed(() => {
    const minutes = totalDuration.value;
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    if (hours === 0) return `${rest} min`;
    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
});

const toggleAddon = (addon) => {
    const index = form.services.indexOf(addon.id);
    if (index === -1) {
        form.services.push(addon.id);
    } else {
        form.services.splice(index, 1);
    }
};

const selectPackage = (pkg) => {
    const packageIds = packages.value.map((item) => item.id);
    form.services = form.services.filter((id) => !packageIds.includes(id));

    if (!form.services.includes(pkg.id)) {
        form.services.push(pkg.id);
    }
};

// ---------------------------------------------------------------- Vehículo
const chooseCar = (car) => {
    vehicleMode.value = 'saved';
    form.car_id = car.id;
    form.vehicle_brand = car.brand;
    form.vehicle_model = car.model;
    form.vehicle_category_id = car.vehicle_category_id ?? '';
};

const chooseNewVehicle = () => {
    vehicleMode.value = 'new';
    form.car_id = null;
    form.vehicle_brand = '';
    form.vehicle_model = '';
    form.vehicle_category_id = '';
};

// ---------------------------------------------------------------- Dirección / mapa
const mapElement = ref(null);
let map = null;
let marker = null;
let markerIcon = null;

const setCoordinates = (lat, lng, center = false) => {
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

    if (center) {
        map.setView(position, 16);
    }
};

const initMap = () => {
    if (map || !mapElement.value) return;

    markerIcon = L.divIcon({
        className: '',
        html: '<div style="width:20px;height:20px;border-radius:9999px;background:#0b6b9b;border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,.4)"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10],
    });

    const center = form.latitude && form.longitude
        ? [form.latitude, form.longitude]
        : [GUADALAJARA.lat, GUADALAJARA.lng];

    map = L.map(mapElement.value).setView(center, form.latitude ? 16 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    map.on('click', (event) => setCoordinates(event.latlng.lat, event.latlng.lng));

    if (form.latitude && form.longitude) {
        setCoordinates(form.latitude, form.longitude);
    }
};

const selectAddress = (address) => {
    form.address_id = address.id;
    form.street = address.street ?? '';
    form.street_number = address.street_number ?? '';
    form.neighborhood = address.neighborhood ?? '';
    form.postal_code = address.postal_code ?? '';
    form.state = address.state ?? 'Jalisco';
    form.latitude = address.latitude;
    form.longitude = address.longitude;

    nextTick(() => {
        if (address.latitude && address.longitude) {
            setCoordinates(address.latitude, address.longitude, true);
        }
    });
};

const clearAddress = () => {
    form.address_id = null;
    form.street = '';
    form.street_number = '';
    form.neighborhood = '';
    form.postal_code = '';
    form.state = 'Jalisco';
    form.latitude = null;
    form.longitude = null;

    if (marker) {
        marker.remove();
        marker = null;
    }

    map?.setView([GUADALAJARA.lat, GUADALAJARA.lng], 12);
};

const markAddressEdited = () => {
    form.address_id = null;
};

const useMyLocation = () => {
    if (!navigator.geolocation) {
        toast.add({ severity: 'warn', summary: 'Ubicación no disponible', life: 3000 });
        return;
    }

    toast.add({ severity: 'info', summary: 'Buscando ubicación…', life: 1800 });

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.address_id = null;
            setCoordinates(position.coords.latitude, position.coords.longitude, true);
            toast.add({ severity: 'success', summary: 'Ubicación actualizada', detail: 'Mueve el punto si lo necesitas.', life: 3000 });
        },
        () => toast.add({ severity: 'warn', summary: 'No se pudo obtener tu ubicación', life: 3000 }),
        { timeout: 8000 }
    );
};

onMounted(() => {
    if (currentStep.value === 2) {
        nextTick(initMap);
    }
});

watch(currentStep, async (step) => {
    if (step === 2) {
        await nextTick();
        initMap();
        if (form.latitude && form.longitude) {
            setCoordinates(form.latitude, form.longitude, true);
        }
        return;
    }

    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});

// ---------------------------------------------------------------- Horarios
const availableTimes = ref([]);
const isLoadingTimes = ref(false);

const fetchTimes = async () => {
    form.block = '';

    if (!form.date || !totalDuration.value || isDateClosed(form.date)) {
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

const operatingSlots = computed(() => {
    if (!form.date || isDateClosed(form.date)) return [];

    const exceptions = exceptionsByDate.value[form.date] ?? [];
    const open = timeToMinutes(scheduleConfig.value.opening_time);
    const close = timeToMinutes(scheduleConfig.value.closing_time);
    const duration = Math.max(totalDuration.value, 60);
    const slots = [];

    for (let minutes = open; minutes + duration <= close; minutes += 60) {
        const time = `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
        const end = minutes + duration;

        const blocked = exceptions.some((exception) => {
            if (exception.is_closed || !exception.start_time || !exception.end_time) return false;

            return minutes < timeToMinutes(exception.end_time) && end > timeToMinutes(exception.start_time);
        });

        slots.push({ time, blocked });
    }

    return slots;
});

const slotAvailable = (slot) => !slot.blocked && availableTimes.value.includes(slot.time);
const slotEndLabel = (time) => {
    const minutes = timeToMinutes(time) + Math.max(totalDuration.value, 60);
    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
};

// ---------------------------------------------------------------- Calendario
const monthCursor = ref(startOfMonth(todayStart()));

const monthLabel = computed(() =>
    monthCursor.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' })
);

const canGoPrevMonth = computed(() =>
    startOfMonth(monthCursor.value) > startOfMonth(parseIso(scheduleConfig.value.min_date))
);

const canGoNextMonth = computed(() =>
    startOfMonth(monthCursor.value) < startOfMonth(parseIso(scheduleConfig.value.max_date))
);

const changeMonth = (direction) => {
    const next = new Date(monthCursor.value.getFullYear(), monthCursor.value.getMonth() + direction, 1);

    if (next < startOfMonth(parseIso(scheduleConfig.value.min_date)) || next > startOfMonth(parseIso(scheduleConfig.value.max_date))) {
        return;
    }

    monthCursor.value = next;
};

const calendarDays = computed(() => {
    const first = startOfMonth(monthCursor.value);
    const mondayOffset = (first.getDay() + 6) % 7;
    const gridStart = addDays(first, -mondayOffset);
    const minDate = parseIso(scheduleConfig.value.min_date);
    const maxDate = parseIso(scheduleConfig.value.max_date);

    return Array.from({ length: 42 }, (_, index) => {
        const date = addDays(gridStart, index);
        const iso = isoOf(date);
        const inMonth = date.getMonth() === first.getMonth();
        const exceptions = exceptionsByDate.value[iso] ?? [];
        const fullDayClosed = exceptions.some((exception) => exception.is_closed);
        const operatingDay = scheduleConfig.value.operating_days.includes(date.getDay());
        const outOfRange = date < minDate || date > maxDate;
        const disabled = !inMonth || outOfRange || fullDayClosed || !operatingDay;

        let reason = null;
        if (outOfRange) reason = 'Fuera del rango disponible';
        else if (fullDayClosed) reason = dateExceptionLabel(iso);
        else if (!operatingDay) reason = 'Día no operativo';

        return {
            iso,
            day: date.getDate(),
            inMonth,
            disabled,
            reason,
            partial: exceptions.some((exception) => !exception.is_closed),
            isToday: iso === todayIso(),
        };
    });
});

const selectDay = (day) => {
    if (day.disabled) return;
    form.date = day.iso;
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

// ---------------------------------------------------------------- Validación en vivo
const touched = reactive({});
const attempted = reactive({});

const STEP_FIELDS = {
    1: ['name', 'phone_number'],
    2: ['street', 'street_number', 'neighborhood', 'postal_code', 'state'],
    3: ['vehicle', 'vehicle_category_id', 'vehicle_brand', 'vehicle_model'],
    4: ['services'],
    5: ['date', 'block'],
    6: [],
};

const validation = computed(() => {
    const errors = {};
    const phoneDigits = form.phone_number.replace(/\D/g, '');

    if (form.name.trim().length < 3) errors.name = 'Escribe tu nombre completo.';
    if (phoneDigits.length < 10) errors.phone_number = 'Ingresa un teléfono de 10 dígitos.';

    if (form.street.trim().length < 3) errors.street = 'Escribe el nombre de la calle.';
    if (!form.street_number.trim()) errors.street_number = 'Escribe el número exterior.';
    if (form.neighborhood.trim().length < 3) errors.neighborhood = 'Escribe la colonia.';
    if (!/^\d{5}$/.test(form.postal_code)) errors.postal_code = 'El código postal debe tener 5 dígitos.';
    if (!form.state) errors.state = 'Selecciona el estado.';

    if (vehicleMode.value === 'saved') {
        if (!form.car_id) errors.vehicle = 'Selecciona un vehículo o agrega uno nuevo.';
        if (!form.vehicle_category_id) errors.vehicle_category_id = 'Selecciona el tipo de vehículo.';
    } else {
        if (form.vehicle_brand.trim().length < 2) errors.vehicle_brand = 'Escribe la marca del vehículo.';
        if (!form.vehicle_model.trim()) errors.vehicle_model = 'Escribe el modelo del vehículo.';
        if (!form.vehicle_category_id) errors.vehicle_category_id = 'Selecciona el tipo de vehículo.';
    }

    if (!form.services.length) errors.services = 'Elige el tipo de lavado.';
    else if (!selectedPackage.value) errors.services = 'Selecciona una modalidad de lavado.';

    if (!form.date) errors.date = 'Elige una fecha disponible.';
    if (!form.block) errors.block = 'Elige un horario disponible.';

    return errors;
});

const showError = (field) => {
    if (!touched[field] && !attempted[currentStep.value]) return '';
    return validation.value[field] ?? '';
};

const fieldClass = (field) => {
    const base = 'w-full rounded-xl border bg-white text-sm text-mist-800 shadow-sm transition focus:outline-none focus:ring-2 dark:bg-slate-800 dark:text-slate-100';

    return showError(field)
        ? `${base} border-rose-400 focus:border-rose-500 focus:ring-rose-100 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-500/30`
        : `${base} border-mist-200 focus:border-cloud-400 focus:ring-cloud-200 dark:border-slate-600 dark:focus:border-cloud-400 dark:focus:ring-cloud-500/30`;
};

const markStepTouched = (step) => {
    (STEP_FIELDS[step] ?? []).forEach((field) => {
        touched[field] = true;
    });
};

const canContinue = computed(() => {
    const errors = validation.value;

    switch (currentStep.value) {
        case 1:
            return !errors.name && !errors.phone_number;
        case 2:
            return !errors.street && !errors.street_number && !errors.neighborhood
                && !errors.postal_code && !errors.state;
        case 3:
            return !errors.vehicle && !errors.vehicle_category_id && !errors.vehicle_brand && !errors.vehicle_model;
        case 4:
            return !errors.services;
        case 5:
            return !errors.date && !errors.block && !!form.block;
        default:
            return true;
    }
});

// ---------------------------------------------------------------- Navegación
const nextStep = () => {
    if (currentStep.value >= STEPS.length) return;

    if (!canContinue.value) {
        attempted[currentStep.value] = true;
        markStepTouched(currentStep.value);
        return;
    }

    markStepTouched(currentStep.value);
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

// ---------------------------------------------------------------- Envío
const submit = () => {
    if (isSubmitting.value) return;

    submitError.value = '';

    if (Object.keys(validation.value).length > 0) {
        const firstInvalidStep = [1, 2, 3, 4, 5].find((step) =>
            (STEP_FIELDS[step] ?? []).some((field) => validation.value[field])
        ) ?? 1;

        currentStep.value = firstInvalidStep;
        attempted[firstInvalidStep] = true;
        markStepTouched(firstInvalidStep);
        submitError.value = 'Revisa los campos marcados en rojo antes de confirmar.';
        return;
    }

    isSubmitting.value = true;

    router.post(
        route('client.bookings.store'),
        {
            name: form.name,
            phone_number: form.phone_number,
            address_id: form.address_id,
            street: form.street,
            street_number: form.street_number,
            neighborhood: form.neighborhood,
            postal_code: form.postal_code,
            state: form.state,
            latitude: form.latitude,
            longitude: form.longitude,
            car_id: vehicleMode.value === 'saved' ? form.car_id : null,
            vehicle_category_id: form.vehicle_category_id || null,
            vehicle_brand: vehicleMode.value === 'new' ? form.vehicle_brand : null,
            vehicle_model: vehicleMode.value === 'new' ? form.vehicle_model : null,
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

                Object.keys(errors).forEach((field) => {
                    if (field === 'scheduled_at') {
                        touched.block = true;
                        currentStep.value = 5;
                        return;
                    }
                    touched[field] = true;
                });
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
                        Reserva en {{ STEPS.length }} pasos. Tus domicilios y vehículos se llenan automáticamente.
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
                                    :class="fieldClass('name')"
                                    @blur="touched.name = true"
                                />
                                <p v-if="showError('name')" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                                    <i class="pi pi-exclamation-circle text-[11px]"></i>{{ showError('name') }}
                                </p>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Teléfono *</label>
                                <input
                                    v-model="form.phone_number"
                                    type="tel"
                                    :class="fieldClass('phone_number')"
                                    placeholder="33 1234 5678"
                                    @blur="touched.phone_number = true"
                                />
                                <p v-if="showError('phone_number')" class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                                    <i class="pi pi-exclamation-circle text-[11px]"></i>{{ showError('phone_number') }}
                                </p>
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
                            Elige un domicilio guardado o captura una dirección nueva.
                        </p>

                        <div v-if="addresses.length" class="mt-5">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-mist-400">Domicilios guardados</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button
                                    v-for="address in addresses"
                                    :key="address.id"
                                    type="button"
                                    class="rounded-2xl border p-3 text-left transition"
                                    :class="form.address_id === address.id
                                        ? 'border-cloud-500 bg-cloud-50/70 ring-2 ring-cloud-200 dark:bg-cloud-900/20'
                                        : 'border-mist-200 bg-white hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800/60'"
                                    @click="selectAddress(address)"
                                >
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-mist-900 dark:text-white">{{ address.label }}</span>
                                        <span v-if="address.is_default" class="rounded-full bg-cloud-600 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-white">Principal</span>
                                    </span>
                                    <span class="mt-1 block text-xs text-mist-500 dark:text-slate-400">{{ address.full_address }}</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-2xl border border-dashed p-3 text-left text-sm font-medium transition"
                                    :class="form.address_id === null
                                        ? 'border-cloud-500 bg-cloud-50/70 text-cloud-700 ring-2 ring-cloud-200 dark:bg-cloud-900/20 dark:text-cloud-300'
                                        : 'border-mist-300 text-mist-500 hover:border-cloud-300 dark:border-slate-600 dark:text-slate-400'"
                                    @click="clearAddress"
                                >
                                    <i class="pi pi-plus mr-1 text-xs"></i>
                                    Usar otra dirección
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Calle *</label>
                                <input v-model="form.street" type="text" :class="fieldClass('street')" @input="markAddressEdited" @blur="touched.street = true" />
                                <p v-if="showError('street')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('street') }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Número *</label>
                                <input v-model="form.street_number" type="text" :class="fieldClass('street_number')" @input="markAddressEdited" @blur="touched.street_number = true" />
                                <p v-if="showError('street_number')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('street_number') }}</p>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Colonia *</label>
                                <input v-model="form.neighborhood" type="text" :class="fieldClass('neighborhood')" @input="markAddressEdited" @blur="touched.neighborhood = true" />
                                <p v-if="showError('neighborhood')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('neighborhood') }}</p>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Código postal *</label>
                                <input
                                    v-model="form.postal_code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="5"
                                    :class="fieldClass('postal_code')"
                                    @input="form.postal_code = $event.target.value.replace(/\D/g, '').slice(0, 5); markAddressEdited()"
                                    @blur="touched.postal_code = true"
                                />
                                <p v-if="showError('postal_code')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('postal_code') }}</p>
                            </div>
                            <div class="sm:col-span-6">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Estado *</label>
                                <select v-model="form.state" :class="fieldClass('state')" @change="markAddressEdited" @blur="touched.state = true">
                                    <option v-for="state in MEXICAN_STATES" :key="state" :value="state">{{ state }}</option>
                                </select>
                                <p v-if="showError('state')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('state') }}</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Ubicación exacta (opcional)</p>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-cloud-600 to-sage-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lift transition hover:from-cloud-700 hover:to-sage-600"
                                    @click="useMyLocation"
                                >
                                    <i class="pi pi-map-marker"></i>
                                    Usar mi ubicación
                                </button>
                            </div>
                            <div ref="mapElement" class="mt-3 h-64 w-full overflow-hidden rounded-2xl border border-mist-200 dark:border-slate-700" style="z-index: 0"></div>
                            <p class="mt-2 text-[11px] text-mist-400">
                                Toca el mapa o arrastra el punto para ajustar la referencia exacta.
                                <span v-if="form.latitude"> · {{ form.latitude }}, {{ form.longitude }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- PASO 3: VEHÍCULO -->
                    <div v-else-if="currentStep === 3">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Vehículo</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Selecciona uno de tus vehículos o registra los datos de otro. El vehículo nuevo no se guarda en tu perfil.
                        </p>

                        <div v-if="cars.length" class="mt-5 grid gap-2 sm:grid-cols-2">
                            <button
                                v-for="car in cars"
                                :key="car.id"
                                type="button"
                                class="rounded-2xl border p-3 text-left transition"
                                :class="vehicleMode === 'saved' && form.car_id === car.id
                                    ? 'border-cloud-500 bg-cloud-50/70 ring-2 ring-cloud-200 dark:bg-cloud-900/20'
                                    : 'border-mist-200 bg-white hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800/60'"
                                @click="chooseCar(car)"
                            >
                                <span class="flex items-center gap-2">
                                    <i class="pi pi-car text-cloud-600"></i>
                                    <span class="text-sm font-semibold text-mist-900 dark:text-white">{{ car.brand }} {{ car.model }}</span>
                                </span>
                                <span class="mt-1 block text-xs text-mist-500 dark:text-slate-400">
                                    {{ [car.year, car.color, car.plates, car.vehicle_category].filter(Boolean).join(' · ') || 'Sin detalles' }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="rounded-2xl border border-dashed p-3 text-left text-sm font-medium transition"
                                :class="vehicleMode === 'new'
                                    ? 'border-cloud-500 bg-cloud-50/70 text-cloud-700 ring-2 ring-cloud-200 dark:bg-cloud-900/20 dark:text-cloud-300'
                                    : 'border-mist-300 text-mist-500 hover:border-cloud-300 dark:border-slate-600 dark:text-slate-400'"
                                @click="chooseNewVehicle"
                            >
                                <i class="pi pi-plus mr-1 text-xs"></i>
                                Agregar otro vehículo
                            </button>
                        </div>

                        <p v-if="showError('vehicle')" class="mt-2 text-xs font-medium text-rose-600">{{ showError('vehicle') }}</p>

                        <div v-if="vehicleMode === 'new'" class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Marca *</label>
                                <input v-model="form.vehicle_brand" type="text" :class="fieldClass('vehicle_brand')" placeholder="Toyota" @blur="touched.vehicle_brand = true" />
                                <p v-if="showError('vehicle_brand')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('vehicle_brand') }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Modelo *</label>
                                <input v-model="form.vehicle_model" type="text" :class="fieldClass('vehicle_model')" placeholder="Corolla 2021" @blur="touched.vehicle_model = true" />
                                <p v-if="showError('vehicle_model')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('vehicle_model') }}</p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Tipo de vehículo *</label>
                            <select v-model="form.vehicle_category_id" :class="fieldClass('vehicle_category_id')" @blur="touched.vehicle_category_id = true">
                                <option value="">Selecciona una categoría…</option>
                                <option v-for="category in vehicleCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <p v-if="showError('vehicle_category_id')" class="mt-1.5 text-xs font-medium text-rose-600">{{ showError('vehicle_category_id') }}</p>
                            <p class="mt-2 text-[11px] text-mist-400">
                                La categoría define el precio y la duración del servicio.
                            </p>
                        </div>
                    </div>

                    <!-- PASO 4: SERVICIOS -->
                    <div v-else-if="currentStep === 4">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Modalidad y adicionales</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Elige si tu lavado es en seco o con agua y súmale extras.
                        </p>

                        <div class="mt-6 space-y-3">
                            <button
                                v-for="pkg in packages"
                                :key="pkg.id"
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-4 py-3 text-left transition"
                                :class="form.services.includes(pkg.id)
                                    ? 'border-cloud-500 bg-cloud-50/70 ring-2 ring-cloud-200 dark:bg-cloud-900/20'
                                    : 'border-mist-200 bg-white hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800/60'"
                                @click="selectPackage(pkg)"
                            >
                                <span
                                    class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border-2"
                                    :class="form.services.includes(pkg.id) ? 'border-cloud-600 bg-cloud-600 text-white' : 'border-mist-300'"
                                >
                                    <span v-if="form.services.includes(pkg.id)" class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-mist-900 dark:text-white">{{ pkg.name }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="typeMeta[pkg.type]?.chip || typeMeta.Seco.chip">
                                            {{ typeMeta[pkg.type]?.water || typeMeta.Seco.water }}
                                        </span>
                                        <span class="rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-semibold text-mist-500 dark:bg-slate-700 dark:text-slate-300">+{{ pkg.points }} pts</span>
                                    </span>
                                    <span class="mt-1 block text-xs text-mist-500 dark:text-slate-400">{{ pkg.description }}</span>
                                </span>
                                <span class="whitespace-nowrap text-sm font-semibold text-mist-900 dark:text-slate-100">{{ formatCurrency(priceFor(pkg)) }}</span>
                            </button>
                        </div>
                        <p v-if="showError('services')" class="mt-2 text-xs font-medium text-rose-600">{{ showError('services') }}</p>

                        <div class="mt-8">
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Servicios adicionales</p>
                                <span class="text-xs text-mist-400">Opcional</span>
                            </div>

                            <div v-if="addons.length" class="mt-3 grid gap-3 sm:grid-cols-2">
                                <button
                                    v-for="addon in addons"
                                    :key="addon.id"
                                    type="button"
                                    class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-left transition"
                                    :class="form.services.includes(addon.id)
                                        ? 'border-cloud-500 bg-cloud-50/70 ring-2 ring-cloud-200 dark:bg-cloud-900/20'
                                        : 'border-mist-200 bg-white hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800/60'"
                                    @click="toggleAddon(addon)"
                                >
                                    <span class="flex items-center gap-2.5">
                                        <span
                                            class="grid h-5 w-5 shrink-0 place-items-center rounded-md border-2"
                                            :class="form.services.includes(addon.id) ? 'border-cloud-600 bg-cloud-600 text-white' : 'border-mist-300'"
                                        >
                                            <svg v-if="form.services.includes(addon.id)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span class="text-sm font-medium text-mist-800 dark:text-slate-200">{{ addon.name }}</span>
                                    </span>
                                    <span class="whitespace-nowrap text-xs font-semibold text-mist-600 dark:text-slate-300">+ {{ formatCurrency(priceFor(addon)) }}</span>
                                </button>
                            </div>
                            <p v-else class="mt-3 text-sm text-mist-400">No hay adicionales disponibles.</p>
                        </div>
                    </div>

                    <!-- PASO 5: HORARIO -->
                    <div v-else-if="currentStep === 5">
                        <h2 class="text-xl font-semibold text-mist-900 dark:text-white">Fecha y horario</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Los días y horas fuera de operación aparecen deshabilitados.
                        </p>

                        <!-- Calendario -->
                        <div class="mt-5 overflow-hidden rounded-3xl border border-mist-200 bg-white shadow-soft dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="flex items-center justify-between gap-3 bg-gradient-to-r from-cloud-600 to-sage-500 px-4 py-3 text-white">
                                <button
                                    type="button"
                                    class="grid h-9 w-9 place-items-center rounded-full bg-white/15 transition enabled:hover:bg-white/25 disabled:opacity-30"
                                    :disabled="!canGoPrevMonth"
                                    aria-label="Mes anterior"
                                    @click="changeMonth(-1)"
                                >
                                    <i class="pi pi-chevron-left text-xs"></i>
                                </button>
                                <p class="text-sm font-semibold capitalize tracking-wide">{{ monthLabel }}</p>
                                <button
                                    type="button"
                                    class="grid h-9 w-9 place-items-center rounded-full bg-white/15 transition enabled:hover:bg-white/25 disabled:opacity-30"
                                    :disabled="!canGoNextMonth"
                                    aria-label="Mes siguiente"
                                    @click="changeMonth(1)"
                                >
                                    <i class="pi pi-chevron-right text-xs"></i>
                                </button>
                            </div>

                            <div class="grid grid-cols-7 gap-1 px-3 pb-1 pt-3 text-center text-[10px] font-semibold uppercase tracking-wider text-mist-400">
                                <span v-for="day in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']" :key="day">{{ day }}</span>
                            </div>

                            <div class="grid grid-cols-7 gap-1 p-3 pt-1">
                                <button
                                    v-for="day in calendarDays"
                                    :key="day.iso"
                                    type="button"
                                    :disabled="day.disabled"
                                    :title="day.reason || undefined"
                                    class="relative aspect-square rounded-xl border text-center transition-all duration-200"
                                    :class="[
                                        !day.inMonth ? 'border-transparent opacity-30' : 'border-mist-100',
                                        day.disabled
                                            ? 'cursor-not-allowed bg-mist-50 text-mist-300 line-through dark:bg-slate-700/30 dark:text-slate-600'
                                            : 'bg-white text-mist-700 hover:border-cloud-300 hover:bg-cloud-50 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700',
                                        form.date === day.iso
                                            ? '!border-cloud-600 !bg-cloud-600 !text-white shadow-soft'
                                            : '',
                                        day.isToday && form.date !== day.iso ? 'ring-1 ring-cloud-300' : '',
                                    ]"
                                    @click="selectDay(day)"
                                >
                                    <span class="text-sm font-semibold">{{ day.day }}</span>
                                    <span
                                        v-if="day.partial && !day.disabled"
                                        class="absolute bottom-1 left-1/2 h-1.5 w-1.5 -translate-x-1/2 rounded-full bg-amber-400"
                                    ></span>
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 border-t border-mist-100 px-4 py-2.5 text-[10px] text-mist-400 dark:border-slate-700">
                                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-mist-300"></span> Día no operativo</span>
                                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Horario especial</span>
                                <span class="ml-auto font-semibold uppercase tracking-wider">
                                    {{ scheduleConfig.opening_time }}–{{ scheduleConfig.closing_time }}
                                </span>
                            </div>
                        </div>

                        <p v-if="showError('date')" class="mt-2 text-xs font-medium text-rose-600">{{ showError('date') }}</p>

                        <div v-if="form.date" class="mt-4 rounded-2xl border border-cloud-100 bg-cloud-50/60 px-4 py-3 dark:border-cloud-500/20 dark:bg-cloud-500/10">
                            <p class="text-sm font-semibold capitalize text-mist-900 dark:text-white">{{ formatDateLong(form.date) }}</p>
                            <p v-if="dateExceptionLabel(form.date)" class="mt-0.5 text-xs font-medium text-amber-600">
                                <i class="pi pi-info-circle mr-1"></i>{{ dateExceptionLabel(form.date) }}
                            </p>
                            <p v-else class="mt-0.5 text-xs text-mist-500 dark:text-slate-400">
                                Duración estimada: {{ durationLabel || '—' }}
                            </p>
                        </div>

                        <!-- Horarios -->
                        <div class="mt-5">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-mist-400">Horarios disponibles</p>

                            <div v-if="!form.date" class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-500 dark:border-slate-700 dark:bg-slate-800/60">
                                Elige una fecha en el calendario.
                            </div>
                            <div v-else-if="isDateClosed(form.date)" class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-500 dark:border-slate-700 dark:bg-slate-800/60">
                                Este día no hay servicio. Elige otra fecha.
                            </div>
                            <div v-else-if="isLoadingTimes" class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-400 dark:border-slate-700 dark:bg-slate-800/60">
                                <i class="pi pi-spin pi-spinner mr-2"></i>Consultando horarios…
                            </div>
                            <div v-else-if="operatingSlots.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button
                                    v-for="slot in operatingSlots"
                                    :key="slot.time"
                                    type="button"
                                    :disabled="!slotAvailable(slot)"
                                    class="rounded-xl border px-3 py-2.5 text-sm font-medium transition"
                                    :class="form.block === slot.time
                                        ? 'border-cloud-600 bg-cloud-600 text-white shadow-soft'
                                        : slotAvailable(slot)
                                            ? 'border-mist-200 bg-white text-mist-600 hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                            : 'cursor-not-allowed border-mist-100 bg-mist-50 text-mist-300 line-through dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-600'"
                                    @click="form.block = slot.time"
                                >
                                    {{ slot.time }} – {{ slotEndLabel(slot.time) }}
                                </button>
                            </div>
                            <div v-else class="rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-6 text-center text-sm text-mist-500 dark:border-slate-700 dark:bg-slate-800/60">
                                No hay horarios disponibles para este día. Prueba con otra fecha.
                            </div>
                            <p v-if="showError('block')" class="mt-2 text-xs font-medium text-rose-600">{{ showError('block') }}</p>
                        </div>
                    </div>

                    <!-- PASO 6: PAGO -->
                    <div v-else-if="currentStep === 6">
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
                                :class="form.payment_method === method
                                    ? 'border-cloud-600 bg-cloud-50 text-cloud-700 ring-2 ring-cloud-200 dark:bg-cloud-900/20 dark:text-cloud-300'
                                    : 'border-mist-200 bg-white text-mist-600 hover:border-cloud-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'"
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
                                <dt class="text-mist-400">Vehículo</dt>
                                <dd class="max-w-[62%] text-right font-medium text-mist-800 dark:text-slate-200">
                                    {{ [form.vehicle_brand, form.vehicle_model].filter(Boolean).join(' ') || 'Por definir' }}
                                    <span class="block text-xs font-normal text-mist-400">{{ selectedCategory?.name || 'Sin categoría' }}</span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-mist-400">Fecha</dt>
                                <dd class="text-right font-medium text-mist-800 dark:text-slate-200">
                                    {{ form.date ? formatDateLong(form.date) : 'Por definir' }}
                                    <span v-if="form.block" class="block text-xs font-normal text-mist-400">{{ form.block }} – {{ slotEndLabel(form.block) }}</span>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 space-y-2 border-t border-mist-100 pt-4 text-sm dark:border-slate-700">
                            <div v-for="service in selectedServices" :key="service.id" class="flex items-center justify-between gap-2">
                                <span class="truncate text-mist-500 dark:text-slate-400">{{ service.name }}</span>
                                <span class="whitespace-nowrap font-medium text-mist-800 dark:text-slate-200">{{ formatCurrency(priceFor(service)) }}</span>
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
