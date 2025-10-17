<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash.debounce';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import InputText from 'primevue/inputtext';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

const props = defineProps({
    services: Object,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();

const menu = ref();
const search = ref(props.filters.search);

// Menú de acciones dinámico
const activeServiceMenuItems = ref([]);

// Observador para el campo de búsqueda con debounce
watch(search, debounce((value) => {
    router.get(route('admin.services.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

// Formatea un número como moneda MXN
const formatCurrency = (value) => {
    if (typeof value !== 'number') {
        return value;
    }
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value);
};

// Abre y configura el menú para un servicio específico
const toggleMenu = (event, service) => {
    activeServiceMenuItems.value = [
        { label: 'Ver', icon: 'pi pi-fw pi-eye', command: () => router.get(route('admin.services.show', service.id)) },
        { label: 'Cambiar Estado', icon: 'pi pi-fw pi-sync', command: () => toggleIsActive(service) },
        { label: 'Editar', icon: 'pi pi-fw pi-pencil', command: () => router.get(route('admin.services.edit', service.id)) },
        { label: 'Eliminar', icon: 'pi pi-fw pi-trash', command: () => confirmDelete(service) }
    ];
    menu.value.toggle(event);
};

// Devuelve el color de la etiqueta según el estado (booleano)
const getStatusSeverity = (isActive) => {
    return isActive ? 'success' : 'danger';
};

// Devuelve el color de la etiqueta según el tipo de servicio
const getTypeSeverity = (type) => {
    switch (type) {
        case 'Agua': return 'info';
        case 'Seco': return 'warning';
        default: return 'secondary';
    }
};

// Llama a la ruta para cambiar el estado del servicio
const toggleIsActive = (service) => {
    router.patch(route('admin.services.toggleActive', service.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estado del servicio actualizado.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 3000 });
        }
    });
};

const onRowClick = (event) => {
    router.get(route('admin.services.show', event.data.id));
};

// Muestra un diálogo de confirmación antes de eliminar
const confirmDelete = (service) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar este servicio?',
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Eliminar',
        accept: () => {
            router.delete(route('admin.services.destroy', service.id), {
                preserveScroll: true,
                 onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Servicio eliminado.', life: 3000 });
                },
                onError: (page) => {
                    const error = page.props.errors.general || 'No se pudo eliminar el servicio.';
                    toast.add({ severity: 'error', summary: 'Error', detail: error, life: 3000 });
                }
            });
        }
    });
};

</script>

<template>
    <AppLayout title="Gestionar Servicios">
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <!-- Header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold">Gestión de Servicios</h1>
                </div>
                <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                     <span class="p-input-icon-left flex items-center space-x-2">
                        <i class="pi pi-search" />
                        <InputText v-model="search" placeholder="Buscar por nombre" class="w-full"/>
                    </span>
                     <Link :href="route('admin.services.create')">
                        <Button label="Crear Servicio" icon="pi pi-plus" severity="success" />
                    </Link>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 relative">
                 <!-- Vista de Tabla para pantallas grandes -->
                <div class="hidden md:block">
                    <DataTable :value="services.data" dataKey="id" @row-click="onRowClick" rowHover class="cursor-pointer" tableStyle="min-width: 50rem">
                         <Column field="id" header="ID" sortable></Column>
                         <Column field="name" header="Nombre" sortable>
                            <template #body="slotProps">
                                <div>
                                    <div class="font-medium text-slate-800 dark:text-slate-100">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-slate-500 dark:text-slate-400 truncate" style="max-width: 300px;">{{ slotProps.data.description }}</div>
                                </div>
                            </template>
                         </Column>
                         <Column field="price" header="Precio" sortable>
                             <template #body="slotProps">
                                 {{ formatCurrency(slotProps.data.price) }}
                             </template>
                         </Column>
                         <Column field="duration_minutes" header="Duración" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.duration_minutes }} min.
                            </template>
                         </Column>
                         <Column field="type" header="Tipo" sortable>
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.type" :severity="getTypeSeverity(slotProps.data.type)" />
                            </template>
                         </Column>
                         <Column field="is_active" header="Estado" sortable>
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'" :severity="getStatusSeverity(slotProps.data.is_active)" />
                            </template>
                         </Column>
                         <Column header="Acciones" style="width: 5rem; text-align: center;">
                            <template #body="slotProps">
                                <Button icon="pi pi-ellipsis-v" text rounded @click="toggleMenu($event, slotProps.data)" aria-haspopup="true" />
                                <Menu ref="menu" :model="activeServiceMenuItems" :popup="true" />
                            </template>
                         </Column>
                    </DataTable>
                </div>

                <!-- Vista de Tarjetas para pantallas pequeñas -->
                <div class="md:hidden p-4 grid gap-4 grid-cols-1">
                    <div v-if="services.data.length > 0" class="space-y-3">
                        <div v-for="service in services.data" :key="service.id" class="block bg-slate-50 dark:bg-slate-700/50 p-4 rounded-lg shadow border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-start">
                                 <div>
                                    <h3 class="font-bold text-lg text-slate-800 dark:text-slate-100">{{ service.name }}</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-300 font-semibold">{{ formatCurrency(service.price) }} - {{ service.duration_minutes }} min.</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ service.description }}</p>
                                </div>
                                <div>
                                    <Button icon="pi pi-ellipsis-v" text rounded @click.prevent="toggleMenu($event, service)" aria-haspopup="true" />
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <Tag :value="service.type" :severity="getTypeSeverity(service.type)" />
                                <Tag :value="service.is_active ? 'Activo' : 'Inactivo'" :severity="getStatusSeverity(service.is_active)" />
                            </div>
                        </div>
                    </div>
                     <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                        No se encontraron servicios.
                    </div>
                </div>

                <!-- Paginación para ambas vistas -->
                <div v-if="services.total > 0" class="p-4">
                     <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Mostrando de <span class="font-medium">{{ services.from }}</span> a <span class="font-medium">{{ services.to }}</span> de <span class="font-medium">{{ services.total }}</span> resultados
                        </p>

                        <div v-if="services.links.length > 3" class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in services.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-700 rounded-md" v-html="link.label" />
                                <Link
                                    v-else
                                    class="mr-1 mb-1 px-3 py-2 text-sm border rounded-md transition"
                                    :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700': !link.active }"
                                    :href="link.url"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
