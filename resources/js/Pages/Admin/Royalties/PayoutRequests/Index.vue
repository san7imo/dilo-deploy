<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
  requests: Object,
  kpis: Object,
  filters: Object,
});

const status = ref(props.filters?.status || "all");

watch(status, (value) => {
  router.get(
    route("admin.royalties.payout-requests.index"),
    { status: value === "all" ? undefined : value },
    { preserveState: true, replace: true }
  );
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
</script>

<template>
  <AdminLayout title="Solicitudes de pago">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Solicitudes de pago de regalías</h1>
        <p class="text-gray-400 text-sm">Gestión de solicitudes hechas por artistas internos y externos.</p>
      </div>
      <Link :href="route('admin.royalties.dashboard')" class="text-gray-400 hover:text-white">
        Volver al dashboard
      </Link>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Pendientes</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ kpis.pending_count ?? 0 }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Pendiente de pago (USD)</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(kpis.pending_amount_usd) }}</p>
      </div>
      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg p-4">
        <p class="text-gray-400 text-sm">Pagado histórico (USD)</p>
        <p class="text-[#ffa236] text-2xl font-semibold">{{ formatMoney(kpis.paid_amount_usd) }}</p>
      </div>
    </div>

    <div class="flex items-center justify-end mb-4">
      <select v-model="status" class="input">
        <option value="all">Todos</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="paid">Paid</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Artista</th>
            <th class="px-4 py-2 text-left">Correo</th>
            <th class="px-4 py-2 text-left">Solicitado USD</th>
            <th class="px-4 py-2 text-left">Pagado USD</th>
            <th class="px-4 py-2 text-left">Pendiente USD</th>
            <th class="px-4 py-2 text-left">Estado</th>
            <th class="px-4 py-2 text-left">Fecha</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="request in requests.data"
            :key="request.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ request.requester_name }}</td>
            <td class="px-4 py-3">{{ request.requester_email }}</td>
            <td class="px-4 py-3">{{ formatMoney(request.requested_amount_usd) }}</td>
            <td class="px-4 py-3">{{ formatMoney(request.paid_total_usd) }}</td>
            <td class="px-4 py-3">{{ formatMoney(request.outstanding_usd) }}</td>
            <td class="px-4 py-3 uppercase text-xs">{{ request.status }}</td>
            <td class="px-4 py-3">{{ request.requested_at || "-" }}</td>
            <td class="px-4 py-3">
              <Link :href="route('admin.royalties.payout-requests.show', request.id)" class="text-[#ffa236] hover:underline">
                Gestionar pago
              </Link>
            </td>
          </tr>
          <tr v-if="!requests.data?.length">
            <td colspan="8" class="px-4 py-6 text-center text-gray-400">
              No hay solicitudes registradas.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="requests.links" :links="requests.links" :meta="requests.meta" class="justify-end mt-4" />
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}
</style>
