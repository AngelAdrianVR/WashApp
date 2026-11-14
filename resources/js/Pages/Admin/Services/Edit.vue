<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import ServiceForm from './Partials/ServiceForm.vue';

// Componentes de PrimeVue
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';

const props = defineProps({
    service: Object,
    vehicleCategories: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const currentMedia = ref([...props.service.media]);

const buildVariants = () =>
    Object.fromEntries(
        props.vehicleCategories.map((category) => [
            category.id,
            {
                price: props.service.variants?.[category.id]?.price ?? null,
                duration_minutes: props.service.variants?.[category.id]?.duration_minutes ?? null,
            },
        ])
    );

const form = useForm({
    _method: 'PUT',
    name: props.service.name,
    description: props.service.description,
    featuresText: (props.service.features ?? []).join('\n'),
    price: props.service.price,
    duration_minutes: props.service.duration_minutes,
    type: props.service.type,
    is_addon: props.service.is_addon,
    points: props.service.points,
    sort_order: props.service.sort_order,
    is_active: props.service.is_active,
    variants: buildVariants(),
    new_images: [],
});

const onFileSelect = (event) => {
    form.new_images = event.files;
};

const confirmDeleteMedia = (mediaItem) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar esta imagen?',
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('media.destroy', mediaItem.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Confirmado', detail: 'Imagen eliminada', life: 3000 });
                    currentMedia.value = currentMedia.value.filter((item) => item.id !== mediaItem.id);
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar la imagen', life: 3000 });
                },
            });
        },
    });
};

const submit = () => {
    form.transform(({ featuresText, ...data }) => ({
        ...data,
        features: featuresText
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean),
    }));

    form.post(route('admin.services.update', props.service.id), { forceFormData: true });
};
</script>

<template>
    <AppLayout title="Editar Servicio">
        <Toast position="top-right" />
        <ConfirmDialog />
        <Back :href="route('admin.services.index')" />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto">
                <Card class="dark:bg-gray-800 dark:text-gray-200">
                    <template #title>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Editar Servicio</h1>
                    </template>
                    <template #subtitle>
                        <p class="text-gray-600 dark:text-gray-400">
                            Actualiza precios, tiempos por categoría y puntos de fidelidad.
                        </p>
                    </template>
                    <template #content>
                        <form @submit.prevent="submit" class="mt-6">
                            <ServiceForm :form="form" :vehicle-categories="vehicleCategories" />

                            <!-- Imágenes actuales -->
                            <div v-if="currentMedia.length" class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Imágenes actuales</h3>
                                <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div
                                        v-for="mediaItem in currentMedia"
                                        :key="mediaItem.id"
                                        class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"
                                    >
                                        <Image :src="mediaItem.url" alt="Imagen del servicio" preview class="w-full h-32 object-cover" />
                                        <button
                                            type="button"
                                            class="absolute top-2 right-2 p-1.5 rounded-full bg-red-600 text-white opacity-0 group-hover:opacity-100 transition"
                                            @click="confirmDeleteMedia(mediaItem)"
                                            aria-label="Eliminar imagen"
                                        >
                                            <i class="pi pi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Nuevas imágenes -->
                            <div class="mt-8 flex flex-col gap-2">
                                <label class="font-semibold">Agregar imágenes</label>
                                <FileUpload
                                    name="new_images"
                                    @select="onFileSelect"
                                    :showUploadButton="false"
                                    :showCancelButton="false"
                                    accept="image/*"
                                    chooseLabel="Seleccionar imágenes"
                                    :multiple="true"
                                />
                            </div>

                            <div class="flex justify-end mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <Button type="submit" label="Guardar Cambios" icon="pi pi-check" :loading="form.processing" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
