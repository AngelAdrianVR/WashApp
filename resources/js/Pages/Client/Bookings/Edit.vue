<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import axios from 'axios';
import dayjs from 'dayjs'; // Librería para manejar fechas fácilmente

// Importando componentes de PrimeVue
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Calendar from 'primevue/calendar';
import ProgressSpinner from 'primevue/progressspinner';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Card from 'primevue/card';

const props = defineProps({
    services: Array,
    user: Object,
    booking: Object, // La reserva que estamos editando viene como prop
});

// --- ESTADO GENERAL ---
const activeStep = ref(0); // Iniciamos en el primer paso para claridad
let map = null;
let marker = null;
let geocodeTimeout = null;

// --- PASO 1: SERVICIOS ---
// Pre-seleccionamos los servicios de la reserva existente
const selectedServices = ref(props.booking.services.map(s => s.id));
const totalDuration = computed(() => selectedServices.value.reduce((acc, id) => {
    const service = props.services.find(s => s.id === id);
    return acc + (service ? service.duration_minutes : 0);
}, 0));
const totalPrice = computed(() => selectedServices.value.reduce((acc, id) => {
    const service = props.services.find(s => s.id === id);
    return acc + (service ? parseFloat(service.price) : 0);
}, 0));

const formattedDuration = computed(() => {
    const totalMinutes = totalDuration.value;
    if (totalMinutes < 60) return `${totalMinutes} min`;
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return minutes === 0 ? `${hours}h` : `${hours}h ${minutes}m`;
});

// --- PASO 2: FECHA Y HORA ---
const selectedDate = ref(dayjs(props.booking.scheduled_at).toDate());
const availableTimes = ref([]);
const isLoadingTimes = ref(false);
const selectedTime = ref(dayjs(props.booking.scheduled_at).format('HH:mm'));

// Función para buscar horarios, reutilizable
const fetchAvailableTimes = async (date) => {
    if (!date || totalDuration.value === 0) return;
    isLoadingTimes.value = true;
    availableTimes.value = [];
    try {
        const dateString = dayjs(date).format('YYYY-MM-DD');
        const response = await axios.post(route('client.bookings.availableTimes'), {
            date: dateString,
            duration: totalDuration.value
        });
        availableTimes.value = response.data;
        // Si la hora que ya estaba seleccionada no está en la nueva lista, la deseleccionamos.
        if (!availableTimes.value.includes(selectedTime.value)) {
            selectedTime.value = null;
        }
    } catch (error) {
        console.error("Error fetching available times:", error);
    } finally {
        isLoadingTimes.value = false;
    }
};

// Observadores para re-calcular horarios si cambia la fecha o la duración (servicios)
watch(selectedDate, (newDate) => {
    selectedTime.value = null; // Reseteamos la hora al cambiar de día
    fetchAvailableTimes(newDate);
});
watch(totalDuration, () => {
    fetchAvailableTimes(selectedDate.value);
});

// Cargamos los horarios para la fecha inicial al montar el componente
onMounted(() => {
    fetchAvailableTimes(selectedDate.value);
});

// --- PASO 3: DETALLES Y PAGO ---
const bookingForm = useForm({
    _method: 'PUT', // Clave para que Laravel reconozca la petición como UPDATE
    name: props.user.name,
    phone_number: props.user.phone_number,
    address: props.booking.address,
    notes: props.booking.notes || '',
    payment_method: props.booking.payment_method,
    latitude: parseFloat(props.booking.latitude),
    longitude: parseFloat(props.booking.longitude),
    services: [],
    scheduled_at: '',
    total_price: 0,
});

// (El resto de funciones del mapa son idénticas a Create.vue)
const updateAddressFromCoords = async (lat, lng) => {
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
        if (response.data?.display_name) {
            bookingForm.address = response.data.display_name;
        }
    } catch (error) { console.error("Error en reverse geocoding:", error); }
};
const updateMapFromAddress = async () => {
    if (!bookingForm.address.trim()) return;
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(bookingForm.address)}&format=json&limit=1`);
        if (response.data?.length > 0) {
            const { lat, lon } = response.data[0];
            const newLatLng = L.latLng(lat, lon);
            bookingForm.latitude = parseFloat(lat);
            bookingForm.longitude = parseFloat(lon);
            map?.setView(newLatLng, 16);
            marker?.setLatLng(newLatLng);
        }
    } catch (error) { console.error("Error en geocoding:", error); }
};
watch(() => bookingForm.address, (newAddress) => {
    clearTimeout(geocodeTimeout);
    geocodeTimeout = setTimeout(updateMapFromAddress, 1200);
});
const initMap = async () => {
    await nextTick();
    if (typeof L === 'undefined' || !document.getElementById('map')) return;
    if (!map) {
        map = L.map('map').setView([bookingForm.latitude, bookingForm.longitude], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        marker = L.marker([bookingForm.latitude, bookingForm.longitude], { draggable: true }).addTo(map)
            .on('dragend', function (event) {
                const position = marker.getLatLng();
                bookingForm.latitude = position.lat;
                bookingForm.longitude = position.lng;
                updateAddressFromCoords(position.lat, position.lng);
            });
    }
};
watch(activeStep, (newStep) => {
    if (newStep === 2) initMap();
});

// --- ENVÍO FINAL ---
const submitUpdate = () => {
    bookingForm.services = selectedServices.value;
    bookingForm.total_price = totalPrice.value;
    const date = dayjs(selectedDate.value).format('YYYY-MM-DD');
    bookingForm.scheduled_at = `${date} ${selectedTime.value}`;

    // Apuntamos a la ruta de update, pasando el ID de la reserva
    bookingForm.post(route('client.bookings.update', { booking: props.booking.id }), {
        onError: (errors) => {
            console.error("Errores de validación:", errors);
        }
    });
};
</script>

<template>
    <AppLayout title="Editar Cita">
        <Head>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <component is="script" src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></component>
        </Head>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Cita #{{ booking.id }}
            </h2>
        </template>
        
        <Back :href="route('client.bookings.index')" />
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- PASO 1: SERVICIOS -->
                <Card v-if="activeStep === 0">
                    <template #title>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Paso 1: Modifica los servicios</h3>
                    </template>
                    <template #content>
                        <!-- (Contenido idéntico a Create.vue) -->
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Paso 2: Cambia la Fecha y Hora</h3>
                    </template>
                    <template #content>
                        <!-- (Contenido idéntico a Create.vue) -->
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Paso 3: Confirma los nuevos datos</h3>
                    </template>
                    <template #content>
                        <!-- (Contenido idéntico a Create.vue) -->
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
                            <Button label="Guardar Cambios" @click="submitUpdate" :loading="bookingForm.processing" />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
