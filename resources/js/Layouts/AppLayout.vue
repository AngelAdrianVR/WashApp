<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AppSidebar from '@/Components/MyComponents/AppSidebar.vue';
import NotificationDropdown from '@/Components/MyComponents/NotificationDropdown.vue';

const props = defineProps({
    title: String,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isClient = computed(() => user.value?.role === 'Cliente');

// expanded | collapsed | hidden
const sidebar = ref('expanded');
const mobileOpen = ref(false);
const dark = ref(false);

const applyDark = (value) => {
    document.documentElement.classList.toggle('dark', value);
    localStorage.setItem('darkMode', value);
};

const toggleTheme = () => {
    dark.value = !dark.value;
    applyDark(dark.value);
};

const toggleCollapse = () => {
    sidebar.value = sidebar.value === 'expanded' ? 'collapsed' : 'expanded';
};

const toggleSidebar = () => {
    sidebar.value = sidebar.value === 'hidden' ? 'expanded' : 'hidden';
};

watch(sidebar, (value) => localStorage.setItem('sidebarMode', value));

onMounted(() => {
    const storedSidebar = localStorage.getItem('sidebarMode');
    if (['expanded', 'collapsed', 'hidden'].includes(storedSidebar)) {
        sidebar.value = storedSidebar;
    }

    dark.value = localStorage.getItem('darkMode') === 'true';
    applyDark(dark.value);
});

const contentPadding = computed(() => {
    if (sidebar.value === 'expanded') return 'lg:pl-[17.5rem]';
    if (sidebar.value === 'collapsed') return 'lg:pl-[6.5rem]';
    return 'lg:pl-0';
});

const sidebarWidth = computed(() => (sidebar.value === 'collapsed' ? 'w-20' : 'w-64'));

const profileRoute = computed(() => (isClient.value ? 'client.profile.show' : 'profile.show'));

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-mist-50 text-mist-800 dark:bg-gray-900 dark:text-slate-200">
        <Head :title="title" />
        <Banner />

        <!-- ============ Sidebar escritorio ============ -->
        <aside
            class="fixed inset-y-3 left-3 z-40 hidden flex-col overflow-hidden rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur-xl transition-all duration-300 dark:border-slate-800 dark:bg-gray-800/90 lg:flex"
            :class="[
                sidebarWidth,
                sidebar === 'hidden' ? '-translate-x-full pointer-events-none opacity-0' : 'translate-x-0 opacity-100',
            ]"
        >
            <AppSidebar :collapsed="sidebar === 'collapsed'" @toggle-collapse="toggleCollapse" />
        </aside>

        <!-- ============ Sidebar móvil ============ -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="mobileOpen = false"></div>
                <aside class="absolute inset-y-0 left-0 w-72 overflow-hidden rounded-r-3xl bg-white shadow-lift dark:bg-gray-800">
                    <AppSidebar mobile @close="mobileOpen = false" />
                </aside>
            </div>
        </Transition>

        <!-- ============ Contenido ============ -->
        <div class="min-h-screen transition-[padding] duration-300" :class="contentPadding">
            <!-- Barra superior -->
            <header class="sticky top-0 z-30 border-b border-white/60 bg-white/70 backdrop-blur-xl dark:border-slate-800 dark:bg-gray-900/80">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <!-- Mostrar / ocultar menú -->
                    <button
                        type="button"
                        class="grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-200 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-white lg:hidden"
                        aria-label="Abrir menú"
                        @click="mobileOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="hidden h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-200 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-white lg:grid"
                        :aria-label="sidebar === 'hidden' ? 'Mostrar menú' : 'Ocultar menú'"
                        @click="toggleSidebar"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path v-if="sidebar === 'hidden'" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16" />
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                            WashApp · Panel
                        </p>
                        <h1 class="truncate text-sm font-semibold text-mist-900 dark:text-white">
                            {{ title || 'Dashboard' }}
                        </h1>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <!-- Puntos -->
                        <span
                            v-if="isClient"
                            class="hidden items-center gap-1.5 rounded-2xl border border-sage-200 bg-sage-50 px-3 py-2 text-xs font-semibold text-sage-700 sm:inline-flex dark:border-sage-500/30 dark:bg-sage-500/10 dark:text-sage-300"
                        >
                            <i class="pi pi-star-fill text-[11px]"></i>
                            {{ user?.points ?? 0 }} pts
                        </span>

                        <!-- Tema -->
                        <button
                            type="button"
                            class="grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-200 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-white"
                            :aria-label="dark ? 'Activar tema claro' : 'Activar tema oscuro'"
                            @click="toggleTheme"
                        >
                            <svg v-if="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <circle cx="12" cy="12" r="4" />
                                <path stroke-linecap="round" d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4" />
                            </svg>
                        </button>

                        <!-- Notificaciones -->
                        <NotificationDropdown />

                        <!-- Usuario -->
                        <Dropdown align="right" width="56">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-2xl border border-mist-200 bg-white p-1 pr-2 transition hover:border-cloud-200 dark:border-slate-700 dark:bg-slate-800"
                                >
                                    <img
                                        :src="user?.avatar_url || user?.profile_photo_url"
                                        :alt="user?.name"
                                        class="h-8 w-8 rounded-xl object-cover"
                                    />
                                    <span class="hidden max-w-[9rem] truncate text-sm font-semibold text-mist-800 sm:block dark:text-slate-100">
                                        {{ user?.name }}
                                    </span>
                                    <svg class="hidden h-3.5 w-3.5 text-mist-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-3">
                                    <p class="text-sm font-semibold text-mist-900 dark:text-white">{{ user?.name }}</p>
                                    <p class="text-xs text-mist-400">{{ user?.email }}</p>
                                </div>
                                <div class="border-t border-mist-200/70 dark:border-slate-700"></div>
                                <DropdownLink :href="route(profileRoute)">Mi perfil</DropdownLink>
                                <DropdownLink :href="route('dashboard')">Dashboard</DropdownLink>
                                <div class="border-t border-mist-200/70 dark:border-slate-700"></div>
                                <form @submit.prevent="logout">
                                    <DropdownLink as="button">Cerrar sesión</DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Contenido de la página -->
            <main class="p-4 sm:p-6 lg:p-8">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
