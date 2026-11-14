<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    label: { type: String, default: 'Carrusel' },
});

const track = ref(null);
const canPrev = ref(false);
const canNext = ref(false);
const scrollable = ref(false);

let frame = null;

const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const update = () => {
    const el = track.value;
    if (!el) return;

    scrollable.value = el.scrollWidth > el.clientWidth + 4;
    canPrev.value = el.scrollLeft > 4;
    canNext.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;

    if (prefersReducedMotion()) return;

    if (!scrollable.value) {
        Array.from(el.children).forEach((card) => card.style.setProperty('--focus', '1'));
        return;
    }

    const trackRect = el.getBoundingClientRect();
    const center = trackRect.left + trackRect.width / 2;

    Array.from(el.children).forEach((card) => {
        const rect = card.getBoundingClientRect();
        const cardCenter = rect.left + rect.width / 2;
        const distance = Math.min(Math.abs(cardCenter - center) / (trackRect.width || 1), 1);
        card.style.setProperty('--focus', (1 - distance).toFixed(3));
    });
};

const requestUpdate = () => {
    if (frame) return;

    frame = requestAnimationFrame(() => {
        frame = null;
        update();
    });
};

const scrollByCard = (direction) => {
    const el = track.value;
    if (!el) return;

    const first = el.firstElementChild;
    const gap = parseFloat(getComputedStyle(el).columnGap || '0') || 0;
    const step = (first?.getBoundingClientRect().width ?? el.clientWidth) + gap;

    el.scrollBy({ left: step * direction, behavior: 'smooth' });
};

onMounted(() => {
    update();
    track.value?.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
});

onBeforeUnmount(() => {
    track.value?.removeEventListener('scroll', requestUpdate);
    window.removeEventListener('resize', requestUpdate);
    if (frame) cancelAnimationFrame(frame);
});
</script>

<template>
    <div>
        <div
            ref="track"
            class="wa-scroll-track flex snap-x snap-mandatory gap-4 overflow-x-auto px-1 pb-3 pt-1 sm:gap-5"
            role="region"
            :aria-label="label"
            tabindex="0"
            @keydown.left.prevent="scrollByCard(-1)"
            @keydown.right.prevent="scrollByCard(1)"
        >
            <slot />
        </div>

        <div v-if="scrollable" class="mt-3 flex items-center justify-between gap-4">
            <p class="text-[11px] uppercase tracking-[0.18em] text-mist-400">Desliza para explorar</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-2xl border border-mist-200 bg-white/80 text-mist-600 transition hover:border-cloud-300 hover:text-cloud-700 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Anterior"
                    :disabled="!canPrev"
                    @click="scrollByCard(-1)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-2xl border border-mist-200 bg-white/80 text-mist-600 transition hover:border-cloud-300 hover:text-cloud-700 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Siguiente"
                    :disabled="!canNext"
                    @click="scrollByCard(1)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
