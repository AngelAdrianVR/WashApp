<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="route('landing.index')" class="inline-flex items-center">
        <img
            src="/images/logo-washapp.png"
            alt="WashApp · Eco Car Wash Móvil"
            class="h-16 w-auto sm:h-[4.5rem]"
        />
    </Link>
</template>
