<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import FinanceCharts from "@/Components/Finance/FinanceCharts.vue";
import PaymentModal from "@/Components/Finance/PaymentModal.vue";
import ExpenseModal from "@/Components/Finance/ExpenseModal.vue";
import PersonalExpenseModal from "@/Components/Finance/PersonalExpenseModal.vue";
import PaymentsTable from "@/Components/Finance/PaymentsTable.vue";
import ExpensesTable from "@/Components/Finance/ExpensesTable.vue";
import PersonalExpensesTable from "@/Components/Finance/PersonalExpensesTable.vue";
import { formatDateES } from "@/utils/date";
import { formatAmount, formatMoney, formatMoneyWithSymbol } from "@/utils/money";

const props = defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
    collaborators: { type: Array, default: () => [] },
});

const { props: pageProps } = usePage();
const roleNames = computed(() => pageProps.auth?.user?.role_names || []);
const isAdmin = computed(() => roleNames.value.includes("admin"));
const isRoadManager = computed(() => roleNames.value.includes("roadmanager"));

// UI State
const activeTab = ref("resumen"); // resumen | pagos | gastos | gastos-personales | analisis
const showPaymentModal = ref(false);
const showExpenseModal = ref(false);
const showPersonalExpenseModal = ref(false);
const editingPayment = ref(null);
const editingExpense = ref(null);

// Opciones
const paymentMethodOptions = [
    { value: "transferencia", label: "Transferencia bancaria" },
    { value: "efectivo", label: "Efectivo" },
    { value: "tarjeta", label: "Tarjeta" },
    { value: "paypal", label: "PayPal" },
    { value: "otro", label: "Otro" },
];

const personalExpenseMethodOptions = [
    { value: "transferencia", label: "Transferencia bancaria" },
    { value: "efectivo", label: "Efectivo" },
    { value: "tercero", label: "Enviado a otra persona" },
    { value: "otro", label: "Otro" },
];

// Forms
const toDateInputValue = (value) => {
    if (!value) return "";
    const raw = String(value);
    if (raw.includes("T")) return raw.split("T")[0];
    if (raw.includes(" ")) return raw.split(" ")[0];
    return raw;
};

const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
    amount_original: "",
    currency: "USD",
    exchange_rate_to_base: 1,
    payment_method: "",
    collaborator_id: "",
    is_advance: false,
    notes: "",
    receipt_file: null,
});

const expenseForm = useForm({
    expense_date: new Date().toISOString().slice(0, 10),
    name: "",
    description: "",
    category: "",
    receipt_file: null,
    amount_original: "",
    currency: "USD",
    exchange_rate_to_base: 1,
});

const personalExpenseForm = useForm({
    expense_date: new Date().toISOString().slice(0, 10),
    expense_type: "",
    name: "",
    description: "",
    payment_method: "",
    recipient: "",
    amount_original: "",
    currency: "USD",
    exchange_rate_to_base: 1,
    receipt_file: null,
});

const eventMetaForm = useForm({
    status: props.event.status || "",
    event_date: toDateInputValue(props.event.event_date),
    full_payment_due_date: toDateInputValue(props.event.full_payment_due_date),
});

const confirmForm = useForm({
    confirmed: true,
});

const roadManagers = computed(() => {
    return props.event.road_managers || props.event.roadManagers || [];
});

const personalExpenses = computed(() => {
    return props.event.personal_expenses || props.event.personalExpenses || [];
});

const editingPersonalExpense = ref(null);

const currentRoadManager = computed(() => {
    if (!isRoadManager.value) return null;
    const userId = pageProps.auth?.user?.id;
    return roadManagers.value.find((rm) => rm.id === userId) || null;
});

const roadManagerConfirmedAt = computed(() => {
    return currentRoadManager.value?.pivot?.payment_confirmed_at || null;
});

const roadManagerDue = computed(() => {
    const fee = Number(props.event.show_fee_total ?? 0);
    const advancePct = Number(props.event.advance_percentage ?? 0);
    const pct = Number.isFinite(advancePct) ? advancePct : 0;
    const normalizedPct = Math.min(Math.max(pct, 0), 100);
    const due = fee * (1 - normalizedPct / 100);
    return Number.isFinite(due) ? due : 0;
});

watch(
    isAdmin,
    (value) => {
        if (!value) {
            activeTab.value = "pagos";
        }
    },
    { immediate: true }
);

const artistSharePct = computed(() => {
    const value = Number(props.event?.artist_share_percentage ?? 70);
    return Number.isFinite(value) ? value : 70;
});

