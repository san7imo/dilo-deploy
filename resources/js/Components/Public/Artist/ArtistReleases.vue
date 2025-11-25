<!-- resources/js/Components/Public/Artist/ArtistReleases.vue -->
<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  releases: { type: Array, required: true },
})

// Estado del carrusel
const currentIndex = ref(0)

// Releases ordenados por fecha (más recientes primero)
const sortedReleases = computed(() => {
  return [...props.releases].sort((a, b) => {
    return new Date(b.release_date) - new Date(a.release_date)
  })
})

// Calcular cuántos items mostrar según pantalla
const itemsToShow = computed(() => {
  return 4 // Por defecto 4, se ajusta con CSS
})

// Release actual
const currentRelease = computed(() => {
  return sortedReleases.value[currentIndex.value]
})

// Próximo release
const nextIndex = computed(() => {
  return (currentIndex.value + 1) % sortedReleases.value.length
})

// Release anterior
const prevIndex = computed(() => {
  return (currentIndex.value - 1 + sortedReleases.value.length) % sortedReleases.value.length
})

// Métodos de navegación
const goToNext = () => {
  currentIndex.value = nextIndex.value
}

const goToPrev = () => {
  currentIndex.value = prevIndex.value
}

const goToSlide = (index) => {
  currentIndex.value = index
}

// Formatear fecha
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

// Obtener tipo de release con icono
const getReleaseType = (type) => {
  const types = {
    album: { label: 'Álbum', icon: '💿' },
    single: { label: 'Single', icon: '🎵' },
    ep: { label: 'EP', icon: '📀' },
  }
  return types[type?.toLowerCase()] || { label: 'Lanzamiento', icon: '🎶' }
}
</script>

<template>
  <section v-if="sortedReleases.length > 0" class="relative w-full bg-gradient-to-b from-black to-zinc-950 py-16 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Título -->
      <div class="mb-12 text-center">
        <h2 class="text-4xl md:text-5xl font-black text-white mb-2">Discografía</h2>
        <p class="text-zinc-400 text-lg">{{ sortedReleases.length }} lanzamientos</p>
      </div>

      <!-- Carrusel -->
      <div class="relative">
        <!-- Container del carrusel -->
        <div class="overflow-hidden">
          <div class="flex transition-transform duration-500 ease-out" :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
            <div v-for="(release, index) in sortedReleases" :key="index" class="w-full flex-shrink-0 px-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <!-- Imagen del release -->
                <div class="flex justify-center md:justify-end">
                  <div class="relative group w-64 h-64 md:w-72 md:h-72 rounded-2xl overflow-hidden shadow-2xl">
                    <img
                      :src="release.optimized_cover_url"
                      :alt="release.title"
                      class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <!-- Overlay gradiente -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-6">
                      <div>
                        <p class="text-white/80 text-sm mb-1">{{ getReleaseType(release.type).label }}</p>
                        <p class="text-white font-bold text-lg">{{ formatDate(release.release_date) }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Información del release -->
                <div class="md:col-start-1 md:row-start-1 md:flex md:flex-col md:justify-center">
                  <!-- Badge de tipo -->
                  <div class="inline-flex items-center gap-2 w-fit mb-4">
                    <span class="text-2xl">{{ getReleaseType(release.type).icon }}</span>
                    <span class="px-3 py-1 bg-white/10 text-white text-xs font-bold rounded-full">
                      {{ getReleaseType(release.type).label }}
                    </span>
                  </div>

                  <!-- Título -->
                  <h3 class="text-3xl md:text-4xl font-black text-white mb-2">{{ release.title }}</h3>

                  <!-- Fecha -->
                  <p class="text-zinc-400 text-sm mb-4">{{ formatDate(release.release_date) }}</p>

                  <!-- Descripción -->
                  <p v-if="release.description" class="text-zinc-300 text-base leading-relaxed mb-6 line-clamp-3">
                    {{ release.description }}
                  </p>

                  <!-- Plataformas de streaming -->
                  <div v-if="release.platforms && release.platforms.length > 0" class="flex flex-wrap gap-3 mb-6">
                    <a
                      v-for="platform in release.platforms"
                      :key="platform.name"
                      :href="platform.url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-lg transition-colors duration-300"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" />
                      </svg>
                      {{ platform.name }}
                    </a>
                  </div>

                  <!-- Botón explorar -->
                  <button class="w-full md:w-auto px-8 py-3 bg-white text-black font-bold rounded-xl hover:bg-gray-100 transition-colors duration-300 flex items-center justify-center gap-2">
                    <span>Explorar</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de navegación -->
        <button
          @click="goToPrev"
          class="absolute left-0 top-1/2 -translate-y-1/2 z-10 -ml-6 md:-ml-8 bg-white/10 hover:bg-white/20 text-white p-3 rounded-full transition-all duration-300 hover:scale-110"
          title="Anterior"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>

        <button
          @click="goToNext"
          class="absolute right-0 top-1/2 -translate-y-1/2 z-10 -mr-6 md:-mr-8 bg-white/10 hover:bg-white/20 text-white p-3 rounded-full transition-all duration-300 hover:scale-110"
          title="Siguiente"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>

      <!-- Indicadores de deslizamiento -->
      <div class="flex justify-center gap-2 mt-12">
        <button
          v-for="(_, index) in sortedReleases"
          :key="index"
          @click="goToSlide(index)"
          :class="[
            'h-3 rounded-full transition-all duration-300',
            index === currentIndex
              ? 'w-8 bg-white'
              : 'w-3 bg-white/30 hover:bg-white/50'
          ]"
          :title="`Ir al release ${index + 1}`"
        ></button>
      </div>
    </div>
  </section>
</template>
