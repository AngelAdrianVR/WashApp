<script setup>
import { ref, computed } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';

const currentStep = ref(1);
const totalSteps = 3;

// Datos del formulario
const selectedServices = ref([]);
const selectedDate = ref('');
const selectedTime = ref('');
const userInfo = ref({
    name: '',
    phone: '',
    address: '',
    notes: ''
});

// Opciones de servicios y horarios
const services = ref([
  { id: 1, name: 'Lavado Ecológico Premium', price: 250 },
  { id: 2, name: 'Lavado a Detalle (Showroom)', price: 600 },
  { id: 3, name: 'Pulido y Encerado Cerámico', price: 1200 },
  { id: 4, name: 'Limpieza Profunda de Interiores', price: 850 },
]);

const availableTimes = ref(['09:00 AM', '11:00 AM', '01:00 PM', '03:00 PM', '05:00 PM']);

// Lógica de navegación
const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

// Cálculo del total
const totalPrice = computed(() => {
    return selectedServices.value.reduce((total, serviceId) => {
        const service = services.value.find(s => s.id === serviceId);
        return total + (service ? service.price : 0);
    }, 0);
});

const submitAppointment = () => {
    // Aquí iría la lógica para enviar los datos al backend
    console.log({
        services: selectedServices.value,
        date: selectedDate.value,
        time: selectedTime.value,
        user: userInfo.value,
        total: totalPrice.value
    });
    // Mostrar un mensaje de éxito
    alert('¡Cita agendada con éxito! Nos pondremos en contacto contigo para confirmar.');
}

</script>

<template>
    <LandingLayout :title="'Agendar Cita'">
        <main class="bg-slate-50 text-gray-800">
            <section id="agendar-cita" class="py-24 lg:py-32 pt-36">
                <div class="container mx-auto px-6">
                    <div class="max-w-3xl mx-auto">
                        <div class="text-center mb-12">
                            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800 leading-tight">
                                Agenda tu Servicio
                            </h1>
                            <p class="text-slate-600 mt-4 text-lg">
                                Sigue estos sencillos pasos para reservar tu cita. ¡Te tomará menos de 2 minutos!
                            </p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full px-10 mb-12">
                            <div class="relative">
                                <div class="absolute left-0 top-1/2 w-full h-0.5 bg-gray-300"></div>
                                <div class="absolute left-0 top-1/2 h-0.5 bg-blue-500 transition-all duration-500" :style="{ width: ((currentStep - 1) / (totalSteps - 1)) * 100 + '%' }"></div>
                                <div class="flex justify-between items-center">
                                    <div v-for="step in totalSteps" :key="step" class="relative">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-500" :class="currentStep >= step ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-500'">
                                            {{ step }}
                                        </div>
                                        <span class="absolute -bottom-6 text-sm" :class="currentStep >= step ? 'text-blue-500 font-semibold' : 'text-gray-500'">{{ ['Servicios', 'Fecha', 'Datos'][step - 1] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-xl p-8 md:p-12">
                            <!-- Step 1: Select Services -->
                            <div v-if="currentStep === 1">
                                <h3 class="text-2xl font-bold mb-6">1. Selecciona los servicios</h3>
                                <div class="space-y-4">
                                    <label v-for="service in services" :key="service.id" class="flex items-center p-4 border rounded-lg cursor-pointer transition-all" :class="selectedServices.includes(service.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                        <input type="checkbox" :value="service.id" v-model="selectedServices" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-4 flex-grow text-slate-700 font-medium">{{ service.name }}</span>
                                        <span class="text-slate-800 font-semibold">${{ service.price }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Step 2: Select Date & Time -->
                            <div v-if="currentStep === 2">
                                <h3 class="text-2xl font-bold mb-6">2. Elige fecha y hora</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                                        <input type="date" id="date" v-model="selectedDate" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Horarios Disponibles</h4>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button v-for="time in availableTimes" :key="time" @click="selectedTime = time" class="p-3 border rounded-lg text-sm transition-all" :class="selectedTime === time ? 'bg-blue-500 text-white border-blue-500' : 'bg-white hover:bg-gray-100'">
                                                {{ time }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: User Information -->
                            <div v-if="currentStep === 3">
                                 <h3 class="text-2xl font-bold mb-6">3. Tus datos de contacto</h3>
                                 <div class="space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                                        <input type="text" id="name" v-model="userInfo.name" class="mt-1 block w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                        <input type="tel" id="phone" v-model="userInfo.phone" class="mt-1 block w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700">Dirección del servicio</label>
                                        <input type="text" id="address" v-model="userInfo.address" class="mt-1 block w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                     <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700">Notas adicionales (opcional)</label>
                                        <textarea id="notes" v-model="userInfo.notes" rows="3" class="mt-1 block w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                 </div>
                            </div>

                             <!-- Navigation Buttons -->
                            <div class="mt-10 pt-6 border-t flex justify-between items-center">
                                <button @click="prevStep" :disabled="currentStep === 1" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Anterior
                                </button>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Total:</p>
                                    <p class="text-2xl font-bold text-slate-800">${{ totalPrice }} MXN</p>
                                </div>
                                <button v-if="currentStep < totalSteps" @click="nextStep" class="px-6 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                                    Siguiente
                                </button>
                                <button v-if="currentStep === totalSteps" @click="submitAppointment" class="px-6 py-2 text-sm font-medium text-white bg-cyan-500 rounded-lg hover:bg-cyan-600">
                                    Confirmar Cita
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </LandingLayout>
</template>
