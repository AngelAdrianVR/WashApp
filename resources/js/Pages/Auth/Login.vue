<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="animate-fade-in-up">
            <!-- Email Field with Icon -->
            <div class="relative">
                <InputLabel for="email" value="Correo Electrónico" class="text-gray-200" />
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pt-6 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full pl-10 bg-gray-700/50 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 transition-shadow duration-300 focus:shadow-lg focus:shadow-blue-500/20"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password Field with Icon -->
            <div class="mt-4 relative">
                <InputLabel for="password" value="Contraseña" class="text-gray-200" />
                 <div class="absolute inset-y-0 left-0 flex items-center pl-3 pt-6 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>
                </div>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full pl-10 bg-gray-700/50 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500 transition-shadow duration-300 focus:shadow-lg focus:shadow-blue-500/20"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" class="rounded border-gray-500 text-blue-500 focus:ring-blue-600" />
                    <span class="ms-2 text-sm text-gray-300">Recuérdame</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-8">
                <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-blue-800 hover:text-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-gray-800 transition-all duration-300 ease-in-out">
                    ¿Olvidaste tu contraseña?
                </Link>

                <PrimaryButton class="ms-4 bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold shadow-lg shadow-blue-500/30 transform hover:scale-105 transition-transform duration-300 animate-pulse-glow" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Iniciar Sesión
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>

<style scoped>
/* Animation for form fade-in */
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.7s ease-out forwards;
}

/* Animation for the button's glowing pulse effect */
@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.4), 0 0 12px rgba(6, 182, 212, 0.3);
    }
    50% {
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.6), 0 0 25px rgba(6, 182, 212, 0.5);
    }
}

.animate-pulse-glow {
    animation: pulse-glow 2.5s infinite ease-in-out;
}
</style>
