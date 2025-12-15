<!-- resources/js/Components/Public/Artist/StreamingModal.vue -->
<script setup>
import { Icon } from '@iconify/vue'

const props = defineProps({
  artist: { type: Object, required: true },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Plataformas de streaming con iconos de Iconify
const streamingPlatforms = [
  {
    name: 'Spotify',
    key: 'spotify',
    icon: 'simple-icons:spotify',
    color: 'group-hover:text-green-500',
  },
  {
    name: 'Apple Music',
    key: 'apple',
    icon: 'simple-icons:applemusic',
    color: 'group-hover:text-pink-500',
  },
  {
    name: 'YouTube Music',
    key: 'youtube',
    icon: 'simple-icons:youtube',
    color: 'group-hover:text-red-600',
  },
  {
    name: 'SoundCloud',
    key: 'soundcloud',
    icon: 'simple-icons:soundcloud',
    color: 'group-hover:text-orange-500',
  },
  {
    name: 'Amazon Music',
    key: 'amazon',
    icon: 'simple-icons:amazonmusic',
    color: 'group-hover:text-blue-400',
  },
  {
    name: 'Deezer',
    key: 'deezer',
    icon: 'simple-icons:deezer',
    color: 'group-hover:text-orange-600',
  },
  {
    name: 'Tidal',
    key: 'tidal',
    icon: 'simple-icons:tidal',
    color: 'group-hover:text-cyan-500',
  },
]

const getStreamingUrl = (key) => {
  return props.artist.social_links?.[key]
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
        <div v-if="isOpen" class="relative w-full max-w-md bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl p-8">
          <!-- Botón cerrar -->
          <button @click="closeModal"
            class="absolute top-4 right-4 z-10 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Contenido -->
          <h2 class="text-3xl font-black text-white mb-2">Escúchame en</h2>
          <p class="text-zinc-400 mb-8">{{ artist.name }}</p>

          <!-- Grid de plataformas -->
          <div class="grid grid-cols-2 gap-4">
            <a v-for="platform in streamingPlatforms" :key="platform.key" v-show="getStreamingUrl(platform.key)"
              :href="getStreamingUrl(platform.key)" target="_blank" rel="noopener noreferrer"
              class="group flex flex-col items-center justify-center p-4 bg-zinc-900 rounded-2xl ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300 hover:scale-105">
              <Icon :icon="platform.icon" :class="`w-8 h-8 text-white mb-2 transition-colors ${platform.color}`" />
              <span class="text-white font-semibold text-xs">{{ platform.name }}</span>
            </a>
          </div>

          <!-- Mensaje si no hay plataformas -->
          <div v-if="!streamingPlatforms.some(p => getStreamingUrl(p.key))" class="text-center py-8">
            <p class="text-zinc-400">No hay plataformas de streaming configuradas</p>
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
