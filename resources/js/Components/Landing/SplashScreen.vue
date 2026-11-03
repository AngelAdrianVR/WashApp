<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    duration: { type: Number, default: 3000 },
});

const emit = defineEmits(['done']);

const isLeaving = ref(false);

const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Burbujas de jabón que acompañan la salida del splash
const bubbles = Array.from({ length: 18 }, () => ({
    left: 3 + Math.random() * 94,
    size: 16 + Math.random() * 64,
    delay: Math.random() * 0.45,
    duration: 0.85 + Math.random() * 0.6,
}));

let showTimer = null;
let doneTimer = null;

const finish = () => {
    if (isLeaving.value) return;

    isLeaving.value = true;
    clearTimeout(showTimer);
    doneTimer = setTimeout(() => emit('done'), 1150);
};

onMounted(() => {
    document.body.style.overflow = 'hidden';

    if (prefersReducedMotion) {
        emit('done');
        return;
    }

    showTimer = setTimeout(finish, props.duration);
});

onUnmounted(() => {
    clearTimeout(showTimer);
    clearTimeout(doneTimer);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            class="splash fixed inset-0 z-[120] flex items-center justify-center overflow-hidden"
            :class="{ 'splash--leaving': isLeaving }"
            role="status"
            aria-live="polite"
            aria-label="Cargando WashApp"
        >
            <!-- Fondo con halos de marca -->
            <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-sage-300/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-28 -right-20 h-96 w-96 rounded-full bg-cloud-300/30 blur-3xl"></div>

            <!-- Burbujas de salida -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <span
                    v-for="(bubble, index) in bubbles"
                    :key="`bubble-${index}`"
                    class="splash__bubble"
                    :style="{
                        left: `${bubble.left}%`,
                        width: `${bubble.size}px`,
                        height: `${bubble.size}px`,
                        animationDelay: `${bubble.delay}s`,
                        animationDuration: `${bubble.duration}s`,
                    }"
                ></span>
            </div>

            <!-- Contenido -->
            <div class="splash__content relative z-10 flex flex-col items-center px-8 text-center">
                <img
                    src="/images/logo-washapp.png"
                    alt="WashApp · Eco Car Wash Móvil"
                    class="splash__logo w-44 sm:w-56"
                />

                <p class="splash__tagline mt-6 px-4 text-[10px] font-semibold uppercase tracking-[0.22em] text-white/80 sm:text-[11px] sm:tracking-[0.35em]">
                    Cuidamos tu auto · Cuidamos el agua
                </p>

                <div class="splash__bar mt-8 h-[3px] w-40 overflow-hidden rounded-full bg-white/20">
                    <span class="splash__bar-fill block h-full w-full rounded-full bg-white/90"></span>
                </div>

                <button
                    type="button"
                    class="splash__skip mt-8 rounded-full border border-white/25 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/80 transition hover:border-white/50 hover:text-white"
                    @click="finish"
                >
                    Saltar
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.splash {
    background:
        radial-gradient(60rem 40rem at 15% 0%, rgba(92, 192, 102, 0.28), transparent 60%),
        radial-gradient(50rem 36rem at 90% 100%, rgba(21, 133, 189, 0.35), transparent 60%),
        linear-gradient(150deg, #0a587f 0%, #0b6b9b 45%, #245b2a 100%);
    animation: splash-in 0.5s ease-out both;
}

.splash__logo {
    filter: brightness(0) invert(1) drop-shadow(0 16px 34px rgba(3, 32, 48, 0.45));
    animation: logo-in 1s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.splash__tagline {
    animation: fade-rise 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.45s both;
}

.splash__bar {
    animation: fade-rise 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.6s both;
}

.splash__bar-fill {
    transform-origin: left center;
    animation: bar-fill 3s linear 0.15s both;
}

.splash__skip {
    animation: fade-rise 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.9s both;
}

/* Burbujas */
.splash__bubble {
    position: absolute;
    bottom: -12vh;
    border-radius: 9999px;
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0.15) 55%, rgba(255, 255, 255, 0) 75%);
    border: 1px solid rgba(255, 255, 255, 0.35);
    box-shadow: inset 0 0 18px rgba(255, 255, 255, 0.25);
    opacity: 0;
}

.splash--leaving {
    animation: splash-out 0.9s cubic-bezier(0.55, 0, 0.55, 1) forwards;
}

.splash--leaving .splash__content {
    animation: content-out 0.7s cubic-bezier(0.55, 0, 0.55, 1) forwards;
}

.splash--leaving .splash__bubble {
    animation-name: bubble-rise;
    animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
    animation-fill-mode: forwards;
}

@keyframes splash-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes splash-out {
    0% {
        opacity: 1;
    }
    60% {
        opacity: 0.85;
    }
    100% {
        opacity: 0;
    }
}

@keyframes content-out {
    to {
        opacity: 0;
        transform: scale(0.96) translateY(-10px);
    }
}

@keyframes logo-in {
    from {
        opacity: 0;
        transform: translateY(14px) scale(0.92);
        filter: brightness(0) invert(1) blur(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: brightness(0) invert(1) blur(0) drop-shadow(0 16px 34px rgba(3, 32, 48, 0.45));
    }
}

@keyframes fade-rise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bar-fill {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}

@keyframes bubble-rise {
    0% {
        opacity: 0;
        transform: translateY(0) scale(0.35);
    }
    18% {
        opacity: 0.85;
    }
    100% {
        opacity: 0;
        transform: translateY(-125vh) scale(1.15);
    }
}
</style>
