<!-- resources/js/Components/Public/Events/EventCard.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  event: { type: Object, required: true },
})

const emit = defineEmits(['select'])

// Formatear la fecha de manera legible
const formattedDate = computed(() => {
  if (!props.event.event_date) return 'Próximamente'
  const date = new Date(props.event.event_date)
  return new Intl.DateTimeFormat('es-ES', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(date)
})

// Obtener el mes y año para el badge
const monthBadge = computed(() => {
  if (!props.event.event_date) return 'TBD'
  const date = new Date(props.event.event_date)
  return new Intl.DateTimeFormat('es-ES', {
    month: 'short',
    day: 'numeric'
  }).format(date).toUpperCase()
})
</script>

<template>
  <div
    @click="emit('select', event)"
    class="cursor-pointer group relative overflow-hidden rounded-2xl bg-zinc-900 ring-1 ring-white/10 hover:ring-white/20 transition-all duration-300 hover:shadow-xl"
  >
    <!-- Imagen del póster -->
    <div class="relative h-80 overflow-hidden bg-gradient-to-br from-zinc-800 to-zinc-900">
      <img
        v-if="event.poster_url"
        :src="event.poster_url"
        :alt="event.title"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <div class="text-center">
          <svg class="w-12 h-12 text-zinc-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <p class="text-zinc-500 text-sm">Sin imagen</p>
        </div>
      </div>
      <!-- Overlay gradiente -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

      <!-- Badge de fecha -->
      <div class="absolute top-3 right-3 bg-white/90 backdrop-blur text-black px-3 py-2 rounded-lg font-bold text-xs">
        {{ monthBadge }}
      </div>
    </div>

    <!-- Contenido -->
    <div class="p-4">
      <h3 class="text-white font-bold text-lg truncate">{{ event.title }}</h3>
      <p class="text-zinc-400 text-sm mt-1 truncate">{{ event.location || 'Ubicación no disponible' }}</p>
      <p class="text-zinc-300 text-xs mt-2">{{ formattedDate }}</p>

      <!-- Artistas asociados (preview) -->
      <div v-if="event.artists && event.artists.length > 0" class="mt-3 pt-3 border-t border-white/10">
        <p class="text-zinc-400 text-xs mb-2">Artistas:</p>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(artist, idx) in event.artists.slice(0, 2)"
            :key="idx"
            class="text-xs bg-zinc-800 text-white px-2 py-1 rounded"
          >
            {{ artist.name }}
          </span>
          <span
            v-if="event.artists.length > 2"
            class="text-xs bg-zinc-800 text-zinc-300 px-2 py-1 rounded"
          >
            +{{ event.artists.length - 2 }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
