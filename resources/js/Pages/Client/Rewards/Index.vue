<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    points: { type: Number, default: 0 },
    rewards: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
});

const page = usePage();
const toast = useToast();

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({ severity: 'success', summary: 'Premio canjeado', detail: message, life: 4200 });
        }
    }
);

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) {
            toast.add({ severity: 'error', summary: 'No se pudo canjear', detail: message, life: 4200 });
        }
    }
);

const redeemingId = ref(null);

const redeem = (reward) => {
    if (!reward.can_redeem || redeemingId.value) return;

    if (!confirm(`¿Canjear "${reward.name}" por ${reward.points_cost} puntos?`)) return;

    redeemingId.value = reward.id;

    router.post(route('client.rewards.redeem', reward.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            redeemingId.value = null;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'No se pudo canjear',
                detail: errors.redeem || 'Inténtalo de nuevo.',
                life: 4200,
            });
        },
    });
};

const historyCount = computed(() => props.history.length);
</script>

<template>
    <AppLayout title="Mis premios">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-6xl space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-500">Programa de fidelidad</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-3xl">Mis premios</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Canjea tus puntos por lavados, tratamientos y beneficios exclusivos.
                    </p>
                </div>
                <Link :href="route('client.bookings.create')" class="wa-btn-primary !px-4 !py-2.5 w-fit">
                    <i class="pi pi-calendar-plus text-xs"></i>
                    Agendar y ganar puntos
                </Link>
            </div>

            <!-- Puntos -->
            <section class="relative overflow-hidden rounded-3xl border border-sage-200/70 bg-gradient-to-br from-sage-600 via-cloud-600 to-cloud-700 p-6 text-white shadow-lift sm:p-7">
                <div class="pointer-events-none absolute -right-10 -top-14 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-16 left-10 h-36 w-36 rounded-full bg-white/10 blur-2xl"></div>

                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sage-100">Puntos disponibles</p>
                        <p class="mt-2 text-5xl font-semibold tracking-tight">{{ points }}</p>
                        <p class="mt-1 text-sm text-sage-50/85">
                            Cada servicio completado suma puntos automáticamente.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 text-right backdrop-blur-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-sage-100">Canjes realizados</p>
                        <p class="mt-1 text-2xl font-semibold">{{ historyCount }}</p>
                    </div>
                </div>
            </section>

            <!-- Catálogo -->
            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Premios disponibles</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                            Elige un premio y canjéalo al instante con tus puntos.
                        </p>
                    </div>
                    <span class="rounded-full bg-mist-100 px-3 py-1 text-xs font-semibold text-mist-500 dark:bg-slate-700 dark:text-slate-300">
                        {{ rewards.length }} premio{{ rewards.length === 1 ? '' : 's' }}
                    </span>
                </div>

                <div v-if="rewards.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="reward in rewards"
                        :key="reward.id"
                        class="group flex flex-col overflow-hidden rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur-md transition hover:-translate-y-0.5 hover:shadow-lift dark:border-slate-700 dark:bg-gray-800"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-cloud-100 to-sage-50 dark:from-slate-700 dark:to-slate-700">
                            <img
                                v-if="reward.image_url"
                                :src="reward.image_url"
                                :alt="reward.name"
                                loading="lazy"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                            <div v-else class="grid h-full place-items-center text-cloud-400 dark:text-slate-500">
                                <i class="pi pi-gift text-3xl"></i>
                            </div>

                            <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-cloud-700 shadow-sm">
                                <i class="pi pi-star-fill text-[10px] text-amber-400"></i>
                                {{ reward.points_cost }} pts
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-base font-semibold text-mist-900 dark:text-white">{{ reward.name }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-mist-500 dark:text-slate-400">
                                {{ reward.description }}
                            </p>

                            <button
                                v-if="reward.can_redeem"
                                type="button"
                                class="wa-btn-sage mt-5 w-full !px-4 !py-2.5"
                                :disabled="redeemingId === reward.id"
                                @click="redeem(reward)"
                            >
                                <i :class="['text-xs', redeemingId === reward.id ? 'pi pi-spin pi-spinner' : 'pi pi-gift']"></i>
                                {{ redeemingId === reward.id ? 'Canjeando…' : 'Canjear premio' }}
                            </button>
                            <button
                                v-else
                                type="button"
                                disabled
                                class="mt-5 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-mist-200 bg-mist-50 px-4 py-2.5 text-xs font-semibold text-mist-400 dark:border-slate-600 dark:bg-slate-700/40 dark:text-slate-500"
                            >
                                <i class="pi pi-lock text-[10px]"></i>
                                Te faltan {{ reward.missing_points }} pts
                            </button>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="rounded-3xl border border-dashed border-mist-300 bg-white/60 px-6 py-12 text-center dark:border-slate-600 dark:bg-gray-800/60"
                >
                    <i class="pi pi-gift text-2xl text-mist-300"></i>
                    <p class="mt-3 text-sm text-mist-500 dark:text-slate-400">Estamos preparando nuevos premios. Vuelve pronto.</p>
                </div>
            </section>

            <!-- Historial -->
            <section class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-mist-900 dark:text-white">Premios canjeados</h2>
                        <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Tu historial de beneficios utilizados.</p>
                    </div>
                    <span class="rounded-full bg-sage-50 px-3 py-1 text-xs font-semibold text-sage-700 dark:bg-sage-500/15 dark:text-sage-300">
                        {{ history.length }}
                    </span>
                </div>

                <div v-if="history.length" class="mt-4 space-y-3">
                    <div
                        v-for="entry in history"
                        :key="entry.id"
                        class="flex items-start gap-3 rounded-2xl border border-mist-100 bg-white p-3.5 dark:border-slate-700 dark:bg-slate-800/60"
                    >
                        <span
                            class="grid h-10 w-10 shrink-0 place-items-center rounded-xl"
                            :class="entry.kind === 'reward'
                                ? 'bg-sage-50 text-sage-600 dark:bg-sage-500/15 dark:text-sage-300'
                                : 'bg-cloud-50 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300'"
                        >
                            <i :class="['text-sm', entry.kind === 'reward' ? 'pi pi-gift' : 'pi pi-ticket']"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">{{ entry.title }}</p>
                            <p class="text-xs text-mist-500 dark:text-slate-400">{{ entry.description || 'Beneficio canjeado' }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-mist-400">
                                {{ entry.date || 'Sin fecha' }}
                            </p>
                        </div>
                        <span class="whitespace-nowrap rounded-full bg-mist-100 px-2.5 py-1 text-[10px] font-semibold text-mist-600 dark:bg-slate-700 dark:text-slate-300">
                            {{ entry.detail || 'Canjeado' }}
                        </span>
                    </div>
                </div>

                <p v-else class="mt-4 rounded-2xl border border-dashed border-mist-200 bg-mist-50/60 p-4 text-sm text-mist-500 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                    Aún no has canjeado premios. Cuando lo hagas, aparecerán aquí.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
