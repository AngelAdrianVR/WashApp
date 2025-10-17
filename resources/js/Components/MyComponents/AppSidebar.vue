<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Button from 'primevue/button';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Función para verificar si el usuario tiene un rol específico.
// Asegúrate que los roles en tu base de datos coincidan con 'Cliente', 'Empleado', 'Admin'.
// Si en tu base de datos están en minúsculas ('cliente'), deberías compararlos así:
// return user.value?.role.toLowerCase() === role.toLowerCase();
const hasRole = (role) => {
    return user.value?.role === role;
};

// Definición de los enlaces de navegación basados en roles con las rutas correctas.
const menuItems = computed(() => [
    // --- Menú Común para todos ---
    {
        label: 'Dashboard',
        icon: 'pi pi-th-large',
        route: 'dashboard',
        visible: true,
    },
    // --- Menú exclusivo para Clientes ---
    {
        label: 'Agendar Cita',
        icon: 'pi pi-calendar-plus',
        // Esta es una ruta pública, ideal para que puedan agendar fácilmente.
        route: 'client.bookings.create',
        visible: hasRole('Cliente'),
    },
    {
        label: 'Mis Reservas',
        icon: 'pi pi-book',
        // Ruta específica para que el cliente vea sus reservas.
        route: 'client.bookings.index',
        visible: hasRole('Cliente'),
    },
    // --- Menú para Empleados y Administradores ---
    {
        label: 'Agenda del Día',
        icon: 'pi pi-calendar',
        // Ruta para que el empleado vea las reservas que tiene asignadas.
        route: 'employee.bookings.index',
        visible: hasRole('Empleado') || hasRole('Admin'),
    },
    // --- Menú exclusivo para Administradores ---
    {
        label: 'Administración',
        isTitle: true,
        visible: hasRole('Admin'),
    },
    {
        label: 'Gestionar Reservas',
        icon: 'pi pi-bookmark-fill',
        // Ruta para que el admin gestione TODAS las reservas.
        route: 'admin.bookings.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Gestionar Servicios',
        icon: 'pi pi-car',
        route: 'admin.services.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Gestionar Promociones',
        icon: 'pi pi-tags',
        route: 'admin.promotions.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Gestionar Usuarios',
        icon: 'pi pi-users',
        route: 'admin.users.index',
        visible: hasRole('Admin'),
    },
]);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex h-full flex-col bg-white dark:bg-gray-800 shadow-lg">
        <!-- Logo en la parte superior -->
        <div class="border-b border-gray-200 dark:border-gray-700 px-5 py-5">
            <Link :href="route('dashboard')">
                <ApplicationMark class="block h-32 w-auto" />
            </Link>
        </div>

        <!-- Contenedor principal con scroll para la navegación -->
        <div class="flex flex-1 flex-col overflow-y-auto pt-4">
            <nav class="flex-1 space-y-1 px-3">
                <template v-for="(item, index) in menuItems" :key="index">
                    <!-- Renderiza el item si es visible -->
                    <template v-if="item.visible">
                        <!-- Si es un título -->
                        <h3 v-if="item.isTitle" class="px-3 pt-4 pb-2 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">
                            {{ item.label }}
                        </h3>
                        <!-- Si es un enlace -->
                        <Link v-else
                            :href="route(item.route)"
                            class="flex transform items-center rounded-lg px-3 py-3 text-gray-600 dark:text-gray-300 transition-colors duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200"
                            :class="{ 'bg-blue-100 text-blue-700 font-semibold dark:bg-blue-900/50 dark:text-blue-300': route().current(item.route) }">
                            <i :class="[item.icon, 'text-lg']" />
                            <span class="mx-3 text-sm font-medium">{{ item.label }}</span>
                        </Link>
                    </template>
                </template>
            </nav>
        </div>

        <!-- Sección del Perfil de Usuario en la parte inferior -->
        <div class="border-t border-gray-200 dark:border-gray-700 p-3">
            <div class="flex items-center gap-3 rounded-lg p-2 transition-colors">
                <img v-if="$page.props.jetstream.managesProfilePhotos" class="size-10 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                <div class="flex flex-col">
                     <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ user.name }}</span>
                     <Link :href="route('profile.show')" class="text-xs text-gray-500 dark:text-gray-400 hover:underline">
                        Ver Perfil
                     </Link>
                </div>
                 <Button @click="logout" icon="pi pi-sign-out" text rounded severity="danger" aria-label="Cerrar sesión" class="ml-auto" />
            </div>
        </div>
    </div>
</template>
