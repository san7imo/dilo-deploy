<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import BackNavButton from "@/Components/BackNavButton.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  worker: { type: Object, required: true },
  payments: { type: Object, required: true },
  summary: { type: Object, required: true },
});

const form = useForm({
  concept: "",
  payment_method: "",
  payment_date: new Date().toISOString().slice(0, 10),
  amount_usd: "",
  description: "",
  receipt_file: null,
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingPaymentId = ref(null);

const openDeleteModal = (paymentId) => {
  pendingPaymentId.value = paymentId;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingPaymentId.value = null;
};

const handleSubmit = () => {
  form.post(route("admin.workers.payroll-payments.store", props.worker.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => form.reset("concept", "payment_method", "amount_usd", "description", "receipt_file"),
  });
};

const handleDelete = () => {
  if (!pendingPaymentId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.payroll-payments.destroy", pendingPaymentId.value), {
    preserveScroll: true,
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};

const formatUsd = (value) => {
  const amount = Number(value ?? 0);
  if (!Number.isFinite(amount)) return "$0.00";

  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};

const formatDate = (value) => {
  if (!value) return "-";
  return new Date(`${value}T00:00:00`).toLocaleDateString("es-CO");
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-white">Nómina · {{ worker.full_name }}</h1>
          <p class="text-sm text-gray-400">{{ worker.position || "Sin cargo" }} · {{ worker.document_number || "Sin documento" }}</p>
        </div>
        <BackNavButton :href="route('admin.workers.index')" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Mensual</p>
          <p class="mt-2 text-xl font-semibold text-white">{{ formatUsd(summary.month_usd) }}</p>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Trimestral</p>
          <p class="mt-2 text-xl font-semibold text-white">{{ formatUsd(summary.three_months_usd) }}</p>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Semestral</p>
          <p class="mt-2 text-xl font-semibold text-white">{{ formatUsd(summary.six_months_usd) }}</p>
        </div>
        <div class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4">
          <p class="text-xs text-gray-500 uppercase">Anual</p>
          <p class="mt-2 text-xl font-semibold text-white">{{ formatUsd(summary.year_usd) }}</p>
        </div>
      </div>

      <section class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4 space-y-4">
        <h2 class="text-lg font-semibold text-[#ffa236]">Registrar pago de nómina</h2>

        <form class="space-y-4" @submit.prevent="handleSubmit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-gray-300">Concepto</label>
              <input v-model="form.concept" type="text" class="input" placeholder="Concepto del pago" />
              <p v-if="form.errors.concept" class="text-red-500 text-sm mt-1">{{ form.errors.concept }}</p>
            </div>
            <div>
              <label class="text-sm text-gray-300">Método de pago</label>
              <input v-model="form.payment_method" type="text" class="input" placeholder="Transferencia, efectivo, etc." />
              <p v-if="form.errors.payment_method" class="text-red-500 text-sm mt-1">{{ form.errors.payment_method }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm text-gray-300">Fecha</label>
              <input v-model="form.payment_date" type="date" class="input" />
              <p v-if="form.errors.payment_date" class="text-red-500 text-sm mt-1">{{ form.errors.payment_date }}</p>
            </div>
            <div>
              <label class="text-sm text-gray-300">Monto (USD)</label>
              <input v-model="form.amount_usd" type="number" min="0.01" step="0.01" class="input" placeholder="0.00" />
              <p v-if="form.errors.amount_usd" class="text-red-500 text-sm mt-1">{{ form.errors.amount_usd }}</p>
            </div>
          </div>

          <div>
            <label class="text-sm text-gray-300">Descripción</label>
            <textarea v-model="form.description" rows="3" class="input" placeholder="Descripción (opcional)"></textarea>
            <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
          </div>

          <div>
            <label class="text-sm text-gray-300">Soporte (imagen o PDF)</label>
            <input
              type="file"
              class="input-file"
              accept=".jpg,.jpeg,.png,.webp,.pdf,application/pdf,image/*"
              @change="(e) => { form.receipt_file = e.target.files[0] || null; }"
            />
            <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG, WEBP o PDF. Máximo 10 MB.</p>
            <p v-if="form.errors.receipt_file" class="text-red-500 text-sm mt-1">{{ form.errors.receipt_file }}</p>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors disabled:opacity-50"
              :disabled="form.processing"
            >
              {{ form.processing ? "Guardando..." : "Registrar pago" }}
            </button>
          </div>
        </form>
      </section>

      <section class="rounded-xl border border-[#2a2a2a] bg-[#1d1d1b] p-4 space-y-4">
        <h2 class="text-lg font-semibold text-[#ffa236]">Histórico de pagos</h2>

        <div class="overflow-x-auto">
          <table class="w-full text-sm text-gray-300">
            <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
              <tr>
                <th class="py-3 px-4">Fecha</th>
                <th class="py-3 px-4">Concepto</th>
                <th class="py-3 px-4">Método</th>
                <th class="py-3 px-4">Descripción</th>
                <th class="py-3 px-4">Monto</th>
                <th class="py-3 px-4">Soporte</th>
                <th class="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="payment in payments.data"
                :key="payment.id"
                class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
              >
                <td class="py-3 px-4">{{ formatDate(payment.payment_date) }}</td>
                <td class="py-3 px-4">{{ payment.concept }}</td>
                <td class="py-3 px-4">{{ payment.payment_method || "-" }}</td>
                <td class="py-3 px-4">{{ payment.description || "-" }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(payment.amount_usd) }}</td>
                <td class="py-3 px-4">
                  <a
                    v-if="payment.receipt_url"
                    :href="payment.receipt_url"
                    target="_blank"
                    rel="noopener"
                    class="text-[#ffa236] hover:underline"
                  >
                    Ver soporte
                  </a>
                  <span v-else class="text-gray-500">—</span>
                </td>
                <td class="py-3 px-4 text-right">
                  <button
                    type="button"
                    class="text-sm text-red-300 hover:text-red-200 transition-colors"
                    @click="openDeleteModal(payment.id)"
                  >
                    Mover a papelera
                  </button>
                </td>
              </tr>
              <tr v-if="payments.data.length === 0">
                <td colspan="7" class="py-6 text-center text-gray-400">No hay pagos registrados para este trabajador.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <PaginationLinks
          v-if="payments.links"
          :links="payments.links"
          :meta="payments.meta"
          class="justify-center"
        />
      </section>
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover pago a papelera"
      message="El pago de nómina se moverá a la papelera."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
.input-file {
  @apply block w-full text-sm text-gray-300 border border-[#2a2a2a] rounded-md cursor-pointer bg-[#0f0f0f] file:bg-[#ffa236] file:text-black file:px-3 file:py-2 file:border-0;
}
</style>
