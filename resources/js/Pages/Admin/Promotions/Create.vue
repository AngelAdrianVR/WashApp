<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Importaciones de PrimeVue
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import InlineMessage from 'primevue/inlinemessage';

// Definimos el formulario con los campos del modelo Promotion
const form = useForm({
    code: '',
    description: '',
    discount_type: 'Fijo',
    discount_value: null,
    max_uses: null,
    expires_at: null,
    is_active: true,
});

// Opciones para el tipo de descuento
const discountTypeOptions = ref([
    { label: 'Fijo ($)', value: 'Fijo' },
    { label: 'Porcentaje (%)', value: 'Porcentaje' },
]);

// Función para enviar el formulario al backend
const submit = () => {
    form.post(route('admin.promotions.store'));
};
</script>

<template>
    <AppLayout title="Crear Promoción">
        <Back :href="route('admin.promotions.index')" />
        
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 sm:p-6 lg:p-8">
            <div class="max-w-2xl mx-auto">
                <Card class="dark:bg-gray-800 dark:text-gray-200">
                    <template #title>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            Crear Nueva Promoción
                        </h1>
                    </template>
                    <template #subtitle>
                        <p class="text-gray-600 dark:text-gray-400">
                            Completa los datos para registrar un nuevo código de descuento.
                        </p>
                    </template>
                    <template #content>
                        <form @submit.prevent="submit" class="mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <div class="flex flex-col gap-2">
                                    <label for="code" class="font-semibold">Código</label>
                                    <InputText id="code" v-model="form.code" :invalid="!!form.errors.code" />
                                    <InlineMessage v-if="form.errors.code" severity="error">{{ form.errors.code }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="description" class="font-semibold">Descripción (Opcional)</label>
                                    <Textarea id="description" v-model="form.description" rows="1" autoResize :invalid="!!form.errors.description" />
                                    <InlineMessage v-if="form.errors.description" severity="error">{{ form.errors.description }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="discount_type" class="font-semibold">Tipo de Descuento</label>
                                    <Dropdown id="discount_type" v-model="form.discount_type" :options="discountTypeOptions" optionLabel="label" optionValue="value" :invalid="!!form.errors.discount_type" />
                                    <InlineMessage v-if="form.errors.discount_type" severity="error">{{ form.errors.discount_type }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="discount_value" class="font-semibold">Valor del Descuento</label>
                                    <InputNumber id="discount_value" v-model="form.discount_value" 
                                        :prefix="form.discount_type === 'Fijo' ? '$' : ''" 
                                        :suffix="form.discount_type === 'Porcentaje' ? '%' : ''"
                                        :minFractionDigits="form.discount_type === 'Fijo' ? 2 : 0"
                                        :maxFractionDigits="2"
                                        :invalid="!!form.errors.discount_value" />
                                    <InlineMessage v-if="form.errors.discount_value" severity="error">{{ form.errors.discount_value }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="max_uses" class="font-semibold">Límite de Usos (Opcional)</label>
                                    <InputNumber id="max_uses" v-model="form.max_uses" placeholder="Sin límite" :invalid="!!form.errors.max_uses" />
                                    <InlineMessage v-if="form.errors.max_uses" severity="error">{{ form.errors.max_uses }}</InlineMessage>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="expires_at" class="font-semibold">Fecha de Expiración (Opcional)</label>
                                    <Calendar id="expires_at" v-model="form.expires_at" showIcon iconDisplay="input" dateFormat="dd/mm/yy" placeholder="Sin expiración" :invalid="!!form.errors.expires_at" />
                                    <InlineMessage v-if="form.errors.expires_at" severity="error">{{ form.errors.expires_at }}</InlineMessage>
                                </div>

                                <div class="flex flex-col gap-2 justify-center md:col-span-2">
                                    <label for="is_active" class="font-semibold">Promoción Activa</label>
                                    <div class="flex items-center mt-2">
                                        <InputSwitch id="is_active" v-model="form.is_active" />
                                        <span :class="['ml-3', form.is_active ? 'text-green-500' : 'text-gray-500']">
                                            {{ form.is_active ? 'Sí, el código se puede usar' : 'No, el código está desactivado' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <Button type="submit" label="Crear Promoción" icon="pi pi-check" :loading="form.processing" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
