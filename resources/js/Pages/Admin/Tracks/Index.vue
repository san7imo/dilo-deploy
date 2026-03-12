<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DangerConfirmModal from "@/Components/DangerConfirmModal.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({ tracks: Object });

const deleteModalOpen = ref(false);
const deleteProcessing = ref(false);
const pendingTrackId = ref(null);
const searchQuery = ref("");
const filteredTracks = computed(() => {
  const list = props.tracks?.data ?? [];
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return list;
  return list.filter((track) => {
    const title = (track.title || "").toLowerCase();
    const release = (track.release?.title || "").toLowerCase();
    const artists = (track.artists || []).map((a) => (a.name || "").toLowerCase()).join(" ");
    const duration = (track.duration || "").toString().toLowerCase();
    return (
      title.includes(q) ||
      release.includes(q) ||
      artists.includes(q) ||
      duration.includes(q)
    );
  });
});

const openDeleteModal = (id) => {
  pendingTrackId.value = id;
  deleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  if (deleteProcessing.value) return;
  deleteModalOpen.value = false;
  pendingTrackId.value = null;
};

const handleDelete = () => {
  if (!pendingTrackId.value || deleteProcessing.value) return;

  deleteProcessing.value = true;
  router.delete(route("admin.tracks.destroy", pendingTrackId.value), {
    onFinish: () => {
      deleteProcessing.value = false;
      closeDeleteModal();
    },
  });
};
</script>

<template>
  <AdminLayout title="Pistas">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-white">🎧 Pistas</h1>
      <div class="flex items-center gap-2">
        <Link :href="route('admin.tracks.trash')" class="btn-secondary">Papelera</Link>
        <Link href="/admin/tracks/create" class="btn-primary">+ Nueva pista</Link>
      </div>
    </div>

    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        class="input"
        placeholder="Buscar por título, lanzamiento, artista o duración..."
      />
    </div>

    <div class="overflow-x-auto bg-[#0f0f0f] rounded-lg shadow">
      <table class="min-w-full text-sm text-gray-300">
        <thead class="bg-[#1c1c1c] text-gray-400 uppercase text-xs">
          <tr>
            <th class="px-4 py-2 text-left">Título</th>
            <th class="px-4 py-2 text-left">Lanzamiento</th>
            <th class="px-4 py-2 text-left">Artistas</th>
            <th class="px-4 py-2 text-left">Duración</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="track in filteredTracks"
            :key="track.id"
            class="border-b border-[#2a2a2a] hover:bg-[#181818]"
          >
            <td class="px-4 py-3">{{ track.title }}</td>
            <td class="px-4 py-3">{{ track.release?.title || '-' }}</td>
            <td class="px-4 py-3">
              <span v-for="(artist, idx) in track.artists" :key="artist.id">
                {{ artist.name }}<span v-if="idx < track.artists.length - 1">, </span>
              </span>
            </td>
            <td class="px-4 py-3">{{ track.duration || '-' }}</td>
            <td class="px-4 py-3 text-right">
              <RowActionMenu label="Acciones de pista">
                <Link
                  :href="route('admin.tracks.edit', track.id)"
                  class="block rounded px-3 py-2 text-sm text-gray-200 hover:bg-white/10"
                >
                  Editar
                </Link>
                <Link
                  :href="route('admin.tracks.splits.index', track.id)"
                  class="block rounded px-3 py-2 text-sm text-[#ffa236] hover:bg-white/10"
                >
                  Split master
                </Link>
                <Link
                  :href="route('admin.tracks.compositions.index', track.id)"
                  class="block rounded px-3 py-2 text-sm text-[#ffa236] hover:bg-white/10"
                >
                  Composición y splits
                </Link>
                <button
                  type="button"
                  class="block w-full rounded px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/20"
                  @click="openDeleteModal(track.id)"
                >
                  Mover a papelera
                </button>
              </RowActionMenu>
            </td>
          </tr>
          <tr v-if="!filteredTracks.length">
            <td class="px-4 py-6 text-center text-gray-500" colspan="5">
              No hay pistas con ese criterio.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationLinks v-if="tracks.links" :links="tracks.links" :meta="tracks.meta" class="justify-end mt-4" />

    <DangerConfirmModal
      :show="deleteModalOpen"
      title="Mover pista a papelera"
      message="La pista se moverá a la papelera y podrá restaurarse después."
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
