<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash.debounce';

// PrimeVue
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    expenses: { type: Object, required: true },
    filters: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
    categoryBreakdown: { type: Array, default: () => [] },
    monthlyTrend: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(`${value}T00:00:00`);
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const today = () => new Date().toISOString().slice(0, 10);

// ---------------------------------------------------------------- Filtros
const filterForm = reactive({
    from: props.filters.from,
    to: props.filters.to,
    category: props.filters.category,
    search: props.filters.search,
});

const applyFilters = () => {
    router.get(
        route('admin.expenses.index'),
        {
            from: filterForm.from || undefined,
            to: filterForm.to || undefined,
            category: filterForm.category || undefined,
            search: filterForm.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const resetFilters = () => {
    const now = new Date();
    filterForm.from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
    filterForm.to = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);
    filterForm.category = null;
    filterForm.search = '';
    applyFilters();
};

watch(
    () => filterForm.search,
    debounce(() => applyFilters(), 400)
);

// ---------------------------------------------------------------- KPIs
const kpiCards = computed(() => [
    {
        label: 'Gastos del mes',
        value: formatCurrency(props.summary.monthTotal),
        hint: `${props.summary.monthCount} movimientos registrados`,
        classes: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 0c-1.11 0-2.08-.402-2.599-1M12 4V3m0 18v-1',
    },
    {
        label: 'Total del rango',
        value: formatCurrency(props.summary.filteredTotal),
        hint: 'Según los filtros aplicados',
        classes: 'bg-cloud-50 text-cloud-600 dark:bg-cloud-900/40 dark:text-cloud-300',
        icon: 'M9 17v-2a4 4 0 014-4h4m0 0l-3-3m3 3l-3 3M4 7h6a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2z',
    },
    {
        label: 'Promedio por gasto',
        value: formatCurrency(props.summary.average),
        hint: `${props.summary.filteredCount} gastos en el rango`,
        classes: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
        icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
    },
    {
        label: 'Categoría principal',
        value: props.summary.topCategory || 'Sin datos',
        hint: props.summary.topCategory ? formatCurrency(props.summary.topCategoryTotal) : 'Registra tu primer gasto',
        classes: 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300',
        icon: 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z',
    },
]);

const trendChartData = computed(() => ({
    labels: props.monthlyTrend.map((month) => month.label),
    datasets: [
        {
            label: 'Gastos',
            data: props.monthlyTrend.map((month) => month.total),
            backgroundColor: 'rgba(244, 63, 94, 0.75)',
            hoverBackgroundColor: 'rgba(244, 63, 94, 1)',
            borderRadius: 8,
            maxBarThickness: 38,
        },
    ],
}));

const trendChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(58, 68, 78, 0.94)',
            padding: 12,
            cornerRadius: 12,
            callbacks: { label: (context) => ` ${formatCurrency(context.parsed.y)}` },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#a3b8c8', font: { size: 10 } }, border: { display: false } },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(163, 184, 200, 0.2)' },
            ticks: {
                color: '#a3b8c8',
                font: { size: 10 },
                callback: (value) =>
                    new Intl.NumberFormat('es-MX', { notation: 'compact', maximumFractionDigits: 1 }).format(value),
            },
            border: { display: false },
        },
    },
}));

const categoryMax = computed(() => Math.max(...props.categoryBreakdown.map((row) => row.total), 1));

const categoryTagClass = (category) => {
    const map = {
        Insumos: 'bg-cloud-100 text-cloud-700 dark:bg-cloud-900/40 dark:text-cloud-300',
        Combustible: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        Mantenimiento: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        Renta: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        Sueldos: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300',
        Servicios: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        Marketing: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/40 dark:text-fuchsia-300',
        Impuestos: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    };

    return map[category] ?? 'bg-mist-100 text-mist-600 dark:bg-slate-700 dark:text-slate-300';
};

// ---------------------------------------------------------------- Formulario
const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    concept: '',
    category: 'Insumos',
    amount: '',
    expense_date: today(),
    payment_method: 'Efectivo',
    supplier: '',
    notes: '',
});

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.expense_date = today();
    form.category = props.categories[0] ?? 'Insumos';
    form.payment_method = props.paymentMethods[0] ?? 'Efectivo';
    showForm.value = true;
};

