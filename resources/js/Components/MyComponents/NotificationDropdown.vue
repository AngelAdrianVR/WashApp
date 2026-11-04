<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';

const page = usePage();

const notifications = computed(() => page.props.notifications ?? []);
const unreadCount = computed(() => page.props.unreadNotifications ?? 0);

const selected = ref([]);

const allSelected = computed(
    () => notifications.value.length > 0 && selected.value.length === notifications.value.length
);

watch(notifications, () => {
    selected.value = selected.value.filter((id) => notifications.value.some((item) => item.id === id));
});

const toneClass = (tone) => ({
    'bg-sage-100 text-sage-600 dark:bg-sage-500/15 dark:text-sage-300': tone === 'sage',
    'bg-sand-100 text-sand-500 dark:bg-amber-500/15 dark:text-amber-300': tone === 'sand',
    'bg-cloud-100 text-cloud-600 dark:bg-cloud-500/15 dark:text-cloud-300': tone === 'cloud',
    'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300': tone === 'rose',
});

const toggleSelect = (id) => {
    const index = selected.value.indexOf(id);
    if (index === -1) {
        selected.value.push(id);
    } else {
        selected.value.splice(index, 1);
    }
};

const toggleSelectAll = () => {
    selected.value = allSelected.value ? [] : notifications.value.map((item) => item.id);
};

const markSelected = () => {
    if (!selected.value.length) return;

    router.post(
        route('notifications.readMany'),
        { ids: [...selected.value] },
        { preserveScroll: true, onSuccess: () => (selected.value = []) }
    );
};

const deleteSelected = () => {
    if (!selected.value.length) return;

    router.post(
        route('notifications.destroyMany'),
        { ids: [...selected.value] },
        { preserveScroll: true, onSuccess: () => (selected.value = []) }
    );
};

const markAllAsRead = () => {
    router.post(route('notifications.markAll'), {}, { preserveScroll: true });
};

const openNotification = (item) => {
    if (!item.read) {
        router.patch(route('notifications.read', item.id), {}, { preserveScroll: true });
    }

    if (item.url) {
        router.visit(item.url);
    }
};

const removeNotification = (item) => {
    router.delete(route('notifications.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = selected.value.filter((id) => id !== item.id);
        },
    });
};
</script>

<template>
    <Dropdown
        align="right"
        width="80"
        :content-classes="[]"
        panel-classes=""
        wrapper-classes=""
    >
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
            <div class="w-[26rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-3xl border border-mist-200 bg-white shadow-lift dark:border-slate-700 dark:bg-gray-900">
                <!-- Encabezado -->
                <div class="flex items-center justify-between gap-2 border-b border-mist-200/70 px-4 py-3 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-mist-900 dark:text-white">Notificaciones</p>
                        <span
                            v-if="unreadCount"
                            class="rounded-full bg-cloud-100 px-2 py-0.5 text-[10px] font-semibold text-cloud-700 dark:bg-cloud-900/40 dark:text-cloud-300"
                        >
                            {{ unreadCount }} nueva{{ unreadCount === 1 ? '' : 's' }}
                        </span>
                    </div>
                    <button
                        v-if="unreadCount"
                        type="button"
                        class="text-xs font-semibold text-cloud-600 transition hover:text-cloud-700 dark:text-cloud-300"
                        @click="markAllAsRead"
                    >
                        Marcar todas
                    </button>
                </div>

                <!-- Barra de selección -->
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-mist-200/70 bg-mist-50/70 px-4 py-2 dark:border-slate-800 dark:bg-slate-800/50">
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-mist-500 dark:text-slate-400">
                        <input
                            type="checkbox"
                            class="h-3.5 w-3.5 rounded border-mist-300 text-cloud-600 focus:ring-cloud-400 dark:border-slate-600 dark:bg-slate-700"
                            :checked="allSelected"
                            :indeterminate.prop="selected.length > 0 && !allSelected"
                            @change="toggleSelectAll"
                        />
                        {{ selected.length ? `${selected.length} seleccionada${selected.length === 1 ? '' : 's'}` : 'Seleccionar todo' }}
                    </label>

                    <div v-if="selected.length" class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-[11px] font-semibold text-cloud-600 transition hover:bg-cloud-50 dark:text-cloud-300 dark:hover:bg-cloud-900/30"
                            @click="markSelected"
                        >
                            <i class="pi pi-check mr-1 text-[10px]"></i>Marcar leídas
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                            @click="deleteSelected"
                        >
                            <i class="pi pi-trash mr-1 text-[10px]"></i>Eliminar
                        </button>
                    </div>
                </div>

                <!-- Lista -->
                <ul v-if="notifications.length" class="max-h-[26rem] overflow-y-auto">
                    <li v-for="item in notifications" :key="item.id" class="border-b border-mist-100/70 last:border-0 dark:border-slate-800/70">
                        <div class="flex items-start gap-2 px-3 py-3 transition hover:bg-mist-50 dark:hover:bg-slate-800/70">
                            <input
                                type="checkbox"
                                class="mt-3 h-3.5 w-3.5 shrink-0 rounded border-mist-300 text-cloud-600 focus:ring-cloud-400 dark:border-slate-600 dark:bg-slate-700"
                                :checked="selected.includes(item.id)"
                                :aria-label="`Seleccionar ${item.title}`"
                                @change="toggleSelect(item.id)"
                            />

                            <button type="button" class="flex min-w-0 flex-1 items-start gap-3 text-left" @click="openNotification(item)">
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

                            <button
                                type="button"
                                class="mt-1 grid h-7 w-7 shrink-0 place-items-center rounded-lg text-mist-400 transition hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-900/20"
                                aria-label="Eliminar notificación"
                                @click.stop="removeNotification(item)"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </li>
                </ul>

                <div v-else class="px-6 py-10 text-center">
                    <i class="pi pi-bell mb-2 block text-2xl text-mist-300 dark:text-slate-600"></i>
                    <p class="text-sm font-medium text-mist-500 dark:text-slate-400">No tienes notificaciones</p>
                    <p class="mt-1 text-xs text-mist-400 dark:text-slate-500">Aquí verás las actualizaciones de tus servicios.</p>
                </div>
            </div>
        </template>
    </Dropdown>
</template>
