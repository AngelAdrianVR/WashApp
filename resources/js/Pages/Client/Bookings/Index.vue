<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Card from 'primevue/card';

// Props recibidos desde el controlador de Laravel
const props = defineProps({
    bookings: Object, // El objeto paginado de reservas
});

// Referencias reactivas para manejar el estado del modal
const isCancelModalVisible = ref(false);
const selectedBooking = ref(null);

// Formulario de Inertia para manejar la cancelación
const cancelForm = useForm({
    reason: '',
});

// --- FUNCIONES ---

// Abre el modal de cancelación y prepara los datos
const openCancelModal = (booking) => {
    selectedBooking.value = booking;
    cancelForm.reset(); // Limpiamos el formulario por si tenía datos previos
    isCancelModalVisible.value = true;
};

// Cierra el modal de cancelación
const closeCancelModal = () => {
    isCancelModalVisible.value = false;
    selectedBooking.value = null;
};

// Envía la petición para cancelar la reserva
const submitCancellation = () => {
    if (!selectedBooking.value) return;

    const routeName = 'client.bookings.cancel';
    const routeParams = { booking: selectedBooking.value.id };

    cancelForm.patch(route(routeName, routeParams), {
        preserveScroll: true,
        onSuccess: () => {
            closeCancelModal();
            // Aquí podrías añadir una notificación (toast) de éxito
        },
        onError: () => {
            // Manejo de errores, por si falla la validación
        },
    });
};

// --- HELPERS DE FORMATO ---

// Formatea la fecha para que sea más legible
const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-MX', options);
};

// Devuelve clases de CSS y texto según el estado de la reserva
const statusInfo = (status) => {
    const statuses = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' },
        confirmed: { text: 'Confirmada', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' },
        on_way: { text: 'En Camino', class: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' },
        in_progress: { text: 'En Progreso', class: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' },
        completed: { text: 'Completada', class: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' },
        cancelled: { text: 'Cancelada', class: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' },
    };
    return statuses[status] || { text: 'Desconocido', class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' };
};

</script>

<template>
    <AppLayout title="Mis Reservas">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Mi Historial de Reservas
                </h2>
                <Link :href="route('client.bookings.create')">
                    <Button label="Agendar Cita" icon="pi pi-plus" size="small" />
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 md:p-6">

                    <!-- Mensaje si no hay reservas -->
                    <div v-if="!bookings.data.length" class="text-center py-12">
                        <i class="pi pi-book text-5xl text-gray-400"></i>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No tienes reservas todavía</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            ¡Agenda tu primer lavado de auto con nosotros!
                        </p>
                        <Link :href="route('client.bookings.create')" class="mt-6 inline-block">
                             <Button label="Agendar mi primer lavado" icon="pi pi-calendar-plus" />
                        </Link>
                    </div>

                    <!-- Listado de Reservas -->
                    <div v-else class="space-y-4">
                        <Card v-for="booking in bookings.data" @click="$inertia.visit(route('client.bookings.show', booking.id))" :key="booking.id" class="overflow-hidden cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900">
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-2">
                                    <!-- Columna 1: Fecha y Estado -->
                                    <div class="space-y-2">
                                        <div class="font-bold text-gray-700 dark:text-gray-300">Fecha y Hora</div>
                                        <div class="text-gray-600 dark:text-gray-400">{{ formatDate(booking.scheduled_at) }}</div>
                                        <span :class="['text-xs font-semibold mr-2 px-2.5 py-0.5 rounded-full', statusInfo(booking.status).class]">
                                            {{ statusInfo(booking.status).text }}
                                        </span>
                                    </div>

                                    <!-- Columna 2: Dirección y Servicios -->
                                    <div class="space-y-2">
                                        <div class="font-bold text-gray-700 dark:text-gray-300">Ubicación</div>
                                        <div class="text-gray-600 dark:text-gray-400 text-sm">{{ booking.address }}</div>
                                        <div v-if="booking.services.length" class="mt-2">
                                            <div class="font-semibold text-xs text-gray-500 dark:text-gray-400">Servicios:</div>
                                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400">
                                                <li v-for="service in booking.services" :key="service.id">{{ service.name }}</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Columna 3: Precio y Acciones -->
                                    <div class="space-y-2 flex flex-col items-start md:items-end">
                                        <div class="font-bold text-gray-700 dark:text-gray-300">Total</div>
                                        <div class="text-2xl font-extrabold text-gray-800 dark:text-white">${{ booking.total_price }}</div>
                                        <Button
                                            v-if="booking.status === 'pending' || booking.status === 'confirmed'"
                                            @click="openCancelModal(booking)"
                                            label="Cancelar"
                                            severity="danger"
                                            text
                                            size="small"
                                            class="mt-2 -mr-2"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Paginación -->
                    <div v-if="bookings.links.length > 3" class="mt-8 flex justify-center items-center">
                        <div class="flex flex-wrap">
                            <template v-for="(link, key) in bookings.links" :key="key">
                                <div v-if="link.url === null"
                                     class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600"
                                     v-html="link.label" />
                                <Link v-else
                                      class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 focus:border-indigo-500 focus:text-indigo-500"
                                      :class="{ 'bg-blue-600 text-white dark:bg-blue-500 dark:text-white': link.active }"
                                      :href="link.url"
                                      v-html="link.label" />
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Modal para Cancelar Reserva -->
        <Dialog v-model:visible="isCancelModalVisible" modal header="Cancelar Reserva" :style="{ width: '90vw', maxWidth: '500px' }">
            <div class="flex flex-col gap-4">
                <p class="text-gray-600 dark:text-gray-300">
                    Estás a punto de cancelar tu reserva del <span class="font-semibold text-gray-800 dark:text-gray-100">{{ selectedBooking ? formatDate(selectedBooking.scheduled_at) : '' }}</span>.
                    Por favor, cuéntanos el motivo.
                </p>

                <div class="flex flex-col gap-2">
                    <label for="reason" class="text-sm font-medium text-gray-700 dark:text-gray-300">Motivo de la cancelación</label>
                    <Textarea id="reason" v-model="cancelForm.reason" rows="4" cols="30" :invalid="!!cancelForm.errors.reason" />
                    <small v-if="cancelForm.errors.reason" class="text-red-500">{{ cancelForm.errors.reason }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" @click="closeCancelModal" text />
                <Button label="Confirmar Cancelación" icon="pi pi-check" @click="submitCancellation" :loading="cancelForm.processing" severity="danger" />
            </template>
        </Dialog>

    </AppLayout>
</template>

