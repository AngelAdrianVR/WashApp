<script setup>
import { Head, router } from '@inertiajs/vue3';
import SplashScreen from '@/Components/Landing/SplashScreen.vue';

const goToHome = () => {
    router.visit(route('landing.index'), { replace: true });
};
</script>

<template>
    <Head title="Cargando" />

    <div class="wa-page min-h-screen"></div>

    <SplashScreen :duration="4000" @done="goToHome" />
</template>
