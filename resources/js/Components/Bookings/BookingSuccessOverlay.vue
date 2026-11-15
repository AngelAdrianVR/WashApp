<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    folio: { type: String, default: '' },
    title: { type: String, default: '¡Todo listo!' },
    subtitle: {
        type: String,
        default: 'Estamos asignando a tu colaborador. Te avisaremos por correo y aquí mismo.',
    },
    duration: { type: Number, default: 4200 },
});

const emit = defineEmits(['close']);

const visible = ref(props.show);

let timer = null;

const palette = ['#3fae45', '#1585bd', '#bfae95', '#8dd494', '#7fc3e5'];
let seed = 7;

const particles = computed(() =>
    Array.from({ length: 18 }, (_, index) => {
        seed = (seed * 9301 + 49297) % 233280;
        const random = seed / 233280;
        const left = Math.round(random * 100);
        const delay = (random * 0.9).toFixed(2);
        const drift = Math.round((random - 0.5) * 160);
        const size = 6 + Math.round(random * 8);

        return {
            id: index,
            style: {
                left: `${left}%`,
                width: `${size}px`,
                height: `${size}px`,
                backgroundColor: palette[index % palette.length],
                animationDelay: `${delay}s`,
                '--drift': `${drift}px`,
            },
        };
    })
);

const close = () => {
    visible.value = false;
    clearTimeout(timer);
    emit('close');
};

onMounted(() => {
    if (props.show) {
        timer = setTimeout(close, props.duration);
    }
});

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <Teleport to="body">
        <Transition name="celebrate">
            <div v-if="visible" class="fixed inset-0 z-[130] flex items-center justify-center p-4" role="dialog" aria-live="polite">
                <div class="absolute inset-0 bg-mist-900/45 backdrop-blur-md" @click="close"></div>

                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <span v-for="particle in particles" :key="particle.id" class="celebrate-confetti" :style="particle.style"></span>
                </div>

                <div
                    class="relative w-full max-w-sm overflow-hidden rounded-[2rem] border border-white/70 bg-white/95 px-7 pb-7 pt-9 text-center shadow-lift"
                >
                    <span class="celebrate-ring" style="animation-delay: 0.05s"></span>
                    <span class="celebrate-ring" style="animation-delay: 0.25s"></span>
                    <span class="celebrate-ring" style="animation-delay: 0.45s"></span>

                    <div class="relative mx-auto grid h-24 w-24 place-items-center rounded-full bg-gradient-to-br from-sage-500 via-sage-600 to-cloud-600 text-white shadow-soft">
                        <span class="absolute inset-0 rounded-full bg-white/10 celebrate-pulse"></span>
                        <svg viewBox="0 0 52 52" class="relative h-12 w-12" aria-hidden="true">
                            <circle cx="26" cy="26" r="23" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2" />
                            <path
                                class="celebrate-check"
                                d="M15 27.5l7.5 7.5L38 19"
                                fill="none"
                                stroke="#ffffff"
                                stroke-width="4.2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <p class="mt-6 text-[10px] font-semibold uppercase tracking-[0.22em] text-cloud-500">Reserva registrada</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-mist-900">{{ title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500">{{ subtitle }}</p>

                    <div
                        v-if="folio"
                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-mist-50 px-4 py-2 text-xs font-semibold text-mist-600"
                    >
                        <i class="pi pi-hashtag text-[10px]"></i>
                        {{ folio }}
                    </div>

                    <button type="button" class="wa-btn-primary mt-6 w-full" @click="close">
                        Ver mi reserva
                        <i class="pi pi-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.celebrate-enter-active,
.celebrate-leave-active {
    transition: opacity 0.35s ease;
}

.celebrate-enter-active > div:last-child,
.celebrate-leave-active > div:last-child {
    transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.35s ease;
}

.celebrate-enter-from,
.celebrate-leave-to {
    opacity: 0;
}

.celebrate-enter-from > div:last-child,
.celebrate-leave-to > div:last-child {
    transform: translateY(24px) scale(0.94);
    opacity: 0;
}

.celebrate-ring {
    position: absolute;
    top: 36px;
    left: 50%;
    height: 96px;
    width: 96px;
    margin-left: -48px;
    border-radius: 9999px;
    border: 2px solid rgba(63, 174, 69, 0.45);
    animation: ring-out 1.8s cubic-bezier(0.22, 1, 0.36, 1) infinite;
    pointer-events: none;
}

.celebrate-pulse {
    animation: halo 2.2s ease-in-out infinite;
}

.celebrate-check {
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    animation: draw-check 0.7s cubic-bezier(0.65, 0, 0.35, 1) 0.15s forwards;
}

.celebrate-confetti {
    position: absolute;
    top: -24px;
    border-radius: 3px;
    opacity: 0;
    animation: confetti-fall 2.6s cubic-bezier(0.2, 0.6, 0.4, 1) forwards;
}

@keyframes ring-out {
    0% {
        transform: scale(0.55);
        opacity: 0.9;
    }
    100% {
        transform: scale(2.4);
        opacity: 0;
    }
}

@keyframes halo {
    0%,
    100% {
        transform: scale(0.9);
        opacity: 0.35;
    }
    50% {
        transform: scale(1.12);
        opacity: 0.12;
    }
}

@keyframes draw-check {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes confetti-fall {
    0% {
        transform: translate3d(0, 0, 0) rotate(0deg);
        opacity: 0;
    }
    15% {
        opacity: 1;
    }
    100% {
        transform: translate3d(var(--drift), 105vh, 0) rotate(520deg);
        opacity: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .celebrate-ring,
    .celebrate-pulse,
    .celebrate-confetti {
        animation: none;
        display: none;
    }

    .celebrate-check {
        animation: none;
        stroke-dashoffset: 0;
    }
}
</style>
