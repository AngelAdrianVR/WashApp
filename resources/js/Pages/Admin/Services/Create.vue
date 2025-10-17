<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Importaciones de PrimeVue
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import InlineMessage from 'primevue/inlinemessage';
import FileUpload from 'primevue/fileupload'; // Importamos el componente para subir archivos

// Definimos el formulario con los campos del modelo Service
const form = useForm({
    name: '',
    description: '',
    price: null,
    duration_minutes: null,
    type: 'Agua', // Valor por defecto
    is_active: true,
    image: null, // Añadimos el campo para la imagen
});

// Opciones para el tipo de lavado
const washTypeOptions = ref([
    { label: 'Lavado con Agua', value: 'Agua' },
    { label: 'Lavado en Seco', value: 'Seco' },
]);

// Función para manejar la selección del archivo y asignarlo al formulario
const onFileSelect = (event) => {
    form.image = event.files[0];
};

// Función para enviar el formulario al backend
const submit = () => {
    // Inertia se encargará de enviar el formulario como multipart/form-data
    form.post(route('admin.services.store'));
};
</script>

<template>
    <AppLayout title="Crear Servicio">
        <Back :href="route('admin.services.index')" />
        
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
            <div class="max-w-3xl mx-auto">
                <Card class="dark:bg-gray-800 dark:text-gray-200">
                    <template #title>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            Crear Nuevo Servicio
                        </h1>
                    </template>
                    <template #subtitle>
                        <p class="text-gray-600 dark:text-gray-400">
                            Completa los datos para registrar un nuevo servicio de lavado.
                        </p>
                    </template>
                    <template #content>
                        <form @submit.prevent="submit" class="mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="name" class="font-semibold">Nombre del Servicio</label>
                                    <InputText id="name" v-model="form.name" :invalid="!!form.errors.name" />
                                    <InlineMessage v-if="form.errors.name" severity="error">{{ form.errors.name }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="description" class="font-semibold">Descripción</label>
                                    <Textarea id="description" v-model="form.description" rows="3" autoResize :invalid="!!form.errors.description" />
                                    <InlineMessage v-if="form.errors.description" severity="error">{{ form.errors.description }}</InlineMessage>
                                </div>

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="image" class="font-semibold">Imagen del Servicio (Opcional)</label>
                                    <FileUpload 
                                        name="image" 
                                        @select="onFileSelect" 
                                        :showUploadButton="false" 
                                        :showCancelButton="false" 
                                        accept="image/*" 
                                        chooseLabel="Seleccionar Imagen"
                                        :multiple="true"
                                    >
                                        <template #empty>
                                            <div class="text-center p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                                <i class="pi pi-image text-3xl text-gray-400 dark:text-gray-500"></i>
                                                <p class="mt-2 text-gray-500 dark:text-gray-400">Arrastra y suelta una imagen aquí.</p>
                                            </div>
                                        </template>
                                    </FileUpload>
                                    <InlineMessage v-if="form.errors.image" severity="error">{{ form.errors.image }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="price" class="font-semibold">Precio</label>
                                    <InputNumber id="price" v-model="form.price" mode="currency" currency="MXN" locale="es-MX" :invalid="!!form.errors.price" />
                                    <InlineMessage v-if="form.errors.price" severity="error">{{ form.errors.price }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="duration_minutes" class="font-semibold">Duración (minutos)</label>
                                    <InputNumber id="duration_minutes" v-model="form.duration_minutes" suffix=" min" :invalid="!!form.errors.duration_minutes" />
                                    <InlineMessage v-if="form.errors.duration_minutes" severity="error">{{ form.errors.duration_minutes }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="type" class="font-semibold">Tipo de Lavado</label>
                                    <Dropdown id="type" v-model="form.type" :options="washTypeOptions" optionLabel="label" optionValue="value" :invalid="!!form.errors.type" />
                                    <InlineMessage v-if="form.errors.type" severity="error">{{ form.errors.type }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2 justify-center">
                                    <label for="is_active" class="font-semibold">Servicio Activo</label>
                                    <div class="flex items-center mt-2">
                                        <InputSwitch id="is_active" v-model="form.is_active" />
                                        <span :class="['ml-3', form.is_active ? 'text-green-500' : 'text-gray-500']">
                                            {{ form.is_active ? 'Sí, el servicio está disponible' : 'No, el servicio está oculto' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <Button type="submit" label="Crear Servicio" icon="pi pi-check" :loading="form.processing" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