const labelSharePct = computed(() => {
    const value = Number(props.event?.label_share_percentage ?? (100 - artistSharePct.value));
    return Number.isFinite(value) ? value : Math.max(0, 100 - artistSharePct.value);
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
        shareLabel: Number(props.finance?.share_label ?? net * (labelSharePct.value / 100)),
        shareArtist: Number(props.finance?.share_artist ?? net * (artistSharePct.value / 100)),
    };
});

const totalPersonalExpenses = computed(() =>
    Number(props.finance?.total_personal_expenses_base ?? 0)
);

const shareArtistAfterPersonal = computed(() => {
    if (typeof props.finance?.share_artist_after_personal !== "undefined") {
        return Number(props.finance?.share_artist_after_personal ?? 0);
    }
    return Math.max(totals.value.shareArtist - totalPersonalExpenses.value, 0);
});

const personalRemaining = computed(() =>
    Math.max(totals.value.shareArtist - totalPersonalExpenses.value, 0)
);

const personalRemainingForModal = computed(() => {
    if (!editingPersonalExpense.value) return personalRemaining.value;
    const current = Number(editingPersonalExpense.value.amount_base ?? 0);
    return Math.max(personalRemaining.value + current, 0);
});

const roadManagerTotals = computed(() => ({
    paid: Number(props.finance?.total_paid_base ?? 0),
    expenses: Number(props.finance?.total_expenses_base ?? 0),
}));

const totalPaidBase = computed(() => Number(props.finance?.total_paid_base ?? 0));
const showFeeTotal = computed(() => Number(props.event.show_fee_total ?? 0));
const canMarkPaid = computed(() => showFeeTotal.value > 0 && totalPaidBase.value >= showFeeTotal.value);
const paidShortfall = computed(() => {
    if (showFeeTotal.value <= 0) return 0;
    return Math.max(showFeeTotal.value - totalPaidBase.value, 0);
});
const paidOverage = computed(() => {
    if (showFeeTotal.value <= 0) return 0;
    return Math.max(totalPaidBase.value - showFeeTotal.value, 0);
});
const autoPaidBadge = computed(() => {
    if (showFeeTotal.value <= 0) {
        return {
            label: "Sin fee",
            className: "bg-gray-500/20 text-gray-300 border border-gray-500/30",
        };
    }
    const paid = canMarkPaid.value;
    return {
        label: paid ? "Pagado" : "Pendiente",
        className: paid
            ? "bg-green-500/20 text-green-300 border border-green-500/40"
            : "bg-yellow-500/20 text-yellow-300 border border-yellow-500/40",
    };
});
const paidStatusAlert = computed(() => {
    if (showFeeTotal.value <= 0) {
        return "Define el fee del show para calcular el estado de ingresos.";
    }
    if (!canMarkPaid.value) {
        return `Faltan $${formatAmount(paidShortfall.value)} USD para completar el ingreso.`;
    }
    if (paidOverage.value > 0) {
        return `Ingreso completo. Excedente: $${formatAmount(paidOverage.value)} USD.`;
    }
    return "Ingreso completo.";
});

const formatCurrency = (value, currency = "USD") => formatMoney(value, currency);
const formatUsd = (value) => formatMoneyWithSymbol(value, "$");
const formatSignedUsd = (value) => {
    const sign = value >= 0 ? "+" : "-";
    return `${sign} $${formatAmount(Math.abs(value))}`;
};

const eventStatusValue = computed(() => {
    const raw = eventMetaForm.status || props.event.status || "";
    return raw.toString().trim().toLowerCase();
});

const eventStatusBadge = computed(() => {
    const status = eventStatusValue.value;
    if (!status) {
        return {
            label: "Sin estado",
            className: "bg-gray-500/20 text-gray-300 border border-gray-500/30",
        };
    }

    const labels = {
        cotizado: "Cotizado",
        reservado: "Reservado",
        confirmado: "Confirmado",
        pospuesto: "Pospuesto",
        pagado: "Pagado",
        cancelado: "Cancelado",
    };

    const classes = {
        cotizado: "bg-gray-500/20 text-gray-200 border border-gray-500/30",
        reservado: "bg-yellow-500/20 text-yellow-300 border border-yellow-500/30",
        confirmado: "bg-blue-500/20 text-blue-300 border border-blue-500/30",
        pospuesto: "bg-orange-500/20 text-orange-300 border border-orange-500/30",
        pagado: "bg-green-500/20 text-green-300 border border-green-500/30",
        cancelado: "bg-red-500/20 text-red-300 border border-red-500/30",
    };

    return {
        label: labels[status] || status,
        className: classes[status] || "bg-gray-500/20 text-gray-300 border border-gray-500/30",
    };
});

