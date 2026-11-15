<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: Array,
        default: () => ['py-1', 'bg-white'],
    },
    panelClasses: {
        type: String,
        default: 'rounded-md ring-1 ring-black ring-opacity-5',
    },
    wrapperClasses: {
        type: String,
        default: 'rounded-md shadow-lg',
    },
    // En móvil ancla el panel a los bordes de la pantalla en lugar del trigger.
    fixedOnMobile: {
        type: Boolean,
        default: false,
    },
});

let open = ref(false);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        '48': props.fixedOnMobile ? 'sm:w-48' : 'w-48',
    }[props.width.toString()];
});

const positionClasses = computed(() =>
    props.fixedOnMobile
        ? 'fixed inset-x-4 top-[4.5rem] z-50 sm:absolute sm:inset-x-auto sm:top-auto sm:mt-2'
        : 'absolute z-50 mt-2'
);

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return props.fixedOnMobile
            ? 'sm:ltr:origin-top-left sm:rtl:origin-top-right sm:start-0'
            : 'ltr:origin-top-left rtl:origin-top-right start-0';
    }

    if (props.align === 'right') {
        return props.fixedOnMobile
            ? 'sm:ltr:origin-top-right sm:rtl:origin-top-left sm:end-0'
            : 'ltr:origin-top-right rtl:origin-top-left end-0';
    }

    return props.fixedOnMobile ? 'sm:origin-top' : 'origin-top';
});
</script>

<template>
    <div class="relative">
        <div @click="open = ! open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false" />

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="open"
                :class="[positionClasses, widthClass, alignmentClasses, wrapperClasses]"
                style="display: none;"
            >
                <div :class="[panelClasses, contentClasses]">
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>
