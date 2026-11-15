<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';

const props = defineProps({
    visible: { type: Boolean, default: false },
    bookingId: { type: [Number, String], required: true },
    indexRoute: { type: String, required: true },
    storeRoute: { type: String, required: true },
    peer: { type: Object, default: () => ({}) },
    maxImages: { type: Number, default: 3 },
});

const emit = defineEmits(['update:visible']);

const messages = ref([]);
const body = ref('');
const files = ref([]);
const previews = ref([]);
const loading = ref(false);
const sending = ref(false);
const error = ref(null);
const scroller = ref(null);
const fileInput = ref(null);
const previewUrl = ref(null);
const previewVisible = ref(false);

let pollTimer = null;

const isOpen = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const canSend = computed(
    () => (body.value.trim().length > 0 || files.value.length > 0) && !sending.value
);

const imagesLimit = computed(() => props.maxImages ?? 3);

const scrollToEnd = async () => {
    await nextTick();
    if (scroller.value) {
        scroller.value.scrollTop = scroller.value.scrollHeight;
    }
};

const clearPreviews = () => {
    previews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    previews.value = [];
};

const applyPayload = (payload) => {
    const previousCount = messages.value.length;
    messages.value = payload?.messages ?? [];

    if (messages.value.length !== previousCount) {
        scrollToEnd();
    }
};

const load = async (silent = true) => {
    if (!silent) loading.value = true;

    try {
        const { data } = await axios.get(route(props.indexRoute, props.bookingId));
        applyPayload(data);
        error.value = null;
    } catch (e) {
        error.value = 'No pudimos cargar la conversación.';
    } finally {
        loading.value = false;
    }
};