const statusAlert = computed(() => {
    const status = eventStatusValue.value;
    if (status === "pospuesto") {
        return {
            title: "Evento pospuesto",
            message: "Actualiza la nueva fecha y confirma el plan con el equipo.",
            className: "bg-orange-500/10 border border-orange-500/30 text-orange-200",
        };
    }
    if (status === "cancelado") {
        return {
            title: "Evento cancelado",
            message: "Revisa ingresos y gastos para cerrar la contabilidad.",
            className: "bg-red-500/10 border border-red-500/30 text-red-200",
        };
    }
    return null;
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
            total_personal_expenses_base: props.finance?.total_personal_expenses_base ?? 0,
            net_base:
                (props.finance?.total_paid_base ?? 0) -
                (props.finance?.total_expenses_base ?? 0),
            label_share_estimated_base: props.finance?.share_label ?? 0,
            artist_share_estimated_base: props.finance?.share_artist ?? 0,
            artist_share_after_personal_base:
                props.finance?.share_artist_after_personal ??
                props.finance?.share_artist_after_personal_base ??
                0,
        },
    ];
});

// Helpers
const normalizePaymentCurrencyAndRate = () => {
    const cur = (paymentForm.currency || "").toUpperCase().trim();
    paymentForm.currency = cur || "USD";

    if (paymentForm.currency === "USD") {
        paymentForm.exchange_rate_to_base = 1;
    } else if (!paymentForm.exchange_rate_to_base || Number(paymentForm.exchange_rate_to_base) <= 0) {
        paymentForm.exchange_rate_to_base = 1;
    }
};

const normalizeExpenseCurrencyAndRate = () => {
    const cur = (expenseForm.currency || "").toUpperCase().trim();
    expenseForm.currency = cur || "USD";

    if (expenseForm.currency === "USD") {
        expenseForm.exchange_rate_to_base = 1;
    } else if (!expenseForm.exchange_rate_to_base || Number(expenseForm.exchange_rate_to_base) <= 0) {
        expenseForm.exchange_rate_to_base = 1;
    }
};

const normalizePersonalExpenseCurrencyAndRate = () => {
    const cur = (personalExpenseForm.currency || "").toUpperCase().trim();
    personalExpenseForm.currency = cur || "USD";

    if (personalExpenseForm.currency === "USD") {
        personalExpenseForm.exchange_rate_to_base = 1;
    } else if (
        !personalExpenseForm.exchange_rate_to_base ||
        Number(personalExpenseForm.exchange_rate_to_base) <= 0
    ) {
        personalExpenseForm.exchange_rate_to_base = 1;
    }
};

// Actions
const submitPayment = () => {
    normalizePaymentCurrencyAndRate();

    const isEditing = !!editingPayment.value;
    const routeName = isEditing
        ? route("admin.events.payments.update", editingPayment.value.id)
        : route("admin.events.payments.store", props.event.id);
    paymentForm[isEditing ? "put" : "post"](routeName, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset(
                "amount_original",
                "payment_method",
                "collaborator_id",
                "is_advance",
                "notes",
                "receipt_file"
            );
            editingPayment.value = null;
            showPaymentModal.value = false;
            activeTab.value = "pagos";
        },
    });
};

const deletePayment = (paymentId) => {
    if (!isAdmin.value) return;
    if (!confirm("¿Eliminar este ingreso?")) return;

    paymentForm.delete(route("admin.events.payments.destroy", paymentId), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingPayment.value?.id === paymentId) {
                editingPayment.value = null;
                showPaymentModal.value = false;
            }
        },
    });
};

const submitExpense = () => {
    normalizeExpenseCurrencyAndRate();

    const isEditing = !!editingExpense.value;
    const routeName = isEditing
        ? route("admin.events.expenses.update", editingExpense.value.id)
        : route("admin.events.expenses.store", props.event.id);
    expenseForm[isEditing ? "put" : "post"](routeName, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            expenseForm.reset("amount_original", "name", "description", "category", "receipt_file");
            editingExpense.value = null;
            showExpenseModal.value = false;
            activeTab.value = "gastos";
        },
    });
};

const deleteExpense = (expenseId) => {
    if (!isAdmin.value) return;
    if (!confirm("¿Eliminar este gasto?")) return;

    expenseForm.delete(route("admin.events.expenses.destroy", expenseId), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingExpense.value?.id === expenseId) {
                editingExpense.value = null;
                showExpenseModal.value = false;
            }
        },
    });
};

