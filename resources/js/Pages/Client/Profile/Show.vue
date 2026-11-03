<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    cars: { type: Array, default: () => [] },
    sides: { type: Object, default: () => ({}) },
});

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
const carForm = useForm({ brand: '', model: '', year: null, color: '', plates: '' });
const editingCar = ref(null);
const editForm = useForm({ brand: '', model: '', year: null, color: '', plates: '' });

const addCar = () => {
    carForm.post(route('client.cars.store'), {
        preserveScroll: true,
        onSuccess: () => carForm.reset(),
    });
};

const startEdit = (car) => {
    editingCar.value = car.id;
    editForm.brand = car.brand;
    editForm.model = car.model;
    editForm.year = car.year;
    editForm.color = car.color ?? '';
    editForm.plates = car.plates ?? '';
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
</script>

<template>
    <AppLayout title="Mi perfil">
        <div class="mx-auto w-full max-w-6xl">
            <!-- Encabezado -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Mi cuenta</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white">Perfil y vehículos</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Administra tu foto, tus datos y los coches que lavamos por ti.
                    </p>
                </div>
                <Link :href="route('landing.premios')" class="wa-btn-soft w-fit">
                    <i class="pi pi-gift"></i> Ver premios
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <!-- ============ Columna izquierda ============ -->
                <div class="space-y-6">
                    <!-- Foto de perfil -->
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
                        <Link :href="route('landing.premios')" class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-white/95 px-4 py-2 text-sm font-semibold text-mist-800 transition hover:bg-white">
                            Ir a premios
                            <i class="pi pi-arrow-right text-xs"></i>
                        </Link>
                    </section>
                </div>

                <!-- ============ Columna derecha: vehículos ============ -->
                <div class="space-y-6">
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Mis vehículos</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                                    Cada coche guarda hasta 4 fotos: una por cada lado.
                                </p>
                            </div>
                            <span class="rounded-full bg-mist-100 px-3 py-1 text-xs font-semibold text-mist-500 dark:bg-slate-700 dark:text-slate-300">
                                {{ cars.length }} registrado{{ cars.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <!-- Nuevo vehículo -->
                        <form class="mt-5 grid gap-3 rounded-2xl border border-dashed border-mist-300 p-4 sm:grid-cols-2 dark:border-slate-600" @submit.prevent="addCar">
                            <div class="sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-mist-400">Agregar vehículo</p>
                            </div>
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
                                    <i class="pi pi-plus text-xs"></i>
                                    {{ carForm.processing ? 'Guardando…' : 'Agregar vehículo' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Lista de coches -->
                    <section
                        v-for="car in cars"
                        :key="car.id"
                        class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800"
                    >
                        <div v-if="editingCar !== car.id" class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cloud-100 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 13l1.6-4.2A2 2 0 017.5 7.5h9A2 2 0 0118.4 8.8L20 13v5h-3v-1H7v1H4v-5z" />
                                        <path stroke-linecap="round" d="M7 13h10" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-base font-semibold text-mist-900 dark:text-white">{{ car.brand }} {{ car.model }}</p>
                                    <p class="text-xs text-mist-400">
                                        {{ [car.year, car.color, car.plates].filter(Boolean).join(' · ') || 'Sin detalles' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-mist-400 transition hover:bg-mist-100 hover:text-cloud-600 dark:hover:bg-slate-700" aria-label="Editar vehículo" @click="startEdit(car)">
                                    <i class="pi pi-pencil text-sm"></i>
                                </button>
                                <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-mist-400 transition hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-500/10" aria-label="Eliminar vehículo" @click="removeCar(car)">
                                    <i class="pi pi-trash text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Edición -->
                        <form v-else class="grid gap-3 sm:grid-cols-2" @submit.prevent="saveEdit(car)">
                            <div>
                                <label class="wa-label">Marca *</label>
                                <input v-model="editForm.brand" type="text" class="wa-input" />
                                <InputError class="mt-1.5" :message="editForm.errors.brand" />
                            </div>
                            <div>
                                <label class="wa-label">Modelo *</label>
                                <input v-model="editForm.model" type="text" class="wa-input" />
                                <InputError class="mt-1.5" :message="editForm.errors.model" />
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:col-span-2">
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
                                <button type="submit" class="wa-btn-primary !px-4 !py-2.5" :disabled="editForm.processing">Guardar</button>
                                <button type="button" class="wa-btn-soft !px-4 !py-2.5" @click="cancelEdit">Cancelar</button>
                            </div>
                        </form>

                        <!-- Fotos por lado -->
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div
                                v-for="slot in car.photos"
                                :key="slot.side"
                                class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-mist-200 bg-mist-50 dark:border-slate-600 dark:bg-slate-700/40"
                            >
                                <label class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center">
                                    <input type="file" class="sr-only" accept="image/*" @change="uploadPhoto(car, slot.side, $event)" />

                                    <img v-if="slot.url" :src="slot.url" :alt="slot.label" class="absolute inset-0 h-full w-full object-cover" />
                                    <span v-else class="flex flex-col items-center gap-1 text-mist-400">
                                        <i class="pi pi-camera text-lg"></i>
                                        <span class="text-[10px] font-semibold uppercase tracking-wider">Subir foto</span>
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
                                    @click="deletePhoto(car, slot.media_id)"
                                >
                                    <i class="pi pi-times text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <div
                        v-if="!cars.length"
                        class="rounded-3xl border border-dashed border-mist-300 bg-white/60 p-8 text-center text-sm text-mist-500 dark:border-slate-600 dark:bg-gray-800/60 dark:text-slate-400"
                    >
                        Aún no tienes vehículos registrados. Agrega el primero para agilizar tus reservas.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
