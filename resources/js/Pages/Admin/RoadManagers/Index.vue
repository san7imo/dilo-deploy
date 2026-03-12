<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  roadManagers: Object,
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingRoadManagerId = ref(null);

const openDeleteModal = (id) => {
  pendingRoadManagerId.value = id;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingRoadManagerId.value = null;
};

const handleDelete = () => {
  if (!pendingRoadManagerId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.roadmanagers.destroy", pendingRoadManagerId.value), {
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-[#ffa236]">Road managers</h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.roadmanagers.trash')"
            class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
          >
            Papelera
          </Link>
          <Link
            :href="route('admin.roadmanagers.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            + Nuevo road manager
          </Link>
        </div>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <table class="w-full text-sm text-gray-300">
          <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
            <tr>
              <th class="py-3 px-4">Nombre</th>
              <th class="py-3 px-4">Correo</th>
              <th class="py-3 px-4">Verificado</th>
              <th class="py-3 px-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="manager in props.roadManagers.data"
              :key="manager.id"
              class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
            >
              <td class="py-3 px-4">{{ manager.name }}</td>
              <td class="py-3 px-4">{{ manager.email }}</td>
              <td class="py-3 px-4">
                <span
                  :class="manager.email_verified_at ? 'text-green-400' : 'text-yellow-400'"
                >
                  {{ manager.email_verified_at ? 'Si' : 'No' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <RowActionMenu label="Acciones de road manager">
                  <Link
                    :href="route('admin.roadmanagers.edit', manager.id)"
                    class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                  >
                    Editar
                  </Link>
                  <button
                    type="button"
                    class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                    @click="openDeleteModal(manager.id)"
                  >
                    Mover a papelera
                  </button>
                </RowActionMenu>
              </td>
            </tr>
            <tr v-if="props.roadManagers.data.length === 0">
              <td colspan="4" class="py-6 text-center text-gray-400">
                No hay road managers registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationLinks v-if="props.roadManagers.links" :links="props.roadManagers.links" :meta="props.roadManagers.meta" class="justify-center" />
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover road manager a papelera"
      message="Este usuario se moverá a la papelera y podrá restaurarse luego."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </AdminLayout>
</template>
