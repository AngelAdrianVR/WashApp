<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';

// Importaciones de PrimeVue para confirmación y notificaciones
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

// Componentes de PrimeVue
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import InlineMessage from 'primevue/inlinemessage';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';

// Props
const props = defineProps({
    service: Object,
});

// Hooks de PrimeVue
const confirm = useConfirm();
const toast = useToast();

// State
const washTypeOptions = ref([
    { label: 'Lavado con Agua', value: 'Agua' },
    { label: 'Lavado en Seco', value: 'Seco' },
]);

// Mantenemos una lista reactiva de las imágenes actuales
const currentMedia = ref([...props.service.media]);

// Form setup
const form = useForm({
    _method: 'PUT',
    name: props.service.name,
    description: props.service.description,
    price: props.service.price,
    duration_minutes: props.service.duration_minutes,
    type: props.service.type,
    is_active: props.service.is_active,
    new_images: [], // Campo para las nuevas imágenes a subir
    // Ya no necesitamos images_to_delete
});

// Handlers
const onFileSelect = (event) => {
    form.new_images = event.files;
};

// Nueva función para eliminar imágenes con confirmación
const confirmDeleteMedia = (mediaItem) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar esta imagen?',
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            // Hacemos una petición DELETE a la nueva ruta
            router.delete(route('media.destroy', mediaItem.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Confirmado', detail: 'Imagen eliminada', life: 3000 });
                    // Actualizamos la lista de imágenes en el frontend
                    currentMedia.value = currentMedia.value.filter(m => m.id !== mediaItem.id);
                },
                onError: () => {
                     toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar la imagen', life: 3000 });
                }
            });
        },
    });
};


const submit = () => {
    form.post(route('admin.services.update', props.service.id));
};
</script>

<template>
    <AppLayout title="Editar Servicio">
        <!-- Componentes para notificaciones y diálogos -->
        <Toast position="top-right" />
        <ConfirmDialog />

        <Back :href="route('admin.services.index')" />
        
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
            <div class="max-w-3xl mx-auto">
                <Card class="dark:bg-gray-800 dark:text-gray-200">
                    <template #title>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            Editar Servicio: {{ form.name }}
                        </h1>
                    </template>
                    <template #content>
                        <form @submit.prevent="submit" class="mt-6">
                            <!-- Sección de Imágenes Actuales (Modificada) -->
                            <div v-if="currentMedia.length" class="mb-8">
                                <h3 class="font-semibold text-lg mb-4">Imágenes Actuales</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                                    <div v-for="media in currentMedia" :key="media.id" class="relative group">
                                        <Image :src="media.url" alt="Imagen del servicio" preview class="w-auto h-32 object-cover rounded-lg" />
                                        <Button 
                                            icon="pi pi-trash" 
                                            severity="danger" 
                                            rounded 
                                            @click="confirmDeleteMedia(media)" 
                                            class="absolute top-0 right-5 !w-7 !h-7 bg-black/50 hover:bg-red-700/80 transition-colors duration-200"
                                            aria-label="Eliminar imagen"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Resto de los campos del formulario (sin cambios) -->
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
                                    <label for="new_images" class="font-semibold">Agregar Nuevas Imágenes (Opcional)</label>
                                    <FileUpload 
                                        name="new_images[]" 
                                        @select="onFileSelect" 
                                        :showUploadButton="false" 
                                        :showCancelButton="false" 
                                        accept="image/*" 
                                        chooseLabel="Seleccionar Imágenes"
                                        :multiple="true"
                                    >
                                        <template #empty>
                                            <div class="text-center p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                                <i class="pi pi-image text-3xl text-gray-400 dark:text-gray-500"></i>
                                                <p class="mt-2 text-gray-500 dark:text-gray-400">Arrastra y suelta imágenes aquí.</p>
                                            </div>
                                        </template>
                                    </FileUpload>
                                    <InlineMessage v-if="form.errors.new_images" severity="error">{{ form.errors.new_images }}</InlineMessage>
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
                                            {{ form.is_active ? 'Sí, disponible' : 'No, oculto' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <Button type="submit" label="Actualizar Servicio" icon="pi pi-save" :loading="form.processing" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

