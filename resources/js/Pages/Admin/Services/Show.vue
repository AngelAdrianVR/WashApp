<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

// Componentes de PrimeVue
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';
import InlineMessage from 'primevue/inlinemessage'; // <-- IMPORTACIÓN AGREGADA

const props = defineProps({
    service: Object,
});

const confirm = useConfirm();
const toast = useToast();

// Formulario para subir nuevas imágenes
const imageForm = useForm({
    images: [],
});

// Formatea un número como moneda MXN
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

// Devuelve el color de la etiqueta según el estado
const getStatusSeverity = (isActive) => isActive ? 'success' : 'danger';
const getTypeSeverity = (type) => type === 'Agua' ? 'info' : 'warning';

// Llama a la ruta para cambiar el estado del servicio
const toggleIsActive = () => {
    router.patch(route('admin.services.toggleActive', props.service.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estado del servicio actualizado.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 3000 }),
    });
};

// Muestra un diálogo de confirmación antes de eliminar el servicio
const confirmDeleteService = () => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar este servicio? Esta acción no se puede deshacer.',
        header: 'Confirmación de Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('admin.services.destroy', props.service.id));
        },
    });
};

// Muestra un diálogo de confirmación antes de eliminar una imagen
const confirmDeleteMedia = (mediaId) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar esta imagen?',
        header: 'Confirmar Eliminación de Imagen',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('media.destroy', mediaId), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Éxito', detail: 'Imagen eliminada.', life: 3000 }),
            });
        },
    });
};

// Maneja la selección de nuevos archivos
const onFilesSelect = (event) => {
    imageForm.images = event.files;
};

// Sube las nuevas imágenes
const uploadImages = () => {
    imageForm.post(route('admin.services.addImages', props.service.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Imágenes subidas.', life: 3000 });
            imageForm.reset();
            // Limpiar visualmente el FileUpload (requiere una referencia)
            fileUploader.value.clear();
        },
    });
};

const fileUploader = ref(null);

</script>

<template>
    <AppLayout title="Detalle del Servicio">
        <Toast position="top-right" />
        <ConfirmDialog />

        <Back :href="route('admin.services.index')" />

        <div class="p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Columna de Información General -->
                <div class="lg:col-span-1 space-y-6">
                    <Card class="dark:bg-gray-800">
                        <template #title>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Información del Servicio</h2>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div>
                                    <h3 class="font-semibold text-gray-600 dark:text-gray-400">Nombre</h3>
                                    <p class="text-gray-800 dark:text-gray-200">{{ service.name }}</p>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-600 dark:text-gray-400">Descripción</h3>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm whitespace-pre-line">{{ service.description }}</p>
                                </div>
                                <div class="flex justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-600 dark:text-gray-400">Precio</h3>
                                        <p class="text-gray-800 dark:text-gray-200">{{ formatCurrency(service.price) }}</p>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-600 dark:text-gray-400">Duración</h3>
                                        <p class="text-gray-800 dark:text-gray-200">{{ service.duration_minutes }} min.</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div>
                                        <h3 class="font-semibold text-gray-600 dark:text-gray-400">Tipo</h3>
                                        <Tag :value="service.type" :severity="getTypeSeverity(service.type)" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-600 dark:text-gray-400">Estado</h3>
                                        <Tag :value="service.is_active ? 'Activo' : 'Inactivo'" :severity="getStatusSeverity(service.is_active)" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="dark:bg-gray-800">
                         <template #title>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Acciones</h2>
                        </template>
                        <template #content>
                            <div class="flex flex-col space-y-3">
                                <Link :href="route('admin.services.edit', service.id)">
                                    <Button label="Editar Servicio" icon="pi pi-pencil" class="w-full" outlined />
                                </Link>
                                <Button :label="service.is_active ? 'Desactivar Servicio' : 'Activar Servicio'" 
                                        :icon="service.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'" 
                                        class="w-full" 
                                        outlined 
                                        :severity="service.is_active ? 'secondary' : 'success'"
                                        @click="toggleIsActive" />
                                <Button label="Eliminar Servicio" icon="pi pi-trash" class="w-full" severity="danger" @click="confirmDeleteService" />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Columna de Galería -->
                <div class="lg:col-span-2">
                    <Card class="dark:bg-gray-800">
                        <template #title>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Galería de Imágenes</h2>
                        </template>
                        <template #content>
                            <!-- Grid de Imágenes Existentes -->
                            <div v-if="service.media.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                                <div v-for="image in service.media" :key="image.id" class="relative group">
                                    <Image :src="image.url" alt="Imagen del servicio" preview class="w-full h-40 object-cover rounded-lg shadow-md" />
                                    <div class="absolute top-2 right-2">
                                        <Button icon="pi pi-trash" rounded text severity="danger" class="bg-white/70 dark:bg-gray-800/70" @click="confirmDeleteMedia(image.id)" />
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400 border-2 border-dashed rounded-lg mb-8">
                                <p>Este servicio aún no tiene imágenes.</p>
                            </div>
                            
                            <!-- Uploader para Nuevas Imágenes -->
                            <div>
                                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Añadir más imágenes</h3>
                                <FileUpload 
                                    ref="fileUploader"
                                    name="images[]" 
                                    @select="onFilesSelect"
                                    :multiple="true" 
                                    accept="image/*"
                                    :maxFileSize="2000000"
                                    chooseLabel="Seleccionar"
                                    :showCancelButton="false"
                                    :showUploadButton="false"
                                >
                                    <template #empty>
                                        <p class="p-8 text-center text-gray-500 dark:text-gray-400">Arrastra y suelta las imágenes aquí.</p>
                                    </template>
                                </FileUpload>
                                <div class="mt-4 flex justify-end">
                                    <Button label="Subir Imágenes" icon="pi pi-upload" :disabled="imageForm.images.length === 0 || imageForm.processing" @click="uploadImages" :loading="imageForm.processing" />
                                </div>
                                <InlineMessage v-if="imageForm.errors.images" severity="error" class="mt-2 w-full">{{ imageForm.errors.images }}</InlineMessage>
                            </div>
                        </template>
                    </Card>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

