<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    active: { type: Boolean, default: true },
});

const kind = computed(() => {
    if (!props.active) return null;

    if (['pending', 'confirmed'].includes(props.status)) return 'waiting';
    if (props.status === 'on_way') return 'driving';
    if (props.status === 'in_progress') return 'washing';

    return null;
});
</script>

<template>
    <span v-if="kind === 'waiting'" class="status-anim status-anim--waiting" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.8V12l2.9 1.9" stroke-linecap="round" />
        </svg>
    </span>

    <span v-else-if="kind === 'driving'" class="status-anim status-anim--driving" aria-hidden="true">
        <svg viewBox="0 0 32 20" fill="currentColor">
            <path d="M3 13.5h1.4a2.8 2.8 0 0 1 5.4 0h7.4a2.8 2.8 0 0 1 5.4 0h1.2a1.6 1.6 0 0 0 1.6-1.6v-1.4a2 2 0 0 0-1.4-1.9l-2.1-.6-2-3.4A3 3 0 0 0 17.2 3h-4.6a3 3 0 0 0-2.2 1L8 6.6l-3.4.5A2 2 0 0 0 3 9v2.9c0 .9.7 1.6 1.6 1.6Zm3.5-2.6a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8Zm12.8 0a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8Z" />
        </svg>
    </span>

    <span v-else-if="kind === 'washing'" class="status-anim status-anim--washing" aria-hidden="true">
        <span class="bubble bubble--1"></span>
        <span class="bubble bubble--2"></span>
        <span class="bubble bubble--3"></span>
    </span>
</template>

<style scoped>
.status-anim {
    display: grid;
    place-items: center;
    height: 100%;
    width: 100%;
}

.status-anim svg {
    height: 60%;
    width: 60%;
}

.status-anim--waiting {
    animation: status-bounce 1.1s ease-in-out infinite;
}

.status-anim--driving svg {
    height: 48%;
    width: 78%;
    animation: status-drive 1.6s ease-in-out infinite;
}

.status-anim--washing {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 2px;
    padding-bottom: 1px;
}

.bubble {
    display: block;
    width: 4px;
    height: 4px;
    border-radius: 9999px;
    background: currentColor;
    animation: status-bubble 1.5s ease-in-out infinite;
}

.bubble--2 {
    animation-delay: 0.35s;
}

.bubble--3 {
    animation-delay: 0.7s;
}

@keyframes status-bounce {
    0%,
    100% {
        transform: translateY(-8%);
    }
    50% {
        transform: translateY(8%);
    }
}

@keyframes status-drive {
    0%,
    100% {
        transform: translateX(-8%);
    }
    50% {
        transform: translateX(8%);
    }
}

@keyframes status-bubble {
    0% {
        transform: translateY(0) scale(0.7);
        opacity: 0.2;
    }
    60% {
        transform: translateY(-45%) scale(1.15);
        opacity: 1;
    }
    100% {
        transform: translateY(-90%) scale(0.8);
        opacity: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .status-anim,
    .status-anim svg,
    .bubble {
        animation: none;
    }
}
</style>
