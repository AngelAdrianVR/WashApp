<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    profile: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    ratingBreakdown: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    recentServices: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
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

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 }).format(
        Number(value || 0)
    );

const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
};

const summary = computed(() => [
    { label: 'Servicios realizados', value: formatNumber(props.stats.completed), icon: 'pi pi-check-circle', tone: 'sage' },
    {
        label: 'Puntuación media',
        value: props.stats.average_rating ? props.stats.average_rating.toFixed(1) : '—',
        icon: 'pi pi-star-fill',
        tone: 'sand',
        hint: `${formatNumber(props.stats.reviews_count)} opiniones`,
    },
    { label: 'Clientes atendidos', value: formatNumber(props.stats.clients_served), icon: 'pi pi-users', tone: 'cloud' },
    { label: 'Horas de servicio', value: `${formatNumber(props.stats.hours)} h`, icon: 'pi pi-clock', tone: 'mist' },
    { label: 'Ingresos generados', value: formatCurrency(props.stats.revenue), icon: 'pi pi-wallet', tone: 'cloud' },
]);

const toneClasses = {
    sage: 'bg-sage-50 text-sage-700 ring-sage-100 dark:bg-sage-500/15 dark:text-sage-300 dark:ring-sage-500/20',
    cloud: 'bg-cloud-50 text-cloud-700 ring-cloud-100 dark:bg-cloud-500/15 dark:text-cloud-300 dark:ring-cloud-500/20',
    sand: 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/20',
    mist: 'bg-mist-100 text-mist-600 ring-mist-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600',
};

// ---------------------------------------------------------------- Foto
const avatarForm = useForm({ photo: null });
const fileInput = ref(null);

