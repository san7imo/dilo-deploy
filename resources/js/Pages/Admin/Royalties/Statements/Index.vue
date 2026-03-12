<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({ statements: Object });
const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingDeleteId = ref(null);

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
  router.post(route("admin.royalties.statements.process", id));
};

const openDeleteModal = (id) => {
  pendingDeleteId.value = id;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingDeleteId.value = null;
};

const confirmDelete = () => {
  if (!pendingDeleteId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.royalties.statements.destroy", pendingDeleteId.value), {
    preserveScroll: true,
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
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
      <div class="flex items-center gap-2">
        <Link :href="route('admin.royalties.composition-statements.index')" class="btn-secondary">
          Statements composición
        </Link>
        <Link :href="route('admin.royalties.statements.trash')" class="btn-secondary">
          Papelera
        </Link>
        <Link :href="route('admin.royalties.statements.create')" class="btn-primary">
          Upload CSV
        </Link>
      </div>
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Fecha</th>
            <th class="px-4 py-2 text-left">Proveedor</th>
            <th class="px-4 py-2 text-left">Label</th>
            <th class="px-4 py-2 text-left">Periodo</th>
            <th class="px-4 py-2 text-left">Versión</th>
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
            <td class="px-4 py-3">
              <span class="text-white">v{{ statement.version ?? 1 }}</span>
            </td>
            <td class="px-4 py-3">{{ statement.original_filename }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded bg-[#2a2a2a] text-xs uppercase">
                {{ statement.status }}
              </span>
              <span v-if="statement.is_current" class="ml-2 px-2 py-1 rounded bg-green-500/20 text-green-300 text-xs uppercase">
                current
              </span>
              <span
                v-if="statement.is_reference_only"
                class="ml-2 px-2 py-1 rounded bg-yellow-500/20 text-yellow-300 text-xs uppercase"
              >
                reference
              </span>
            </td>
            <td class="px-4 py-3">{{ statement.currency }}</td>
            <td class="px-4 py-3">{{ statement.total_units ?? 0 }}</td>
            <td class="px-4 py-3">{{ formatMoney(statement.total_net_usd) }}</td>
            <td class="px-4 py-3">{{ statement.creator?.name || "-" }}</td>
            <td class="px-4 py-3 text-right">
              <RowActionMenu label="Acciones del statement">
                <Link
                  :href="route('admin.royalties.statements.show', statement.id)"
                  class="block rounded px-3 py-2 text-sm text-[#ffa236] hover:bg-white/10"
                >
                  Ver
                </Link>
                <a
                  :href="route('admin.royalties.statements.download', statement.id)"
                  class="block rounded px-3 py-2 text-sm text-blue-300 hover:bg-blue-500/20"
                >
                  Descargar CSV
                </a>
                <button
                  v-if="['uploaded', 'failed'].includes(statement.status)"
                  type="button"
                  class="block w-full rounded px-3 py-2 text-left text-sm text-green-300 hover:bg-green-500/20"
                  @click="handleProcess(statement.id)"
                >
                  {{ statement.status === 'failed' ? 'Reprocesar' : 'Procesar' }}
                </button>
                <button
                  type="button"
                  class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                  @click="openDeleteModal(statement.id)"
                >
                  Mover a papelera
                </button>
              </RowActionMenu>
            </td>
          </tr>
          <tr v-if="!statements.data?.length">
            <td colspan="12" class="px-4 py-6 text-center text-gray-400">
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

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover statement a papelera"
      message="El statement se moverá a la papelera y podrá restaurarse luego."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="confirmDelete"
    />
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