const submitPersonalExpense = () => {
    normalizePersonalExpenseCurrencyAndRate();

    const isEditing = !!editingPersonalExpense.value;
    const routeName = isEditing
        ? route("admin.events.personal-expenses.update", editingPersonalExpense.value.id)
        : route("admin.events.personal-expenses.store", props.event.id);
    personalExpenseForm[isEditing ? "put" : "post"](routeName, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            personalExpenseForm.reset(
                "amount_original",
                "expense_type",
                "name",
                "description",
                "payment_method",
                "recipient",
                "receipt_file"
            );
            editingPersonalExpense.value = null;
            showPersonalExpenseModal.value = false;
            activeTab.value = "gastos-personales";
        },
    });
};

const deletePersonalExpense = (expenseId) => {
    if (!isAdmin.value) return;
    if (!confirm("¿Eliminar este pago al artista?")) return;

    personalExpenseForm.delete(route("admin.events.personal-expenses.destroy", expenseId), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingPersonalExpense.value?.id === expenseId) {
                editingPersonalExpense.value = null;
                showPersonalExpenseModal.value = false;
            }
        },
    });
};

const updateEventDetails = () => {
    eventMetaForm.patch(route("admin.events.details.update", props.event.id), {
        preserveScroll: true,
    });
};

const confirmRoadManagerPayment = () => {
    if (confirmForm.processing) return;
    if (roadManagerConfirmedAt.value) return;

    confirmForm.patch(route("admin.events.roadmanager-payment.update", props.event.id), {
        preserveScroll: true,
    });
};

const canSubmitEventDetails = computed(() => eventStatusValue.value !== "pagado" || canMarkPaid.value);

const openPaymentModal = () => {
    editingPayment.value = null;
    paymentForm.clearErrors();
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    paymentForm.currency = (paymentForm.currency || "USD").toUpperCase().trim() || "USD";
    if (paymentForm.currency === "USD") paymentForm.exchange_rate_to_base = 1;
    paymentForm.collaborator_id = "";
    paymentForm.receipt_file = null;
    showPaymentModal.value = true;
};

const openExpenseModal = () => {
    editingExpense.value = null;
    expenseForm.clearErrors();
    expenseForm.expense_date = new Date().toISOString().slice(0, 10);
    expenseForm.currency = (expenseForm.currency || "USD").toUpperCase().trim() || "USD";
    if (expenseForm.currency === "USD") expenseForm.exchange_rate_to_base = 1;
    expenseForm.receipt_file = null;
    showExpenseModal.value = true;
};

const editPayment = (payment) => {
    if (!payment) return;
    editingPayment.value = payment;
    paymentForm.clearErrors();
    paymentForm.payment_date = toDateInputValue(payment.payment_date) || new Date().toISOString().slice(0, 10);
    paymentForm.amount_original = payment.amount_original ?? "";
    paymentForm.currency = (payment.currency || "USD").toUpperCase().trim() || "USD";
    paymentForm.exchange_rate_to_base =
        payment.exchange_rate_to_base ?? paymentForm.exchange_rate_to_base ?? 1;
    if (paymentForm.currency === "USD") paymentForm.exchange_rate_to_base = 1;
    paymentForm.payment_method = payment.payment_method || "";
    paymentForm.collaborator_id = payment.collaborator_id ?? "";
    paymentForm.is_advance = !!payment.is_advance;
    paymentForm.notes = payment.notes || "";
    paymentForm.receipt_file = null;
    showPaymentModal.value = true;
};

const editExpense = (expense) => {
    if (!expense) return;
    editingExpense.value = expense;
    expenseForm.clearErrors();
    expenseForm.expense_date = toDateInputValue(expense.expense_date) || new Date().toISOString().slice(0, 10);
    expenseForm.name = expense.name || "";
    expenseForm.description = expense.description || "";
    expenseForm.category = expense.category || "";
    expenseForm.amount_original = expense.amount_original ?? "";
    expenseForm.currency = (expense.currency || "USD").toUpperCase().trim() || "USD";
    expenseForm.exchange_rate_to_base =
        expense.exchange_rate_to_base ?? expenseForm.exchange_rate_to_base ?? 1;
    if (expenseForm.currency === "USD") expenseForm.exchange_rate_to_base = 1;
    expenseForm.receipt_file = null;
    showExpenseModal.value = true;
};

