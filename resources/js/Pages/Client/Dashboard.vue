<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusAnimation from '@/Components/Bookings/StatusAnimation.vue';

// --- PROPS ---
const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
    default: () => ({
        completedBookingsCount: 0,
        availableRewards: 0,
    }),
  },
  upcomingBookings: {
    type: Array,
    required: true,
    default: () => [],
  },
  // New prop for active bookings to track
  activeBookings: {
    type: Array,
    required: true,
    default: () => [],
  }
});

// --- HELPERS ---
const formatDate = (dateString) => {
  if (!dateString) return '';
  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  };
  return new Date(dateString).toLocaleDateString('es-MX', options);
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(value);
};

const statusStyles = (status) => {
  const styles = {
    pending: {
      text: 'Pendiente',
      chip: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
      dot: 'bg-amber-500',
    },
    confirmed: {
      text: 'Confirmada',
      chip: 'bg-cloud-50 text-cloud-700 ring-cloud-200 dark:bg-cloud-500/10 dark:text-cloud-300 dark:ring-cloud-500/30',
      dot: 'bg-cloud-500',
    },
    on_way: {
      text: 'En camino',
      chip: 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30',
      dot: 'bg-indigo-500',
    },
    in_progress: {
      text: 'En progreso',
      chip: 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30',
      dot: 'bg-violet-500',
    },
    completed: {
      text: 'Completada',
      chip: 'bg-sage-50 text-sage-700 ring-sage-200 dark:bg-sage-500/10 dark:text-sage-300 dark:ring-sage-500/30',
      dot: 'bg-sage-500',
    },
    cancelled: {
      text: 'Cancelada',
      chip: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30',
      dot: 'bg-rose-500',
    },
  };

  return styles[status] || {
    text: status,
    chip: 'bg-mist-100 text-mist-600 ring-mist-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600',
    dot: 'bg-mist-400',
  };
};

const toneClasses = {
  cloud: 'bg-cloud-50 text-cloud-600 ring-cloud-100 dark:bg-cloud-500/15 dark:text-cloud-300 dark:ring-cloud-500/20',
  sage: 'bg-sage-50 text-sage-600 ring-sage-100 dark:bg-sage-500/15 dark:text-sage-300 dark:ring-sage-500/20',
  sand: 'bg-amber-50 text-amber-600 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/20',
};

const statsCards = computed(() => [
  {
    label: 'Servicios completados',
    hint: 'Historial de lavados',
    value: props.stats.completedBookingsCount,
    icon: 'pi pi-check-circle',
    tone: 'cloud',
  },
  {
    label: 'Recompensas disponibles',
    hint: 'Listas para canjear',
    value: props.stats.availableRewards,
    icon: 'pi pi-gift',
    tone: 'sage',
  },
  {
    label: 'Reservas activas',
    hint: 'En seguimiento ahora',
    value: props.activeBookings.length,
    icon: 'pi pi-bolt',
    tone: 'sand',
  },
]);

// --- STEPPER LOGIC ---
const stepperSteps = [
    { id: 'confirmed', label: 'Confirmada' },
    { id: 'on_way', label: 'En Camino' },
    { id: 'in_progress', label: 'En Progreso' },
    { id: 'completed', label: 'Completada' }
];

const progressIndex = (status) => {
    if (status === 'completed') return stepperSteps.length;

    const index = stepperSteps.findIndex(step => step.id === status);
    return index >= 0 ? index + 1 : 0;
};

const isStepDone = (status, index) => progressIndex(status) > index;

const isStepCurrent = (status, index) => progressIndex(status) === index + 1;
</script>

