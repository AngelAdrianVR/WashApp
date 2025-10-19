<script setup>
import { ref, computed, onMounted } from 'vue'; // Import onMounted
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import 'leaflet/dist/leaflet.css'; // Import Leaflet CSS
import L from 'leaflet'; // Import Leaflet library

// --- PROPS ---
const props = defineProps({
    booking: {
        type: Object,
        required: true,
    }
});

// --- STATE MANAGEMENT ---
const isCancelModalVisible = ref(false);
const isNotesModalVisible = ref(false);

// --- FORMS ---
const cancelForm = useForm({
    reason: '',
});

const notesForm = useForm({
    notes: props.booking.notes || '',
});


// --- MODAL FUNCTIONS ---
const openCancelModal = () => {
    cancelForm.reset();
    isCancelModalVisible.value = true;
};

const closeCancelModal = () => isCancelModalVisible.value = false;

const openNotesModal = () => {
    notesForm.notes = props.booking.notes || ''; // Reset notes on open
    isNotesModalVisible.value = true;
};

const closeNotesModal = () => isNotesModalVisible.value = false;


// --- ACTIONS ---
const submitCancellation = () => {
    cancelForm.patch(route('client.bookings.cancel', { booking: props.booking.id }), {
        preserveScroll: true,
        onSuccess: () => closeCancelModal(),
    });
};

const submitNotes = () => {
    notesForm.patch(route('client.bookings.updateNotes', { booking: props.booking.id }), {
        preserveScroll: true,
        onSuccess: () => closeNotesModal(),
    });
};


// --- HELPERS ---
const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-MX', options);
};

const formatCurrency = (value) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);