const startPolling = () => {
    stopPolling();
    pollTimer = setInterval(() => load(true), 7000);
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const pickFiles = () => fileInput.value?.click();

const onFilesSelected = (event) => {
    const selected = Array.from(event.target.files ?? []);

    for (const file of selected) {
        if (files.value.length >= imagesLimit.value) {
            error.value = `Puedes adjuntar hasta ${imagesLimit.value} imágenes por mensaje.`;
            break;
        }

        if (file.size > 5 * 1024 * 1024) {
            error.value = 'Cada imagen debe pesar menos de 5 MB.';
            continue;
        }

        files.value.push(file);
        previews.value.push({ name: file.name, url: URL.createObjectURL(file) });
    }

    event.target.value = '';
};

const removeFile = (index) => {
    URL.revokeObjectURL(previews.value[index].url);
    previews.value.splice(index, 1);
    files.value.splice(index, 1);
};

const send = async () => {
    if (!canSend.value) return;

    sending.value = true;
    error.value = null;

    const form = new FormData();
    if (body.value.trim()) form.append('body', body.value.trim());
    files.value.forEach((file) => form.append('images[]', file));

    try {
        const { data } = await axios.post(route(props.storeRoute, props.bookingId), form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        applyPayload(data);
        body.value = '';
        files.value = [];
        clearPreviews();
        scrollToEnd();
    } catch (e) {
        error.value = e?.response?.data?.errors
            ? Object.values(e.response.data.errors)[0]?.[0] ?? 'No se pudo enviar el mensaje.'
            : 'No se pudo enviar el mensaje.';
    } finally {
        sending.value = false;
    }
};

const openImage = (url) => {
    previewUrl.value = url;
    previewVisible.value = true;
};

watch(
    () => props.visible,
    (open) => {
        if (open) {
            load(false);
            startPolling();
            scrollToEnd();
        } else {
            stopPolling();
        }
    }
);

onBeforeUnmount(() => {
    stopPolling();
    clearPreviews();
});
</script>

<template>
    <Dialog
        v-model:visible="isOpen"
        modal
        :header="`Chat con ${peer?.name || 'el colaborador'}`"
        :style="{ width: '92vw', maxWidth: '520px' }"
        :breakpoints="{ '640px': '96vw' }"
    >
        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3 rounded-2xl bg-mist-50 px-3 py-2.5 dark:bg-slate-700/40">
                <img
                    v-if="peer?.avatar"
                    :src="peer.avatar"
                    :alt="peer.name"
                    class="h-9 w-9 rounded-full object-cover"
                />
                <span
                    v-else
                    class="grid h-9 w-9 place-items-center rounded-full bg-cloud-100 text-xs font-semibold text-cloud-700 dark:bg-cloud-500/15 dark:text-cloud-300"
                >
                    {{ (peer?.name || 'WA').charAt(0).toUpperCase() }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">{{ peer?.name || 'WashApp' }}</p>
                    <p class="text-[11px] text-mist-500 dark:text-slate-400">
                        {{ peer?.role || (peer?.name ? 'En línea durante el servicio' : 'Soporte WashApp') }}
                    </p>
                </div>
                <span class="ml-auto inline-flex items-center gap-1.5 rounded-full bg-sage-50 px-2.5 py-1 text-[10px] font-semibold text-sage-700 dark:bg-sage-500/15 dark:text-sage-300">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-sage-500"></span>
                    Activo
                </span>
            </div>

            <div
                ref="scroller"
                class="max-h-[52vh] min-h-[14rem] space-y-3 overflow-y-auto rounded-2xl bg-mist-50/70 p-3 dark:bg-slate-800/60"
            >
                <p v-if="loading" class="py-10 text-center text-xs text-mist-400">
                    <i class="pi pi-spin pi-spinner mr-1"></i> Cargando conversación…
                </p>

                <template v-else-if="messages.length">
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex gap-2"
                        :class="message.mine ? 'flex-row-reverse' : 'flex-row'"
                    >
                        <img
                            v-if="message.sender_avatar"
                            :src="message.sender_avatar"
                            :alt="message.sender_name"
                            class="mt-1 h-7 w-7 shrink-0 rounded-full object-cover"
                        />
                        <span
                            v-else
                            class="mt-1 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-mist-200 text-[10px] font-semibold text-mist-600 dark:bg-slate-600 dark:text-slate-200"
                        >
                            {{ (message.sender_name || 'U').charAt(0).toUpperCase() }}
                        </span>

                        <div class="min-w-0 max-w-[78%]" :class="message.mine ? 'items-end text-right' : ''">
                            <div
                                class="rounded-2xl px-3.5 py-2 text-sm shadow-sm"
                                :class="message.mine
                                    ? 'bg-cloud-600 text-white'
                                    : 'bg-white text-mist-700 dark:bg-slate-700 dark:text-slate-100'"
                            >
                                <p v-if="message.body" class="whitespace-pre-line break-words">{{ message.body }}</p>
                                <div v-if="message.images.length" class="mt-1.5 flex flex-wrap gap-1.5" :class="message.body ? '' : 'mt-0'">
                                    <button
                                        v-for="image in message.images"
                                        :key="image.id"
                                        type="button"
                                        class="overflow-hidden rounded-xl ring-1 ring-black/5 transition hover:opacity-90"
                                        @click="openImage(image.url)"
                                    >
                                        <img :src="image.url" alt="Adjunto del chat" class="h-20 w-20 object-cover" />
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1 text-[10px] text-mist-400">
                                {{ message.sender_name }} · {{ message.time_label }}
                            </p>
                        </div>
                    </div>
                </template>

                <div v-else class="py-10 text-center">
                    <i class="pi pi-comments mb-2 block text-xl text-mist-300"></i>
                    <p class="text-xs text-mist-500 dark:text-slate-400">
                        Aún no hay mensajes. Escribe o comparte una foto del vehículo.
                    </p>
                </div>
            </div>

            <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-[11px] font-medium text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                {{ error }}
            </p>

            <div v-if="previews.length" class="flex flex-wrap gap-2">
                <div v-for="(preview, index) in previews" :key="preview.url" class="relative">
                    <img :src="preview.url" :alt="preview.name" class="h-14 w-14 rounded-xl object-cover ring-1 ring-mist-200" />
                    <button
                        type="button"
                        class="absolute -right-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full bg-rose-500 text-white shadow"
                        aria-label="Quitar imagen"
                        @click="removeFile(index)"
                    >
                        <i class="pi pi-times text-[9px]"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="button"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-mist-200 text-mist-500 transition hover:border-cloud-300 hover:text-cloud-600 dark:border-slate-600 dark:text-slate-300"
                    :disabled="previews.length >= imagesLimit"
                    :title="`Adjuntar imágenes (máx. ${imagesLimit})`"
                    @click="pickFiles"
                >
                    <i class="pi pi-image text-sm"></i>
                </button>
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="onFilesSelected"
                />

                <textarea
                    v-model="body"
                    rows="1"
                    class="min-h-10 w-full resize-none rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-mist-700 focus:border-cloud-400 focus:ring-2 focus:ring-cloud-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="Escribe un mensaje…"
                    @keydown.enter.exact.prevent="send"
                ></textarea>

                <button
                    type="button"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-cloud-600 text-white shadow-soft transition hover:bg-cloud-700 disabled:opacity-50"
                    :disabled="!canSend"
                    aria-label="Enviar mensaje"
                    @click="send"
                >
                    <i :class="['text-sm', sending ? 'pi pi-spin pi-spinner' : 'pi pi-send']"></i>
                </button>
            </div>

            <p class="text-center text-[10px] text-mist-400">
                Máximo {{ imagesLimit }} imágenes por mensaje · hasta 5 MB cada una.
            </p>
        </div>

        <Teleport to="body">
            <div
                v-if="previewVisible"
                class="fixed inset-0 z-[1250] flex items-center justify-center bg-black/80 p-4"
                @click.self="previewVisible = false"
            >
                <button
                    type="button"
                    class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                    aria-label="Cerrar imagen"
                    @click="previewVisible = false"
                >
                    <i class="pi pi-times text-sm"></i>
                </button>
                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt="Imagen del chat"
                    class="max-h-[85vh] w-auto max-w-full rounded-2xl object-contain shadow-2xl"
                />
            </div>
        </Teleport>
    </Dialog>
</template>
