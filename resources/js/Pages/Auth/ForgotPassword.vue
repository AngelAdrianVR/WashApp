<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Recuperar contraseña" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-5 text-sm leading-relaxed text-mist-500">
            Escribe tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </div>

        <div v-if="status" class="mb-4 rounded-2xl bg-sage-50 px-4 py-3 text-sm font-medium text-sage-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <label for="email" class="wa-label">Correo electrónico</label>
                <input id="email" v-model="form.email" type="email" class="wa-input" placeholder="tu@correo.com" required autofocus autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-5">
                <button
                    type="submit"
                    class="wa-btn-primary w-full !py-3.5"
                    :class="{ 'pointer-events-none opacity-60': form.processing }"
                    :disabled="form.processing"
                >
                    Enviar enlace de restablecimiento
                </button>
            </div>
        </form>
    </AuthenticationCard>
</template>
