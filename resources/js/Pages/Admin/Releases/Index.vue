<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  releases: { type: Object, required: true }, // paginator
});
</script>

<template>
  <AdminLayout title="Lanzamientos">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Lanzamientos</h1>
      <Link :href="route('admin.releases.create')" class="btn-primary">Nuevo</Link>
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
          <tr v-for="r in releases.data" :key="r.id" class="border-t border-[#2a2a2a]">
            <td class="px-4 py-3">
              <img v-if="r.cover_url" :src="r.cover_url + '?tr=w-64,h-64,q-80,fo-auto'" class="w-12 h-12 rounded object-cover" />
            </td>
            <td class="px-4 py-3">{{ r.title }}</td>
            <td class="px-4 py-3">{{ r.artist?.name }}</td>
            <td class="px-4 py-3 capitalize">{{ r.type || '–' }}</td>
            <td class="px-4 py-3">{{ r.release_date || '–' }}</td>
            <td class="px-4 py-3 text-right">
              <Link :href="route('admin.releases.edit', r.id)" class="text-[#ffa236] hover:underline">Editar</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- paginación simple -->
    <div class="flex items-center justify-end gap-2 mt-4">
      <Link v-if="releases.prev_page_url" :href="releases.prev_page_url" class="px-3 py-1 border border-[#2a2a2a] rounded">Anterior</Link>
      <Link v-if="releases.next_page_url" :href="releases.next_page_url" class="px-3 py-1 border border-[#2a2a2a] rounded">Siguiente</Link>
    </div>
  </AdminLayout>
</template>

<style scoped>
.btn-primary {
  @apply bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors;
}
</style>
