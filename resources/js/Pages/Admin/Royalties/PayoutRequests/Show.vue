<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  payoutRequest: Object,
  payments: Array,
  summary: Object,
});

const form = useForm({
  amount_usd: props.summary?.outstanding_usd ?? 0,
  payment_method: "",
  payment_reference: "",
  paid_at: new Date().toISOString().slice(0, 10),
  description: "",
});

const formatMoney = (value) => {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 6,
  }).format(Number.isNaN(number) ? 0 : number);
};

const submitPayment = () => {
  form.post(route("admin.royalties.payout-requests.payments.store", props.payoutRequest.id), {
    preserveScroll: true,
  });
};
</script>

<template>
  <AdminLayout title="Gestión de pago">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Solicitud #{{ payoutRequest.id }}</h1>
        <p class="text-gray-400 text-sm">{{ payoutRequest.requester_name }} · {{ payoutRequest.requester_email }}</p>
      </div>
      <Link :href="route('admin.royalties.payout-requests.index')" class="text-gray-400 hover:text-white">
        Volver
      </Link>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Solicitado</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(payoutRequest.requested_amount_usd) }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Pagado</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(summary.paid_total_usd) }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Pendiente</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(summary.outstanding_usd) }}</p>
      </div>
    </div>

    <form @submit.prevent="submitPayment" class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg p-4 mb-6 space-y-4">
      <h2 class="text-lg font-semibold text-[#ffa236]">Registrar pago</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-xs uppercase text-gray-400">Monto USD</label>
          <input v-model="form.amount_usd" type="number" step="0.000001" min="0" class="input" />
          <p v-if="form.errors.amount_usd" class="text-red-400 text-xs mt-1">{{ form.errors.amount_usd }}</p>
        </div>
        <div>
          <label class="text-xs uppercase text-gray-400">Fecha de pago</label>
          <input v-model="form.paid_at" type="date" class="input" />
          <p v-if="form.errors.paid_at" class="text-red-400 text-xs mt-1">{{ form.errors.paid_at }}</p>
        </div>
        <div>
          <label class="text-xs uppercase text-gray-400">Método</label>
          <input v-model="form.payment_method" type="text" class="input" placeholder="Transferencia / PayPal / Efectivo" />
        </div>
        <div>
          <label class="text-xs uppercase text-gray-400">Referencia</label>
          <input v-model="form.payment_reference" type="text" class="input" placeholder="ID transacción" />
        </div>
      </div>
      <div>
        <label class="text-xs uppercase text-gray-400">Descripción</label>
        <textarea v-model="form.description" rows="3" class="input"></textarea>
      </div>
      <button type="submit" class="btn-primary" :disabled="form.processing">
        {{ form.processing ? "Guardando..." : "Registrar pago" }}
      </button>
    </form>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Fecha</th>
            <th class="px-4 py-2 text-left">Monto USD</th>
            <th class="px-4 py-2 text-left">Método</th>
            <th class="px-4 py-2 text-left">Referencia</th>
            <th class="px-4 py-2 text-left">Registrado por</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="payment in payments"
            :key="payment.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ payment.paid_at || "-" }}</td>
            <td class="px-4 py-3">{{ formatMoney(payment.amount_usd) }}</td>
            <td class="px-4 py-3">{{ payment.payment_method || "-" }}</td>
            <td class="px-4 py-3">{{ payment.payment_reference || "-" }}</td>
            <td class="px-4 py-3">{{ payment.created_by || "-" }}</td>
          </tr>
          <tr v-if="!payments.length">
            <td colspan="5" class="px-4 py-6 text-center text-gray-400">
              No hay pagos registrados para esta solicitud.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply w-full bg-[#151515] border border-[#2a2a2a] rounded px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors disabled:opacity-50;
}
</style>
