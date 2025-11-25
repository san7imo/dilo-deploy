<!-- resources/js/Components/Public/Artist/SocialNetworksModal.vue -->
<script setup>
const props = defineProps({
  artist: { type: Object, required: true },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Mapeo de redes sociales
const networks = [
  {
    name: 'Facebook',
    key: 'facebook',
    icon: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
    color: 'hover:text-blue-600',
  },
  {
    name: 'Instagram',
    key: 'instagram',
    icon: 'M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.117.626c-.788.297-1.459.711-2.126 1.378-.667.667-1.079 1.335-1.395 2.126-.296.784-.497 1.663-.558 2.921C.008 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.307.788.723 1.459 1.379 2.126.667.666 1.336 1.079 2.125 1.394.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.059 2.148-.262 2.913-.558.788-.306 1.459-.723 2.126-1.379.666-.667 1.079-1.335 1.395-2.125.296-.765.499-1.636.558-2.913.058-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.059-1.277-.262-2.149-.558-2.913-.306-.789-.723-1.459-1.379-2.126C21.333 1.061 20.666.476 19.875.122c-.765-.296-1.636-.499-2.913-.558C15.667.008 15.26 0 12 0zm0 2.16c3.203 0 3.585.009 4.849.07 1.17.054 1.805.244 2.227.408.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.354 1.057.408 2.227.061 1.264.07 1.646.07 4.849s-.009 3.585-.07 4.849c-.054 1.17-.244 1.805-.408 2.227-.217.562-.477.96-.896 1.382-.42.419-.819.679-1.381.896-.422.164-1.057.354-2.227.408-1.264.061-1.646.07-4.849.07s-3.585-.009-4.849-.07c-1.17-.054-1.805-.244-2.227-.408-.562-.217-.96-.477-1.382-.896-.419-.42-.679-.819-.896-1.381-.164-.422-.354-1.057-.408-2.227-.061-1.264-.07-1.646-.07-4.849s.009-3.585.07-4.849c.054-1.17.244-1.805.408-2.227.217-.562.477-.96.896-1.382.42-.419.819-.679 1.381-.896.422-.164 1.057-.354 2.227-.408 1.264-.061 1.646-.07 4.849-.07Z',
    color: 'hover:text-pink-500',
  },
  {
    name: 'Twitter',
    key: 'twitter',
    icon: 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417a9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z',
    color: 'hover:text-blue-400',
  },
  {
    name: 'TikTok',
    key: 'tiktok',
    icon: 'M19.498 3.75H15.5V19.5a1.5 1.5 0 11-3 0V3.75h-3.998a1.5 1.5 0 000 3h3.998V19.5a4.5 4.5 0 109-4.5V6.75h4.5a1.5 1.5 0 000-3z',
    color: 'hover:text-gray-700',
  },
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
    <div
      v-if="isOpen"
      @click="handleBackdropClick"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <transition name="modal-scale">
        <div
          v-if="isOpen"
          class="relative w-full max-w-md bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl p-8"
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
          <h2 class="text-3xl font-black text-white mb-8">Sígueme en Redes</h2>

          <!-- Grid de redes sociales -->
          <div class="grid grid-cols-2 gap-4">
            <a
              v-for="network in networks"
              :key="network.key"
              v-show="getSocialUrl(network.key)"
              :href="getSocialUrl(network.key)"
              target="_blank"
              rel="noopener noreferrer"
              class="flex flex-col items-center justify-center p-6 bg-zinc-900 rounded-2xl ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300 hover:scale-105"
            >
              <svg :class="`w-8 h-8 text-white mb-3 transition-colors ${network.color}`" fill="currentColor" :viewBox="`0 0 24 24`">
                <path :d="network.icon"></path>
              </svg>
              <span class="text-white font-semibold text-sm">{{ network.name }}</span>
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
