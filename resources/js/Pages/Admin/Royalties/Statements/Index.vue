<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";

defineProps({ statements: Object });

const formatDate = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString("es-ES");
};

const formatMoney = (value) => {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2,
    maximumFractionDigits: 6,
  }).format(Number.isNaN(number) ? 0 : number);
};

const handleProcess = (id) => {
  if (confirm("¿Procesar este statement ahora?")) {
    router.post(route("admin.royalties.statements.process", id));
  }
};
</script>

<template>
  <AdminLayout title="Royalties">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-white">Royalties · Statements</h1>
        <Link :href="route('admin.royalties.dashboard')" class="text-gray-400 hover:text-white text-sm">
          Volver al dashboard
        </Link>
      </div>
      <Link :href="route('admin.royalties.statements.create')" class="btn-primary">
        Upload CSV
      </Link>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Fecha</th>
            <th class="px-4 py-2 text-left">Proveedor</th>
            <th class="px-4 py-2 text-left">Label</th>
            <th class="px-4 py-2 text-left">Periodo</th>
            <th class="px-4 py-2 text-left">Archivo</th>
            <th class="px-4 py-2 text-left">Estado</th>
            <th class="px-4 py-2 text-left">Moneda</th>
            <th class="px-4 py-2 text-left">Units</th>
            <th class="px-4 py-2 text-left">Total (USD)</th>
            <th class="px-4 py-2 text-left">Cargado por</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="statement in statements.data"
            :key="statement.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ formatDate(statement.created_at) }}</td>
            <td class="px-4 py-3 capitalize">{{ statement.provider }}</td>
            <td class="px-4 py-3">{{ statement.label || "-" }}</td>
            <td class="px-4 py-3">{{ statement.reporting_period || "-" }}</td>
            <td class="px-4 py-3">{{ statement.original_filename }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded bg-[#2a2a2a] text-xs uppercase">
                {{ statement.status }}
              </span>
            </td>
            <td class="px-4 py-3">{{ statement.currency }}</td>
            <td class="px-4 py-3">{{ statement.total_units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(statement.total_net_usd) }}</td>
            <td class="px-4 py-3">{{ statement.creator?.name || "-" }}</td>
            <td class="px-4 py-3 space-x-2">
              <Link
                :href="route('admin.royalties.statements.show', statement.id)"
                class="text-[#ffa236] hover:underline"
              >
                Ver
              </Link>
              <button
                v-if="statement.status === 'uploaded'"
                type="button"
                class="text-green-400 hover:text-green-300"
                @click="handleProcess(statement.id)"
              >
                Procesar
              </button>
            </td>
          </tr>
          <tr v-if="!statements.data?.length">
            <td colspan="11" class="px-4 py-6 text-center text-gray-400">
              No hay statements cargados aún.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks
      v-if="statements.links"
      :links="statements.links"
      :meta="statements.meta"
      class="justify-end mt-4"
    />
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
