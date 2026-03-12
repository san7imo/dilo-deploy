<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  genres: Object,
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingGenreId = ref(null);

const openDeleteModal = (id) => {
  pendingGenreId.value = id;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingGenreId.value = null;
};

const handleDelete = () => {
  if (!pendingGenreId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.genres.destroy", pendingGenreId.value), {
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
        <h1 class="text-2xl font-bold text-[#ffa236]">Géneros musicales</h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('admin.genres.trash')"
            class="bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors"
          >
            Papelera
          </Link>
          <Link
            :href="route('admin.genres.create')"
            class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
          >
            + Nuevo género
          </Link>
        </div>
      </div>

      <div class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-4">
        <table class="w-full text-sm text-gray-300">
          <thead class="text-[#ffa236] text-left border-b border-[#2a2a2a]">
            <tr>
              <th class="py-3 px-4">#</th>
              <th class="py-3 px-4">Nombre</th>
              <th class="py-3 px-4">Slug</th>
              <th class="py-3 px-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="genre in props.genres.data"
              :key="genre.id"
              class="border-b border-[#2a2a2a] hover:bg-[#2a2a2a]/30"
            >
              <td class="py-3 px-4">{{ genre.id }}</td>
              <td class="py-3 px-4">{{ genre.name }}</td>
              <td class="py-3 px-4">{{ genre.slug }}</td>
              <td class="py-3 px-4 text-right">
                <RowActionMenu label="Acciones de género">
                  <Link
                    :href="route('admin.genres.edit', genre.id)"
                    class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                  >
                    Editar
                  </Link>
                  <button
                    type="button"
                    class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                    @click="openDeleteModal(genre.id)"
                  >
                    Mover a papelera
                  </button>
                </RowActionMenu>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationLinks v-if="props.genres.links" :links="props.genres.links" :meta="props.genres.meta" class="justify-center" />
    </div>

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover género a papelera"
      message="El género se moverá a la papelera y podrá restaurarse después."
      confirm-label="Mover a papelera"
      :processing="deleteProcessing"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </AdminLayout>
</template>
