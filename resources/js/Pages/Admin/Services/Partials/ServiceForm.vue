<script setup>
import { computed } from 'vue';

// Componentes de PrimeVue
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import InlineMessage from 'primevue/inlinemessage';

const props = defineProps({
    form: { type: Object, required: true },
    vehicleCategories: { type: Array, default: () => [] },
});

const washTypeOptions = [
    { label: 'Lavado con Agua', value: 'Agua' },
    { label: 'Lavado en Seco', value: 'Seco' },
];

const isAddon = computed({
    get: () => props.form.is_addon,
    set: (value) => {
        props.form.is_addon = value;
        // Los extras no dependen del tipo de lavado
        if (value) {
            props.form.type = props.form.type || 'Seco';
        }
    },
});
</script>

<template>
    <div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Datos generales -->
            <div class="flex flex-col gap-2 md:col-span-2">
                <label for="name" class="font-semibold">Título del servicio</label>
                <InputText id="name" v-model="form.name" placeholder="Ej. Lavado Ecológico en Seco" :invalid="!!form.errors.name" />
                <InlineMessage v-if="form.errors.name" severity="error">{{ form.errors.name }}</InlineMessage>
            </div>

            <div class="flex flex-col gap-2 md:col-span-2">
                <label for="description" class="font-semibold">Descripción</label>
                <Textarea id="description" v-model="form.description" rows="3" autoResize :invalid="!!form.errors.description" />
                <InlineMessage v-if="form.errors.description" severity="error">{{ form.errors.description }}</InlineMessage>
            </div>

            <div class="flex flex-col gap-2 md:col-span-2">
                <label for="featuresText" class="font-semibold">Puntos que incluye el servicio</label>
                <Textarea
                    id="featuresText"
                    v-model="form.featuresText"
                    rows="5"
                    autoResize
                    placeholder="Un elemento por línea:&#10;Aspirado de interiores&#10;Limpieza de cristales"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Escribe una característica por línea. Se muestran como lista en la landing.
                </p>
                <InlineMessage v-if="form.errors.features" severity="error">{{ form.errors.features }}</InlineMessage>
            </div>

            <!-- Clasificación -->
            <div class="flex flex-col gap-2">
                <label for="type" class="font-semibold">Tipo de lavado</label>
                <Dropdown id="type" v-model="form.type" :options="washTypeOptions" optionLabel="label" optionValue="value" :invalid="!!form.errors.type" />
                <InlineMessage v-if="form.errors.type" severity="error">{{ form.errors.type }}</InlineMessage>
            </div>

            <div class="flex flex-col gap-2">
                <label for="points" class="font-semibold">Puntos de fidelidad</label>
                <InputNumber id="points" v-model="form.points" :min="0" suffix=" pts" :invalid="!!form.errors.points" />
                <InlineMessage v-if="form.errors.points" severity="error">{{ form.errors.points }}</InlineMessage>
            </div>

            <div class="flex flex-col gap-2 justify-center">
                <label for="is_addon" class="font-semibold">¿Es un servicio adicional?</label>
                <div class="flex items-center mt-2 gap-3">
                    <InputSwitch id="is_addon" v-model="isAddon" />
                    <span class="text-sm" :class="isAddon ? 'text-green-600' : 'text-gray-500'">
                        {{ isAddon ? 'Sí, es un extra (encerado, detallado…)' : 'No, es un paquete de lavado' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-2 justify-center">
                <label for="is_active" class="font-semibold">Servicio activo</label>
                <div class="flex items-center mt-2 gap-3">
                    <InputSwitch id="is_active" v-model="form.is_active" />
                    <span class="text-sm" :class="form.is_active ? 'text-green-600' : 'text-gray-500'">
                        {{ form.is_active ? 'Visible en la web' : 'Oculto en la web' }}
                    </span>
                </div>
            </div>

            <!-- Precio base -->
            <div class="flex flex-col gap-2">
                <label for="price" class="font-semibold">Precio base (opcional)</label>
                <InputNumber id="price" v-model="form.price" mode="currency" currency="MXN" locale="es-MX" :invalid="!!form.errors.price" />
                <p class="text-xs text-gray-500 dark:text-gray-400">Si se deja vacío se usa el precio más bajo por categoría.</p>
                <InlineMessage v-if="form.errors.price" severity="error">{{ form.errors.price }}</InlineMessage>
            </div>

            <div class="flex flex-col gap-2">
                <label for="duration_minutes" class="font-semibold">Duración base (minutos)</label>
                <InputNumber id="duration_minutes" v-model="form.duration_minutes" suffix=" min" :invalid="!!form.errors.duration_minutes" />
                <p class="text-xs text-gray-500 dark:text-gray-400">Referencia para el panel; el tiempo real se toma por categoría.</p>
                <InlineMessage v-if="form.errors.duration_minutes" severity="error">{{ form.errors.duration_minutes }}</InlineMessage>
            </div>

            <div class="flex flex-col gap-2">
                <label for="sort_order" class="font-semibold">Orden de aparición</label>
                <InputNumber id="sort_order" v-model="form.sort_order" :min="0" :invalid="!!form.errors.sort_order" />
                <InlineMessage v-if="form.errors.sort_order" severity="error">{{ form.errors.sort_order }}</InlineMessage>
            </div>
        </div>

        <!-- Precio y duración por categoría -->
        <div v-if="!isAddon" class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Precio y tiempo por categoría</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Cada categoría de vehículo puede tener su propio precio y duración.
                    </p>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ vehicleCategories.length }} categorías configuradas</span>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="py-2 pr-4 font-semibold">Categoría</th>
                            <th class="py-2 pr-4 font-semibold">Precio (MXN)</th>
                            <th class="py-2 font-semibold">Duración (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="category in vehicleCategories" :key="category.id" class="border-t border-gray-100 dark:border-gray-700">
                            <td class="py-3 pr-4">
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ category.name }}</span>
                                <span v-if="!category.is_active" class="ml-2 text-xs text-amber-600">(inactiva)</span>
                            </td>
                            <td class="py-3 pr-4">
                                <InputNumber
                                    v-model="form.variants[category.id].price"
                                    mode="currency"
                                    currency="MXN"
                                    locale="es-MX"
                                    :min="0"
                                    :invalid="!!form.errors[`variants.${category.id}.price`]"
                                />
                            </td>
                            <td class="py-3">
                                <InputNumber
                                    v-model="form.variants[category.id].duration_minutes"
                                    suffix=" min"
                                    :min="0"
                                    :invalid="!!form.errors[`variants.${category.id}.duration_minutes`]"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