const openEdit = (expense) => {
    editingId.value = expense.id;
    form.clearErrors();
    form.concept = expense.concept;
    form.category = expense.category;
    form.amount = expense.amount;
    form.expense_date = expense.expense_date;
    form.payment_method = expense.payment_method || 'Efectivo';
    form.supplier = expense.supplier || '';
    form.notes = expense.notes || '';
    showForm.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            toast.add({
                severity: 'success',
                summary: editingId.value ? 'Gasto actualizado' : 'Gasto registrado',
                detail: 'Las finanzas se actualizaron correctamente.',
                life: 3000,
            });
        },
        onError: () =>
            toast.add({
                severity: 'error',
                summary: 'Revisa el formulario',
                detail: 'Faltan datos obligatorios o no son válidos.',
                life: 4000,
            }),
    };

    if (editingId.value) {
        form.put(route('admin.expenses.update', editingId.value), options);
    } else {
        form.post(route('admin.expenses.store'), options);
    }
};

const confirmDelete = (expense) => {
    confirm.require({
        message: `¿Eliminar el gasto «${expense.concept}»?`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Eliminar',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () =>
            router.delete(route('admin.expenses.destroy', expense.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Gasto eliminado', life: 2500 }),
            }),
    });
};
</script>

<template>
    <Head title="Gastos" />

    <AppLayout title="Gastos">
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-mist-400 dark:text-slate-500">
                        Finanzas · WashApp
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-mist-900 dark:text-white sm:text-3xl">Gastos</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-slate-400">
                        Registra y clasifica los gastos para calcular la utilidad neta del negocio.
                    </p>
                </div>
                <Button label="Nuevo gasto" icon="pi pi-plus" size="small" @click="openCreate" />
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mist-400 dark:text-slate-500">
                                {{ card.label }}
                            </p>
                            <p class="mt-2 truncate text-2xl font-semibold text-mist-900 dark:text-white">{{ card.value }}</p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl" :class="card.classes">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-4 text-xs text-mist-400 dark:text-slate-500">{{ card.hint }}</p>
                </div>
            </div>

            <!-- Tendencia + categorías -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90 xl:col-span-2"
                >
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Gastos por mes</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Últimos seis meses</p>
                    <div class="mt-4" style="height: 240px">
                        <Chart type="bar" :data="trendChartData" :options="trendChartOptions" style="height: 100%" />
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
                >
                    <h2 class="text-base font-semibold text-mist-900 dark:text-white">Por categoría</h2>
                    <p class="text-xs text-mist-400 dark:text-slate-500">Según los filtros aplicados</p>

                    <div v-if="categoryBreakdown.length" class="mt-5 space-y-4">
                        <div v-for="row in categoryBreakdown" :key="row.category">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-mist-700 dark:text-slate-200">{{ row.category }}</span>
                                <span class="whitespace-nowrap text-xs font-semibold text-mist-500 dark:text-slate-300">
                                    {{ formatCurrency(row.total) }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-rose-400 to-amber-400"
                                    :style="{ width: `${Math.max((row.total / categoryMax) * 100, 4)}%` }"
                                ></div>
                            </div>
                            <p class="mt-1 text-[11px] text-mist-400 dark:text-slate-500">
                                {{ row.expenses_count }} movimiento{{ row.expenses_count === 1 ? '' : 's' }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="mt-10 text-center text-sm text-mist-400 dark:text-slate-500">
                        <i class="pi pi-wallet mb-2 block text-2xl"></i>
                        Sin gastos en este rango.
                    </div>
                </div>
            </div>

            <!-- Filtros + tabla -->
            <div
                class="rounded-3xl border border-white/70 bg-white/85 shadow-soft backdrop-blur dark:border-slate-800 dark:bg-gray-800/90"
            >
                <div
                    class="flex flex-col gap-3 border-b border-mist-100 px-5 py-4 dark:border-slate-700 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <h2 class="text-base font-semibold text-mist-900 dark:text-white">Movimientos</h2>
                        <p class="text-xs text-mist-400 dark:text-slate-500">Filtra por fecha, categoría o proveedor.</p>
                    </div>

                    <div class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Desde</label>
                            <input
                                v-model="filterForm.from"
                                type="date"
                                class="rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Hasta</label>
                            <input
                                v-model="filterForm.to"
                                type="date"
                                class="rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div class="min-w-[10rem]">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Categoría</label>
                            <Select
                                v-model="filterForm.category"
                                :options="categories"
                                placeholder="Todas"
                                showClear
                                size="small"
                                class="w-full"
                                @change="applyFilters"
                            />
                        </div>
                        <div class="min-w-[11rem]">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-mist-400">Buscar</label>
                            <input
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Concepto o proveedor"
                                class="w-full rounded-xl border-mist-200 bg-white text-xs text-mist-700 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <Button label="Aplicar" icon="pi pi-filter" size="small" @click="applyFilters" />
                        <Button label="Limpiar" icon="pi pi-times" size="small" text severity="secondary" @click="resetFilters" />
                    </div>
                </div>

                <div v-if="expenses.data.length">
                    <!-- Tabla -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-mist-50/60 text-[11px] uppercase tracking-wide text-mist-400 dark:bg-slate-800/60 dark:text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-semibold">Fecha</th>
                                    <th class="px-5 py-3 font-semibold">Concepto</th>
                                    <th class="px-5 py-3 font-semibold">Categoría</th>
                                    <th class="px-5 py-3 font-semibold">Pago</th>
                                    <th class="px-5 py-3 font-semibold">Registró</th>
                                    <th class="px-5 py-3 text-right font-semibold">Monto</th>
                                    <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-100 dark:divide-slate-700">
                                <tr
                                    v-for="expense in expenses.data"
                                    :key="expense.id"
                                    class="transition hover:bg-mist-50/60 dark:hover:bg-slate-800/40"
                                >
                                    <td class="whitespace-nowrap px-5 py-3.5 align-top text-mist-600 dark:text-slate-300">
                                        {{ formatDate(expense.expense_date) }}
                                    </td>
                                    <td class="px-5 py-3.5 align-top">
                                        <p class="font-medium text-mist-800 dark:text-slate-100">{{ expense.concept }}</p>
                                        <p v-if="expense.supplier" class="text-xs text-mist-400 dark:text-slate-500">
                                            {{ expense.supplier }}
                                        </p>
                                        <p v-if="expense.notes" class="mt-0.5 max-w-xs truncate text-xs text-mist-400 dark:text-slate-500">
                                            {{ expense.notes }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="categoryTagClass(expense.category)">
                                            {{ expense.category }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 align-top text-mist-500 dark:text-slate-400">
                                        {{ expense.payment_method || '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 align-top text-mist-500 dark:text-slate-400">
                                        {{ expense.created_by || '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right align-top font-semibold text-rose-600 dark:text-rose-300">
                                        {{ formatCurrency(expense.amount) }}
                                    </td>
                                    <td class="px-5 py-3.5 align-top">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button
                                                icon="pi pi-pencil"
                                                text
                                                rounded
                                                size="small"
                                                severity="secondary"
                                                aria-label="Editar gasto"
                                                @click="openEdit(expense)"
                                            />
                                            <Button
                                                icon="pi pi-trash"
                                                text
                                                rounded
                                                size="small"
                                                severity="danger"
                                                aria-label="Eliminar gasto"
                                                @click="confirmDelete(expense)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div class="divide-y divide-mist-100 md:hidden dark:divide-slate-700">
                        <div v-for="expense in expenses.data" :key="expense.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-mist-900 dark:text-white">{{ expense.concept }}</p>
                                    <p class="text-xs text-mist-400 dark:text-slate-500">{{ formatDate(expense.expense_date) }}</p>
                                </div>
                                <p class="whitespace-nowrap font-semibold text-rose-600 dark:text-rose-300">
                                    {{ formatCurrency(expense.amount) }}
                                </p>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="categoryTagClass(expense.category)">
                                    {{ expense.category }}
                                </span>
                                <span class="text-[11px] text-mist-400 dark:text-slate-500">{{ expense.payment_method || 'Sin método' }}</span>
                            </div>
                            <div class="mt-3 flex justify-end gap-1">
                                <Button
                                    label="Editar"
                                    icon="pi pi-pencil"
                                    text
                                    size="small"
                                    severity="secondary"
                                    @click="openEdit(expense)"
                                />
                                <Button
                                    label="Eliminar"
                                    icon="pi pi-trash"
                                    text
                                    size="small"
                                    severity="danger"
                                    @click="confirmDelete(expense)"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="expenses.total > 0" class="border-t border-mist-100 px-5 py-4 dark:border-slate-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-mist-500 dark:text-slate-400">
                                Mostrando de <span class="font-medium">{{ expenses.from }}</span> a
                                <span class="font-medium">{{ expenses.to }}</span> de
                                <span class="font-medium">{{ expenses.total }}</span> gastos
                            </p>
                            <div v-if="expenses.links.length > 3" class="flex flex-wrap gap-1">
                                <template v-for="(link, key) in expenses.links" :key="key">
                                    <span
                                        v-if="link.url === null"
                                        class="rounded-lg bg-mist-100 px-3 py-1.5 text-xs text-mist-400 dark:bg-slate-700 dark:text-slate-500"
                                        v-html="link.label"
                                    />
                                    <Link
                                        v-else
                                        class="rounded-lg border px-3 py-1.5 text-xs transition"
                                        :class="
                                            link.active
                                                ? 'border-cloud-600 bg-cloud-600 text-white'
                                                : 'border-mist-200 bg-white text-mist-600 hover:bg-mist-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                        "
                                        :href="link.url"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="px-5 py-16 text-center text-sm text-mist-400 dark:text-slate-500">
                    <i class="pi pi-inbox mb-2 block text-2xl"></i>
                    No hay gastos registrados con estos filtros.
                </div>
            </div>
        </div>

        <!-- Diálogo de alta / edición -->
        <Dialog
            v-model:visible="showForm"
            modal
            :header="editingId ? 'Editar gasto' : 'Nuevo gasto'"
            :style="{ width: '36rem' }"
            :breakpoints="{ '640px': '96vw' }"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                        Concepto
                    </label>
                    <input
                        v-model="form.concept"
                        type="text"
                        placeholder="Ej. Shampoo para autos, gasolina, renta…"
                        class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                    <p v-if="form.errors.concept" class="mt-1 text-xs text-red-500">{{ form.errors.concept }}</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                            Categoría
                        </label>
                        <Select v-model="form.category" :options="categories" class="w-full" />
                        <p v-if="form.errors.category" class="mt-1 text-xs text-red-500">{{ form.errors.category }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                            Monto
                        </label>
                        <input
                            v-model="form.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                            Fecha del gasto
                        </label>
                        <input
                            v-model="form.expense_date"
                            type="date"
                            class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                        />
                        <p v-if="form.errors.expense_date" class="mt-1 text-xs text-red-500">{{ form.errors.expense_date }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                            Método de pago
                        </label>
                        <Select v-model="form.payment_method" :options="paymentMethods" placeholder="Selecciona" class="w-full" />
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                        Proveedor (opcional)
                    </label>
                    <input
                        v-model="form.supplier"
                        type="text"
                        placeholder="Nombre del proveedor"
                        class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-slate-400">
                        Notas (opcional)
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="Detalles adicionales del gasto"
                        class="w-full rounded-xl border-mist-200 bg-white text-sm text-mist-800 shadow-sm focus:border-cloud-400 focus:ring-cloud-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-mist-100 pt-4 dark:border-slate-700">
                    <Button label="Cancelar" text severity="secondary" type="button" @click="showForm = false" />
                    <Button
                        :label="editingId ? 'Guardar cambios' : 'Registrar gasto'"
                        icon="pi pi-check"
                        type="submit"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
