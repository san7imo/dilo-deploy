<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import FinanceCharts from "@/Components/Finance/FinanceCharts.vue";
import PaymentModal from "@/Components/Finance/PaymentModal.vue";
import ExpenseModal from "@/Components/Finance/ExpenseModal.vue";
import PaymentsTable from "@/Components/Finance/PaymentsTable.vue";
import ExpensesTable from "@/Components/Finance/ExpensesTable.vue";
import AnalysisFilters from "@/Components/Finance/AnalysisFilters.vue";
import { formatDateES } from "@/utils/date";

const props = defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
});

// UI State
const activeTab = ref("resumen"); // resumen | pagos | gastos | analisis
const showPaymentModal = ref(false);
const showExpenseModal = ref(false);

// Filtros (análisis)
const filterType = ref("all");
const filterYear = ref(null);
const filterMonth = ref(null);
const filterDateFrom = ref(null);
const filterDateTo = ref(null);

// Opciones
const paymentMethodOptions = [
    { value: "transferencia", label: "Transferencia bancaria" },
    { value: "efectivo", label: "Efectivo" },
    { value: "tarjeta", label: "Tarjeta" },
    { value: "paypal", label: "PayPal" },
    { value: "otro", label: "Otro" },
];

// Forms
const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
    amount_original: "",
    currency: "EUR",
    exchange_rate_to_base: 1,
    payment_method: "",
    is_advance: false,
    notes: "",
});

const expenseForm = useForm({
    expense_date: new Date().toISOString().slice(0, 10),
    name: "",
    description: "",
    category: "",
    amount_original: "",
    currency: "EUR",
    exchange_rate_to_base: 1,
});

const statusForm = useForm({
    is_paid: props.event.is_paid ?? false,
});

// Totales
const totals = computed(() => {
    const paid = Number(props.finance?.total_paid_base ?? 0);
    const expenses = Number(props.finance?.total_expenses_base ?? 0);
    const net = paid - expenses;

    return {
        paid,
        expenses,
        net,
        shareLabel: Number(props.finance?.share_label ?? net * 0.3),
        shareArtist: Number(props.finance?.share_artist ?? net * 0.7),
    };
});

// Adaptador para FinanceCharts
const eventArray = computed(() => {
    if (!props.event) return [];
    return [
        {
            ...props.event,
            title: props.event.title || props.event.name || "Evento",
            event_date: props.event.event_date || new Date().toISOString(),
            status: props.event.is_paid ? "pagado" : "pendiente",
            total_paid_base: props.finance?.total_paid_base ?? 0,
            total_expenses_base: props.finance?.total_expenses_base ?? 0,
            net_base:
                (props.finance?.total_paid_base ?? 0) -
                (props.finance?.total_expenses_base ?? 0),
        },
    ];
});

// Helpers
const normalizePaymentCurrencyAndRate = () => {
    const cur = (paymentForm.currency || "").toUpperCase().trim();
    paymentForm.currency = cur || "EUR";

    if (paymentForm.currency === "EUR") {
        paymentForm.exchange_rate_to_base = 1;
    } else if (!paymentForm.exchange_rate_to_base || Number(paymentForm.exchange_rate_to_base) <= 0) {
        paymentForm.exchange_rate_to_base = 1;
    }
};

const normalizeExpenseCurrencyAndRate = () => {
    const cur = (expenseForm.currency || "").toUpperCase().trim();
    expenseForm.currency = cur || "EUR";

    if (expenseForm.currency === "EUR") {
        expenseForm.exchange_rate_to_base = 1;
    } else if (!expenseForm.exchange_rate_to_base || Number(expenseForm.exchange_rate_to_base) <= 0) {
        expenseForm.exchange_rate_to_base = 1;
    }
};

// Actions
const submitPayment = () => {
    normalizePaymentCurrencyAndRate();

    paymentForm.post(route("admin.events.payments.store", props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset("amount_original", "payment_method", "is_advance", "notes");
            showPaymentModal.value = false;
            activeTab.value = "pagos";
        },
    });
};

const deletePayment = (paymentId) => {
    if (!confirm("¿Eliminar este pago?")) return;

    paymentForm.delete(route("admin.events.payments.destroy", paymentId), {
        preserveScroll: true,
    });
};

const submitExpense = () => {
    normalizeExpenseCurrencyAndRate();

    expenseForm.post(route("admin.events.expenses.store", props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            expenseForm.reset("amount_original", "name", "description", "category");
            showExpenseModal.value = false;
            activeTab.value = "gastos";
        },
    });
};

const deleteExpense = (expenseId) => {
    if (!confirm("¿Eliminar este gasto?")) return;

    expenseForm.delete(route("admin.events.expenses.destroy", expenseId), {
        preserveScroll: true,
    });
};

const updatePaymentStatus = () => {
    statusForm.patch(route("admin.events.payment-status.update", props.event.id), {
        preserveScroll: true,
    });
};