const statusInfo = computed(() => {
    const statuses = {
        pending: { text: 'Pendiente', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' },
        confirmed: { text: 'Confirmada', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' },
        on_way: { text: 'En Camino', class: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' },
        in_progress: { text: 'En Progreso', class: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' },
        completed: { text: 'Completada', class: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' },
        cancelled: { text: 'Cancelada', class: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' },
    };
    return statuses[props.booking.status] || { text: 'Desconocido', class: 'bg-gray-100' };
});

const canCancel = computed(() => ['pending', 'confirmed'].includes(props.booking.status));

// --- STEPPER LOGIC ---
const stepperSteps = [
    { id: 'confirmed', label: 'Confirmada' },
    { id: 'on_way', label: 'En Camino' },
    { id: 'in_progress', label: 'En Progreso' },
    { id: 'completed', label: 'Completada' }
];

const getCurrentStepIndex = (status) => {
    const index = stepperSteps.findIndex(step => step.id === status);
    if (status === 'completed') return stepperSteps.length;
    if (status === 'pending') return 0;
    return index >= 0 ? index + 1 : 0;
};

// --- MAP LOGIC ---
onMounted(() => {
    if (props.booking.latitude && props.booking.longitude) {
        // Fix for default icon path issue with bundlers like Vite
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png',
            iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
        });

        const map = L.map('map').setView([props.booking.latitude, props.booking.longitude], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        L.marker([props.booking.latitude, props.booking.longitude]).addTo(map)
            .bindPopup(`<b>Ubicación del servicio</b><br>${props.booking.address}`)
            .openPopup();
    }
});
</script>

<template>
    <Head :title="'Detalles de reserva'" />
    <AppLayout title="Detalle de Reserva">
        <template #header>
            <div class="flex items-center">
                 <Link :href="route('client.bookings.index')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <i class="pi pi-arrow-left"></i>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight ml-4">
                    Detalles de Reserva
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Columna Izquierda: Detalles de la Reserva -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6 space-y-6">
                    <!-- Stepper de Estado -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Estado del Servicio</h3>
                         <ol class="items-center w-full space-y-4 sm:flex sm:space-x-8 sm:space-y-0">
                            <li v-for="(step, index) in stepperSteps" :key="step.id" class="flex items-center space-x-2.5" :class="{'text-blue-600 dark:text-blue-500': getCurrentStepIndex(booking.status) > index, 'text-gray-500 dark:text-gray-400': getCurrentStepIndex(booking.status) <= index}">
                                <span class="flex items-center justify-center w-8 h-8 border rounded-full shrink-0" :class="{'border-blue-600 dark:border-blue-500': getCurrentStepIndex(booking.status) > index, 'border-gray-500 dark:border-gray-400': getCurrentStepIndex(booking.status) <= index}">
                                    <i v-if="getCurrentStepIndex(booking.status) > index + 1 || booking.status === 'completed'" class="pi pi-check text-blue-600 dark:text-blue-500"></i>
                                    <span v-else>{{ index + 1 }}</span>
                                </span>
                                <span><h3 class="font-medium leading-tight">{{ step.label }}</h3></span>
                                <div v-if="index < stepperSteps.length - 1" class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            </li>
                        </ol>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- Detalles del Servicio -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha y Hora</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ formatDate(booking.scheduled_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado Actual</p>
                            <p class="mt-1"><span :class="['text-sm font-semibold px-3 py-1 rounded-full', statusInfo.class]">{{ statusInfo.text }}</span></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dirección del Servicio</p>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ booking.address }}</p>
                        </div>
                         <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Notas Adicionales</p>
                            <p class="mt-1 text-gray-700 dark:text-gray-300 italic">{{ booking.notes || 'No hay notas adicionales.' }}</p>
                        </div>
                    </div>

                    <!-- Servicios Contratados -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Servicios Contratados</h3>
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700 border-t border-b dark:border-gray-700">
                            <li v-for="service in booking.services" :key="service.id" class="py-3 flex justify-between items-center">
                                <span class="text-gray-800 dark:text-gray-200">{{ service.name }}</span>
                                <span class="font-semibold text-gray-600 dark:text-gray-300">{{ formatCurrency(service.pivot.price_at_booking) }}</span>
                            </li>
                        </ul>
                        <div class="flex justify-end items-center mt-3 pr-2">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400 mr-4">Total</span>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(booking.total_price) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Mapa y Acciones -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Ubicación en el Mapa</h3>
                        <div id="map" class="h-64 z-0 rounded-lg"></div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                         <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Acciones</h3>
                         <div class="space-y-3">
                            <Button @click="$inertia.visit(route('client.bookings.edit', booking.id))" label="Editar Reserva" icon="pi pi-pencil" severity="secondary" outlined class="w-full" />
                            <Button label="Añadir o Editar Notas" icon="pi pi-file-edit" severity="info" outlined class="w-full" @click="openNotesModal"/>
                            <Button label="Cancelar Reserva" icon="pi pi-times-circle" severity="danger" class="w-full" @click="openCancelModal" :disabled="!canCancel" />
                            <small v-if="!canCancel" class="text-center block text-gray-500 dark:text-gray-400 mt-2">La cancelación solo está disponible para reservas pendientes o confirmadas.</small>
                         </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal para Cancelar -->
        <Dialog v-model:visible="isCancelModalVisible" modal header="Cancelar Reserva" :style="{ width: '90vw', maxWidth: '500px' }">
            <p class="mb-4 text-gray-600 dark:text-gray-300">¿Estás seguro de que quieres cancelar? Por favor, indícanos el motivo.</p>
            <Textarea v-model="cancelForm.reason" rows="4" class="w-full" :invalid="!!cancelForm.errors.reason" />
            <small v-if="cancelForm.errors.reason" class="text-red-500">{{ cancelForm.errors.reason }}</small>
            <template #footer>
                <Button label="Cerrar" @click="closeCancelModal" text />
                <Button label="Confirmar Cancelación" @click="submitCancellation" :loading="cancelForm.processing" severity="danger" />
            </template>
        </Dialog>

        <!-- Modal para Notas -->
        <Dialog v-model:visible="isNotesModalVisible" modal header="Añadir o Editar Notas" :style="{ width: '90vw', maxWidth: '500px' }">
            <p class="mb-4 text-gray-600 dark:text-gray-300">Agrega cualquier detalle extra que nuestro equipo deba saber.</p>
            <Textarea v-model="notesForm.notes" rows="5" class="w-full" placeholder="Ej: Mi coche es color rojo, el timbre no funciona, etc." />
            <small v-if="notesForm.errors.notes" class="text-red-500">{{ notesForm.errors.notes }}</small>
            <template #footer>
                <Button label="Cerrar" @click="closeNotesModal" text />
                <Button label="Guardar Notas" @click="submitNotes" :loading="notesForm.processing" />
            </template>
        </Dialog>

    </AppLayout>
</template>