const openPersonalExpenseModal = () => {
    if (personalRemaining.value <= 0) {
        alert(`No puedes agregar más pagos al artista porque el ${artistSharePct.value}% del artista ya está en 0.`);
        return;
    }

    editingPersonalExpense.value = null;
    personalExpenseForm.clearErrors();
    personalExpenseForm.expense_date = new Date().toISOString().slice(0, 10);
    personalExpenseForm.expense_type = "";
    personalExpenseForm.name = "";
    personalExpenseForm.description = "";
    personalExpenseForm.payment_method = "";
    personalExpenseForm.recipient = "";
    personalExpenseForm.amount_original = "";
    personalExpenseForm.currency =
        (personalExpenseForm.currency || "USD").toUpperCase().trim() || "USD";
    personalExpenseForm.exchange_rate_to_base = 1;
    if (personalExpenseForm.currency === "USD") personalExpenseForm.exchange_rate_to_base = 1;
    personalExpenseForm.receipt_file = null;
    showPersonalExpenseModal.value = true;
};

const editPersonalExpense = (expense) => {
    if (!expense) return;

    editingPersonalExpense.value = expense;
    personalExpenseForm.clearErrors();
    personalExpenseForm.expense_date = expense.expense_date || new Date().toISOString().slice(0, 10);
    personalExpenseForm.expense_type = expense.expense_type || "";
    personalExpenseForm.name = expense.name || "";
    personalExpenseForm.description = expense.description || "";
    personalExpenseForm.payment_method = expense.payment_method || "";
    personalExpenseForm.recipient = expense.recipient || "";
    personalExpenseForm.amount_original = expense.amount_original ?? "";
    personalExpenseForm.currency = (expense.currency || "USD").toUpperCase().trim() || "USD";
    personalExpenseForm.exchange_rate_to_base =
        expense.exchange_rate_to_base ?? personalExpenseForm.exchange_rate_to_base ?? 1;
    personalExpenseForm.receipt_file = null;
    showPersonalExpenseModal.value = true;
};

const closePersonalExpenseModal = () => {
    showPersonalExpenseModal.value = false;
    editingPersonalExpense.value = null;
};
</script>

