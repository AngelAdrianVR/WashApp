<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

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
        totalSpent: 0,
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

const bookingStatus = computed(() => (status) => {
  const statuses = {
    pending: { text: 'Pendiente', classes: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' },
    confirmed: { text: 'Confirmada', classes: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' },
    on_way: { text: 'En Camino', classes: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300' },
    in_progress: { text: 'En Progreso', classes: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' },
    completed: { text: 'Completada', classes: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' },
    cancelled: { text: 'Cancelada', classes: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' },
  };
  return statuses[status] || { text: status, classes: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' };
});

// --- STEPPER LOGIC ---
// Define the steps and their order for the stepper component
const stepperSteps = [
    { id: 'confirmed', label: 'Confirmada' },
    { id: 'on_way', label: 'En Camino' },
    { id: 'in_progress', label: 'En Progreso' },
    { id: 'completed', label: 'Completada' }
];

// Get the current step index based on the booking status
const getCurrentStepIndex = (status) => {
    const index = stepperSteps.findIndex(step => step.id === status);
    // If a booking is 'completed', we want the last step to be fully active.
    if (status === 'completed') return stepperSteps.length;
    return index >= 0 ? index + 1 : 0;
};

</script>

<template>
  <Head title="Mi Dashboard" />

  <AppLayout title="Dashboard">
    <!-- The main content area with dark mode background -->
    <div class="bg-gray-100 dark:bg-gray-900">
        <div class="py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 px-4 sm:px-0">
              <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">¡Hola de nuevo, {{ user.name }}!</h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">Aquí tienes un resumen de tu actividad.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
              <div v-for="(stat, index) in [
                  { label: 'Servicios Completados', value: stats.completedBookingsCount, icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', color: 'blue' },
                  { label: 'Total Gastado', value: formatCurrency(stats.totalSpent), icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 0c-1.11 0-2.08-.402-2.599-1M12 4V3m0 18v-1', color: 'green' },
                  { label: 'Recompensas Disponibles', value: stats.availableRewards, icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-1a6 6 0 00-5.197-5.975M15 10a4 4 0 11-8 0 4 4 0 018 0z', color: 'yellow' }
              ]" :key="index" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg p-6 flex items-center">
                <div :class="`bg-${stat.color}-500`" class="rounded-md p-3 mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ stat.label }}</p>
                  <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ stat.value }}</p>
                </div>
              </div>
            </div>

            <!-- Active Bookings Stepper Section -->
            <div class="mb-12">
              <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 px-4 sm:px-0">Seguimiento de Reservas Activas</h2>
              <div v-if="activeBookings.length > 0" class="space-y-6">
                <div v-for="booking in activeBookings" :key="booking.id" class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                  <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
                      <div>
                          <p class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">{{ formatDate(booking.scheduled_at) }}</p>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ booking.address }}</p>
                      </div>
                      <div :class="bookingStatus(booking.status).classes" class="mt-2 sm:mt-0 text-xs font-bold mr-2 px-2.5 py-0.5 rounded-full w-fit">
                          {{ bookingStatus(booking.status).text }}
                      </div>
                  </div>

                  <!-- Stepper Component -->
                  <ol class="items-center w-full space-y-4 sm:flex sm:space-x-8 sm:space-y-0 rtl:space-x-reverse">
                      <li v-for="(step, index) in stepperSteps" :key="step.id" class="flex items-center space-x-2.5 rtl:space-x-reverse" :class="{'text-blue-600 dark:text-blue-500': getCurrentStepIndex(booking.status) > index, 'text-gray-500 dark:text-gray-400': getCurrentStepIndex(booking.status) <= index}">
                          <span class="flex items-center justify-center w-8 h-8 border rounded-full shrink-0" :class="{'border-blue-600 dark:border-blue-500': getCurrentStepIndex(booking.status) > index, 'border-gray-500 dark:border-gray-400': getCurrentStepIndex(booking.status) <= index}">
                            <svg v-if="getCurrentStepIndex(booking.status) > index + 1 || booking.status === 'completed'" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5.917 5.724 10.5 15 1.5"/></svg>
                            <span v-else>{{ index + 1 }}</span>
                          </span>
                          <span>
                              <h3 class="font-medium leading-tight">{{ step.label }}</h3>
                          </span>
                           <!-- Connector Line -->
                          <div v-if="index < stepperSteps.length - 1" class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                      </li>
                  </ol>
                </div>
              </div>
              <div v-else class="text-center py-12 px-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                  <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No tienes reservas en curso</h3>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aquí aparecerá el estado de tus servicios activos.</p>
              </div>
            </div>

            <!-- Upcoming Bookings Section -->
            <div>
              <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 px-4 sm:px-0">Tus Próximas Reservas Pendientes</h2>
              <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
                <ul v-if="upcomingBookings.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                  <li v-for="booking in upcomingBookings" :key="booking.id" class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <p class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">{{ formatDate(booking.scheduled_at) }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ booking.address }}</p>
                         <div class="mt-2">
                            <span v-for="service in booking.services" :key="service.id" class="inline-block bg-gray-200 dark:bg-gray-700 rounded-full px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 mr-2 mb-2">
                                {{ service.name }}
                            </span>
                        </div>
                      </div>
                      <div class="mt-4 sm:mt-0 flex flex-col items-start sm:items-end">
                        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ formatCurrency(booking.total_price) }}</p>
                        <span :class="bookingStatus(booking.status).classes" class="mt-2 text-xs font-bold mr-2 px-2.5 py-0.5 rounded-full">
                          {{ bookingStatus(booking.status).text }}
                        </span>
                      </div>
                    </div>
                  </li>
                </ul>
                <div v-else class="text-center py-12 px-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No tienes reservas pendientes</h3>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">¡Agenda tu próximo lavado de coche ahora!</p>
                  <div class="mt-6">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                      Agendar un Servicio
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>
  </AppLayout>
</template>

