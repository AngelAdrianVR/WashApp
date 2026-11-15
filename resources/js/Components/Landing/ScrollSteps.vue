<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    steps: { type: Array, default: () => [] },
    eyebrow: { type: String, default: 'Cómo funciona' },
    title: { type: String, default: 'Tu auto limpio, paso a paso' },
});

const root = ref(null);
const viewport = ref(null);
const track = ref(null);

const shift = ref(0);
const progress = ref(0);
const focus = ref(props.steps.map(() => 1));
const rootHeight = ref('');
const activeIndex = ref(0);
const reduced = ref(false);

let maxShift = 0;
let frame = null;

const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));

const measure = () => {
    const trackEl = track.value;
    const viewportEl = viewport.value;

    if (!trackEl || !viewportEl || reduced.value) {
        maxShift = 0;
        rootHeight.value = '';
        return;
    }

    const cards = Array.from(trackEl.children);
    const last = cards[cards.length - 1];

    maxShift = last ? Math.max(last.offsetLeft + last.offsetWidth - viewportEl.clientWidth, 0) : 0;

    // El tramo de scroll acompaña el recorrido horizontal de las tarjetas.
    rootHeight.value = `${Math.round(window.innerHeight + maxShift * 1.15)}px`;
};

const update = () => {
    frame = null;

    const el = root.value;

    if (!el || reduced.value) {
        shift.value = 0;
        progress.value = 0;
        focus.value = props.steps.map(() => 1);
        activeIndex.value = 0;
        return;
    }

    const span = Math.max(el.offsetHeight - window.innerHeight, 1);
    const nextProgress = clamp(-el.getBoundingClientRect().top / span);

    progress.value = nextProgress;
    shift.value = nextProgress * maxShift;

    const trackEl = track.value;
    if (!trackEl) return;

    const cards = Array.from(trackEl.children);
    const active = nextProgress * Math.max(props.steps.length - 1, 1);

    focus.value = cards.map((card, index) => clamp(1 - Math.abs(active - index) / 1.25));
    activeIndex.value = Math.round(active);
};

const requestUpdate = () => {
    if (frame) return;
    frame = requestAnimationFrame(update);
};

const handleResize = () => {
    measure();
    update();
};

const goToStep = (index) => {
    const el = root.value;
    if (!el || reduced.value || props.steps.length < 2) return;

    const span = Math.max(el.offsetHeight - window.innerHeight, 0);
    const top = el.getBoundingClientRect().top + window.scrollY;
    const ratio = index / (props.steps.length - 1);

    window.scrollTo({ top: top + span * ratio + 1, behavior: 'smooth' });
};

const cardStyle = (index) => {
    if (reduced.value) return {};

    const value = focus.value[index] ?? 0;

    return {
        transform: `translate3d(0, ${((1 - value) * 26).toFixed(1)}px, 0) scale(${(0.9 + value * 0.1).toFixed(3)})`,
        opacity: (0.35 + value * 0.65).toFixed(3),
    };
};

onMounted(async () => {
    reduced.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    await nextTick();
    measure();
    update();

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', handleResize);
    window.addEventListener('load', handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', requestUpdate);
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('load', handleResize);

    if (frame) cancelAnimationFrame(frame);
});
</script>

<template>
    <section ref="root" class="relative" :style="rootHeight ? { height: rootHeight } : null">
        <div :class="reduced ? 'relative' : 'sticky top-0 flex h-screen items-center overflow-hidden'">
            <div class="w-full px-3 sm:px-5" :class="reduced ? '' : 'pt-20 sm:pt-24'">
                <div class="mx-auto max-w-7xl">
                    <header class="max-w-xl">
                        <span class="wa-eyebrow">{{ eyebrow }}</span>
                        <h2 class="wa-title mt-5 text-3xl sm:text-4xl">{{ title }}</h2>
                    </header>

                    <div ref="viewport" :class="reduced ? 'mt-8' : 'wa-steps-viewport mt-8 overflow-hidden'">
                        <div
                            ref="track"
                            class="relative flex will-change-transform"
                            :class="reduced ? 'flex-col gap-4' : 'gap-4 sm:gap-5'"
                            :style="reduced ? null : { transform: `translate3d(${-shift}px, 0, 0)` }"
                        >
                            <article
                                v-for="(step, index) in steps"
                                :key="step.title"
                                class="relative flex shrink-0 flex-col overflow-hidden rounded-[1.75rem] border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur-md sm:p-8"
                                :class="reduced ? 'w-full' : 'w-[78vw] sm:w-[400px]'"
                                :style="cardStyle(index)"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <span
                                        class="grid h-12 w-12 place-items-center rounded-2xl text-lg"
                                        :class="step.tone"
                                    >
                                        <i :class="step.icon"></i>
                                    </span>
                                    <span class="text-4xl font-semibold leading-none text-mist-200">
                                        {{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                </div>

                                <h3 class="mt-6 text-xl font-semibold tracking-tight text-mist-900 sm:text-2xl">
                                    {{ step.title }}
                                </h3>
                                <p class="mt-3 text-sm leading-relaxed text-mist-500">{{ step.description }}</p>

                                <div class="mt-auto pt-6">
                                    <div class="h-px w-full bg-gradient-to-r from-mist-200 to-transparent"></div>
                                    <p class="mt-4 text-[11px] font-semibold uppercase tracking-[0.18em] text-cloud-600">
                                        Paso {{ index + 1 }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <button
                                v-for="(step, index) in steps"
                                :key="`dot-${step.title}`"
                                type="button"
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="
                                    index === activeIndex
                                        ? 'w-10 bg-cloud-600'
                                        : 'w-4 bg-mist-200 hover:bg-mist-300'
                                "
                                :aria-label="`Ir al paso ${index + 1}: ${step.title}`"
                                @click="goToStep(index)"
                            ></button>
                        </div>

                        <div v-if="!reduced" class="hidden h-1 flex-1 overflow-hidden rounded-full bg-mist-200/70 sm:block">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-cloud-500 to-sage-500"
                                :style="{ width: `${Math.round(progress * 100)}%` }"
                            ></div>
                        </div>

                        <p class="ml-auto text-xs font-semibold tabular-nums tracking-[0.14em] text-mist-400">
                            {{ String(activeIndex + 1).padStart(2, '0') }} / {{ String(steps.length).padStart(2, '0') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
