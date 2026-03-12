<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
  workers: { type: Object, required: true },
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingWorkerId = ref(null);

const openDeleteModal = (workerId) => {
  pendingWorkerId.value = workerId;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingWorkerId.value = null;
};

const handleDelete = () => {
  if (!pendingWorkerId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.workers.destroy", pendingWorkerId.value), {
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
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-[#ffa236]">Nómina · Trabajadores</h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.workers.trash')"
            class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
          >
            Papelera
          </Link>
          <Link
            :href="route('admin.workers.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            + Nuevo trabajador
          </Link>
        </div>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-gray-300">
            <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
              <tr>
                <th class="py-3 px-4">Nombre</th>
                <th class="py-3 px-4">Cargo</th>
                <th class="py-3 px-4">Documento</th>
                <th class="py-3 px-4">Contacto</th>
                <th class="py-3 px-4">Mes</th>
                <th class="py-3 px-4">Trimestre</th>
                <th class="py-3 px-4">Semestre</th>
                <th class="py-3 px-4">Año</th>
                <th class="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="worker in workers.data"
                :key="worker.id"
                class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
              >
                <td class="py-3 px-4">{{ worker.full_name }}</td>
                <td class="py-3 px-4">{{ worker.position || "-" }}</td>
                <td class="py-3 px-4">{{ worker.document_type || "-" }} {{ worker.document_number || "" }}</td>
                <td class="py-3 px-4">
                  <div>{{ worker.email || "-" }}</div>
                  <div class="text-xs text-gray-500">{{ worker.whatsapp || "-" }}</div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(worker.received_month_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(worker.received_three_months_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(worker.received_six_months_usd) }}</td>
                <td class="py-3 px-4 whitespace-nowrap">{{ formatUsd(worker.received_year_usd) }}</td>
                <td class="py-3 px-4 text-right">
                  <RowActionMenu label="Acciones de trabajador">
                    <Link
                      :href="route('admin.workers.payroll', worker.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Ver nómina
                    </Link>
                    <Link
                      :href="route('admin.workers.edit', worker.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Editar
                    </Link>
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="openDeleteModal(worker.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </td>
              </tr>
              <tr v-if="workers.data.length === 0">
                <td colspan="9" class="py-6 text-center text-gray-400">No hay trabajadores registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <PaginationLinks
        v-if="workers.links"
        :links="workers.links"
        :meta="workers.meta"
        class="justify-center"
      />
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover trabajador a papelera"
      message="El trabajador se moverá a la papelera y podrás restaurarlo después."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </AdminLayout>
</template>
