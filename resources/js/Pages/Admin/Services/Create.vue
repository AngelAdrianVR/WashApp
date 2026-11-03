<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import ServiceForm from './Partials/ServiceForm.vue';
import { useForm } from '@inertiajs/vue3';

import Card from 'primevue/card';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';

const props = defineProps({
    vehicleCategories: { type: Array, default: () => [] },
});

const emptyVariants = () =>
    Object.fromEntries(
        props.vehicleCategories.map((category) => [
            category.id,
            { price: null, duration_minutes: null },
        ])
    );

const form = useForm({
    name: '',
    description: '',
    featuresText: '',
    price: null,
    duration_minutes: null,
    type: 'Agua',
    is_addon: false,
    points: 0,
    sort_order: 0,
    is_active: true,
    variants: emptyVariants(),
    images: [],
});

const onFileSelect = (event) => {
    form.images = event.files;
};

const submit = () => {
    form.transform(({ featuresText, ...data }) => ({
        ...data,
        features: featuresText
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean),
    }));

    form.post(route('admin.services.store'), { forceFormData: true });
};
</script>

<template>
    <AppLayout title="Crear Servicio">
        <Back :href="route('admin.services.index')" />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto">
                <Card class="dark:bg-gray-800 dark:text-gray-200">
                    <template #title>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Crear Nuevo Servicio</h1>
                    </template>
                    <template #subtitle>
                        <p class="text-gray-600 dark:text-gray-400">
                            Registra un paquete de lavado o un servicio adicional. Los cambios se reflejan en la web.
                        </p>
                    </template>
                    <template #content>
                        <form @submit.prevent="submit" class="mt-6">
                            <ServiceForm :form="form" :vehicle-categories="vehicleCategories" />

                            <div class="mt-8 flex flex-col gap-2">
                                <label class="font-semibold">Imágenes del servicio (opcional)</label>
                                <FileUpload
                                    name="images"
                                    @select="onFileSelect"
                                    :showUploadButton="false"
                                    :showCancelButton="false"
                                    accept="image/*"
                                    chooseLabel="Seleccionar imágenes"
                                    :multiple="true"
                                >
                                    <template #empty>
                                        <div class="text-center p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                            <i class="pi pi-image text-3xl text-gray-400 dark:text-gray-500"></i>
                                            <p class="mt-2 text-gray-500 dark:text-gray-400">Arrastra y suelta imágenes aquí.</p>
                                        </div>
                                    </template>
                                </FileUpload>
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