<template>
    <AdminLayout title="Finanzas">
        <div class="space-y-6 text-white">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold">Finanzas — {{ event.title }}</h1>
                        <span v-if="!isRoadManager" :class="[
                            'px-3 py-1 text-xs font-semibold rounded-full capitalize border',
                            autoPaidBadge.className
                        ]">
                            {{ autoPaidBadge.label }}
                        </span>
                    </div>
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

            <div v-if="!isRoadManager" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Fee negociado</p>
                    <p class="text-white text-xl font-semibold">
                        {{ formatCurrency(showFeeTotal, event.currency || "USD") }}
                    </p>
                    <p class="text-gray-500 text-xs mt-1">Total acordado para el show.</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Total ingresado</p>
                    <p class="text-white text-xl font-semibold">{{ formatUsd(totals.paid) }}</p>
                    <p class="text-gray-500 text-xs mt-1">Ingresos registrados (USD).</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Gastos del evento</p>
                    <p class="text-red-400 text-xl font-semibold">{{ formatUsd(totals.expenses) }}</p>
                    <p class="text-gray-500 text-xs mt-1">Gastos generales (USD).</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Neto del evento</p>
                    <p class="text-white text-xl font-semibold">{{ formatUsd(totals.net) }}</p>
                    <p class="text-gray-500 text-xs mt-1">Ingresos − gastos.</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Disquera ({{ labelSharePct }}%)
                    </p>
                    <p class="text-white text-xl font-semibold">{{ formatUsd(totals.shareLabel) }}</p>
                    <p class="text-gray-500 text-xs mt-1">Sobre neto (USD).</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Artista ({{ artistSharePct }}%) antes
                    </p>
                    <p class="text-[#ffa236] text-xl font-semibold">{{ formatUsd(totals.shareArtist) }}</p>
                    <p class="text-gray-500 text-xs mt-1">Sobre neto (USD).</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Pagos al artista</p>
                    <p class="text-red-400 text-xl font-semibold">{{ formatUsd(totalPersonalExpenses) }}</p>
                    <p class="text-gray-500 text-xs mt-1">Descuento al {{ artistSharePct }}%.</p>
                </div>
                <div class="card">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">
                        Artista ({{ artistSharePct }}%) después
                    </p>
                    <p class="text-[#ffa236] text-xl font-semibold">{{ formatUsd(shareArtistAfterPersonal) }}</p>
                    <p class="text-gray-500 text-xs mt-1">
                        {{ artistSharePct }}% − pagos al artista.
                    </p>
                </div>
            </div>

            <div v-if="isRoadManager" class="space-y-6">
                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">Ingreso pendiente al road manager</h2>
                            <p class="text-sm text-gray-400">
                                Corresponde al porcentaje restante despues del adelanto.
                            </p>
                            <p class="text-2xl font-semibold text-[#ffa236] mt-2">
                                {{ formatCurrency(roadManagerDue, event.currency || "USD") }}
                            </p>
                        </div>
                        <div class="text-sm text-gray-300">
                            <button
                                v-if="!roadManagerConfirmedAt"
                                type="button"
                                class="btn-primary"
                                :disabled="confirmForm.processing"
                                @click="confirmRoadManagerPayment"
                            >
                                Confirmar ingreso recibido
                            </button>
                            <div v-else class="text-green-400 font-semibold">
                                Ingreso confirmado
                                <span v-if="roadManagerConfirmedAt" class="block text-xs text-gray-400 mt-1">
                                    {{ formatDateES(roadManagerConfirmedAt) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold">Información del evento</h2>
                            <p class="text-sm text-gray-400">Detalles generales del show.</p>
                        </div>
                    </div>
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
                            <p v-if="event.venue_address" class="text-gray-500 text-xs mt-1">{{ event.venue_address }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Tipo de evento</p>
                            <p class="text-white font-semibold capitalize">{{ event.event_type || "—" }}</p>
                            <p class="text-gray-400 mt-2">Estado</p>
                            <span :class="['inline-flex px-2 py-1 rounded-full text-xs font-semibold border mt-1', eventStatusBadge.className]">
                                {{ eventStatusBadge.label }}
                            </span>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Fecha del evento</p>
                            <p class="text-white font-semibold">{{ event.event_date ? formatDateES(event.event_date) : "—" }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="card">
                        <p class="text-gray-400 text-xs uppercase tracking-wide">Total ingresos registrados por ti</p>
                        <p class="text-white text-xl font-semibold">{{ formatUsd(roadManagerTotals.paid) }}</p>
                    </div>
                    <div class="card">
                        <p class="text-gray-400 text-xs uppercase tracking-wide">Total gastos registrados por ti</p>
                        <p class="text-red-400 text-xl font-semibold">{{ formatUsd(roadManagerTotals.expenses) }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                        <div>
                            <h2 class="text-lg font-semibold">Tus ingresos</h2>
                            <p class="text-sm text-gray-400">Total: {{ formatUsd(roadManagerTotals.paid) }}</p>
                        </div>
                        <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo ingreso</button>
                    </div>
                <PaymentsTable
                    :payments="event.payments || []"
                    :can-edit="isAdmin"
                    :can-delete="false"
                    :show-collaborator="isAdmin"
                    :show-creator="isAdmin"
                    @edit="editPayment"
                    @delete="deletePayment"
                />
                </div>

                <div class="space-y-4">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                        <div>
                            <h2 class="text-lg font-semibold">Tus gastos</h2>
                            <p class="text-sm text-gray-400">Total: {{ formatUsd(roadManagerTotals.expenses) }}</p>
                        </div>
                        <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                    </div>
                <ExpensesTable
                    :expenses="event.expenses || []"
                    :can-edit="isAdmin"
                    :can-delete="false"
                    :show-creator="isAdmin"
                    @edit="editExpense"
                    @delete="deleteExpense"
                />
                </div>
            </div>

            <div v-else>
                <div v-if="statusAlert" :class="['rounded-lg p-4 text-sm', statusAlert.className]">
                    <p class="font-semibold">{{ statusAlert.title }}</p>
                    <p class="opacity-90 mt-1">{{ statusAlert.message }}</p>
                </div>

                <div class="flex flex-wrap gap-2 border-b border-[#2a2a2a] pb-3">
                    <button v-if="isAdmin" type="button" class="tab-btn"
                        :class="activeTab === 'resumen' ? 'tab-btn--active' : 'tab-btn--idle'"
                        @click="activeTab = 'resumen'">
                        Resumen
                    </button>
                    <button type="button" class="tab-btn"
                        :class="activeTab === 'pagos' ? 'tab-btn--active' : 'tab-btn--idle'" @click="activeTab = 'pagos'">
                        Ingresos
                    </button>
                    <button type="button" class="tab-btn"
                        :class="activeTab === 'gastos' ? 'tab-btn--active' : 'tab-btn--idle'" @click="activeTab = 'gastos'">
                        Gastos
                    </button>
                    <button v-if="isAdmin" type="button" class="tab-btn"
                        :class="activeTab === 'gastos-personales' ? 'tab-btn--active' : 'tab-btn--idle'"
                        @click="activeTab = 'gastos-personales'">
                        Pagos al artista
                    </button>
                    <button v-if="isAdmin" type="button" class="tab-btn"
                        :class="activeTab === 'analisis' ? 'tab-btn--active' : 'tab-btn--idle'"
                        @click="activeTab = 'analisis'">
                        Análisis
                    </button>
                    <div class="flex-1"></div>
                    <div class="flex gap-2">
                        <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo ingreso</button>
                        <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                    </div>
                </div>

                <div v-if="isAdmin && activeTab === 'resumen'" class="space-y-6">
                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold">Estado y fechas</h2>
                            <p class="text-sm text-gray-400">Actualiza el estado del evento y fechas clave.</p>
                        </div>
                        <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', eventStatusBadge.className]">
                            {{ eventStatusBadge.label }}
                        </span>
                    </div>
                    <form @submit.prevent="updateEventDetails" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="text-gray-300 text-sm">Estado</label>
                            <select v-model="eventMetaForm.status" class="input">
                                <option value="">Selecciona un estado</option>
                                <option value="cotizado">Cotizado</option>
                                <option value="reservado">Reservado</option>
                                <option value="confirmado">Confirmado</option>
                                <option value="pospuesto">Pospuesto</option>
                                <option value="pagado" :disabled="!canMarkPaid">Pagado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                            <p v-if="eventMetaForm.errors.status" class="text-red-500 text-sm mt-1">
                                {{ eventMetaForm.errors.status }}
                            </p>
                            <p v-else-if="eventStatusValue === 'pagado' && paidStatusAlert"
                                class="text-amber-300 text-xs mt-1">
                                {{ paidStatusAlert }}
                            </p>
                        </div>
                        <div>
                            <label class="text-gray-300 text-sm">Fecha del evento</label>
                            <input v-model="eventMetaForm.event_date" type="date" class="input" />
                            <p v-if="eventMetaForm.errors.event_date" class="text-red-500 text-sm mt-1">
                                {{ eventMetaForm.errors.event_date }}
                            </p>
                        </div>
                        <div>
                            <label class="text-gray-300 text-sm">Fecha de ingreso final</label>
                            <input v-model="eventMetaForm.full_payment_due_date" type="date" class="input" />
                            <p v-if="eventMetaForm.errors.full_payment_due_date" class="text-red-500 text-sm mt-1">
                                {{ eventMetaForm.errors.full_payment_due_date }}
                            </p>
                        </div>
                        <div class="sm:col-span-3 flex justify-end">
                            <button type="submit" class="btn-primary"
                                :disabled="eventMetaForm.processing || !canSubmitEventDetails">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h2 class="text-lg font-semibold">Estado de ingresos</h2>
                            <p class="text-sm text-gray-400">
                                Automático según el total ingresado vs el fee negociado.
                            </p>
                        </div>
                        <span :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold border',
                            autoPaidBadge.className
                        ]">
                            {{ autoPaidBadge.label }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="mini-card">
                            <p class="text-gray-400">Diferencia vs fee</p>
                            <p :class="[
                                'text-lg font-semibold',
                                canMarkPaid ? 'text-green-300' : 'text-yellow-300'
                            ]">
                                {{ canMarkPaid ? formatSignedUsd(paidOverage) : formatSignedUsd(-paidShortfall) }}
                            </p>
                            <p class="text-gray-500 text-xs mt-1">
                                Se calcula con el fee negociado y el total pagado.
                            </p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Nota</p>
                            <p class="text-gray-300 text-sm mt-1">
                                {{ paidStatusAlert }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold">Datos del evento</h2>
                            <p class="text-sm text-gray-400">Información general y condiciones del show.</p>
                        </div>
                    </div>
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
                            <p class="text-gray-400">Tipo de evento</p>
                            <p class="text-white font-semibold capitalize">{{ event.event_type || "—" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Fee del show</p>
                            <p class="text-white font-semibold">
                                <span>{{ event.currency || "USD" }}</span>
                                <span class="ml-1">{{ formatAmount(event.show_fee_total ?? 0) }}</span>
                            </p>
                            <p class="text-gray-400 mt-2">Moneda</p>
                            <p class="text-white font-semibold">{{ event.currency || "USD" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">% Anticipo</p>
                            <p class="text-white font-semibold">{{ event.advance_percentage ?? 50 }}%</p>
                            <p class="text-gray-400 mt-2">¿Se espera anticipo?</p>
                            <p class="text-white font-semibold">{{ event.advance_expected ? "Sí" : "No" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Fecha de ingreso final</p>
                            <p class="text-white font-semibold">{{ event.full_payment_due_date ?
                                formatDateES(event.full_payment_due_date) : "—" }}</p>
                        </div>
                        <div class="mini-card">
                            <p class="text-gray-400">Fecha del evento</p>
                            <p class="text-white font-semibold">{{ event.event_date ? formatDateES(event.event_date) : "—" }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="roadManagers.length" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                    <h2 class="text-lg font-semibold mb-4">Confirmacion road managers</h2>
                    <div class="space-y-3 text-sm">
                        <div
                            v-for="rm in roadManagers"
                            :key="rm.id"
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-[#2a2a2a] pb-2"
                        >
                            <div>
                                <p class="text-white font-semibold">{{ rm.name }}</p>
                                <p class="text-gray-400 text-xs">{{ rm.email }}</p>
                            </div>
                            <div class="text-sm">
                                <span v-if="rm.pivot?.payment_confirmed_at" class="text-green-400 font-semibold">
                                    Confirmado
                                    <span class="block text-xs text-gray-400">
                                        {{ formatDateES(rm.pivot.payment_confirmed_at) }}
                                    </span>
                                </span>
                                <span v-else class="text-yellow-400 font-semibold">Pendiente</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'pagos'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Ingresos</h2>
                        <p class="text-sm text-gray-400">Total: {{ formatUsd(totals.paid) }}</p>
                    </div>
                    <button type="button" class="btn-primary" @click="openPaymentModal">+ Nuevo ingreso</button>
                </div>
                <PaymentsTable
                    :payments="event.payments || []"
                    :can-edit="isAdmin"
                    :can-delete="isAdmin"
                    :show-collaborator="isAdmin"
                    :show-creator="isAdmin"
                    @edit="editPayment"
                    @delete="deletePayment"
                />
            </div>

            <div v-else-if="activeTab === 'gastos'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Gastos</h2>
                        <p class="text-sm text-gray-400">Total: {{ formatUsd(totals.expenses) }}</p>
                    </div>
                    <button type="button" class="btn-secondary" @click="openExpenseModal">+ Nuevo gasto</button>
                </div>
                <ExpensesTable
                    :expenses="event.expenses || []"
                    :can-edit="isAdmin"
                    :can-delete="isAdmin"
                    :show-creator="isAdmin"
                    @edit="editExpense"
                    @delete="deleteExpense"
                />
            </div>

            <div v-else-if="isAdmin && activeTab === 'gastos-personales'" class="space-y-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Pagos al artista</h2>
                        <p class="text-sm text-gray-400">
                            Total: {{ formatUsd(totalPersonalExpenses) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ artistSharePct }}% disponible: {{ formatUsd(personalRemaining) }} ·
                            {{ artistSharePct }}% luego de pagos: {{
                                formatUsd(shareArtistAfterPersonal) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-secondary"
                        :disabled="personalRemaining <= 0"
                        @click="openPersonalExpenseModal"
                    >
                        + Agregar pago al artista
                    </button>
                </div>

                <div v-if="personalRemaining <= 0" class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4">
                    <p class="text-amber-200 text-sm font-semibold">
                        No puedes agregar más pagos al artista porque el {{ artistSharePct }}% del artista ya está en 0.
                    </p>
                </div>

                <PersonalExpensesTable
                    :expenses="personalExpenses"
                    :can-edit="isAdmin"
                    :can-delete="isAdmin"
                    @edit="editPersonalExpense"
                    @delete="deletePersonalExpense"
                />
            </div>

            <div v-else-if="isAdmin" class="space-y-6">
                <FinanceCharts :totals="totals" :events="eventArray" currency="$" />
            </div>
            </div>

            <PaymentModal
                :show="showPaymentModal"
                :form="paymentForm"
                :payment-method-options="paymentMethodOptions"
                :collaborators="collaborators"
                :show-collaborator="isAdmin"
                :normalize-currency="normalizePaymentCurrencyAndRate"
                :is-editing="!!editingPayment"
                @close="showPaymentModal = false" @submit="submitPayment" />
            <ExpenseModal :show="showExpenseModal" :form="expenseForm"
                :normalize-currency="normalizeExpenseCurrencyAndRate" :is-editing="!!editingExpense"
                @close="showExpenseModal = false" @submit="submitExpense" />
            <PersonalExpenseModal
                :show="showPersonalExpenseModal"
                :form="personalExpenseForm"
                :payment-method-options="personalExpenseMethodOptions"
                :normalize-currency="normalizePersonalExpenseCurrencyAndRate"
                :remaining="personalRemainingForModal"
                :is-editing="!!editingPersonalExpense"
                @close="closePersonalExpenseModal"
                @submit="submitPersonalExpense"
            />
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
