<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="wa-page relative flex min-h-screen flex-col overflow-hidden">
        <!-- Halos suaves de marca -->
        <div class="pointer-events-none absolute -left-28 -top-28 h-80 w-80 rounded-full bg-sage-200/35 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-cloud-200/45 blur-3xl"></div>
        <div class="pointer-events-none absolute left-1/2 top-1/3 h-64 w-64 -translate-x-1/2 rounded-full bg-white/50 blur-3xl"></div>

        <div class="relative mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10 sm:py-14">
            <!-- Logo -->
            <div class="flex justify-center">
                <slot name="logo" />
            </div>

            <!-- Tarjeta -->
            <div class="mt-8 rounded-3xl border border-white/80 bg-white/85 p-7 shadow-soft backdrop-blur-xl sm:p-9">
                <slot />
            </div>

            <!-- Pie -->
            <div class="mt-7 flex flex-col items-center gap-2 text-xs text-mist-400">
                <Link :href="route('landing.index')" class="inline-flex items-center gap-1.5 font-medium text-mist-500 transition hover:text-mist-800">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
                    </svg>
                    Volver al inicio
                </Link>
                <p>&copy; {{ new Date().getFullYear() }} WashApp · Eco Car Wash Móvil</p>
            </div>
        </div>
    </div>
</template>
