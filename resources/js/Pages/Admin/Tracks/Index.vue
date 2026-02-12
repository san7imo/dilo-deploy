<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({ tracks: Object });

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

const handleDelete = (id) => {
  if (confirm("¿Seguro que deseas eliminar esta pista?")) {
    router.delete(route("admin.tracks.destroy", id));
  }
};
</script>

<template>
  <AdminLayout title="Pistas">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-white">🎧 Pistas</h1>
      <Link href="/admin/tracks/create" class="btn-primary">+ Nueva pista</Link>
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
            <td class="px-4 py-3 space-x-2">
              <Link
                :href="route('admin.tracks.edit', track.id)"
                class="text-[#ffa236] hover:underline"
              >
                Editar
              </Link>
              <Link
                :href="route('admin.tracks.splits.index', track.id)"
                class="text-[#ffa236] hover:underline"
              >
                Splits
              </Link>
              <button
                @click="handleDelete(track.id)"
                class="text-red-400 hover:text-red-300"
              >
                Eliminar
              </button>
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
  </AdminLayout>
</template>

<style scoped>
.input {
  @apply w-full bg-[#0f0f0f] border border-[#2a2a2a] rounded-md px-3 py-2 text-white focus:border-[#ffa236] focus:ring-[#ffa236];
}

.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
