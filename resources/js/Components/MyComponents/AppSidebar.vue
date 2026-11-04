<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    collapsed: { type: Boolean, default: false },
    mobile: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-collapse', 'close']);

const page = usePage();
const user = computed(() => page.props.auth.user);

const hasRole = (role) => user.value?.role === role;

const isClient = computed(() => hasRole('Cliente'));

const profileRoute = computed(() => (isClient.value ? 'client.profile.show' : 'profile.show'));

const menuItems = computed(() => [
    {
        label: 'General',
        isTitle: true,
        visible: true,
    },
    {
        label: 'Dashboard',
        icon: 'pi pi-th-large',
        route: 'dashboard',
        visible: true,
    },
    {
        label: 'Mi perfil',
        icon: 'pi pi-user',
        route: profileRoute.value,
        visible: isClient.value,
    },
    {
        label: 'Agendar cita',
        icon: 'pi pi-calendar-plus',
        route: 'client.bookings.create',
        visible: isClient.value,
    },
    {
        label: 'Mis reservas',
        icon: 'pi pi-book',
        route: 'client.bookings.index',
        visible: isClient.value,
    },
    {
        label: 'Mis premios',
        icon: 'pi pi-gift',
        route: 'client.rewards.index',
        visible: isClient.value,
    },
    {
        label: 'Operación',
        isTitle: true,
        visible: hasRole('Empleado') || hasRole('Admin'),
    },
    {
        label: 'Servicios agendados',
        icon: 'pi pi-bookmark',
        route: 'admin.bookings.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Agendar cita',
        icon: 'pi pi-calendar-plus',
        route: 'admin.bookings.create',
        visible: hasRole('Admin'),
    },
    {
        label: 'Mis servicios',
        icon: 'pi pi-briefcase',
        route: 'employee.bookings.index',
        visible: hasRole('Empleado'),
    },
    {
        label: 'Administración',
        isTitle: true,
        visible: hasRole('Admin'),
    },
    {
        label: 'Analítica de vistas',
        icon: 'pi pi-chart-line',
        route: 'admin.analytics.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Análisis de ventas',
        icon: 'pi pi-chart-bar',
        route: 'admin.sales.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Servicios',
        icon: 'pi pi-car',
        route: 'admin.services.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Promociones',
        icon: 'pi pi-tags',
        route: 'admin.promotions.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Gastos',
        icon: 'pi pi-wallet',
        route: 'admin.expenses.index',
        visible: hasRole('Admin'),
    },
    {
        label: 'Usuarios',
        icon: 'pi pi-users',
        route: 'admin.users.index',
        visible: hasRole('Admin'),
    },
]);

const isActive = (item) => item.route && route().current(item.route);

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Marca -->
        <div
            class="relative flex items-center justify-center pb-3 pt-6 transition-all duration-300"
            :class="collapsed && !mobile ? 'px-2' : 'px-4'"
        >
            <Link :href="route('dashboard')" class="flex w-full items-center justify-center" aria-label="WashApp · Panel">
                <img
                    src="/images/logo-washapp.png"
                    alt="WashApp"
                    class="w-auto object-contain transition-all duration-300 dark:brightness-0 dark:invert"
                    :class="collapsed && !mobile ? 'h-8 max-w-[4rem]' : 'h-14 max-w-[11rem]'"
                />
            </Link>

            <!-- Cerrar (móvil) -->
            <button
                v-if="mobile"
                type="button"
                class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-xl text-mist-400 transition hover:bg-mist-100 hover:text-mist-700"
                aria-label="Cerrar menú"
                @click="emit('close')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
            <template v-for="(item, index) in menuItems" :key="index">
                <template v-if="item.visible">
                    <p
                        v-if="item.isTitle"
                        class="px-3 pb-1 pt-5 text-[10px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500"
                        :class="{ 'sr-only': collapsed && !mobile }"
                    >
                        {{ item.label }}
                    </p>

                    <Link
                        v-else
                        :href="route(item.route)"
                        :title="collapsed && !mobile ? item.label : undefined"
                        class="group relative flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                        :class="[
                            isActive(item)
                                ? 'bg-cloud-600 text-white shadow-[0_10px_24px_-12px_rgba(11,107,155,0.9)]'
                                : 'text-mist-600 hover:bg-mist-100 hover:text-mist-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100',
                            collapsed && !mobile ? 'justify-center px-0' : '',
                        ]"
                        @click="mobile ? emit('close') : null"
                    >
                        <i :class="[item.icon, 'text-base']"></i>
                        <span v-if="!collapsed || mobile" class="truncate">{{ item.label }}</span>
                    </Link>
                </template>
            </template>
        </nav>

        <!-- Puntos del cliente -->
        <div v-if="isClient && (!collapsed || mobile)" class="mx-3 mb-3 rounded-2xl border border-sage-200/70 bg-gradient-to-br from-sage-50 to-cloud-50 p-3 dark:border-slate-700 dark:from-slate-800 dark:to-slate-800">
            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-sage-700 dark:text-sage-300">
                Puntos disponibles
            </p>
            <p class="mt-1 text-2xl font-semibold text-mist-900 dark:text-white">
                {{ user?.points ?? 0 }}
                <span class="text-xs font-medium text-mist-400">pts</span>
            </p>
        </div>

        <!-- Plegar / desplegar -->
        <div v-if="!mobile" class="border-t border-mist-200/70 px-3 py-2 dark:border-slate-800">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-2xl px-3 py-2 text-xs font-semibold text-mist-400 transition hover:bg-mist-100 hover:text-mist-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                :class="collapsed ? 'justify-center px-0' : ''"
                :aria-label="collapsed ? 'Expandir menú' : 'Contraer menú'"
                :title="collapsed ? 'Expandir menú' : 'Contraer menú'"
                @click="emit('toggle-collapse')"
            >
                <svg
                    class="h-4 w-4 shrink-0 transition-transform duration-300"
                    :class="{ 'rotate-180': collapsed }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
                </svg>
                <span v-if="!collapsed">Contraer menú</span>
            </button>
        </div>

        <!-- Usuario -->
        <div class="border-t border-mist-200/70 px-3 py-3 dark:border-slate-800">
            <div class="flex items-center gap-3" :class="collapsed && !mobile ? 'justify-center' : ''">
                <img
                    :src="user?.avatar_url || user?.profile_photo_url"
                    :alt="user?.name"
                    class="h-9 w-9 flex-shrink-0 rounded-full object-cover ring-2 ring-white dark:ring-slate-700"
                />
                <div v-if="!collapsed || mobile" class="min-w-0">
                    <p class="truncate text-sm font-semibold text-mist-800 dark:text-slate-100">{{ user?.name }}</p>
                    <p class="truncate text-[11px] uppercase tracking-wider text-mist-400">{{ user?.role }}</p>
                </div>
                <button
                    v-if="!collapsed || mobile"
                    type="button"
                    class="ml-auto grid h-8 w-8 place-items-center rounded-xl text-mist-400 transition hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-500/10"
                    aria-label="Cerrar sesión"
                    @click="logout"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m0 0l3.5-3.5M4 12l3.5 3.5M9.5 4.5h8A2.5 2.5 0 0120 7v10a2.5 2.5 0 01-2.5 2.5h-8" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
