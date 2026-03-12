<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
  releases: { type: Object, required: true }, // paginator
});

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingReleaseId = ref(null);
const searchQuery = ref("");
const filteredReleases = computed(() => {
  const list = props.releases?.data ?? [];
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return list;
  return list.filter((release) => {
    const title = (release.title || "").toLowerCase();
    const artist = (release.artist?.name || "").toLowerCase();
    const type = (release.type || "").toLowerCase();
    const date = (release.release_date || "").toString().toLowerCase();
    return (
      title.includes(q) ||
      artist.includes(q) ||
      type.includes(q) ||
      date.includes(q)
    );
  });
});

const openDeleteModal = (id) => {
  pendingReleaseId.value = id;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingReleaseId.value = null;
};

const handleDelete = () => {
  if (!pendingReleaseId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.releases.destroy", pendingReleaseId.value), {
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};
</script>

<template>
  <AdminLayout title="Lanzamientos">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Lanzamientos</h1>
      <div class="flex items-center gap-2">
        <Link :href="route('admin.releases.trash')" class="btn-secondary">Papelera</Link>
        <Link :href="route('admin.releases.create')" class="btn-primary">Nuevo</Link>
      </div>
    </div>

    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        class="input"
        placeholder="Buscar por título, artista, tipo o fecha..."
      />
    </div>

    <div class="bg-[#0f0f0f] border border-[#2a2a2a] rounded-lg overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-[#111] text-gray-300">
          <tr>
            <th class="px-4 py-3 text-left">Portada</th>
            <th class="px-4 py-3 text-left">Título</th>
            <th class="px-4 py-3 text-left">Artista</th>
            <th class="px-4 py-3 text-left">Tipo</th>
            <th class="px-4 py-3 text-left">Fecha</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in filteredReleases" :key="r.id" class="border-t border-[#2a2a2a]">
            <td class="px-4 py-3">
              <img v-if="r.cover_url" :src="r.cover_url + '?tr=w-64,h-64,q-80,fo-auto'" class="w-12 h-12 rounded object-cover" />
            </td>
            <td class="px-4 py-3">{{ r.title }}</td>
            <td class="px-4 py-3">{{ r.artist?.name }}</td>
            <td class="px-4 py-3 capitalize">{{ r.type || '–' }}</td>
            <td class="px-4 py-3">{{ r.release_date || '–' }}</td>
            <td class="px-4 py-3 text-right">
              <RowActionMenu label="Acciones del lanzamiento">
                <Link
                  :href="route('admin.releases.edit', r.id)"
                  class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                >
                  Editar
                </Link>
                <button
                  type="button"
                  class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                  @click="openDeleteModal(r.id)"
                >
                  Mover a papelera
                </button>
              </RowActionMenu>
            </td>
          </tr>
          <tr v-if="!filteredReleases.length">
            <td class="px-4 py-6 text-center text-gray-500" colspan="6">
              No hay lanzamientos con ese criterio.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="releases.links" :links="releases.links" :meta="releases.meta" class="justify-end mt-4" />

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover lanzamiento a papelera"
      message="El lanzamiento se moverá a la papelera y podrá restaurarse después."
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

.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}

.btn-secondary {
  @apply bg-[#2a2a2a] hover:bg-[#3a3a3a] text-white font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
