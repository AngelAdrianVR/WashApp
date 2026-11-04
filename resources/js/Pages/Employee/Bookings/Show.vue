<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BookingDetail from '@/Components/Bookings/BookingDetail.vue';
import Button from 'primevue/button';
import Toast from 'primevue/toast';

defineProps({
    booking: { type: Object, required: true },
});
</script>

<template>
    <Head title="Detalle del servicio" />

    <AppLayout title="Detalle del servicio">
        <Toast position="top-right" />

        <div class="mx-auto w-full max-w-3xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Servicio #{{ booking.id }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white">Detalle del servicio</h1>
                </div>
                <Link :href="route('employee.bookings.index')">
                    <Button label="Volver a mis servicios" icon="pi pi-arrow-left" size="small" text severity="secondary" />
                </Link>
            </div>

            <div class="rounded-3xl border border-white/70 bg-white/85 p-6 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90">
                <BookingDetail
                    :booking="booking"
                    can-manage
                    status-route="employee.bookings.updateStatus"
                />
            </div>
        </div>
    </AppLayout>
</template>