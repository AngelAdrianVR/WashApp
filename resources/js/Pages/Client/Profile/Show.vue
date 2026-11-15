<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    profile: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    cars: { type: Array, default: () => [] },
    addresses: { type: Array, default: () => [] },
    claimedRewards: { type: Array, default: () => [] },
    vehicleCategories: { type: Array, default: () => [] },
    sides: { type: Object, default: () => ({}) },
});

const page = usePage();
const toast = useToast();

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 3200 });
        }
    }
);

const summary = computed(() => [
    { label: 'Servicios solicitados', value: props.stats.total_services ?? 0, icon: 'pi pi-calendar', tone: 'cloud' },
    { label: 'Servicios completados', value: props.stats.completed_services ?? 0, icon: 'pi pi-check-circle', tone: 'sage' },
    { label: 'Próximos servicios', value: props.stats.upcoming_services ?? 0, icon: 'pi pi-clock', tone: 'amber' },
]);

const toneClasses = {
    cloud: 'bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300',
    sage: 'bg-sage-50 text-sage-700 dark:bg-sage-500/15 dark:text-sage-300',
    amber: 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
};

const avatarForm = useForm({ photo: null });

const uploadAvatar = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    avatarForm.photo = file;
    avatarForm.post(route('client.profile.avatar'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => avatarForm.reset(),
        onFinish: () => {
            event.target.value = '';
        },
    });
};

const deleteAvatar = () => {
    if (!confirm('¿Eliminar tu foto de perfil?')) return;
    router.delete(route('client.profile.avatar.destroy'), { preserveScroll: true });
};

// Datos personales
const dataForm = useForm({
    name: props.profile.name,
    phone_number: props.profile.phone_number ?? '',
});

const saveData = () => dataForm.put(route('client.profile.update'), { preserveScroll: true });

// Vehículos
const showCarForm = ref(false);
const carForm = useForm({
    brand: '',
    model: '',
    year: null,
    color: '',
    plates: '',
    vehicle_category_id: '',
});
const editingCar = ref(null);
const editForm = useForm({
    brand: '',
    model: '',
    year: null,
    color: '',
    plates: '',
    vehicle_category_id: '',
});
const expandedCars = ref([]);

const toggleCar = (carId) => {
    expandedCars.value = expandedCars.value.includes(carId)
        ? expandedCars.value.filter((id) => id !== carId)
        : [...expandedCars.value, carId];
};

const addCar = () => {
    carForm.post(route('client.cars.store'), {
        preserveScroll: true,
        onSuccess: () => {
            carForm.reset();
            showCarForm.value = false;
        },
    });
};

const startEdit = (car) => {
    editingCar.value = car.id;

    if (!expandedCars.value.includes(car.id)) {
        expandedCars.value.push(car.id);
    }

    editForm.brand = car.brand;
    editForm.model = car.model;
    editForm.year = car.year;
    editForm.color = car.color ?? '';
    editForm.plates = car.plates ?? '';
    editForm.vehicle_category_id = car.vehicle_category_id ?? '';
};

const cancelEdit = () => {
    editingCar.value = null;
    editForm.reset();
};

const saveEdit = (car) => {
    editForm.put(route('client.cars.update', car.id), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const removeCar = (car) => {
    if (!confirm(`¿Eliminar el vehículo ${car.brand} ${car.model}?`)) return;
    router.delete(route('client.cars.destroy', car.id), { preserveScroll: true });
};

const uploadPhoto = (car, side, event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    router.post(
        route('client.cars.photos.store', car.id),
        { photo: file, side },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                event.target.value = '';
            },
        }
    );
};

const deletePhoto = (car, mediaId) => {
    if (!confirm('¿Eliminar esta foto?')) return;
    router.delete(route('client.cars.photos.destroy', { car: car.id, media: mediaId }), { preserveScroll: true });
};

// Domicilios
const showAddressForm = ref(false);
const locatingAddress = ref(false);
const expandedAddresses = ref([]);

const toggleAddress = (addressId) => {
    expandedAddresses.value = expandedAddresses.value.includes(addressId)
        ? expandedAddresses.value.filter((id) => id !== addressId)
        : [...expandedAddresses.value, addressId];
};
const addressForm = useForm({
    label: 'Casa',
    street: '',
    street_number: '',
    neighborhood: '',
    postal_code: '',
    city: 'Guadalajara',
    state: 'Jalisco',
    latitude: null,
    longitude: null,
    references: '',
    is_default: false,
});

