<script setup>
import { computed, ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';

// Notificaciones de demostración (se conectarán al backend más adelante)
const notifications = ref([
    {
        id: 1,
        icon: 'pi pi-check-circle',
        tone: 'sage',
        title: 'Servicio completado',
        text: 'Tu lavado de hoy terminó hace 20 min.',
        time: 'Hace 20 min',
        read: false,
    },
    {
        id: 2,
        icon: 'pi pi-star',
        tone: 'sand',
        title: '+60 puntos acreditados',
        text: 'Sumaste puntos por tu último servicio.',
        time: 'Hace 1 h',
        read: false,
    },
    {
        id: 3,
        icon: 'pi pi-calendar',
        tone: 'cloud',
        title: 'Reserva confirmada',
        text: 'Mañana · bloque 10:00 – 12:00',
        time: 'Ayer',
        read: true,
    },
]);

const unreadCount = computed(() => notifications.value.filter((item) => !item.read).length);

const toneClass = (tone) => ({
    'bg-sage-100 text-sage-600 dark:bg-sage-500/15 dark:text-sage-300': tone === 'sage',
    'bg-sand-100 text-sand-500 dark:bg-amber-500/15 dark:text-amber-300': tone === 'sand',
    'bg-cloud-100 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300': tone === 'cloud',
});

const markAllAsRead = () => {
    notifications.value = notifications.value.map((item) => ({ ...item, read: true }));
};
</script>

<template>
    <Dropdown align="right" width="80">
        <template #trigger>
            <button
                type="button"
                class="relative grid h-10 w-10 place-items-center rounded-2xl border border-mist-200 bg-white text-mist-500 transition hover:border-cloud-200 hover:text-cloud-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-white"
                aria-label="Notificaciones"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" />
                </svg>
                <span
                    v-if="unreadCount"
                    class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white dark:ring-slate-900"
                >
                    {{ unreadCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="w-80 overflow-hidden rounded-3xl border border-mist-200 bg-white shadow-lift dark:border-slate-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-mist-200/70 px-4 py-3 dark:border-slate-800">
                    <p class="text-sm font-semibold text-mist-900 dark:text-white">Notificaciones</p>
                    <button
                        v-if="unreadCount"
                        type="button"
                        class="text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300"
                        @click="markAllAsRead"
                    >
                        Marcar leídas
                    </button>
                </div>

                <ul class="max-h-80 overflow-y-auto">
                    <li v-for="item in notifications" :key="item.id">
                        <button
                            type="button"
                            class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-mist-50 dark:hover:bg-slate-800/70"
                        >
                            <span class="mt-0.5 grid h-9 w-9 flex-shrink-0 place-items-center rounded-2xl" :class="toneClass(item.tone)">
                                <i :class="[item.icon, 'text-sm']"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-mist-800 dark:text-slate-100">{{ item.title }}</span>
                                    <span v-if="!item.read" class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-cloud-500"></span>
                                </span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-mist-500 dark:text-slate-400">{{ item.text }}</span>
                                <span class="mt-1 block text-[10px] uppercase tracking-wider text-mist-400">{{ item.time }}</span>
                            </span>
                        </button>
                    </li>
                </ul>

                <div class="border-t border-mist-200/70 px-4 py-2.5 text-center dark:border-slate-800">
                    <span class="text-xs font-medium text-mist-400">Vista de ejemplo</span>
                </div>
            </div>
        </template>
    </Dropdown>
</template>
