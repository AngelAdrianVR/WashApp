<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Crear cuenta" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-mist-900">Crea tu cuenta</h1>
            <p class="mt-2 text-sm text-mist-500">Agenda, paga y acumula puntos desde un solo lugar.</p>
        </div>

        <form class="mt-7" @submit.prevent="submit">
            <div>
                <label for="name" class="wa-label">Nombre completo</label>
                <input id="name" v-model="form.name" type="text" class="wa-input" placeholder="Tu nombre" required autofocus autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <label for="email" class="wa-label">Correo electrónico</label>
                <input id="email" v-model="form.email" type="email" class="wa-input" placeholder="tu@correo.com" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="wa-label">Contraseña</label>
                    <input id="password" v-model="form.password" type="password" class="wa-input" placeholder="••••••••" required autocomplete="new-password" />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div>
                    <label for="password_confirmation" class="wa-label">Confirmar contraseña</label>
                    <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="wa-input" placeholder="••••••••" required autocomplete="new-password" />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div v-if="$page.props.jetstream?.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                <label for="terms" class="flex items-start gap-2.5 text-xs leading-relaxed text-mist-500">
                    <Checkbox id="terms" v-model:checked="form.terms" name="terms" required class="mt-0.5" />
                    <span>
                        Acepto los términos del servicio y el aviso de privacidad de WashApp.
                    </span>
                </label>
                <InputError class="mt-2" :message="form.errors.terms" />
            </div>

            <button
                type="submit"
                class="wa-btn-primary mt-6 w-full !py-3.5 text-base"
                :class="{ 'pointer-events-none opacity-60': form.processing }"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M12 3a9 9 0 109 9" />
                </svg>
                {{ form.processing ? 'Creando cuenta…' : 'Crear cuenta' }}
            </button>
        </form>

        <p class="mt-7 text-center text-sm text-mist-500">
            ¿Ya tienes cuenta?
            <Link :href="route('login')" class="font-semibold text-cloud-700 transition hover:text-cloud-800">
                Iniciar sesión
            </Link>
        </p>
    </AuthenticationCard>
</template>
