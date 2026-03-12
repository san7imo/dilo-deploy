<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  compositions: { type: Object, required: true },
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingCompositionId = ref(null);

const openDeleteModal = (compositionId) => {
  pendingCompositionId.value = compositionId;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingCompositionId.value = null;
};

const handleDelete = () => {
  if (!pendingCompositionId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.compositions.destroy", pendingCompositionId.value), {
    preserveScroll: true,
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
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-[#ffa236]">Composiciones</h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.compositions.trash')"
            class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
          >
            Papelera
          </Link>
          <Link
            :href="route('admin.compositions.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            + Nueva composición
          </Link>
        </div>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-gray-300">
            <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
              <tr>
                <th class="py-3 px-4">Título</th>
                <th class="py-3 px-4">ISWC</th>
                <th class="py-3 px-4">Tracks vinculados</th>
                <th class="py-3 px-4">Registros</th>
                <th class="py-3 px-4">Splits composición</th>
                <th class="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="composition in props.compositions.data"
                :key="composition.id"
                class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
              >
                <td class="py-3 px-4">{{ composition.title }}</td>
                <td class="py-3 px-4">{{ composition.iswc || "-" }}</td>
                <td class="py-3 px-4">{{ composition.tracks_count ?? 0 }}</td>
                <td class="py-3 px-4">{{ composition.registrations_count ?? 0 }}</td>
                <td class="py-3 px-4">{{ composition.split_agreements_count ?? 0 }}</td>
                <td class="py-3 px-4 text-right">
                  <RowActionMenu label="Acciones de composición">
                    <Link
                      :href="route('admin.compositions.splits.index', composition.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Ver splits
                    </Link>
                    <Link
                      :href="route('admin.compositions.edit', composition.id)"
                      class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                    >
                      Editar
                    </Link>
                    <button
                      type="button"
                      class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                      @click="openDeleteModal(composition.id)"
                    >
                      Mover a papelera
                    </button>
                  </RowActionMenu>
                </td>
              </tr>
              <tr v-if="props.compositions.data.length === 0">
                <td colspan="6" class="py-6 text-center text-gray-400">No hay composiciones registradas.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <PaginationLinks
        v-if="props.compositions.links"
        :links="props.compositions.links"
        :meta="props.compositions.meta"
        class="justify-center"
      />
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover composición a papelera"
      message="La composición se moverá a la papelera y podrá restaurarse después."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </AdminLayout>
</template>
