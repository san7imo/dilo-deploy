<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    event: { type: Object, required: true },
    finance: { type: Object, default: () => ({}) },
});

const paymentMethodOptions = [
    { value: "transferencia", label: "Transferencia bancaria" },
    { value: "efectivo", label: "Efectivo" },
    { value: "tarjeta", label: "Tarjeta" },
    { value: "paypal", label: "PayPal" },
    { value: "otro", label: "Otro" },
];

const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
    amount_original: "",
    currency: "EUR",
    exchange_rate_to_base: 1,
    payment_method: "",
    is_advance: false,
});

const statusForm = useForm({
    is_paid: props.event.is_paid ?? false,
});

const normalizeCurrencyAndRate = () => {
    const cur = (paymentForm.currency || "").toUpperCase().trim();
    paymentForm.currency = cur || "EUR";

    if (paymentForm.currency === "EUR") {
        paymentForm.exchange_rate_to_base = 1;
    } else {
        // si el usuario lo borró o quedó en 0, dale un valor base
        if (!paymentForm.exchange_rate_to_base || Number(paymentForm.exchange_rate_to_base) <= 0) {
            paymentForm.exchange_rate_to_base = 1;
        }
    }
};

const formatDateES = (dateStr) => {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    if (Number.isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

const submitPayment = () => {
    normalizeCurrencyAndRate();

    paymentForm.post(route("admin.events.payments.store", props.event.id), {
        preserveScroll: true,
        onSuccess: () => paymentForm.reset("amount_original", "payment_method", "is_advance"),
    });
};

const deletePayment = (paymentId) => {
    if (!confirm("¿Eliminar este pago?")) return;
    paymentForm.delete(route("admin.events.payments.destroy", paymentId), {
        preserveScroll: true,
    });
};

const updatePaymentStatus = () => {
    statusForm.patch(route("admin.events.payment-status.update", props.event.id), {
        preserveScroll: true,
        onError: () => {
            // no-op, errores ya mapeados en statusForm.errors
        },
    });
};
</script>

<template>
    <AdminLayout title="Finanzas">
        <div class="max-w-5xl mx-auto space-y-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">💶 Finanzas — {{ event.title }}</h1>
                    <p class="text-gray-400 text-sm">
                        Artista principal:
                        <span class="text-white">
                            {{ event.main_artist?.name || event.mainArtist?.name || "—" }}
                        </span>
                    </p>
                </div>
                <Link :href="route('admin.events.index')" class="text-[#ffa236] hover:underline">
                    ← Volver
                </Link>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <p class="text-gray-400 text-sm">Total pagado (EUR)</p>
                    <p class="text-2xl font-bold">€ {{ (finance.total_paid_base ?? 0).toFixed(2) }}</p>
                </div>

                <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-5">
                    <p class="text-gray-400 text-sm">Anticipo pagado (EUR)</p>
                    <p class="text-2xl font-bold">€ {{ (finance.advance_paid_base ?? 0).toFixed(2) }}</p>
                </div>
            </div>

            <!-- Estado de pago manual -->
            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-lg font-semibold">Estado de pago</h2>
                        <p class="text-sm text-gray-400">Marca si el evento se considera pagado o pendiente.</p>
                    </div>
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold',
                            statusForm.is_paid ? 'bg-green-500/20 text-green-300 border border-green-500/40' : 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/40',
                        ]"
                    >
                        {{ statusForm.is_paid ? "Pagado" : "Pendiente" }}
                    </span>
                </div>

                <form @submit.prevent="updatePaymentStatus" class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-300">
                        <input v-model="statusForm.is_paid" type="checkbox" class="checkbox" />
                        Marcar como pagado
                    </label>

                    <div class="flex-1"></div>

                    <button type="submit" class="btn-primary self-start sm:self-auto" :disabled="statusForm.processing">
                        Guardar estado
                    </button>
                </form>

                <p v-if="statusForm.errors.is_paid" class="text-red-500 text-sm mt-2">
                    {{ statusForm.errors.is_paid }}
                </p>
            </div>

            <!-- Registrar pago -->
            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                <h2 class="text-lg font-semibold mb-4">Registrar pago</h2>

                <form @submit.prevent="submitPayment" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-300 text-sm">Fecha</label>
                        <input v-model="paymentForm.payment_date" type="date" class="input" />
                        <p v-if="paymentForm.errors.payment_date" class="text-red-500 text-sm mt-1">
                            {{ paymentForm.errors.payment_date }}
                        </p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm">Monto</label>
                        <input v-model="paymentForm.amount_original" type="number" step="0.01" class="input" />
                        <p v-if="paymentForm.errors.amount_original" class="text-red-500 text-sm mt-1">
                            {{ paymentForm.errors.amount_original }}
                        </p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm">Moneda (ISO)</label>
                        <input v-model="paymentForm.currency" @blur="normalizeCurrencyAndRate" type="text" class="input"
                            placeholder="EUR / USD / COP..." />
                        <p class="text-gray-500 text-xs mt-1">Ej: EUR, USD, COP (3 letras).</p>
                    </div>

                    <!-- Tasa: solo si NO es EUR -->
                    <div v-if="paymentForm.currency !== 'EUR'">
                        <label class="text-gray-300 text-sm">Tasa a EUR</label>
                        <input v-model="paymentForm.exchange_rate_to_base" type="number" step="0.000001"
                            class="input" />
                        <p class="text-gray-500 text-xs mt-1">
                            Ej: si 1 USD = 0.92 EUR, pon 0.92
                        </p>
                    </div>

                    <div v-else>
                        <label class="text-gray-300 text-sm">Tasa a EUR</label>
                        <input :value="1" disabled class="input opacity-60" />
                        <p class="text-gray-500 text-xs mt-1">EUR no requiere tasa.</p>
                    </div>

                    <div>
                        <label class="text-gray-300 text-sm">Método de pago</label>
                        <select v-model="paymentForm.payment_method" class="input">
                            <option value="" disabled>Selecciona un método</option>
                            <option v-for="opt in paymentMethodOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 mt-6">
                        <input v-model="paymentForm.is_advance" type="checkbox" class="checkbox" />
                        <span class="text-gray-300 text-sm">¿Es anticipo?</span>
                    </div>

                    <div class="sm:col-span-2 flex justify-end mt-2">
                        <button class="btn-primary" type="submit" :disabled="paymentForm.processing">
                            Guardar pago
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista pagos -->
            <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-6">
                <h2 class="text-lg font-semibold mb-4">Pagos</h2>

                <div v-if="!event.payments || event.payments.length === 0" class="text-gray-400">
                    No hay pagos todavía.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-300">
                        <thead class="text-xs uppercase text-gray-400">
                            <tr>
                                <th class="py-2 text-left">Fecha</th>
                                <th class="py-2 text-left">Original</th>
                                <th class="py-2 text-left">EUR</th>
                                <th class="py-2 text-left">Anticipo</th>
                                <th class="py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in event.payments" :key="p.id" class="border-t border-[#2a2a2a]">
                                <td class="py-2">{{ formatDateES(p.payment_date) }}</td>
                                <td class="py-2">
                                    {{ p.currency }} {{ Number(p.amount_original).toFixed(2) }}
                                </td>
                                <td class="py-2">
                                    € {{ Number(p.amount_base).toFixed(2) }}
                                </td>
                                <td class="py-2">{{ p.is_advance ? "Sí" : "No" }}</td>
                                <td class="py-2 text-right">
                                    <button @click="deletePayment(p.id)" class="text-red-400 hover:underline">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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

.checkbox {
    @apply w-4 h-4 rounded bg-[#0f0f0f] border border-[#2a2a2a] text-[#ffa236] focus:ring-[#ffa236];
}
</style>
