<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const socialNotice = ref('');
let noticeTimer = null;

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: () => form.reset('password'),
    });
};

// Los accesos sociales aún no están habilitados: solo se muestran.
const socialComingSoon = (provider) => {
    socialNotice.value = `El acceso con ${provider} estará disponible muy pronto.`;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => (socialNotice.value = ''), 4000);
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-mist-900">Bienvenido de nuevo</h1>
            <p class="mt-2 text-sm text-mist-500">Ingresa para gestionar tus reservas y recompensas.</p>
        </div>

        <div v-if="status" class="mt-5 rounded-2xl bg-sage-50 px-4 py-3 text-sm font-medium text-sage-700">
            {{ status }}
        </div>

        <form class="mt-7" @submit.prevent="submit">
            <!-- Correo -->
            <div>
                <label for="email" class="wa-label">Correo electrónico</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-mist-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 7.5h17v9a2 2 0 01-2 2h-13a2 2 0 01-2-2v-9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8l8 5.5L20 8" />
                        </svg>
                    </span>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="wa-input !pl-11"
                        placeholder="tu@correo.com"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Contraseña -->
            <div class="mt-4">
                <label for="password" class="wa-label">Contraseña</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-mist-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V8a5 5 0 0110 0v2" />
                            <rect x="4.5" y="10" width="15" height="10" rx="2.5" />
                        </svg>
                    </span>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="wa-input !pl-11 !pr-12"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-mist-400 transition hover:text-mist-700"
                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4l16 16M10.6 6.1A9.6 9.6 0 0112 6c6 0 9.5 6 9.5 6a17 17 0 01-3.2 3.9M6.2 8.1A16.7 16.7 0 002.5 12S6 18 12 18a9.4 9.4 0 003.3-.6" />
                        </svg>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-mist-600">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-mist-300 text-cloud-600 focus:ring-cloud-300"
                    />
                    Recuérdame
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-cloud-700 transition hover:text-cloud-800"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
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
                {{ form.processing ? 'Ingresando…' : 'Iniciar sesión' }}
            </button>
        </form>

        <!-- Separador -->
        <div class="my-6 flex items-center gap-3">
            <span class="h-px flex-1 bg-mist-200"></span>
            <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400">o continúa con</span>
            <span class="h-px flex-1 bg-mist-200"></span>
        </div>

        <!-- Accesos sociales (visuales por ahora) -->
        <div class="grid grid-cols-2 gap-3">
            <button
                type="button"
                class="flex items-center justify-center gap-2.5 rounded-2xl border border-mist-200 bg-white px-4 py-3 text-sm font-semibold text-mist-700 transition hover:border-mist-300 hover:bg-mist-50"
                @click="socialComingSoon('Google')"
            >
                <svg class="h-4 w-4" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                </svg>
                Google
            </button>

            <button
                type="button"
                class="flex items-center justify-center gap-2.5 rounded-2xl border border-mist-200 bg-white px-4 py-3 text-sm font-semibold text-mist-700 transition hover:border-mist-300 hover:bg-mist-50"
                @click="socialComingSoon('Facebook')"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.34l-.53 3.49h-2.81V24C19.61 23.1 24 18.1 24 12.07z" />
                </svg>
                Facebook
            </button>
        </div>

        <p
            v-if="socialNotice"
            class="mt-3 rounded-2xl bg-cloud-50 px-4 py-2.5 text-center text-xs font-medium text-cloud-700"
        >
            {{ socialNotice }}
        </p>

        <!-- Registro -->
        <p class="mt-7 text-center text-sm text-mist-500">
            ¿Aún no tienes cuenta?
            <Link :href="route('register')" class="font-semibold text-cloud-700 transition hover:text-cloud-800">
                Crear cuenta
            </Link>
        </p>
    </AuthenticationCard>
</template>
