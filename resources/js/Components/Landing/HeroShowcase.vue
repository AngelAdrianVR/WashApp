<script setup>
import { ref } from 'vue';

const stage = ref(null);
const tilt = ref({ x: 0, y: 0 });

const reducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const handlePointerMove = (event) => {
    if (reducedMotion || !stage.value) return;

    const rect = stage.value.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - 0.5;
    const y = (event.clientY - rect.top) / rect.height - 0.5;

    tilt.value = {
        x: (-y * 4).toFixed(2),
        y: (x * 6).toFixed(2),
    };
};

const resetTilt = () => {
    tilt.value = { x: 0, y: 0 };
};

// Silueta de sedán en perfil, con los arcos de las ruedas recortados.
const bodyPath =
    'M76 344 L120 344 A48 48 0 0 1 216 344 L406 344 A48 48 0 0 1 502 344 L560 344 ' +
    'C576 344 590 334 590 316 C590 300 580 288 562 284 L470 278 C452 275 444 270 438 264 ' +
    'L392 224 C386 218 378 214 368 214 C340 208 296 208 262 216 C250 219 240 231 232 240 ' +
    'L180 264 C170 272 158 276 142 278 L92 284 C80 286 76 292 76 302 Z';

const wheels = [168, 454];

const spokes = [18, 90, 162, 234, 306].map((angle) => {
    const rad = (angle * Math.PI) / 180;
    const point = (radius) => ({
        x: Number((Math.cos(rad) * radius).toFixed(2)),
        y: Number((Math.sin(rad) * radius).toFixed(2)),
    });

    return { ...point(10), x2: point(24).x, y2: point(24).y };
});

const beads = [
    { x: 122, y: 288, r: 3.6 },
    { x: 158, y: 296, r: 2.4 },
    { x: 190, y: 286, r: 4 },
    { x: 240, y: 302, r: 2.8 },
    { x: 264, y: 322, r: 3.4 },
    { x: 288, y: 292, r: 4.2 },
    { x: 320, y: 330, r: 2.4 },
    { x: 348, y: 306, r: 3 },
    { x: 378, y: 332, r: 3.6 },
    { x: 424, y: 288, r: 3.4 },
    { x: 466, y: 292, r: 2.8 },
    { x: 500, y: 288, r: 3.8 },
    { x: 540, y: 300, r: 2.6 },
    { x: 292, y: 244, r: 2 },
    { x: 356, y: 252, r: 1.6 },
];
</script>

