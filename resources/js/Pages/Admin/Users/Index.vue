<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; // Asegúrate de que este layout exista
import debounce from 'lodash.debounce';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import InputText from 'primevue/inputtext';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

const props = defineProps({
    users: Object,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();

const menu = ref();
const search = ref(props.filters.search);

// Menú de acciones dinámico
const activeUserMenuItems = ref([]);

// Observador para el campo de búsqueda con debounce
watch(search, debounce((value) => {
    router.get(route('admin.users.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));


// Función para abrir y configurar el menú para un usuario específico
const toggleMenu = (event, user) => {
    const baseItems = [
        { label: 'Ver', icon: 'pi pi-fw pi-eye', command: () => router.get(route('admin.users.show', user.id)) },
        { label: 'Editar', icon: 'pi pi-fw pi-pencil', command: () => router.get(route('admin.users.edit', user.id)) },
        { label: 'Eliminar', icon: 'pi pi-fw pi-trash', command: () => confirmDelete(user) }
    ];

    // Si el rol NO es 'Cliente', inserta la opción de cambiar estado
    if (user.role !== 'Cliente') {
        baseItems.splice(1, 0, {
            label: 'Cambiar Estado',
            icon: 'pi pi-fw pi-sync',
            command: () => toggleIsActive(user)
        });
    }

    activeUserMenuItems.value = baseItems;
    menu.value.toggle(event);
};

// Navega a la página de detalles del usuario al hacer clic en una fila
const onRowClick = (event) => {
    // Asegura que el clic no fue en un botón dentro de la fila
    if (event.originalEvent.target.closest('button')) return;
    router.get(route('admin.users.show', event.data.id));
};

// Devuelve el color de la etiqueta según el estado (booleano)
const getStatusSeverity = (isActive) => {
    return isActive ? 'success' : 'danger';
};

// Devuelve el color de la etiqueta según el rol
const getRoleSeverity = (role) => {
    switch (role) {
        case 'Admin': return 'info';
        case 'Empleado': return 'warning';
        case 'Cliente': return 'success';
        default: return 'secondary';
    }
};

// Llama a la ruta para cambiar el estado del usuario
const toggleIsActive = (user) => {
    router.patch(route('admin.users.toggleActive', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estado del usuario actualizado.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 3000 });
        }
    });
};

// Muestra un diálogo de confirmación antes de eliminar
const confirmDelete = (user) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar a este usuario?',
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Eliminar',
        accept: () => {
            router.delete(route('admin.users.destroy', user.id), {
                preserveScroll: true,
                 onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario eliminado.', life: 3000 });
                },
                onError: (page) => {
                    const error = page.props.errors.general || 'No se pudo eliminar el usuario.';
                    toast.add({ severity: 'error', summary: 'Error', detail: error, life: 3000 });
                }
            });
        }
    });
};

</script>

<template>
    <AppLayout title="Gestionar Usuarios">
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <!-- Header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold">Gestión de Usuarios</h1>
                </div>
                <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                     <span class="p-input-icon-left flex items-center space-x-2">
                        <i class="pi pi-search" />
                        <InputText v-model="search" placeholder="Buscar por nombre" class="w-full"/>
                    </span>
                     <Link :href="route('admin.users.create')">
                        <Button label="Crear Usuario" icon="pi pi-plus" severity="success" />
                    </Link>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 relative">
                 <!-- Vista de Tabla para pantallas grandes -->
                <div class="hidden md:block">
                    <DataTable :value="users.data" @row-click="onRowClick" rowHover class="cursor-pointer" tableStyle="min-width: 50rem">
                         <Column field="name" header="Nombre" sortable>
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <Avatar :image="slotProps.data.profile_photo_url" size="large" shape="circle" />
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-slate-100">{{ slotProps.data.name }}</div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ slotProps.data.email }}</div>
                                    </div>
                                </div>
                            </template>
                         </Column>
                         <Column field="phone_number" header="Teléfono" sortable></Column>
                         <Column field="role" header="Rol" sortable>
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.role" :severity="getRoleSeverity(slotProps.data.role)" />
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
                                <Menu ref="menu" :model="activeUserMenuItems" :popup="true" />
                            </template>
                         </Column>
                    </DataTable>
                </div>

                <!-- Vista de Tarjetas para pantallas pequeñas -->
                <div class="md:hidden p-4 grid gap-4 grid-cols-1">
                    <div v-if="users.data.length > 0" class="space-y-3">
                        <Link v-for="user in users.data" :key="user.id" :href="route('admin.users.show', user.id)" class="block bg-slate-50 dark:bg-slate-700/50 p-4 rounded-lg shadow border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow duration-200">
                            <div class="flex justify-between items-start">
                                 <div class="flex items-center gap-4">
                                    <Avatar :image="user.profile_photo_url" size="xlarge" shape="circle" />
                                    <div>
                                        <h3 class="font-bold text-lg text-slate-800 dark:text-slate-100">{{ user.name }}</h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ user.email }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ user.phone_number }}</p>
                                    </div>
                                </div>
                                <div>
                                    <!-- Añadimos .prevent para evitar que el Link se active al abrir el menú -->
                                    <Button icon="pi pi-ellipsis-v" text rounded @click.prevent="toggleMenu($event, user)" aria-haspopup="true" />
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <Tag :value="user.role" :severity="getRoleSeverity(user.role)" />
                                <Tag :value="user.is_active ? 'Activo' : 'Inactivo'" :severity="getStatusSeverity(user.is_active)" />
                            </div>
                        </Link>
                    </div>
                     <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                        No se encontraron usuarios.
                    </div>
                </div>

                <!-- Paginación para ambas vistas -->
                <div v-if="users.total > 0" class="p-4">
                     <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Mostrando de <span class="font-medium">{{ users.from }}</span> a <span class="font-medium">{{ users.to }}</span> de <span class="font-medium">{{ users.total }}</span> resultados
                        </p>

                        <div v-if="users.links.length > 3" class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in users.links" :key="key">
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
