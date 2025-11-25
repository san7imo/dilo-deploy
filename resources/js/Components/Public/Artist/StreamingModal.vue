<!-- resources/js/Components/Public/Artist/StreamingModal.vue -->
<script setup>
const props = defineProps({
  artist: { type: Object, required: true },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Plataformas de streaming populares
const streamingPlatforms = [
  {
    name: 'Spotify',
    url: 'https://open.spotify.com/search/',
    icon: 'M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.13 17.937c-.695 1.174-2.215 1.554-3.388.859-4.034-2.377-9.126-2.923-15.13-1.602-.928.236-1.85-.057-2.086-.986-.237-.929.057-1.85.986-2.086 6.93-1.453 12.54-.51 17.27 1.85 1.173.695 1.554 2.215.859 3.388l-.511.823z',
    color: 'hover:text-green-500',
  },
  {
    name: 'Apple Music',
    url: 'https://music.apple.com/search?term=',
    icon: 'M19.098 10.638c0-1.1-.273-2.166-.745-3.276-1.433-3.536-5.030-5.42-8.623-5.42-5.437 0-9.778 3.932-9.778 8.643 0 4.711 4.34 8.643 9.778 8.643.522 0 1.046-.03 1.554-.096v-6.625h-4.15v2.536h2.158v4.874c-.779.104-1.576.16-2.388.16-3.807 0-6.88-2.537-6.88-5.68 0-3.144 3.073-5.68 6.88-5.68 2.162 0 4.135.79 5.468 2.188.322.33.624.68.9 1.05h3.471c-.375-1.232-.883-2.404-1.514-3.473z',
    color: 'hover:text-gray-400',
  },
  {
    name: 'YouTube Music',
    url: 'https://music.youtube.com/search?q=',
    icon: 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
    color: 'hover:text-red-600',
  },
  {
    name: 'SoundCloud',
    url: 'https://soundcloud.com/search?q=',
    icon: 'M3.3 4.3l.7 2.7H6l-2 1.6.8 2.7-2.3-1.5-2.2 1.5.7-2.7-2-1.6h2.3L3.3.8zm4 0l.7 2.7h2.3l-1.9 1.6.7 2.7-2.2-1.5-2.3 1.5.8-2.7-2-1.6h2.3l.7-2.7zm4 0l.7 2.7h2.3l-1.9 1.6.7 2.7-2.2-1.5-2.3 1.5.8-2.7-2-1.6h2.3l.7-2.7zm4 0l.7 2.7h2.3l-1.9 1.6.7 2.7-2.2-1.5-2.3 1.5.8-2.7-2-1.6h2.3l.7-2.7z',
    color: 'hover:text-orange-500',
  },
  {
    name: 'Amazon Music',
    url: 'https://music.amazon.com/search/',
    icon: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z',
    color: 'hover:text-blue-600',
  },
  {
    name: 'Deezer',
    url: 'https://www.deezer.com/search/',
    icon: 'M24 24H0V0h24v24zM7 9h2v6H7V9zm3.5-2h2v8h-2V7zm3.5 2h2v6h-2V9zm3.5-2h2v8h-2V7z',
    color: 'hover:text-orange-600',
  },
]

const getSearchUrl = (platform, artist) => {
  const baseUrl = platform.url
  return `${baseUrl}${encodeURIComponent(artist.name)}`
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
    <div
      v-if="isOpen"
      @click="handleBackdropClick"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <transition name="modal-scale">
        <div
          v-if="isOpen"
          class="relative w-full max-w-2xl bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl p-8"
        >
          <!-- Botón cerrar -->
          <button
            @click="closeModal"
            class="absolute top-4 right-4 z-10 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Contenido -->
          <h2 class="text-3xl font-black text-white mb-2">Escúchame en</h2>
          <p class="text-zinc-400 mb-8">{{ artist.name }}</p>

          <!-- Grid de plataformas -->
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a
              v-for="platform in streamingPlatforms"
              :key="platform.name"
              :href="getSearchUrl(platform, artist)"
              target="_blank"
              rel="noopener noreferrer"
              class="flex flex-col items-center justify-center p-6 bg-zinc-900 rounded-2xl ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300 hover:scale-105"
            >
              <svg :class="`w-8 h-8 text-white mb-3 transition-colors ${platform.color}`" fill="currentColor" :viewBox="`0 0 24 24`">
                <path :d="platform.icon"></path>
              </svg>
              <span class="text-white font-semibold text-sm">{{ platform.name }}</span>
            </a>
          </div>

          <!-- Botón de acción -->
          <button
            @click="closeModal"
            class="w-full mt-8 px-6 py-3 rounded-2xl bg-white text-black font-semibold hover:bg-gray-100 transition"
          >
            Cerrar
          </button>
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