// Quick computed for UI
const statusBadge = computed(() => {
    const paid = !!statusForm.is_paid;
    return {
        label: paid ? "Pagado" : "Pendiente",
        className: paid
            ? "bg-green-500/20 text-green-300 border border-green-500/40"
            : "bg-yellow-500/20 text-yellow-300 border border-yellow-500/40",
    };
});

const openPaymentModal = () => {
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    paymentForm.currency = (paymentForm.currency || "EUR").toUpperCase().trim() || "EUR";
    if (paymentForm.currency === "EUR") paymentForm.exchange_rate_to_base = 1;
    showPaymentModal.value = true;
};

const openExpenseModal = () => {
    expenseForm.expense_date = new Date().toISOString().slice(0, 10);
    expenseForm.currency = (expenseForm.currency || "EUR").toUpperCase().trim() || "EUR";
    if (expenseForm.currency === "EUR") expenseForm.exchange_rate_to_base = 1;
    showExpenseModal.value = true;
};
</script>

<template>
    <AdminLayout title="Finanzas">
        <div class="space-y-6 text-white">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">Finanzas — {{ event.title }}</h1>
                    <div class="text-gray-400 text-sm mt-2 space-y-1">
                        <p>
                            Artista principal:
                            <span class="text-white">
                                {{ event.main_artist?.name || event.mainArtist?.name || "—" }}
                            </span>
                        </p>
                        <p>
                            Ubicación:
                            <span class="text-white">
                                <span v-if="event.city || event.country">
                                    {{ event.city }}{{ event.city && event.country ? "," : "" }}
                                    {{ event.country }}
                                    <span v-if="event.location" class="block text-gray-400 text-xs">{{ event.location
                                        }}</span>
                                    <span v-if="event.venue_address" class="block text-gray-500 text-xs">{{
                                        event.venue_address }}</span>
                                </span>
                                <span v-else>
                                    <span class="text-white">{{ event.location || "—" }}</span>
                                    <span v-if="event.venue_address" class="block text-gray-500 text-xs">{{
                                        event.venue_address }}</span>
                                </span>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.events.index')" class="text-[#ffa236] hover:underline">
                        ← Volver
                    </Link>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-[#2a2a2a] pb-3">
                <button type="button" class="tab-btn"
                    :class="activeTab === 'resumen' ? 'tab-btn--active' : 'tab-btn--idle'"
                    @click="activeTab = 'resumen'">
                    Resumen
                </button>
                <button type="button" class="tab-btn"
                    :class="activeTab === 'pagos' ? 'tab-btn--active' : 'tab-btn--idle'" @click="activeTab = 'pagos'">
                    Pagos
                </button>
                <button type="button" class="tab-btn"
                    :class="activeTab === 'gastos' ? 'tab-btn--active' : 'tab-btn--idle'" @click="activeTab = 'gastos'">
                    Gastos
                </button>
                <button type="button" class="tab-btn"
                    :class="activeTab === 'analisis' ? 'tab-btn--active' : 'tab-btn--idle'"
                    @click="activeTab = 'analisis'">
                    Análisis
                </button>
                <div class="flex-1"></div>
                <div class="flex gap-2">
                    <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo pago</button>
                    <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                </div>
            </div>

            <div v-if="activeTab === 'resumen'" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="card">
                        <p class="text-gray-400 text-sm">Total pagado</p>
                        <p class="text-white text-xl font-semibold">€ {{ totals.paid.toFixed(2) }}</p>
                        <p class="text-gray-500 text-xs mt-1">Suma de pagos (convertidos a EUR).</p>
                    </div>
                    <div class="card">
                        <p class="text-gray-400 text-sm">Total gastos</p>
                        <p class="text-white text-xl font-semibold">€ {{ totals.expenses.toFixed(2) }}</p>
                        <p class="text-gray-500 text-xs mt-1">Suma de gastos (convertidos a EUR).</p>
                    </div>
                    <div class="card">
                        <p class="text-gray-400 text-sm">Neto</p>
                        <p class="text-white text-xl font-semibold">€ {{ totals.net.toFixed(2) }}</p>
                        <p class="text-gray-500 text-xs mt-1">Pagos − gastos.</p>
                    </div>
                </div>

                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-lg font-semibold">Estado de pago</h2>
                            <p class="text-sm text-gray-400">Marca si el evento se considera pagado o pendiente.</p>
                        </div>
                        <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusBadge.className]">{{
                            statusBadge.label }}</span>
                    </div>
                    <form @submit.prevent="updatePaymentStatus" class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-300">
                            <input v-model="statusForm.is_paid" type="checkbox" class="checkbox" />
                            Marcar como pagado
                        </label>
                        <div class="flex-1"></div>
                        <button type="submit" class="btn-primary self-start sm:self-auto"
                            :disabled="statusForm.processing">Guardar estado</button>
                    </form>
                    <p v-if="statusForm.errors.is_paid" class="text-red-500 text-sm mt-2">{{ statusForm.errors.is_paid
                        }}</p>
                </div>

                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <h2 class="text-lg font-semibold mb-4">Datos del evento</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <div class="mini-card">
                            <p class="text-gray-400">Ubicación</p>
                            <p class="text-white font-semibold">
                                <span v-if="event.city || event.country">
                                    {{ event.city }}{{ event.city && event.country ? "," : "" }} {{ event.country }}
                                </span>
                                <span v-else>{{ event.location || "—" }}</span>
                            </p>
                            <p v-if="event.location" class="text-gray-400 text-xs mt-1">{{ event.location }}</p>
                            <p v-if="event.venue_address" class="text-gray-500 text-xs mt-1">{{ event.venue_address }}
                            </p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Tipo</p>
                            <p class="text-white font-semibold capitalize">{{ event.event_type || "—" }}</p>
                            <p class="text-gray-400 mt-2">Estado</p>
                            <p class="text-white font-semibold capitalize">{{ event.status || "—" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Fee del show</p>
                            <p class="text-white font-semibold">
                                <span>{{ event.currency || "EUR" }}</span>
                                <span class="ml-1">{{ Number(event.show_fee_total ?? 0).toFixed(2) }}</span>
                            </p>
                            <p class="text-gray-400 mt-2">Moneda</p>
                            <p class="text-white font-semibold">{{ event.currency || "EUR" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">% Anticipo</p>
                            <p class="text-white font-semibold">{{ event.advance_percentage ?? 50 }}%</p>
                            <p class="text-gray-400 mt-2">¿Se espera anticipo?</p>
                            <p class="text-white font-semibold">{{ event.advance_expected ? "Sí" : "No" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Fecha pago final</p>
                            <p class="text-white font-semibold">{{ event.full_payment_due_date ?
                                formatDateES(event.full_payment_due_date) : "—" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Reparto (neto)</p>
                            <div class="mt-1 space-y-1">
                                <p class="text-white font-semibold">Label: € {{ totals.shareLabel.toFixed(2) }}</p>
                                <p class="text-white font-semibold">Artista: € {{ totals.shareArtist.toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'pagos'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Pagos</h2>
                        <p class="text-sm text-gray-400">Total: € {{ totals.paid.toFixed(2) }}</p>
                    </div>
                    <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo pago</button>
                </div>
                <PaymentsTable :payments="event.payments || []" @delete="deletePayment" />
            </div>

            <div v-else-if="activeTab === 'gastos'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Gastos</h2>
                        <p class="text-sm text-gray-400">Total: € {{ totals.expenses.toFixed(2) }}</p>
                    </div>
                    <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                </div>
                <ExpensesTable :expenses="event.expenses || []" @delete="deleteExpense" />
            </div>

            <div v-else class="space-y-6">
                <AnalysisFilters v-model:filter-type="filterType" v-model:filter-date-from="filterDateFrom"
                    v-model:filter-date-to="filterDateTo" v-model:filter-year="filterYear"
                    v-model:filter-month="filterMonth" />
                <FinanceCharts :totals="totals" :events="eventArray" :filter-type="filterType" :filter-year="filterYear"
                    :filter-month="filterMonth" :filter-date-from="filterDateFrom" :filter-date-to="filterDateTo"
                    currency="€" />
            </div>

            <PaymentModal :show="showPaymentModal" :form="paymentForm" :payment-method-options="paymentMethodOptions"
                :normalize-currency="normalizePaymentCurrencyAndRate" @close="showPaymentModal = false"
                @submit="submitPayment" />
            <ExpenseModal :show="showExpenseModal" :form="expenseForm"
                :normalize-currency="normalizeExpenseCurrencyAndRate" @close="showExpenseModal = false"
                @submit="submitExpense" />
        </div>
    </AdminLayout>
</template>

<style scoped>
.input {
    @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.btn-primary {
    @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.btn-secondary {
    @apply bg-[#2a2a2a] hover:bg-[#333333] text-white font-semibold px-4 py-2 rounded-md transition-colors border border-[#3a3a3a];
}

.btn-ghost {
    @apply bg-transparent hover:bg-white/5 text-gray-200 font-semibold px-4 py-2 rounded-md transition-colors border border-[#2a2a2a];
}

.checkbox {
    @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}

.card {
    @apply bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5;
}

.mini-card {
    @apply bg-[#111111] border border-[#2a2a2a] rounded-md p-3;
}

.tab-btn {
    @apply px-3 py-2 rounded-md text-sm transition-colors border;
}

.tab-btn--active {
    @apply bg-[#ffa236] text-black font-semibold border-[#ffa236];
}

.tab-btn--idle {
    @apply bg-[#1d1d1b] text-gray-200 border-[#2a2a2a] hover:bg-[#222222];
}
</style>
