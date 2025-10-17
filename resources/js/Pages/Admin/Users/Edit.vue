<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Importaciones de PrimeVue para un diseño moderno
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Password from 'primevue/password';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import InlineMessage from 'primevue/inlinemessage';

// Definimos las props que el componente recibirá desde el controlador
const props = defineProps({
    user: {
        type: Object,
        required: true,
    }
});

// Inicializamos el formulario con los datos del usuario recibido
const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '', // La contraseña se deja en blanco por seguridad
    phone_number: props.user.phone_number,
    role: props.user.role,
    is_active: props.user.is_active,
});

// Opciones para el selector de Rol
const roleOptions = ref([
    { label: 'Admin', value: 'Admin' },
    { label: 'Empleado', value: 'Empleado' },
    { label: 'Cliente', value: 'Cliente' },
]);

// Función para enviar el formulario al backend para actualizar
const submit = () => {
    // Usamos el método PUT para la actualización
    form.put(route('admin.users.update', props.user.id), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AppLayout title="Crear usuario">
        <Back />
        <!-- Asumiendo que usas un Layout principal -->
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
            <div class="max-w-2xl mx-auto">
                <Card class="dark:bg-gray-800 dark:text-gray-200">
                    <template #title>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            Editar Usuario
                        </h1>
                    </template>
                    <template #subtitle>
                        <p class="text-gray-600 dark:text-gray-400">
                            Actualiza la información del usuario.
                        </p>
                    </template>
                    <template #content>
                        <form @submit.prevent="submit" class="mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Campo: Nombre -->
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="font-semibold text-gray-700 dark:text-gray-300">Nombre Completo</label>
                                    <InputText id="name" v-model="form.name" :invalid="!!form.errors.name" />
                                    <InlineMessage v-if="form.errors.name" severity="error" class="mt-1">
                                        {{ form.errors.name }}
                                    </InlineMessage>
                                </div>

                                <!-- Campo: Email -->
                                <div class="flex flex-col gap-2">
                                    <label for="email" class="font-semibold text-gray-700 dark:text-gray-300">Correo Electrónico</label>
                                    <InputText id="email" v-model="form.email" type="email" :invalid="!!form.errors.email" />
                                    <InlineMessage v-if="form.errors.email" severity="error" class="mt-1">
                                        {{ form.errors.email }}
                                    </InlineMessage>
                                </div>

                                <!-- Campo: Contraseña -->
                                <div class="flex flex-col gap-2">
                                    <label for="password" class="font-semibold text-gray-700 dark:text-gray-300">Nueva Contraseña</label>
                                    <Password id="password" v-model="form.password" :feedback="false" toggleMask :invalid="!!form.errors.password" placeholder="Dejar en blanco para no cambiar" />
                                    <InlineMessage v-if="form.errors.password" severity="error" class="mt-1">
                                        {{ form.errors.password }}
                                    </InlineMessage>
                                </div>

                                <!-- Campo: Teléfono -->
                                <div class="flex flex-col gap-2">
                                    <label for="phone_number" class="font-semibold text-gray-700 dark:text-gray-300">Teléfono</label>
                                    <InputText id="phone_number" v-model="form.phone_number" :invalid="!!form.errors.phone_number" />
                                    <InlineMessage v-if="form.errors.phone_number" severity="error" class="mt-1">
                                        {{ form.errors.phone_number }}
                                    </InlineMessage>
                                </div>

                                <!-- Campo: Rol -->
                                <div class="flex flex-col gap-2">
                                    <label for="role" class="font-semibold text-gray-700 dark:text-gray-300">Rol</label>
                                    <Dropdown id="role" v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Selecciona un rol" :invalid="!!form.errors.role" />
                                    <InlineMessage v-if="form.errors.role" severity="error" class="mt-1">
                                        {{ form.errors.role }}
                                    </InlineMessage>
                                </div>

                                <!-- Campo: Activo -->
                                <div class="flex flex-col gap-2 justify-center">
                                    <label for="is_active" class="font-semibold text-gray-700 dark:text-gray-300">Usuario Activo</label>
                                    <div class="flex items-center mt-2">
                                        <InputSwitch id="is_active" v-model="form.is_active" />
                                        <span :class="['ml-3', form.is_active ? 'text-green-500' : 'text-gray-500 dark:text-gray-400']">
                                            {{ form.is_active ? 'Sí' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón de Envío -->
                            <div class="flex justify-end mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <Button type="submit" label="Guardar Cambios" icon="pi pi-save" :loading="form.processing" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