<template>
  <Head title="Mi Dashboard" />

  <AppLayout title="Dashboard">
    <div class="mx-auto w-full max-w-6xl space-y-6">
      <!-- Encabezado -->
      <section class="relative overflow-hidden rounded-3xl border border-cloud-500/20 bg-gradient-to-br from-cloud-600 via-cloud-700 to-sage-600 p-6 text-white shadow-lift sm:p-8">
        <div class="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-8 h-40 w-40 rounded-full bg-sage-300/20 blur-2xl"></div>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <div class="min-w-0">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/85">
              <i class="pi pi-sparkles text-[10px]"></i>
              Resumen de tu cuenta
            </span>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
              ¡Hola de nuevo, {{ user.name }}!
            </h1>
            <p class="mt-2 max-w-xl text-sm text-white/85">
              Este es el estado de tus servicios y recompensas en WashApp.
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <Link
              :href="route('client.bookings.create')"
              class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-cloud-700 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift"
            >
              <i class="pi pi-calendar-plus text-xs"></i>
              Agendar servicio
            </Link>
            <Link
              :href="route('client.rewards.index')"
              class="inline-flex items-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20"
            >
              <i class="pi pi-gift text-xs"></i>
              Mis premios
            </Link>
          </div>
        </div>
      </section>

      <!-- KPIs -->
      <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
        <article
          v-for="(card, index) in statsCards"
          :key="card.label"
          class="group relative overflow-hidden rounded-3xl border border-white/70 bg-white/85 p-4 shadow-soft backdrop-blur-md transition hover:-translate-y-0.5 hover:shadow-lift dark:border-slate-700 dark:bg-gray-800/80 sm:p-5"
          :class="index === 2 ? 'col-span-2 lg:col-span-1' : ''"
        >
          <div class="flex items-start justify-between gap-2">
            <span class="grid h-10 w-10 place-items-center rounded-2xl ring-1 ring-inset sm:h-11 sm:w-11" :class="toneClasses[card.tone]">
              <i :class="[card.icon, 'text-sm sm:text-base']"></i>
            </span>
            <i class="pi pi-chart-line text-xs text-mist-200 transition group-hover:text-cloud-300 dark:text-slate-600"></i>
          </div>
          <p class="mt-4 text-3xl font-semibold tracking-tight text-mist-900 dark:text-white sm:text-4xl">{{ card.value }}</p>
          <p class="mt-1 text-xs font-semibold text-mist-600 dark:text-slate-300 sm:text-sm">{{ card.label }}</p>
          <p class="mt-0.5 text-[11px] text-mist-400 dark:text-slate-500">{{ card.hint }}</p>
        </article>
      </section>

      <!-- Reservas activas -->
      <section class="space-y-3">
        <div class="flex items-end justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold tracking-tight text-mist-900 dark:text-white">Seguimiento en vivo</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Estado de tus servicios en curso.</p>
          </div>
          <span class="rounded-full bg-mist-100 px-3 py-1 text-xs font-semibold text-mist-600 dark:bg-slate-700 dark:text-slate-300">
            {{ activeBookings.length }}
          </span>
        </div>

        <div v-if="activeBookings.length" class="grid gap-3 lg:grid-cols-2">
          <Link
            v-for="booking in activeBookings"
            :key="booking.id"
            :href="route('client.bookings.show', booking.id)"
            class="group block rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md transition hover:-translate-y-0.5 hover:border-cloud-300 hover:shadow-lift focus:outline-none focus-visible:ring-2 focus-visible:ring-cloud-400 dark:border-slate-700 dark:bg-gray-800/80 dark:hover:border-cloud-500/60"
          >
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-mist-900 dark:text-white">{{ formatDate(booking.scheduled_at) }}</p>
                <p class="mt-1 flex items-start gap-1.5 text-xs text-mist-500 dark:text-slate-400">
                  <i class="pi pi-map-marker mt-0.5 text-[10px]"></i>
                  <span class="line-clamp-2">{{ booking.address }}</span>
                </p>
              </div>
              <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset" :class="statusStyles(booking.status).chip">
                <span class="h-1.5 w-1.5 rounded-full" :class="statusStyles(booking.status).dot"></span>
                {{ statusStyles(booking.status).text }}
              </span>
            </div>

            <ol class="mt-5 grid grid-cols-4 gap-1.5">
              <li v-for="(step, index) in stepperSteps" :key="step.id">
                <div class="flex items-center">
                  <span
                    class="grid h-7 w-7 shrink-0 place-items-center rounded-full border text-[11px] font-semibold transition"
                    :class="isStepCurrent(booking.status, index)
                      ? 'border-cloud-500 bg-cloud-50 text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300'
                      : isStepDone(booking.status, index)
                        ? 'border-sage-500 bg-sage-500 text-white shadow-sm'
                        : 'border-mist-200 bg-white text-mist-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-500'"
                  >
                    <i v-if="isStepDone(booking.status, index) && !isStepCurrent(booking.status, index)" class="pi pi-check text-[9px]"></i>
                    <StatusAnimation
                      v-else-if="isStepCurrent(booking.status, index) && step.id !== 'completed'"
                      :status="step.id"
                    />
                    <span v-else>{{ index + 1 }}</span>
                  </span>
                  <span
                    v-if="index < stepperSteps.length - 1"
                    class="ml-1.5 h-1 flex-1 rounded-full"
                    :class="isStepDone(booking.status, index + 1) && !isStepCurrent(booking.status, index + 1) ? 'bg-sage-500' : 'bg-mist-200 dark:bg-slate-700'"
                  ></span>
                </div>
                <p
                  class="mt-2 text-center text-[10px] font-semibold uppercase leading-tight tracking-wide"
                  :class="isStepDone(booking.status, index) || isStepCurrent(booking.status, index)
                    ? 'text-mist-700 dark:text-slate-200'
                    : 'text-mist-400 dark:text-slate-500'"
                >
                  {{ step.label }}
                </p>
              </li>
            </ol>

            <p class="mt-3 flex items-center justify-end gap-1 text-[11px] font-semibold text-cloud-600 opacity-0 transition group-hover:opacity-100 dark:text-cloud-300">
              Ver detalle
              <i class="pi pi-arrow-right text-[9px]"></i>
            </p>
          </Link>
        </div>

        <div v-else class="rounded-3xl border border-dashed border-mist-200 bg-white/70 px-6 py-10 text-center dark:border-slate-700 dark:bg-gray-800/60">
          <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-slate-700 dark:text-slate-400">
            <i class="pi pi-clock text-lg"></i>
          </span>
          <p class="mt-3 text-sm font-semibold text-mist-700 dark:text-slate-200">No tienes servicios en curso</p>
          <p class="mt-1 text-xs text-mist-500 dark:text-slate-400">Cuando confirmes una reserva verás aquí su progreso.</p>
        </div>
      </section>

      <!-- Próximas reservas -->
      <section class="space-y-3">
        <div class="flex items-end justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold tracking-tight text-mist-900 dark:text-white">Próximas reservas</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">Tus servicios pendientes por atender.</p>
          </div>
          <Link
            :href="route('client.bookings.index')"
            class="shrink-0 text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300 dark:hover:text-cloud-200"
          >
            Ver todas
          </Link>
        </div>

        <div v-if="upcomingBookings.length" class="grid gap-3 lg:grid-cols-2">
          <article
            v-for="booking in upcomingBookings"
            :key="booking.id"
            class="flex flex-col gap-4 rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur-md dark:border-slate-700 dark:bg-gray-800/80"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-mist-900 dark:text-white">{{ formatDate(booking.scheduled_at) }}</p>
                <p class="mt-1 flex items-start gap-1.5 text-xs text-mist-500 dark:text-slate-400">
                  <i class="pi pi-map-marker mt-0.5 text-[10px]"></i>
                  <span class="line-clamp-2">{{ booking.address }}</span>
                </p>
              </div>
              <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset" :class="statusStyles(booking.status).chip">
                <span class="h-1.5 w-1.5 rounded-full" :class="statusStyles(booking.status).dot"></span>
                {{ statusStyles(booking.status).text }}
              </span>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="service in booking.services"
                :key="service.id"
                class="inline-flex items-center gap-1 rounded-full bg-mist-100 px-2.5 py-1 text-[11px] font-medium text-mist-600 dark:bg-slate-700 dark:text-slate-300"
              >
                <i class="pi pi-sparkles text-[9px] text-cloud-500"></i>
                {{ service.name }}
              </span>
            </div>

            <div class="mt-auto flex items-center justify-between gap-3 border-t border-mist-100 pt-4 dark:border-slate-700">
              <p class="text-base font-semibold text-mist-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</p>
              <Link
                :href="route('client.bookings.show', booking.id)"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300 dark:hover:text-cloud-200"
              >
                Ver detalle
                <i class="pi pi-arrow-right text-[10px]"></i>
              </Link>
            </div>
          </article>
        </div>

        <div v-else class="rounded-3xl border border-dashed border-mist-200 bg-white/70 px-6 py-10 text-center dark:border-slate-700 dark:bg-gray-800/60">
          <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-slate-700 dark:text-slate-400">
            <i class="pi pi-calendar text-lg"></i>
          </span>
          <p class="mt-3 text-sm font-semibold text-mist-700 dark:text-slate-200">No tienes reservas pendientes</p>
          <p class="mt-1 text-xs text-mist-500 dark:text-slate-400">Agenda tu próximo lavado y acumula puntos.</p>
          <Link :href="route('client.bookings.create')" class="wa-btn-primary mt-5">
            <i class="pi pi-calendar-plus text-xs"></i>
            Agendar un servicio
          </Link>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

