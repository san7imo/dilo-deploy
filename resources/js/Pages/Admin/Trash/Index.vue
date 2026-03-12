<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
  title: { type: String, required: true },
  items: { type: Object, required: true },
  restoreRoute: { type: String, required: true },
  forceDeleteRoute: { type: String, required: true },
  backRoute: { type: String, required: true },
});

const page = usePage();
const forceDeleteModalOpen = ref(false);
const forceDeleteProcessing = ref(false);
const pendingForceDeleteId = ref(null);

const formatDate = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString("es-ES");
};

const handleRestore = (id) => {
  router.patch(route(props.restoreRoute, id), {}, { preserveScroll: true });
};

const forceDeleteTargetLabel = computed(() =>
  pendingForceDeleteId.value ? `ID ${pendingForceDeleteId.value}` : "el registro seleccionado"
);

const openForceDeleteModal = (id) => {
  pendingForceDeleteId.value = id;
  forceDeleteModalOpen.value = true;
};

const closeForceDeleteModal = () => {
  if (forceDeleteProcessing.value) return;
  forceDeleteModalOpen.value = false;
  pendingForceDeleteId.value = null;
};

const handleForceDelete = () => {
  if (!pendingForceDeleteId.value || forceDeleteProcessing.value) return;

  forceDeleteProcessing.value = true;
  router.delete(route(props.forceDeleteRoute, pendingForceDeleteId.value), {
    preserveScroll: true,
    onFinish: () => {
      forceDeleteProcessing.value = false;
      closeForceDeleteModal();
    },
  });
};

const canForceDelete = (item) => item?.can_force_delete !== false;

const blockedReason = (item) =>
  item?.force_delete_blocked_reason || "No se puede eliminar permanentemente por dependencias activas.";

const handleForceDeleteForItem = (item) => {
  if (!canForceDelete(item)) return;
  openForceDeleteModal(item.id);
};
</script>

<template>
  <AdminLayout :title="title">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-white">{{ title }}</h1>
          <p class="text-sm text-gray-400">
            Registros eliminados lógicamente. Puedes restaurarlos o eliminarlos de forma permanente.
          </p>
        </div>
        <Link :href="route(backRoute)" class="btn-back">Volver</Link>
      </div>

      <div
        v-if="page.props?.errors && Object.keys(page.props.errors).length"
        class="bg-red-500/10 border border-red-500/30 text-red-200 rounded-lg p-3 text-sm"
      >
        <p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p>
      </div>

      <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
        <table class="min-w-full text-sm text-gray-300">
          <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
            <tr>
              <th class="px-4 py-2 text-left">Registro</th>
              <th class="px-4 py-2 text-left">Detalle</th>
              <th class="px-4 py-2 text-left">Eliminado</th>
              <th class="px-4 py-2 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items.data" :key="item.id" class="border-b border-[#2a2a2a] hover:bg-[#181818]">
              <td class="px-4 py-3">{{ item.primary || "-" }}</td>
              <td class="px-4 py-3 text-gray-400">{{ item.secondary || "-" }}</td>
              <td class="px-4 py-3">{{ formatDate(item.deleted_at) }}</td>
              <td class="px-4 py-3 text-right space-x-3">
                <button @click="handleRestore(item.id)" class="text-green-400 hover:text-green-300">Restaurar</button>
                <button
                  :disabled="!canForceDelete(item)"
                  :title="!canForceDelete(item) ? blockedReason(item) : 'Eliminar permanentemente'"
                  :class="canForceDelete(item) ? 'text-red-400 hover:text-red-300' : 'text-gray-500 cursor-not-allowed'"
                  @click="handleForceDeleteForItem(item)"
                >
                  Eliminar permanente
                </button>
                <p v-if="!canForceDelete(item)" class="text-xs text-yellow-400 mt-1">{{ blockedReason(item) }}</p>
              </td>
            </tr>
            <tr v-if="!items.data?.length">
              <td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay registros en papelera.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationLinks v-if="items.links" :links="items.links" :meta="items.meta" class="justify-end mt-4" />

      <DangerConfirmModal
        :show="forceDeleteModalOpen"
        title="Eliminar permanentemente"
        :message="`Esta acción eliminará ${forceDeleteTargetLabel} de forma irreversible.`"
        confirm-label="Eliminar permanente"
        :processing="forceDeleteProcessing"
        @close="closeForceDeleteModal"
        @confirm="handleForceDelete"
      />
    </div>
  </AdminLayout>
</template>

<style scoped>
.btn-back {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
