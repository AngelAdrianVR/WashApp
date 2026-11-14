<script setup>
import { reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import InlineMessage from 'primevue/inlinemessage';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const createForm = useForm({
    name: '',
    sort_order: 0,
    is_active: true,
});

const rows = reactive(
    Object.fromEntries(
        props.categories.map((category) => [
            category.id,
            { name: category.name, sort_order: category.sort_order, is_active: category.is_active },
        ])
    )
);

const create = () => {
    createForm.post(route('admin.vehicle-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name');
            toast.add({ severity: 'success', summary: 'Listo', detail: 'Categoría creada.', life: 3000 });
        },
    });
};

const save = (category) => {
    router.put(route('admin.vehicle-categories.update', category.id), rows[category.id], {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Listo', detail: 'Categoría actualizada.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'Revisa los datos de la categoría.', life: 3000 }),
    });
};

const destroy = (category) => {
    confirm.require({
        message: `¿Eliminar la categoría "${category.name}"?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('admin.vehicle-categories.destroy', category.id), {
                preserveScroll: true,
                onError: (page) => {
                    const error = page.props.errors?.category || 'No se pudo eliminar la categoría.';
                    toast.add({ severity: 'error', summary: 'Error', detail: error, life: 4000 });
                },
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Categorías de vehículo">
        <Toast position="top-right" />
        <ConfirmDialog />
        <Back :href="route('admin.services.index')" />

        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-5xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold">Categorías de vehículo</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Cada categoría define un precio y una duración distinta en los paquetes de lavado.
                </p>
            </div>

            <!-- Crear categoría -->
            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5 mb-6">
                <form class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end" @submit.prevent="create">
                    <div class="sm:col-span-5 flex flex-col gap-2">
                        <label class="font-semibold text-sm">Nombre</label>
                        <InputText v-model="createForm.name" placeholder="Ej. Coupé" :invalid="!!createForm.errors.name" />
                        <InlineMessage v-if="createForm.errors.name" severity="error">{{ createForm.errors.name }}</InlineMessage>
                    </div>
                    <div class="sm:col-span-3 flex flex-col gap-2">
                        <label class="font-semibold text-sm">Orden</label>
                        <InputNumber v-model="createForm.sort_order" :min="0" />
                    </div>
                    <div class="sm:col-span-2 flex flex-col gap-2">
                        <label class="font-semibold text-sm">Activa</label>
                        <div class="pt-1">
                            <InputSwitch v-model="createForm.is_active" />
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" label="Agregar" icon="pi pi-plus" class="w-full" :loading="createForm.processing" />
                    </div>
                </form>
            </div>

            <!-- Listado -->
            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 relative">
                <DataTable :value="categories" dataKey="id" tableStyle="min-width: 48rem">
                    <Column field="name" header="Categoría" style="width: 18rem">
                        <template #body="slotProps">
                            <InputText v-model="rows[slotProps.data.id].name" class="w-full" />
                        </template>
                    </Column>
                    <Column field="slug" header="Slug">
                        <template #body="slotProps">
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ slotProps.data.slug }}</span>
                        </template>
                    </Column>
                    <Column header="Servicios" style="width: 7rem; text-align: center">
                        <template #body="slotProps">
                            <Tag :value="`${slotProps.data.services_count}`" severity="secondary" />
                        </template>
                    </Column>
                    <Column header="Orden" style="width: 8rem">
                        <template #body="slotProps">
                            <InputNumber v-model="rows[slotProps.data.id].sort_order" :min="0" :showButtons="false" inputClass="w-20" />
                        </template>
                    </Column>
                    <Column header="Activa" style="width: 6rem; text-align: center">
                        <template #body="slotProps">
                            <InputSwitch v-model="rows[slotProps.data.id].is_active" />
                        </template>
                    </Column>
                    <Column header="Acciones" style="width: 10rem; text-align: right">
                        <template #body="slotProps">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-check" text rounded severity="success" aria-label="Guardar" @click="save(slotProps.data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Eliminar" @click="destroy(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                Las categorías con servicios asociados no se pueden eliminar; desactívalas para ocultarlas de la web.
            </p>
        </div>
    </AppLayout>
</template>
