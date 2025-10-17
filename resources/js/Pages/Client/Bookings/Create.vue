<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import axios from 'axios';

// Importando componentes de PrimeVue para la UI
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Calendar from 'primevue/calendar';
import ProgressSpinner from 'primevue/progressspinner';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Card from 'primevue/card';

// Props pasados desde el controlador
const props = defineProps({
    services: Array,
    user: Object,
});

// --- ESTADO GENERAL ---
const activeStep = ref(0);
let map = null; // Instancia del mapa de Leaflet
let marker = null;
let geocodeTimeout = null; // Temporizador para debounce de geocodificación

// --- PASO 1: SELECCIÓN DE SERVICIOS ---
const selectedServices = ref([]);
const totalDuration = computed(() => selectedServices.value.reduce((acc, id) => acc + props.services.find(s => s.id === id).duration_minutes, 0));
const totalPrice = computed(() => selectedServices.value.reduce((acc, id) => acc + parseFloat(props.services.find(s => s.id === id).price), 0));

// **NUEVO**: Propiedad computada para formatear la duración a horas y minutos
const formattedDuration = computed(() => {
    const totalMinutes = totalDuration.value;
    if (totalMinutes < 60) {
        return `${totalMinutes} min`;
    }
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    if (minutes === 0) {
        return `${hours}h`;
    }
    return `${hours}h ${minutes}m`;
});


// --- PASO 2: SELECCIÓN DE FECHA Y HORA ---
const selectedDate = ref(null);
const availableTimes = ref([]);
const isLoadingTimes = ref(false);
const selectedTime = ref(null);

watch(selectedDate, async (newDate) => {
    if (!newDate || totalDuration.value === 0) return;
    isLoadingTimes.value = true;
    availableTimes.value = [];
    selectedTime.value = null;
    try {
        const dateString = newDate.toISOString().split('T')[0];
        const response = await axios.post(route('client.bookings.availableTimes'), {
            date: dateString,
            duration: totalDuration.value
        });
        availableTimes.value = response.data;
    } catch (error) {
        console.error("Error fetching available times:", error);
    } finally {
        isLoadingTimes.value = false;
    }
});

// --- PASO 3: DETALLES Y PAGO ---
const bookingForm = useForm({
    name: props.user.name || '',
    phone_number: props.user.phone_number || '',
    address: '',
    notes: '',
    payment_method: 'Efectivo',
    latitude: 19.4326, // Coordenadas por defecto (CDMX)
    longitude: -99.1332,
    services: [],
    scheduled_at: '',
    total_price: 0,
});

// **NUEVO**: Función para obtener la dirección desde coordenadas (Reverse Geocoding)
const updateAddressFromCoords = async (lat, lng) => {
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
        if (response.data && response.data.display_name) {
            // Se actualiza el campo de dirección sin disparar el watcher de geocodificación
            bookingForm.address = response.data.display_name;
        }
    } catch (error) {
        console.error("Error en reverse geocoding:", error);
    }
};

