<!-- resources/js/Components/Public/Artist/SocialNetworksModal.vue -->
<script setup>
import { Icon } from '@iconify/vue'

const props = defineProps({
  artist: { type: Object, required: true },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Mapeo de redes sociales con iconos de Iconify
const networks = [
  { key: 'instagram', name: 'Instagram', icon: 'simple-icons:instagram', color: 'group-hover:text-pink-500' },
  { key: 'facebook', name: 'Facebook', icon: 'simple-icons:facebook', color: 'group-hover:text-blue-600' },
  { key: 'x', name: 'X', icon: 'simple-icons:x', color: 'group-hover:text-gray-300' },
  { key: 'twitter', name: 'Twitter', icon: 'simple-icons:twitter', color: 'group-hover:text-blue-400' },
  { key: 'tiktok', name: 'TikTok', icon: 'simple-icons:tiktok', color: 'group-hover:text-white' },
  { key: 'youtube', name: 'YouTube', icon: 'simple-icons:youtube', color: 'group-hover:text-red-600' },
]

const getSocialUrl = (key) => {
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
          <h2 class="text-3xl font-black text-white mb-2">Sígueme en Redes</h2>
          <p class="text-zinc-400 mb-8">{{ artist.name }}</p>

          <!-- Grid de redes -->
          <div class="grid grid-cols-2 gap-4">
            <a v-for="network in networks" :key="network.key" v-show="getSocialUrl(network.key)"
              :href="getSocialUrl(network.key)" target="_blank" rel="noopener noreferrer"
              class="group flex flex-col items-center justify-center p-4 bg-zinc-900 rounded-2xl ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300 hover:scale-105">
              <Icon :icon="network.icon" :class="`w-8 h-8 text-white mb-2 transition-colors ${network.color}`" />
              <span class="text-white font-semibold text-xs">{{ network.name }}</span>
            </a>
          </div>

          <!-- Mensaje si no hay redes -->
          <div v-if="!networks.some(n => getSocialUrl(n.key))" class="text-center py-8">
            <p class="text-zinc-400">No hay redes sociales configuradas</p>
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
