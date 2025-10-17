<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash.debounce';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import InlineMessage from 'primevue/inlinemessage';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

const props = defineProps({
    promotions: Object,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();

const menu = ref();
const search = ref(props.filters.search);
const activePromotionMenuItems = ref([]);
const isModalVisible = ref(false);

// Formulario para editar dentro del modal
const form = useForm({
    id: null,
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


watch(search, debounce((value) => {
    router.get(route('admin.promotions.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));


const openModal = (promotion) => {
    form.defaults({
        ...promotion,
        expires_at: promotion.expires_at ? new Date(promotion.expires_at) : null
    }).reset();
    isModalVisible.value = true;
};

const updatePromotion = () => {
    form.put(route('admin.promotions.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            isModalVisible.value = false;
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Promoción actualizada.', life: 3000 });
            form.reset();
        },
        onError: () => {
            // Los errores se mostrarán automáticamente en el formulario
        }
    });
};


const toggleMenu = (event, promotion) => {
    activePromotionMenuItems.value = [
        { label: 'Ver / Editar', icon: 'pi pi-fw pi-pencil', command: () => openModal(promotion) },
        { label: 'Eliminar', icon: 'pi pi-fw pi-trash', command: () => confirmDelete(promotion) }
    ];
    menu.value.toggle(event);
};

const onRowClick = (event) => {
    if (event.originalEvent.target.closest('button')) return;
    openModal(event.data);
};

const getStatusSeverity = (isActive) => isActive ? 'success' : 'danger';
const formatDiscount = (promotion) => promotion.discount_type === 'Fijo' ? `$${parseFloat(promotion.discount_value).toFixed(2)}` : `${parseInt(promotion.discount_value)}%`;
const formatUses = (promotion) => `${promotion.current_uses} / ${promotion.max_uses || '∞'}`;
const formatDate = (dateString) => dateString ? new Date(dateString).toLocaleDateString() : 'Nunca';

const confirmDelete = (promotion) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar esta promoción?',
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Eliminar',
        accept: () => {
            router.delete(route('admin.promotions.destroy', promotion.id), {
                preserveScroll: true,
                 onSuccess: () => toast.add({ severity: 'success', summary: 'Éxito', detail: 'Promoción eliminada.', life: 3000 }),
                 onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar la promoción.', life: 3000 }),
            });
        }
    });
};

</script>

<template>
    <AppLayout title="Gestionar Promociones">
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <!-- Header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-slate-100 font-bold">Gestión de Promociones</h1>
                </div>
                <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                     <span class="p-input-icon-left flex items-center space-x-2">
                        <i class="pi pi-search" />
                        <InputText v-model="search" placeholder="Buscar por código" class="w-full"/>
                    </span>
                     <Link :href="route('admin.promotions.create')">
                        <Button label="Crear Promoción" icon="pi pi-plus" severity="success" />
                    </Link>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 relative">
                <!-- Tabla para PC -->
                <div class="hidden md:block">
                    <DataTable :value="promotions.data" @row-click="onRowClick" rowHover class="cursor-pointer" tableStyle="min-width: 50rem">
                         <Column field="code" header="Código" sortable>
                            <template #body="slotProps"><div class="font-semibold text-sky-600 dark:text-sky-400">{{ slotProps.data.code }}</div></template>
                         </Column>
                         <Column field="description" header="Descripción" sortable></Column>
                         <Column header="Descuento" sortable><template #body="slotProps">{{ formatDiscount(slotProps.data) }}</template></Column>
                         <Column header="Usos (actual/máx)"><template #body="slotProps">{{ formatUses(slotProps.data) }}</template></Column>
                         <Column header="Expira" sortable><template #body="slotProps">{{ formatDate(slotProps.data.expires_at) }}</template></Column>
                         <Column header="Estado" sortable>
                            <template #body="slotProps"><Tag :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'" :severity="getStatusSeverity(slotProps.data.is_active)" /></template>
                         </Column>
                         <Column header="Acciones" style="width: 5rem; text-align: center;">
                            <template #body="slotProps">
                                <Button icon="pi pi-ellipsis-v" text rounded @click.stop="toggleMenu($event, slotProps.data)" aria-haspopup="true" />
                                <Menu ref="menu" :model="activePromotionMenuItems" :popup="true" />
                            </template>
                         </Column>
                    </DataTable>
                </div>

                <!-- Tarjetas para móvil -->
                 <div class="md:hidden p-4 grid gap-4 grid-cols-1">
                    <div v-if="promotions.data.length > 0" class="space-y-3">
                        <div v-for="promo in promotions.data" :key="promo.id" @click="openModal(promo)" class="block bg-slate-50 dark:bg-slate-700/50 p-4 rounded-lg shadow border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow duration-200 cursor-pointer">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-lg text-sky-600 dark:text-sky-400">{{ promo.code }}</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ promo.description }}</p>
                                </div>
                                <div>
                                    <Button icon="pi pi-ellipsis-v" text rounded @click.stop="toggleMenu($event, promo)" aria-haspopup="true" />
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between items-center text-sm">
                               <div class="font-semibold text-slate-700 dark:text-slate-200">
                                   {{ formatDiscount(promo) }} OFF
                               </div>
                               <div class="text-slate-500 dark:text-slate-400">
                                   Usos: {{ formatUses(promo) }}
                               </div>
                               <Tag :value="promo.is_active ? 'Activo' : 'Inactivo'" :severity="getStatusSeverity(promo.is_active)" />
                            </div>
                        </div>
                    </div>
                     <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                        No se encontraron promociones.
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="promotions.total > 0 && promotions.data.length > 0" class="p-4">
                     <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Mostrando {{ promotions.from }} a {{ promotions.to }} de {{ promotions.total }}</p>
                        <div v-if="promotions.links.length > 3" class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in promotions.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-700 rounded-md" v-html="link.label" />
                                <Link v-else class="mr-1 mb-1 px-3 py-2 text-sm border rounded-md transition" :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700': !link.active }" :href="link.url" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal para Ver/Editar -->
        <Dialog v-model:visible="isModalVisible" modal header="Detalles de la Promoción" :style="{ width: '30rem' }">
            <form @submit.prevent="updatePromotion" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <label for="code-modal" class="font-semibold">Código</label>
                        <InputText id="code-modal" v-model="form.code" :invalid="!!form.errors.code" />
                        <InlineMessage v-if="form.errors.code" severity="error">{{ form.errors.code }}</InlineMessage>
                    </div>
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label for="description-modal" class="font-semibold">Descripción (Opcional)</label>
                        <Textarea id="description-modal" v-model="form.description" rows="2" autoResize :invalid="!!form.errors.description" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="discount_type-modal" class="font-semibold">Tipo de Descuento</label>
                        <Dropdown id="discount_type-modal" v-model="form.discount_type" :options="discountTypeOptions" optionLabel="label" optionValue="value" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="discount_value-modal" class="font-semibold">Valor del Descuento</label>
                        <InputNumber id="discount_value-modal" v-model="form.discount_value" :prefix="form.discount_type === 'Fijo' ? '$' : ''" :suffix="form.discount_type === 'Porcentaje' ? '%' : ''" :maxFractionDigits="2" :invalid="!!form.errors.discount_value" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="max_uses-modal" class="font-semibold">Límite de Usos (Opcional)</label>
                        <InputNumber id="max_uses-modal" v-model="form.max_uses" placeholder="Sin límite" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="expires_at-modal" class="font-semibold">Fecha de Expiración (Opcional)</label>
                        <Calendar id="expires_at-modal" v-model="form.expires_at" showIcon iconDisplay="input" dateFormat="dd/mm/yy" />
                    </div>
                    <div class="flex items-center gap-3 md:col-span-2">
                        <InputSwitch id="is_active-modal" v-model="form.is_active" />
                        <label for="is_active-modal" class="font-semibold">Promoción Activa</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-8 border-t dark:border-gray-700 pt-6">
                    <Button type="button" label="Cancelar" severity="secondary" @click="isModalVisible = false"></Button>
                    <Button type="submit" label="Guardar Cambios" icon="pi pi-check" :loading="form.processing"></Button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