// **NUEVO**: Función para obtener coordenadas desde la dirección (Geocoding)
const updateMapFromAddress = async () => {
    if (!bookingForm.address.trim()) return;
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(bookingForm.address)}&format=json&limit=1`);
        if (response.data && response.data.length > 0) {
            const { lat, lon } = response.data[0];
            const newLatLng = L.latLng(lat, lon);
            bookingForm.latitude = parseFloat(lat);
            bookingForm.longitude = parseFloat(lon);
            if (map && marker) {
                map.setView(newLatLng, 16);
                marker.setLatLng(newLatLng);
            }
        }
    } catch (error) {
        console.error("Error en geocoding:", error);
    }
};

// **NUEVO**: Observador para la dirección con debounce para no hacer llamadas a la API en cada tecla
watch(() => bookingForm.address, (newAddress, oldAddress) => {
    // Solo se activa si el cambio viene del usuario y no del mapa
    if (newAddress !== oldAddress) {
        clearTimeout(geocodeTimeout);
        geocodeTimeout = setTimeout(() => {
            updateMapFromAddress();
        }, 1200); // Espera 1.2 segundos después de que el usuario deja de escribir
    }
});


// Función para inicializar el mapa
const initMap = async () => {
    await nextTick();
    if (typeof L === 'undefined' || document.getElementById('map') === null) {
        console.error("Leaflet no está cargado o el div 'map' no existe.");
        return;
    }
    if (!map) {
        map = L.map('map').setView([bookingForm.latitude, bookingForm.longitude], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        marker = L.marker([bookingForm.latitude, bookingForm.longitude], { draggable: true }).addTo(map);

        // **MODIFICADO**: Evento de arrastre del marcador
        marker.on('dragend', function (event) {
            const position = marker.getLatLng();
            bookingForm.latitude = position.lat;
            bookingForm.longitude = position.lng;
            // Actualiza el campo de dirección cuando se mueve el marcador
            updateAddressFromCoords(position.lat, position.lng);
        });
    }
};

// Observador para inicializar el mapa cuando se llegue al paso 3 (índice 2)
watch(activeStep, (newStep) => {
    if (newStep === 2) {
        initMap();
    }
});

// --- ENVÍO FINAL ---
const submitBooking = () => {
    bookingForm.services = selectedServices.value;
    bookingForm.total_price = totalPrice.value;
    const date = selectedDate.value.toISOString().split('T')[0];
    bookingForm.scheduled_at = `${date} ${selectedTime.value}`;
    bookingForm.post(route('client.bookings.store'), {
        onSuccess: () => {
            // Redirigir o mostrar mensaje de éxito
        }
    });
};
</script>

<template>
    <AppLayout title="Agendar Cita">
        <Head>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <component is="script" src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></component>
        </Head>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Agendar una Nueva Cita
            </h2>
        </template>
        
        <Back :href="route('client.bookings.index')" />
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- PASO 1: SERVICIOS -->
                <Card v-if="activeStep === 0">
                    <template #title>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Paso 1: Selecciona los servicios</h3>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div v-for="service in services" :key="service.id" class="flex items-center justify-between p-4 border rounded-lg dark:border-gray-700">
                                <div class="flex items-center">
                                    <Checkbox v-model="selectedServices" :inputId="`service_${service.id}`" :name="`service_${service.id}`" :value="service.id" />
                                    <label :for="`service_${service.id}`" class="ml-3">
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ service.name }}</span>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ service.description }}</p>
                                        <small class="text-xs text-gray-400 dark:text-gray-500">{{ service.duration_minutes }} min</small>
                                    </label>
                                </div>
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">${{ service.price }}</div>
                            </div>
                        </div>
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg" v-if="selectedServices.length > 0">
                             <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Estimado</div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ totalPrice.toFixed(2) }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Duración Estimada</div>
                                    <!-- **MODIFICADO**: Se usa la nueva propiedad computada -->
                                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formattedDuration }}</div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #footer>
                        <div class="flex justify-end">
                            <Button label="Siguiente" @click="activeStep = 1" :disabled="selectedServices.length === 0" />
                        </div>
                    </template>
                </Card>

                <!-- PASO 2: FECHA Y HORA -->
                <Card v-if="activeStep === 1">
                     <template #title>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Paso 2: Elige Fecha y Hora</h3>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Elige una fecha</h3>
                                <Calendar v-model="selectedDate" inline :min-date="new Date()" />
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Horas disponibles</h3>
                                <div v-if="isLoadingTimes" class="flex justify-center items-center h-48">
                                    <ProgressSpinner />
                                </div>
                                <div v-else-if="availableTimes.length" class="grid grid-cols-3 gap-2">
                                    <Button v-for="time in availableTimes" :key="time" :label="time" :outlined="selectedTime !== time" @click="selectedTime = time" />
                                </div>
                                <div v-else-if="selectedDate" class="text-center text-gray-500 dark:text-gray-400 h-48 flex items-center justify-center">
                                    No hay horarios disponibles para este día.
                                </div>
                                <div v-else class="text-center text-gray-500 dark:text-gray-400 h-48 flex items-center justify-center">
                                    Selecciona una fecha para ver los horarios.
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #footer>
                        <div class="flex justify-between">
                            <Button label="Atrás" severity="secondary" @click="activeStep = 0" />
                            <Button label="Siguiente" @click="activeStep = 2" :disabled="!selectedTime" />
                        </div>
                    </template>
                </Card>

                <!-- PASO 3: DETALLES -->
                <Card v-if="activeStep === 2">
                     <template #title>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Paso 3: Confirma tus Datos</h3>
                    </template>
                    <template #content>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Completo</label>
                                    <InputText id="name" v-model="bookingForm.name" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                                    <InputText id="phone" v-model="bookingForm.phone_number" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección Completa</label>
                                    <Textarea id="address" v-model="bookingForm.address" class="mt-1 block w-full" rows="3" placeholder="Escribe tu dirección para verla en el mapa"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Método de Pago</label>
                                    <div class="mt-2 flex items-center gap-6">
                                        <div class="flex items-center">
                                            <RadioButton v-model="bookingForm.payment_method" inputId="payCash" name="payment" value="Efectivo" />
                                            <label for="payCash" class="ml-2">Efectivo</label>
                                        </div>
                                        <div class="flex items-center">
                                            <RadioButton v-model="bookingForm.payment_method" inputId="payCard" name="payment" value="Tarjeta" />
                                            <label for="payCard" class="ml-2">Tarjeta</label>
                                        </div>
                                        <div class="flex items-center">
                                            <RadioButton v-model="bookingForm.payment_method" inputId="payTransfer" name="payment" value="Transferencia" />
                                            <label for="payTransfer" class="ml-2">Transferencia</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ajusta tu ubicación en el mapa</label>
                                <div id="map" class="mt-1 w-full h-64 rounded-lg"></div>
                            </div>
                        </div>
                    </template>
                    <template #footer>
                        <div class="flex justify-between">
                            <Button label="Atrás" severity="secondary" @click="activeStep = 1" />
                            <Button label="Confirmar Reserva" @click="submitBooking" :loading="bookingForm.processing" />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