const uploadAvatar = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    avatarForm.photo = file;
    avatarForm.post(route('employee.profile.avatar'), {
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
    router.delete(route('employee.profile.avatar.destroy'), { preserveScroll: true });
};

// ---------------------------------------------------------------- Datos
const dataForm = useForm({
    name: props.profile.name,
    phone_number: props.profile.phone_number ?? '',
});

const saveData = () => dataForm.put(route('employee.profile.update'), { preserveScroll: true });

const ratingMax = computed(() => Math.max(...props.ratingBreakdown.map((row) => row.count), 1));

const reviewsWithService = computed(() => props.reviews);
</script>

<template>
    <Head title="Mi perfil" />

    <AppLayout title="Mi perfil">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-6xl space-y-6">
            <!-- Encabezado -->
            <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Cuenta de operador
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-3xl">Mi perfil</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Tu información, desempeño y las opiniones que dejan los clientes.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('employee.services.index')" class="wa-btn-soft !px-4 !py-2.5">
                        <i class="pi pi-history text-xs"></i>
                        Ver historial
                    </Link>
                    <Link :href="route('dashboard')" class="wa-btn-primary !px-4 !py-2.5">
                        <i class="pi pi-chart-bar text-xs"></i>
                        Ir al panel
                    </Link>
                </div>
            </section>

            <!-- Resumen -->
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <div
                    v-for="card in summary"
                    :key="card.label"
                    class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80"
                >
                    <span class="grid h-9 w-9 place-items-center rounded-2xl ring-1 ring-inset" :class="toneClasses[card.tone]">
                        <i :class="[card.icon, 'text-xs']"></i>
                    </span>
                    <p class="mt-3 truncate text-lg font-semibold text-mist-900 dark:text-white">{{ card.value }}</p>
                    <p class="text-[10px] font-semibold uppercase leading-tight tracking-[0.12em] text-mist-400">{{ card.label }}</p>
                    <p v-if="card.hint" class="mt-1 text-[10px] text-mist-400">{{ card.hint }}</p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr] [&>*]:min-w-0">
                <!-- Columna izquierda -->
                <div class="space-y-6">
                    <!-- Foto y datos -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex flex-col items-center text-center">
                            <div class="relative">
                                <img
                                    :src="profile.avatar_url || profile.photo_url"
                                    :alt="profile.name"
                                    class="h-28 w-28 rounded-3xl object-cover shadow-soft ring-4 ring-white dark:ring-slate-700"
                                />
                                <label
                                    class="absolute -bottom-2 -right-2 grid h-10 w-10 cursor-pointer place-items-center rounded-2xl bg-cloud-600 text-white shadow-lift transition hover:bg-cloud-700"
                                    title="Cambiar foto"
                                >
                                    <input ref="fileInput" type="file" class="sr-only" accept="image/*" @change="uploadAvatar" />
                                    <i v-if="!avatarForm.processing" class="pi pi-camera text-sm"></i>
                                    <i v-else class="pi pi-spin pi-spinner text-sm"></i>
                                </label>
                            </div>

                            <p class="mt-5 text-lg font-semibold text-mist-900 dark:text-white">{{ profile.name }}</p>
                            <p class="text-sm text-mist-400">{{ profile.email }}</p>

                            <span
                                class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                :class="profile.is_active
                                    ? 'bg-sage-50 text-sage-700 dark:bg-sage-500/10 dark:text-sage-300'
                                    : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="profile.is_active ? 'bg-sage-500' : 'bg-rose-500'"></span>
                                {{ profile.is_active ? 'Operador activo' : 'Operador inactivo' }}
                            </span>

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
                                    En WashApp desde {{ profile.member_since }}
                                </p>
                                <button type="submit" class="wa-btn-primary !px-4 !py-2.5" :disabled="dataForm.processing">
                                    {{ dataForm.processing ? 'Guardando…' : 'Guardar' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Puntuación -->
                    <section class="relative overflow-hidden rounded-3xl border border-sage-200/70 bg-gradient-to-br from-sage-600 to-cloud-700 p-6 text-white shadow-lift">
                        <div class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sage-100">Puntuación global</p>
                        <div class="mt-2 flex items-end gap-3">
                            <p class="text-5xl font-semibold tracking-tight">{{ stats.average_rating ? stats.average_rating.toFixed(1) : '—' }}</p>
                            <div class="mb-2">
                                <div class="flex">
                                    <i
                                        v-for="star in 5"
                                        :key="star"
                                        class="pi pi-star-fill text-sm"
                                        :class="star <= Math.round(stats.average_rating ?? 0) ? 'text-amber-300' : 'text-white/30'"
                                    ></i>
                                </div>
                                <p class="mt-1 text-[11px] text-sage-100/85">
                                    {{ formatNumber(stats.reviews_count) }} opiniones · {{ formatNumber(stats.five_star) }} con 5★
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-2">
                            <div v-for="row in ratingBreakdown" :key="row.stars" class="flex items-center gap-2">
                                <span class="w-3 text-[11px] font-semibold text-sage-100">{{ row.stars }}</span>
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/20">
                                    <div class="h-full rounded-full bg-amber-300" :style="{ width: `${(row.count / ratingMax) * 100}%` }"></div>
                                </div>
                                <span class="w-6 text-right text-[10px] text-sage-100/80">{{ row.count }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Próximos -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Próximos servicios</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Tu agenda inmediata.</p>
                            </div>
                            <Link :href="route('employee.bookings.index')" class="text-xs font-semibold text-cloud-600 hover:text-cloud-700 dark:text-cloud-300">
                                Ver agenda
                            </Link>
                        </div>

                        <ul v-if="upcoming.length" class="mt-4 space-y-2.5">
                            <li v-for="booking in upcoming" :key="booking.id">
                                <Link
                                    :href="route('employee.bookings.show', booking.id)"
                                    class="flex items-center gap-3 rounded-2xl border border-mist-100 bg-white/70 p-3 transition hover:border-cloud-200 hover:bg-white dark:border-slate-700 dark:bg-slate-800/60"
                                >
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-cloud-50 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300">
                                        <i class="pi pi-calendar text-xs"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-semibold text-mist-800 dark:text-slate-100">
                                            {{ formatDate(booking.scheduled_at) }}
                                        </span>
                                        <span class="block truncate text-[11px] text-mist-400">{{ booking.client_name }}</span>
                                    </span>
                                    <i class="pi pi-chevron-right text-[10px] text-mist-300"></i>
                                </Link>
                            </li>
                        </ul>

                        <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            Sin servicios próximos.
                        </p>
                    </section>
                </div>

                <!-- Columna derecha -->
                <div class="space-y-6">
                    <!-- Opiniones -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Opiniones de clientes</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                                    Cada opinión indica el servicio que calificó el cliente.
                                </p>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                {{ formatNumber(reviews.length) }}
                            </span>
                        </div>

                        <div v-if="reviewsWithService.length" class="mt-5 space-y-3">
                            <article
                                v-for="review in reviewsWithService"
                                :key="review.id"
                                class="rounded-2xl border border-mist-100 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-800/60"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <img
                                            v-if="review.client_avatar"
                                            :src="review.client_avatar"
                                            :alt="review.client_name"
                                            class="h-9 w-9 rounded-full object-cover"
                                        />
                                        <span v-else class="grid h-9 w-9 place-items-center rounded-full bg-cloud-100 text-xs font-semibold text-cloud-700">
                                            {{ review.client_name.charAt(0) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">{{ review.client_name }}</p>
                                            <p class="text-[11px] text-mist-400">{{ review.date_label }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i
                                            v-for="star in 5"
                                            :key="star"
                                            class="pi pi-star-fill text-[11px]"
                                            :class="star <= review.rating ? 'text-amber-400' : 'text-mist-200 dark:text-slate-600'"
                                        ></i>
                                        <span class="ml-1 text-[11px] font-semibold text-mist-500 dark:text-slate-400">{{ review.rating }}/5</span>
                                    </div>
                                </div>

                                <p v-if="review.comment" class="mt-3 text-sm leading-relaxed text-mist-600 dark:text-slate-300">
                                    “{{ review.comment }}”
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-mist-100 pt-3 dark:border-slate-700">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-mist-400">Servicio:</span>
                                    <span
                                        v-for="service in review.services"
                                        :key="service"
                                        class="rounded-full bg-cloud-50 px-2 py-0.5 text-[10px] font-medium text-cloud-700 dark:bg-cloud-500/10 dark:text-cloud-300"
                                    >
                                        {{ service }}
                                    </span>
                                    <Link
                                        :href="route('employee.bookings.show', review.booking_id)"
                                        class="ml-auto inline-flex items-center gap-1 text-[10px] font-semibold text-cloud-600 hover:text-cloud-700 dark:text-cloud-300"
                                    >
                                        Ver servicio
                                        <i class="pi pi-arrow-right text-[8px]"></i>
                                    </Link>
                                </div>
                            </article>
                        </div>

                        <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            Aún no tienes opiniones. Cuando un cliente califique un servicio, aparecerá aquí.
                        </p>
                    </section>

                    <!-- Últimos servicios -->
                    <section class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Últimos servicios</h2>
                                <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Del más reciente al más antiguo.</p>
                            </div>
                            <Link :href="route('employee.services.index')" class="text-xs font-semibold text-cloud-600 hover:text-cloud-700 dark:text-cloud-300">
                                Ver historial
                            </Link>
                        </div>

                        <ul v-if="recentServices.length" class="mt-4 space-y-2.5">
                            <li v-for="booking in recentServices" :key="booking.id">
                                <Link
                                    :href="route('employee.bookings.show', booking.id)"
                                    class="flex items-center gap-3 rounded-2xl border border-mist-100 bg-white/70 p-3 transition hover:border-sage-200 hover:bg-white dark:border-slate-700 dark:bg-slate-800/60"
                                >
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-sage-50 text-sage-600 dark:bg-sage-500/15 dark:text-sage-300">
                                        <i class="pi pi-check text-xs"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-semibold text-mist-800 dark:text-slate-100">
                                            {{ booking.services.map((service) => service.name).join(' + ') }}
                                        </span>
                                        <span class="block truncate text-[11px] text-mist-400">
                                            {{ formatDate(booking.completed_at) }} · {{ formatCurrency(booking.total_price) }}
                                        </span>
                                    </span>
                                    <span v-if="booking.review" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        <i class="pi pi-star-fill text-[8px]"></i>
                                        {{ booking.review.rating }}
                                    </span>
                                    <i v-else class="pi pi-chevron-right text-[10px] text-mist-300"></i>
                                </Link>
                            </li>
                        </ul>

                        <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                            Todavía no tienes servicios finalizados.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