const addAddress = () => {
    addressForm.post(route('client.addresses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addressForm.reset();
            showAddressForm.value = false;
        },
    });
};

const makeDefaultAddress = (address) => {
    router.patch(route('client.addresses.default', address.id), {}, { preserveScroll: true });
};

const removeAddress = (address) => {
    if (!confirm(`¿Eliminar el domicilio "${address.label}"?`)) return;
    router.delete(route('client.addresses.destroy', address.id), { preserveScroll: true });
};

const locateAddress = () => {
    if (!navigator.geolocation) {
        toast.add({ severity: 'warn', summary: 'Ubicación no disponible', life: 3000 });
        return;
    }

    locatingAddress.value = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            addressForm.latitude = Number(position.coords.latitude.toFixed(7));
            addressForm.longitude = Number(position.coords.longitude.toFixed(7));
            locatingAddress.value = false;
            toast.add({ severity: 'success', summary: 'Ubicación lista', detail: 'Referencia guardada con tu domicilio.', life: 3000 });
        },
        () => {
            locatingAddress.value = false;
            toast.add({ severity: 'warn', summary: 'No se pudo obtener tu ubicación', life: 3000 });
        },
        { timeout: 8000 }
    );
};
</script>

<template>
    <AppLayout title="Mi perfil">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-6xl space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Mi cuenta</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white">Perfil y vehículos</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Tus datos, domicilios y vehículos en un solo lugar.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('client.bookings.create')" class="wa-btn-primary !px-4 !py-2.5">
                        <i class="pi pi-calendar-plus text-xs"></i>
                        Agendar cita
                    </Link>
                    <Link :href="route('client.rewards.index')" class="wa-btn-soft !px-4 !py-2.5">
                        <i class="pi pi-gift"></i>
                        Ver premios
                    </Link>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div
                    v-for="item in summary"
                    :key="item.label"
                    class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800"
                >
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl" :class="toneClasses[item.tone]">
                            <i :class="[item.icon, 'text-sm']"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-mist-400">{{ item.label }}</p>
                            <p class="text-xl font-semibold text-mist-900 dark:text-white">{{ item.value }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <!-- Columna izquierda -->
                <div class="space-y-6">
                    <!-- Foto de perfil y datos -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex flex-col items-center text-center">
                            <div class="relative">
                                <img
                                    :src="profile.avatar_url || $page.props.auth.user.profile_photo_url"
                                    :alt="profile.name"
                                    class="h-28 w-28 rounded-3xl object-cover ring-4 ring-white shadow-soft dark:ring-slate-700"
                                />
                                <label
                                    class="absolute -bottom-2 -right-2 grid h-10 w-10 cursor-pointer place-items-center rounded-2xl bg-cloud-600 text-white shadow-lift transition hover:bg-cloud-700"
                                    title="Cambiar foto"
                                >
                                    <input type="file" class="sr-only" accept="image/*" @change="uploadAvatar" />
                                    <svg v-if="!avatarForm.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l4-1 10-10a2.1 2.1 0 013 3L10 19l-7 1 0-3z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" d="M12 3a9 9 0 109 9" />
                                    </svg>
                                </label>
                            </div>

                            <p class="mt-5 text-lg font-semibold text-mist-900 dark:text-white">{{ profile.name }}</p>
                            <p class="text-sm text-mist-400">{{ profile.email }}</p>

                            <button
                                v-if="profile.avatar_url"
                                type="button"
                                class="mt-3 text-xs font-medium text-rose-500 transition hover:text-rose-600"
                                @click="deleteAvatar"
                            >
                                Quitar foto
                            </button>

                            <InputError class="mt-2" :message="avatarForm.errors.photo" />
                        </div>

                        <form class="mt-6 space-y-4 border-t border-mist-200/70 pt-6 dark:border-slate-700" @submit.prevent="saveData">
                            <div>
                                <label for="name" class="wa-label">Nombre</label>
                                <input id="name" v-model="dataForm.name" type="text" class="wa-input" />
                                <InputError class="mt-1.5" :message="dataForm.errors.name" />
                            </div>
                            <div>
                                <label for="phone" class="wa-label">Teléfono</label>
                                <input id="phone" v-model="dataForm.phone_number" type="text" class="wa-input" placeholder="33 1234 5678" />
                                <InputError class="mt-1.5" :message="dataForm.errors.phone_number" />
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[11px] uppercase tracking-wider text-mist-400">
                                    Cliente desde {{ profile.member_since }}
                                </p>
                                <button type="submit" class="wa-btn-primary !px-4 !py-2.5" :disabled="dataForm.processing">
                                    {{ dataForm.processing ? 'Guardando…' : 'Guardar' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Puntos -->
                    <section class="relative overflow-hidden rounded-3xl border border-sage-200/70 bg-gradient-to-br from-sage-600 to-cloud-700 p-6 text-white shadow-lift">
                        <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sage-100">Puntos disponibles</p>
                        <p class="mt-2 text-5xl font-semibold tracking-tight">{{ profile.points }}</p>
                        <p class="mt-2 text-sm text-sage-50/85">
                            Canjéalos por lavados gratis, tratamientos y membresías.
                        </p>
                        <Link :href="route('client.rewards.index')" class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-white/95 px-4 py-2 text-sm font-semibold text-mist-800 transition hover:bg-white">
                            Ir a premios
                            <i class="pi pi-arrow-right text-xs"></i>
                        </Link>
                    </section>

                    <!-- Premios canjeados -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Premios canjeados</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Recompensas que ya usaste.</p>
                            </div>
                            <span class="rounded-full bg-sage-50 px-3 py-1 text-xs font-semibold text-sage-700 dark:bg-sage-500/15 dark:text-sage-300">
                                {{ claimedRewards.length }}
                            </span>
                        </div>

                        <div v-if="claimedRewards.length" class="mt-4 space-y-3">
                            <div
                                v-for="reward in claimedRewards"
                                :key="reward.id"
                                class="flex items-start gap-3 rounded-2xl border border-sage-100 bg-sage-50/60 p-3 dark:border-sage-500/20 dark:bg-sage-500/10"
                            >
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-sage-500 text-white">
                                    <i class="pi pi-check text-xs"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">
                                        {{ reward.code || 'Recompensa aplicada' }}
                                    </p>
                                    <p class="text-xs text-mist-500 dark:text-slate-400">
                                        {{ reward.description || 'Canjeada correctamente' }}
                                    </p>
                                    <p class="mt-1 text-[11px] uppercase tracking-wider text-mist-400">
                                        {{ reward.discount || '' }} · {{ reward.claimed_at || 'Sin fecha' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            Aún no has canjeado premios. Cuando lo hagas, aparecerán aquí.
                        </p>
                    </section>
                </div>

                <!-- Columna derecha -->
                <div class="space-y-6">
                    <!-- Vehículos -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Mis vehículos</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                                    Agrega tus coches para agendar más rápido.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="wa-btn-primary !px-4 !py-2.5"
                                :aria-expanded="showCarForm"
                                @click="showCarForm = !showCarForm"
                            >
                                <i :class="['text-xs', showCarForm ? 'pi pi-times' : 'pi pi-plus']"></i>
                                {{ showCarForm ? 'Cerrar' : 'Agregar vehículo' }}
                            </button>
                        </div>

                        <!-- Formulario colapsable -->
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <form
                                v-if="showCarForm"
                                class="mt-5 grid gap-3 rounded-2xl border border-dashed border-cloud-300 bg-cloud-50/40 p-4 sm:grid-cols-2 dark:border-slate-600 dark:bg-slate-800/40"
                                @submit.prevent="addCar"
                            >
                                <div>
                                    <label for="brand" class="wa-label">Marca *</label>
                                    <input id="brand" v-model="carForm.brand" type="text" class="wa-input" placeholder="Toyota" />
                                    <InputError class="mt-1.5" :message="carForm.errors.brand" />
                                </div>
                                <div>
                                    <label for="model" class="wa-label">Modelo *</label>
                                    <input id="model" v-model="carForm.model" type="text" class="wa-input" placeholder="Corolla 2021" />
                                    <InputError class="mt-1.5" :message="carForm.errors.model" />
                                </div>
                                <div>
                                    <label for="category" class="wa-label">Tipo de vehículo *</label>
                                    <select id="category" v-model="carForm.vehicle_category_id" class="wa-input" required>
                                        <option value="">Selecciona…</option>
                                        <option v-for="category in vehicleCategories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-1.5" :message="carForm.errors.vehicle_category_id" />
                                </div>
                                <div>
                                    <label for="plates" class="wa-label">Placas</label>
                                    <input id="plates" v-model="carForm.plates" type="text" class="wa-input" placeholder="ABC-1234" />
                                    <InputError class="mt-1.5" :message="carForm.errors.plates" />
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:col-span-2">
                                    <div>
                                        <label for="year" class="wa-label">Año</label>
                                        <input id="year" v-model="carForm.year" type="number" min="1950" :max="new Date().getFullYear() + 1" class="wa-input" placeholder="2021" />
                                        <InputError class="mt-1.5" :message="carForm.errors.year" />
                                    </div>
                                    <div>
                                        <label for="color" class="wa-label">Color</label>
                                        <input id="color" v-model="carForm.color" type="text" class="wa-input" placeholder="Azul" />
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <button type="submit" class="wa-btn-primary w-full" :disabled="carForm.processing">
                                        <i class="pi pi-save text-xs"></i>
                                        {{ carForm.processing ? 'Guardando…' : 'Guardar vehículo' }}
                                    </button>
                                </div>
                            </form>
                        </Transition>

                        <p v-if="!cars.length && !showCarForm" class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            Aún no tienes vehículos registrados. Agrega el primero con el botón de arriba.
                        </p>

                        <div v-if="cars.length" class="mt-5 grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="car in cars"
                                :key="car.id"
                                class="rounded-2xl border p-3.5 transition"
                                :class="expandedCars.includes(car.id)
                                    ? 'border-cloud-300 bg-cloud-50/50 dark:border-cloud-500/40 dark:bg-cloud-500/10'
                                    : 'border-mist-200 bg-white dark:border-slate-600 dark:bg-slate-800/60'"
                            >
                                <button type="button" class="w-full text-left" @click="toggleCar(car.id)">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="break-words text-sm font-semibold leading-snug text-mist-900 dark:text-white">{{ car.brand }} {{ car.model }}</p>
                                            <p class="mt-0.5 break-words text-[11px] leading-snug text-mist-400">
                                                {{ [car.vehicle_category, car.year, car.color].filter(Boolean).join(' · ') || 'Sin detalles' }}
                                            </p>
                                        </div>
                                        <i :class="['pi mt-1 text-xs text-mist-400 transition-transform', expandedCars.includes(car.id) ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[10px] font-medium text-mist-500 dark:text-slate-400">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="pi pi-sparkles text-[9px]"></i>
                                            {{ car.wash_count ?? 0 }} lavada{{ car.wash_count === 1 ? '' : 's' }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <i class="pi pi-calendar text-[9px]"></i>
                                            Última: {{ car.last_wash_at || 'Sin registros' }}
                                        </span>
                                    </div>
                                </button>

                                <div v-if="expandedCars.includes(car.id)" class="mt-3 space-y-3 border-t border-mist-200/70 pt-3 dark:border-slate-700">
                                    <div v-if="editingCar !== car.id">
                                        <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                                            <div>
                                                <p class="text-[9px] uppercase tracking-wider text-mist-400">Placas</p>
                                                <p class="font-medium text-mist-700 dark:text-slate-200">{{ car.plates || '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] uppercase tracking-wider text-mist-400">Año</p>
                                                <p class="font-medium text-mist-700 dark:text-slate-200">{{ car.year || '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] uppercase tracking-wider text-mist-400">Color</p>
                                                <p class="font-medium text-mist-700 dark:text-slate-200">{{ car.color || '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] uppercase tracking-wider text-mist-400">Categoría</p>
                                                <p class="font-medium text-mist-700 dark:text-slate-200">{{ car.vehicle_category || '—' }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            <div
                                                v-for="slot in car.photos"
                                                :key="slot.side"
                                                class="group relative aspect-[4/3] overflow-hidden rounded-xl border border-mist-200 bg-mist-50 dark:border-slate-600 dark:bg-slate-700/40"
                                            >
                                                <label class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center">
                                                    <input type="file" class="sr-only" accept="image/*" @change="uploadPhoto(car, slot.side, $event)" />

                                                    <img v-if="slot.url" :src="slot.url" :alt="slot.label" class="absolute inset-0 h-full w-full object-cover" />
                                                    <span v-else class="flex flex-col items-center gap-1 text-mist-400">
                                                        <i class="pi pi-camera text-base"></i>
                                                        <span class="text-[9px] font-semibold uppercase tracking-wider">Subir foto</span>
                                                    </span>

                                                    <span class="absolute inset-0 bg-black/0 transition group-hover:bg-black/25"></span>
                                                    <span class="absolute bottom-1 left-1 rounded-md bg-black/55 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-white">
                                                        {{ slot.label }}
                                                    </span>
                                                </label>

                                                <button
                                                    v-if="slot.url"
                                                    type="button"
                                                    class="absolute right-1 top-1 z-10 grid h-7 w-7 place-items-center rounded-lg bg-black/50 text-white opacity-0 transition hover:bg-rose-600 group-hover:opacity-100"
                                                    aria-label="Eliminar foto"
                                                    @click.stop="deletePhoto(car, slot.media_id)"
                                                >
                                                    <i class="pi pi-times text-[10px]"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex gap-2">
                                            <button type="button" class="wa-btn-soft !px-3 !py-2 text-xs" @click="startEdit(car)">
                                                <i class="pi pi-pencil text-[10px]"></i>
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                                @click="removeCar(car)"
                                            >
                                                <i class="pi pi-trash text-[10px]"></i>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>

                                    <form v-else class="grid gap-2 sm:grid-cols-2" @submit.prevent="saveEdit(car)">
                                        <div>
                                            <label class="wa-label">Marca *</label>
                                            <input v-model="editForm.brand" type="text" class="wa-input" />
                                            <InputError class="mt-1" :message="editForm.errors.brand" />
                                        </div>
                                        <div>
                                            <label class="wa-label">Modelo *</label>
                                            <input v-model="editForm.model" type="text" class="wa-input" />
                                            <InputError class="mt-1" :message="editForm.errors.model" />
                                        </div>
                                        <div>
                                            <label class="wa-label">Tipo de vehículo *</label>
                                            <select v-model="editForm.vehicle_category_id" class="wa-input" required>
                                                <option value="">Selecciona…</option>
                                                <option v-for="category in vehicleCategories" :key="category.id" :value="category.id">
                                                    {{ category.name }}
                                                </option>
                                            </select>
                                            <InputError class="mt-1" :message="editForm.errors.vehicle_category_id" />
                                        </div>
                                        <div>
                                            <label class="wa-label">Placas</label>
                                            <input v-model="editForm.plates" type="text" class="wa-input" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 sm:col-span-2">
                                            <div>
                                                <label class="wa-label">Año</label>
                                                <input v-model="editForm.year" type="number" class="wa-input" />
                                            </div>
                                            <div>
                                                <label class="wa-label">Color</label>
                                                <input v-model="editForm.color" type="text" class="wa-input" />
                                            </div>
                                        </div>
                                        <div class="flex gap-2 sm:col-span-2">
                                            <button type="submit" class="wa-btn-primary !px-3 !py-2 text-xs" :disabled="editForm.processing">Guardar</button>
                                            <button type="button" class="wa-btn-soft !px-3 !py-2 text-xs" @click="cancelEdit">Cancelar</button>
                                        </div>
                                    </form>
                                </div>
                            </article>
                        </div>
                    </section>

                    <!-- Domicilios -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Mis domicilios</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                                    Guárdalos una vez y selecciónalos al agendar.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="wa-btn-soft !px-4 !py-2.5"
                                :aria-expanded="showAddressForm"
                                @click="showAddressForm = !showAddressForm"
                            >
                                <i :class="['text-xs', showAddressForm ? 'pi pi-times' : 'pi pi-plus']"></i>
                                {{ showAddressForm ? 'Cerrar' : 'Agregar domicilio' }}
                            </button>
                        </div>

                        <div v-if="addresses.length" class="mt-5 grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="address in addresses"
                                :key="address.id"
                                class="rounded-2xl border p-3.5 transition"
                                :class="address.is_default
                                    ? 'border-cloud-300 bg-cloud-50/50 dark:border-cloud-500/40 dark:bg-cloud-500/10'
                                    : 'border-mist-200 bg-white dark:border-slate-600 dark:bg-slate-800/60'"
                            >
                                <button type="button" class="flex w-full items-start justify-between gap-3 text-left" @click="toggleAddress(address.id)">
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2 text-sm font-semibold text-mist-900 dark:text-white">
                                            {{ address.label }}
                                            <span v-if="address.is_default" class="rounded-full bg-cloud-600 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-white">
                                                Principal
                                            </span>
                                        </p>
                                        <p class="mt-0.5 truncate text-[11px] text-mist-500 dark:text-slate-400">
                                            {{ address.street }} {{ address.street_number }}, {{ address.neighborhood }}
                                        </p>
                                    </div>
                                    <i :class="['pi mt-1 text-xs text-mist-400 transition-transform', expandedAddresses.includes(address.id) ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
                                </button>

                                <div v-if="expandedAddresses.includes(address.id)" class="mt-3 space-y-2 border-t border-mist-200/70 pt-3 dark:border-slate-700">
                                    <p class="text-xs text-mist-600 dark:text-slate-300">{{ address.full_address }}</p>
                                    <p v-if="address.references" class="text-[11px] text-mist-400">
                                        Referencias: {{ address.references }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 pt-1">
                                        <button
                                            v-if="!address.is_default"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-cloud-50 px-3 py-2 text-[11px] font-semibold text-cloud-700 transition hover:bg-cloud-100 dark:bg-cloud-500/15 dark:text-cloud-300"
                                            @click="makeDefaultAddress(address)"
                                        >
                                            <i class="pi pi-star text-[10px]"></i>
                                            Usar como principal
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                            @click="removeAddress(address)"
                                        >
                                            <i class="pi pi-trash text-[10px]"></i>
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            Aún no tienes domicilios guardados.
                        </p>

                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <form
                                v-if="showAddressForm"
                                class="mt-5 grid gap-3 rounded-2xl border border-dashed border-cloud-300 bg-cloud-50/40 p-4 sm:grid-cols-2 dark:border-slate-600 dark:bg-slate-800/40"
                                @submit.prevent="addAddress"
                            >
                                <div class="sm:col-span-2">
                                    <label class="wa-label">Etiqueta *</label>
                                    <input v-model="addressForm.label" type="text" class="wa-input" placeholder="Casa, Oficina, Casa de mis papás…" />
                                    <InputError class="mt-1.5" :message="addressForm.errors.label" />
                                </div>
                                <div>
                                    <label class="wa-label">Calle *</label>
                                    <input v-model="addressForm.street" type="text" class="wa-input" />
                                    <InputError class="mt-1.5" :message="addressForm.errors.street" />
                                </div>
                                <div>
                                    <label class="wa-label">Número *</label>
                                    <input v-model="addressForm.street_number" type="text" class="wa-input" />
                                    <InputError class="mt-1.5" :message="addressForm.errors.street_number" />
                                </div>
                                <div>
                                    <label class="wa-label">Colonia *</label>
                                    <input v-model="addressForm.neighborhood" type="text" class="wa-input" />
                                    <InputError class="mt-1.5" :message="addressForm.errors.neighborhood" />
                                </div>
                                <div>
                                    <label class="wa-label">Código postal *</label>
                                    <input
                                        v-model="addressForm.postal_code"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="5"
                                        class="wa-input"
                                        @input="addressForm.postal_code = $event.target.value.replace(/\D/g, '').slice(0, 5)"
                                    />
                                    <InputError class="mt-1.5" :message="addressForm.errors.postal_code" />
                                </div>
                                <div>
                                    <label class="wa-label">Ciudad *</label>
                                    <input v-model="addressForm.city" type="text" class="wa-input" />
                                    <InputError class="mt-1.5" :message="addressForm.errors.city" />
                                </div>
                                <div>
                                    <label class="wa-label">Estado *</label>
                                    <input v-model="addressForm.state" type="text" class="wa-input" />
                                    <InputError class="mt-1.5" :message="addressForm.errors.state" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="wa-label">Referencias para el operador</label>
                                    <input v-model="addressForm.references" type="text" class="wa-input" placeholder="Portón negro, timbre 2, entre calles…" />
                                </div>

                                <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                                    <button
                                        type="button"
                                        class="wa-btn-soft !px-4 !py-2.5"
                                        :disabled="locatingAddress"
                                        @click="locateAddress"
                                    >
                                        <i :class="['text-xs', locatingAddress ? 'pi pi-spin pi-spinner' : 'pi pi-map-marker']"></i>
                                        {{ locatingAddress ? 'Ubicando…' : 'Guardar mi ubicación actual' }}
                                    </button>
                                    <span v-if="addressForm.latitude" class="text-[11px] font-medium text-sage-600">
                                        Ubicación capturada
                                    </span>
                                    <label class="flex items-center gap-2 text-xs font-medium text-mist-600 dark:text-slate-300">
                                        <input v-model="addressForm.is_default" type="checkbox" class="rounded border-mist-300 text-cloud-600 focus:ring-cloud-400" />
                                        Usar como principal
                                    </label>
                                </div>

                                <div class="sm:col-span-2">
                                    <button type="submit" class="wa-btn-primary w-full" :disabled="addressForm.processing">
                                        <i class="pi pi-save text-xs"></i>
                                        {{ addressForm.processing ? 'Guardando…' : 'Guardar domicilio' }}
                                    </button>
                                </div>
                            </form>
                        </Transition>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
