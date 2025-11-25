<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";

defineProps({ tracks: Object });
</script>

<template>
  <AdminLayout title="Pistas">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-white">🎧 Pistas</h1>
      <Link href="/admin/tracks/create" class="btn-primary">+ Nueva pista</Link>
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
            v-for="track in tracks.data"
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
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