<template>
    <div
        ref="stage"
        class="relative"
        data-motion-parallax="0.05"
        @pointermove="handlePointerMove"
        @pointerleave="resetTilt"
    >
        <div
            class="pointer-events-none absolute -inset-5 -z-10 rounded-[3rem] bg-gradient-to-tr from-cloud-200/45 via-white/10 to-sage-200/45 blur-2xl"
        ></div>

        <div
            class="hero-stage relative overflow-hidden rounded-[2.25rem] border border-white/70 bg-gradient-to-br from-white/85 via-cloud-50/70 to-sage-50/80 shadow-lift backdrop-blur-md"
            :style="{ transform: `perspective(1400px) rotateX(${tilt.x}deg) rotateY(${tilt.y}deg)` }"
        >
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-cloud-200/45 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-sage-200/45 blur-3xl"></div>

            <svg
                viewBox="18 96 604 368"
                class="relative block w-full"
                role="img"
                aria-labelledby="hero-visual-title"
            >
                <title id="hero-visual-title">Sedán recién lavado con WashApp</title>

                <defs>
                    <linearGradient id="wa-body" x1="0" y1="0" x2="0.28" y2="1">
                        <stop offset="0%" stop-color="#9ad4ec" />
                        <stop offset="30%" stop-color="#1585bd" />
                        <stop offset="100%" stop-color="#062f45" />
                    </linearGradient>
                    <linearGradient id="wa-shade" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#04283a" stop-opacity="0" />
                        <stop offset="100%" stop-color="#04283a" stop-opacity="0.28" />
                    </linearGradient>
                    <linearGradient id="wa-glass" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.92" />
                        <stop offset="48%" stop-color="#b3dcf1" stop-opacity="0.82" />
                        <stop offset="100%" stop-color="#1585bd" stop-opacity="0.72" />
                    </linearGradient>
                    <linearGradient id="wa-floor" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#0b6b9b" stop-opacity="0" />
                        <stop offset="50%" stop-color="#0b6b9b" stop-opacity="0.32" />
                        <stop offset="100%" stop-color="#0b6b9b" stop-opacity="0" />
                    </linearGradient>
                    <linearGradient id="wa-sheen" x1="0" y1="0" x2="1" y2="0.35">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0" />
                        <stop offset="46%" stop-color="#ffffff" stop-opacity="0.5" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                    </linearGradient>
                    <radialGradient id="wa-rim" cx="38%" cy="30%" r="78%">
                        <stop offset="0%" stop-color="#eef7fc" />
                        <stop offset="56%" stop-color="#a9c4d3" />
                        <stop offset="100%" stop-color="#5b7280" />
                    </radialGradient>
                    <radialGradient id="wa-glow" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#8dd494" stop-opacity="0.5" />
                        <stop offset="56%" stop-color="#45a4d3" stop-opacity="0.2" />
                        <stop offset="100%" stop-color="#45a4d3" stop-opacity="0" />
                    </radialGradient>
                    <radialGradient id="wa-bead" cx="32%" cy="30%" r="70%">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.95" />
                        <stop offset="60%" stop-color="#eef7fc" stop-opacity="0.7" />
                        <stop offset="100%" stop-color="#7fc3e5" stop-opacity="0.55" />
                    </radialGradient>
                    <clipPath id="wa-body-clip">
                        <path :d="bodyPath" />
                    </clipPath>
                    <filter id="wa-soft" x="-40%" y="-120%" width="180%" height="340%">
                        <feGaussianBlur stdDeviation="9" />
                    </filter>
                </defs>

                <!-- Halos y aros de marca -->
                <circle cx="330" cy="278" r="196" fill="url(#wa-glow)" />
                <circle cx="330" cy="278" r="214" fill="none" stroke="#b3dcf1" stroke-opacity="0.5" />
                <circle
                    cx="330"
                    cy="278"
                    r="166"
                    fill="none"
                    stroke="#8dd494"
                    stroke-opacity="0.45"
                    stroke-dasharray="2 9"
                />

                <!-- Sombra y piso -->
                <ellipse cx="332" cy="384" rx="244" ry="14" fill="#0b6b9b" opacity="0.16" filter="url(#wa-soft)" />
                <rect x="58" y="388" width="524" height="1.6" rx="0.8" fill="url(#wa-floor)" />

                <!-- Carrocería -->
                <path :d="bodyPath" fill="url(#wa-body)" />

                <!-- Volumen: sombra inferior, luz superior y reflejo especular -->
                <g clip-path="url(#wa-body-clip)">
                    <rect x="40" y="268" width="580" height="120" fill="url(#wa-shade)" />
                    <rect class="hero-sheen" x="0" y="170" width="150" height="220" fill="url(#wa-sheen)" />
                </g>
                <path
                    d="M368 215 C340 209 296 209 262 217 C250 220 240 231 232 240"
                    fill="none"
                    stroke="#dff1fa"
                    stroke-opacity="0.5"
                    stroke-width="2.4"
                />
                <path
                    d="M438 264 C452 275 470 278 470 278 L556 292"
                    fill="none"
                    stroke="#dff1fa"
                    stroke-opacity="0.28"
                    stroke-width="2"
                />

                <!-- Cristales -->
                <path d="M334 222 L368 222 L408 268 L334 268 Z" fill="url(#wa-glass)" />
                <path d="M270 222 L326 222 L326 268 L212 268 Z" fill="url(#wa-glass)" opacity="0.94" />
                <path d="M216 268 L410 268" stroke="#eef7fc" stroke-opacity="0.5" stroke-width="1.6" />

                <!-- Espejo lateral -->
                <path d="M396 246 C406 244 415 246 417 252 C415 258 406 260 397 258 Z" fill="#0c3c53" />

                <!-- Pasos de rueda -->
                <path d="M120 344 A48 48 0 0 1 216 344" fill="none" stroke="#cbe6f5" stroke-opacity="0.5" stroke-width="2.4" />
                <path d="M406 344 A48 48 0 0 1 502 344" fill="none" stroke="#cbe6f5" stroke-opacity="0.5" stroke-width="2.4" />

                <!-- Línea de carácter de la carrocería -->
                <path
                    d="M104 297 C210 289 380 289 552 305"
                    fill="none"
                    stroke="#eef7fc"
                    stroke-opacity="0.24"
                    stroke-width="1.6"
                />

                <!-- Luces -->
                <path d="M548 292 L578 300 C580 307 577 312 571 312 L546 305 Z" fill="#eef7fc" opacity="0.9" />
                <path d="M82 296 L104 293 C106 299 104 304 99 305 L84 306 Z" fill="#d98b8b" opacity="0.75" />

                <!-- Detalles de puerta y parachoques -->
                <path
                    d="M334 272 C330 294 326 320 324 340"
                    fill="none"
                    stroke="#04283a"
                    stroke-opacity="0.22"
                    stroke-width="1.6"
                />
                <path
                    d="M240 274 C238 298 238 322 240 340"
                    fill="none"
                    stroke="#04283a"
                    stroke-opacity="0.18"
                    stroke-width="1.6"
                />
                <path d="M222 341 L400 341" stroke="#7fc3e5" stroke-opacity="0.22" stroke-width="3" />
                <path
                    d="M470 296 C512 303 545 310 570 319"
                    fill="none"
                    stroke="#04283a"
                    stroke-opacity="0.14"
                    stroke-width="2"
                />
                <rect x="516" y="310" width="48" height="9" rx="4.5" fill="#04283a" opacity="0.32" />
                <rect x="348" y="284" width="20" height="5" rx="2.5" fill="#eef7fc" opacity="0.42" />
                <rect x="264" y="286" width="18" height="5" rx="2.5" fill="#eef7fc" opacity="0.32" />

                <!-- Gotas -->
                <g v-for="(bead, index) in beads" :key="`bead-${index}`">
                    <circle :cx="bead.x" :cy="bead.y" :r="bead.r" fill="url(#wa-bead)" />
                    <circle
                        :cx="bead.x - bead.r * 0.32"
                        :cy="bead.y - bead.r * 0.32"
                        :r="bead.r * 0.33"
                        fill="#ffffff"
                        opacity="0.9"
                    />
                </g>

                <!-- Ruedas -->
                <g v-for="cx in wheels" :key="`wheel-${cx}`" :transform="`translate(${cx} 344)`">
                    <circle r="42" fill="#22303a" />
                    <circle r="42" fill="none" stroke="#0b1a22" stroke-opacity="0.5" stroke-width="2" />
                    <path d="M-42 0 A42 42 0 0 1 0 -42" fill="none" stroke="#ffffff" stroke-opacity="0.2" stroke-width="3" />
                    <circle r="26" fill="url(#wa-rim)" />
                    <g stroke="#8fb0c2" stroke-width="3" stroke-linecap="round" stroke-opacity="0.85">
                        <line v-for="(spoke, index) in spokes" :key="`spoke-${index}`" :x1="spoke.x" :y1="spoke.y" :x2="spoke.x2" :y2="spoke.y2" />
                    </g>
                    <circle r="8.5" fill="#2c3a44" />
                    <circle r="3" fill="#c7d5e0" opacity="0.85" />
                </g>

                <!-- Destellos -->
                <g transform="translate(506 232)">
                    <path
                        class="hero-sparkle"
                        d="M0 -11 L2.4 -2.4 L11 0 L2.4 2.4 L0 11 L-2.4 2.4 L-11 0 L-2.4 -2.4 Z"
                        fill="#ffffff"
                    />
                </g>
                <g transform="translate(300 176)">
                    <path
                        class="hero-sparkle hero-sparkle--late"
                        d="M0 -7 L1.6 -1.6 L7 0 L1.6 1.6 L0 7 L-1.6 1.6 L-7 0 L-1.6 -1.6 Z"
                        fill="#ffffff"
                        opacity="0.85"
                    />
                </g>

                <!-- Burbujas flotantes -->
                <g transform="translate(112 168)">
                    <circle class="hero-bubble" r="13" fill="url(#wa-bead)" />
                </g>
                <g transform="translate(556 152)">
                    <circle class="hero-bubble hero-bubble--late" r="9" fill="url(#wa-bead)" />
                </g>
                <g transform="translate(596 336)">
                    <circle class="hero-bubble hero-bubble--slow" r="6" fill="url(#wa-bead)" />
                </g>
            </svg>

            <!-- Tarjetas flotantes -->
            <div
                class="hero-float-card absolute right-3 top-3 w-[172px] rounded-2xl border border-white/80 bg-white/85 p-3.5 shadow-soft backdrop-blur-md sm:right-5 sm:top-5 sm:w-[184px] sm:p-4"
            >
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sage-400 opacity-70"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-sage-500"></span>
                    </span>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-mist-400">En servicio</span>
                </div>
                <p class="mt-2 text-sm font-semibold text-mist-900">Lavado ecológico</p>
                <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-mist-100">
                    <div class="hero-progress h-full rounded-full bg-gradient-to-r from-sage-400 to-cloud-400"></div>
                </div>
            </div>

            <div
                class="hero-float-card absolute bottom-3 left-3 rounded-2xl border border-white/20 bg-sage-600/95 px-4 py-3 text-white shadow-lift backdrop-blur-md sm:bottom-5 sm:left-5"
            >
                <p class="text-[10px] font-medium uppercase tracking-[0.16em] text-sage-100/90">Agua por lavado</p>
                <p class="text-xl font-semibold leading-tight">15 litros</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hero-stage {
    transform-style: preserve-3d;
    transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.hero-sheen {
    animation: sheen 6.5s cubic-bezier(0.45, 0, 0.55, 1) 1.2s infinite;
    transform: translateX(-200px) skewX(-16deg);
}

.hero-progress {
    width: 68%;
    animation: progress-breathe 3.6s ease-in-out infinite;
}

.hero-float-card {
    animation: card-float 8s ease-in-out infinite;
}

.hero-float-card + .hero-float-card {
    animation-delay: 1.6s;
}

.hero-sparkle {
    transform-box: fill-box;
    transform-origin: center;
    opacity: 0;
    animation: sparkle 4.4s ease-in-out 1.8s infinite;
}

.hero-sparkle--late {
    animation-delay: 3.1s;
}

.hero-bubble {
    opacity: 0;
    animation: bubble-float 7s ease-in-out 0.6s infinite;
}

.hero-bubble--late {
    animation-duration: 8.5s;
    animation-delay: 1.6s;
}

.hero-bubble--slow {
    animation-duration: 10s;
    animation-delay: 2.4s;
}

@keyframes sheen {
    0% {
        transform: translateX(-200px) skewX(-16deg);
    }
    55%,
    100% {
        transform: translateX(780px) skewX(-16deg);
    }
}

@keyframes progress-breathe {
    0%,
    100% {
        width: 46%;
    }
    50% {
        width: 82%;
    }
}

@keyframes card-float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

@keyframes sparkle {
    0%,
    100% {
        opacity: 0;
        transform: scale(0.4);
    }
    18% {
        opacity: 0.95;
        transform: scale(1);
    }
    40% {
        opacity: 0;
        transform: scale(0.6);
    }
}

@keyframes bubble-float {
    0% {
        opacity: 0;
        transform: translateY(14px) scale(0.8);
    }
    30% {
        opacity: 0.9;
    }
    70% {
        opacity: 0.65;
    }
    100% {
        opacity: 0;
        transform: translateY(-26px) scale(1.05);
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero-stage {
        transition: none;
        transform: none !important;
    }

    .hero-sheen,
    .hero-sparkle,
    .hero-bubble {
        animation: none;
        opacity: 0;
    }

    .hero-progress {
        animation: none;
        width: 68%;
    }
}
</style>
