<!-- resources/js/Components/Public/Artist/ReleasesModal.vue -->
<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  artist: { type: Object, required: true },
  releases: { type: Array, default: () => [] },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Releases ordenados por fecha
const sortedReleases = computed(() => {
  return [...props.releases].sort((a, b) => {
    return new Date(b.release_date) - new Date(a.release_date)
  })
})

// Formatear fecha
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

// Obtener tipo de release
const getReleaseType = (type) => {
  const types = {
    album: { label: 'Álbum', icon: '💿' },
    single: { label: 'Single', icon: '🎵' },
    ep: { label: 'EP', icon: '📀' },
    mixtape: { label: 'Mixtape', icon: '🎧' },
    live: { label: 'Live', icon: '🎤' },
    compilation: { label: 'Compilación', icon: '📚' },
  }
  return types[type?.toLowerCase()] || { label: 'Lanzamiento', icon: '🎶' }
}

// Construir array de plataformas desde las URLs individuales
const getPlatforms = (release) => {
  const platforms = []

  if (release.spotify_url) platforms.push({ name: 'Spotify', url: release.spotify_url, icon: 'simple-icons:spotify', color: 'text-green-500' })
  if (release.youtube_url) platforms.push({ name: 'YouTube', url: release.youtube_url, icon: 'simple-icons:youtube', color: 'text-red-600' })
  if (release.apple_music_url) platforms.push({ name: 'Apple Music', url: release.apple_music_url, icon: 'simple-icons:applemusic', color: 'text-pink-500' })
  if (release.deezer_url) platforms.push({ name: 'Deezer', url: release.deezer_url, icon: 'simple-icons:deezer', color: 'text-orange-600' })
  if (release.amazon_music_url) platforms.push({ name: 'Amazon Music', url: release.amazon_music_url, icon: 'simple-icons:amazonmusic', color: 'text-blue-400' })
  if (release.soundcloud_url) platforms.push({ name: 'SoundCloud', url: release.soundcloud_url, icon: 'simple-icons:soundcloud', color: 'text-orange-500' })
  if (release.tidal_url) platforms.push({ name: 'Tidal', url: release.tidal_url, icon: 'simple-icons:tidal', color: 'text-cyan-500' })

  return platforms
}

const closeModal = () => {
  emit('close')
}

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) {
    closeModal()
  }
}
</script>

<template>
  <transition name="fade">
    <div v-if="isOpen" @click="handleBackdropClick"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <transition name="modal-scale">
        <div v-if="isOpen"
          class="relative w-full max-w-2xl max-h-[80vh] bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl p-8 overflow-y-auto">
          <!-- Botón cerrar -->
          <button @click="closeModal"
            class="sticky top-0 float-right z-10 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Contenido -->
          <h2 class="text-3xl font-black text-white mb-2">Discografía</h2>
          <p class="text-zinc-400 mb-8">{{ artist.name }} • {{ sortedReleases.length }} lanzamientos</p>

          <!-- Lista de releases -->
          <div class="space-y-4">
            <div v-for="release in sortedReleases" :key="release.id"
              class="group flex gap-4 p-4 bg-zinc-900 rounded-2xl ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300">
              <!-- Cover -->
              <div class="flex-shrink-0">
                <img :src="release.cover_url || '/images/default-cover.jpg'" :alt="release.title"
                  class="w-20 h-20 rounded-lg object-cover group-hover:scale-105 transition-transform duration-300" />
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <!-- Tipo -->
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-lg">{{ getReleaseType(release.type).icon }}</span>
                  <span class="text-xs text-zinc-400 font-semibold">
                    {{ getReleaseType(release.type).label }}
                  </span>
                </div>

                <!-- Título -->
                <h3 class="text-white font-bold text-lg truncate mb-1">{{ release.title }}</h3>

                <!-- Fecha -->
                <p class="text-zinc-400 text-sm">{{ formatDate(release.release_date) }}</p>

                <!-- Plataformas -->
                <div v-if="getPlatforms(release).length > 0" class="flex gap-2 mt-2 flex-wrap">
                  <a v-for="platform in getPlatforms(release).slice(0, 3)" :key="platform.name" :href="platform.url"
                    target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-1 text-xs px-2 py-1 bg-white/10 hover:bg-white/20 text-white rounded transition">
                    <Icon :icon="platform.icon" class="w-3 h-3" />
                    {{ platform.name }}
                  </a>
                  <span v-if="getPlatforms(release).length > 3" class="text-xs text-zinc-500 self-center">
                    +{{ getPlatforms(release).length - 3 }} más
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Mensaje si no hay releases -->
          <div v-if="sortedReleases.length === 0" class="text-center py-12">
            <p class="text-zinc-400">No hay lanzamientos disponibles</p>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-scale-enter-from,
.modal-scale-leave-to {
  transform: scale(0.95);
  opacity: 0;
}
</style>
