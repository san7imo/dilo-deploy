<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
  artists: Object,
});

const handleDelete = (id) => {
  if (confirm("¿Seguro que deseas eliminar este artista?")) {
    router.delete(route("admin.artists.destroy", id));
  }
};
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-[#ffa236]">Artistas</h1>
        <Link
          :href="route('admin.artists.create')"
          class="bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-4 py-2 rounded-md transition-colors"
        >
          <i class="fa-solid fa-plus mr-2"></i>Nuevo artista
        </Link>
      </div>

      <!-- Tabla mejorada con vista de tarjetas -->
      <div v-if="props.artists.data.length === 0" class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-xl p-8 text-center">
        <p class="text-gray-400">No hay artistas registrados. <Link :href="route('admin.artists.create')" class="text-[#ffa236] hover:text-[#ffb54d]">Crear uno</Link></p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div
          v-for="artist in props.artists.data"
          :key="artist.id"
          class="bg-[#1d1d1b] border border-[#2a2a2a] rounded-lg overflow-hidden hover:border-[#ffa236]/50 transition-all hover:shadow-lg hover:shadow-[#ffa236]/20"
        >
          <!-- Imagen principal -->
          <div class="relative h-40 bg-[#0f0f0f] overflow-hidden">
            <img
              v-if="artist.banner_artist_url || artist.carousel_home_url"
              :src="artist.banner_artist_url || artist.carousel_home_url"
              :alt="artist.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <i class="fa-solid fa-music text-4xl text-[#2a2a2a]"></i>
            </div>
          </div>

          <!-- Contenido -->
          <div class="p-4 space-y-3">
            <!-- Nombre y ID -->
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold text-white truncate">{{ artist.name }}</h3>
                <p class="text-xs text-gray-500">#{{ artist.id }}</p>
              </div>
            </div>

            <!-- Info -->
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div>
                <p class="text-gray-400">País</p>
                <p class="text-white font-medium">{{ artist.country || "-" }}</p>
              </div>
              <div>
                <p class="text-gray-400">Género</p>
                <p class="text-white font-medium">{{ artist.genre?.name || "-" }}</p>
              </div>
            </div>

            <!-- Releases -->
            <div class="bg-[#0f0f0f] rounded p-2">
              <p class="text-gray-400 text-xs">Lanzamientos</p>
              <p class="text-[#ffa236] font-bold text-lg">{{ artist.releases_count || 0 }}</p>
            </div>

            <!-- Bio preview -->
            <p v-if="artist.bio" class="text-gray-400 text-sm line-clamp-2">{{ artist.bio }}</p>

            <!-- Acciones -->
            <div class="flex gap-2 pt-2 border-t border-[#2a2a2a]">
              <Link
                :href="route('admin.artists.edit', artist.id)"
                class="flex-1 bg-[#ffa236] hover:bg-[#ffb54d] text-black font-semibold px-3 py-2 rounded text-center text-sm transition-colors"
              >
                <i class="fa-solid fa-pen-to-square mr-1"></i>Editar
              </Link>
              <button
                @click="handleDelete(artist.id)"
                class="flex-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 font-semibold px-3 py-2 rounded text-center text-sm transition-colors"
              >
                <i class="fa-solid fa-trash mr-1"></i>Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="props.artists.links" class="flex justify-center gap-2">
        <Link
          v-for="link in props.artists.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="[
            'px-3 py-2 rounded text-sm font-medium transition-colors',
            link.active
              ? 'bg-[#ffa236] text-black'
              : link.url
              ? 'bg-[#2a2a2a] text-gray-300 hover:bg-[#3a3a3a]'
              : 'bg-[#1a1a1a] text-gray-500 cursor-not-allowed',
          ]"
          v-html="link.label"
        ></Link>
      </div>
    </div>
  </AdminLayout>
</template>
