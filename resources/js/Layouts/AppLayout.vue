<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

// --- Nuevas importaciones ---
import Sidebar from 'primevue/sidebar';
import Button from 'primevue/button';
import AppSidebar from '@/Components/MyComponents/AppSidebar.vue';
import InputSwitch from 'primevue/inputswitch';
import { usePrimeVue } from 'primevue/config';

defineProps({
    title: String,
});

// --- Lógica para Dark Mode (Corregida) ---
const darkMode = ref(false);

const toggleDarkMode = (isDark) => {
    // Con la configuración de Aura en app.js, solo necesitamos
    // agregar o quitar la clase 'dark' del elemento HTML.
    // PrimeVue detectará este cambio automáticamente.
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    
    // Guardar preferencia en localStorage
    localStorage.setItem('darkMode', isDark);
};

onMounted(() => {
    // Revisa si hay una preferencia guardada en localStorage
    const isDarkModeStored = localStorage.getItem('darkMode') === 'true';
    darkMode.value = isDarkModeStored;
    toggleDarkMode(isDarkModeStored);
});


// Estado para controlar la visibilidad del sidebar en móviles
const mobileSidebarVisible = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <!-- Sidebar de PrimeVue para la vista móvil -->
        <Sidebar v-model:visible="mobileSidebarVisible" header=" " class="w-full md:w-80">
            <!-- Reutilizamos el mismo componente de sidebar para mantener la consistencia -->
            <AppSidebar />
        </Sidebar>

        <div class="flex min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Barra lateral para pantallas grandes (lg y superiores) -->
            <aside class="hidden lg:block w-64">
                 <AppSidebar />
            </aside>

            <!-- Contenedor Principal (Barra superior + Contenido de la página) -->
            <div class="flex-1 flex flex-col w-full">

                <!-- Barra de Navegación Superior -->
                <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-end h-16">

                            <!-- Menú de usuario y switch de tema a la derecha -->
                            <div class="flex items-center ms-6">
                                <!-- Switch para Dark Mode -->
                                <div class="flex items-center mr-4">
                                    <i class="pi pi-sun text-yellow-500 mr-2" />
                                    <InputSwitch v-model="darkMode" @update:modelValue="toggleDarkMode" />
                                    <i class="pi pi-moon text-blue-300 ml-2" />
                                </div>

                                <!-- Menú de usuario -->
                                <div class="ms-3 relative hidden md:block">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                                <img class="size-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                            </button>
                                            <span v-else class="inline-flex rounded-md">
                                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                    {{ $page.props.auth.user.name }}
                                                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Administrar Cuenta
                                            </div>
                                            <DropdownLink :href="route('profile.show')">
                                                Perfil
                                            </DropdownLink>
                                            <div class="border-t border-gray-200 dark:border-gray-600" />
                                            <form @submit.prevent="logout">
                                                <DropdownLink as="button">
                                                    Cerrar Sesión
                                                </DropdownLink>
                                            </form>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <!-- Botón de hamburguesa en el extremo derecho para pantallas muy pequeñas -->
                            <div class="-me-2 flex items-center sm:hidden">
                                  <Button icon="pi pi-bars" @click="mobileSidebarVisible = true" text rounded aria-label="Toggle sidebar" class="text-gray-500" />
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Encabezado de la Página -->
                <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Contenido de la Página -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 h-[calc(100vh-20rem)] overflow-auto">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

