<!-- resources/js/Components/Public/Events/EventModal.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  event: { type: Object, required: true },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

// Formatear la fecha
const formattedDate = computed(() => {
  if (!props.event.event_date) return 'Próximamente'
  const date = new Date(props.event.event_date)
  return new Intl.DateTimeFormat('es-ES', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
})

// Generar enlace de WhatsApp
const whatsappLink = computed(() => {
  const message = encodeURIComponent(`Hola, me interesa conocer más sobre el evento "${props.event.title}". ¿Podrías proporcionarme más información?`)
  return `https://wa.me/?text=${message}`
})

const closeModal = () => {
  emit('close')
}

// Cerrar modal al hacer clic fuera
const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) {
    closeModal()
  }
}
</script>

<template>
  <!-- Modal backdrop -->
  <transition name="fade">
    <div
      v-if="isOpen"
      @click="handleBackdropClick"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <!-- Modal contenido -->
      <transition name="modal-scale">
        <div
          v-if="isOpen"
          class="relative w-full max-w-2xl bg-zinc-950 rounded-3xl ring-1 ring-white/10 shadow-2xl max-h-[90vh] overflow-y-auto"
        >
          <!-- Header con imagen -->
          <div class="relative h-64 overflow-hidden">
            <img
              :src="event.poster_url"
              :alt="event.title"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 to-transparent"></div>

            <!-- Botón cerrar -->
            <button
              @click="closeModal"
              class="absolute top-4 right-4 z-10 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full backdrop-blur transition"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Contenido -->
          <div class="p-6 md:p-8">
            <!-- Título y ubicación -->
            <h2 class="text-3xl md:text-4xl font-black text-white">{{ event.title }}</h2>
            <p class="text-lg text-zinc-400 mt-2">📍 {{ event.location || 'Ubicación no disponible' }}</p>

            <!-- Fecha -->
            <div class="mt-4 flex items-center gap-2 text-zinc-300">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
              </svg>
              <span>{{ formattedDate }}</span>
            </div>

            <!-- Descripción -->
            <div v-if="event.description" class="mt-6">
              <h3 class="text-white font-bold mb-2">Descripción</h3>
              <p class="text-zinc-300 leading-relaxed">{{ event.description }}</p>
            </div>

            <!-- Artistas asociados -->
            <div v-if="event.artists && event.artists.length > 0" class="mt-6">
              <h3 class="text-white font-bold mb-3">Artistas</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="artist in event.artists"
                  :key="artist.id"
                  class="inline-flex items-center gap-2 bg-zinc-900 text-white px-4 py-2 rounded-full text-sm"
                >
                  {{ artist.name }}
                </span>
              </div>
            </div>

            <!-- CTA Botones -->
            <div class="mt-8 flex gap-4">
              <a
                :href="whatsappLink"
                target="_blank"
                rel="noopener noreferrer"
                class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-green-500 hover:bg-green-600 text-white font-semibold shadow-lg hover:shadow-xl transition"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-4.255.949c-1.238.503-2.37 1.236-3.355 2.115-1.7 1.653-2.773 3.957-2.773 6.379 0 2.6.844 5.007 2.397 6.999l-2.571 7.351 7.557-2.49c1.926 1.028 4.04 1.57 6.266 1.57h.005c7.287 0 13.203-5.979 13.203-13.331 0-3.559-1.38-6.902-3.887-9.409-2.507-2.507-5.851-3.887-9.389-3.887z"/>
                </svg>
                Más información por WhatsApp
              </a>
              <button
                @click="closeModal"
                class="px-6 py-3 rounded-2xl bg-zinc-800 hover:bg-zinc-700 text-white font-semibold transition"
              >
                Cerrar
              </button>
            </div>
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
