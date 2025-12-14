<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  artist: { type: Object, required: true },
})

// Expandir / contraer biografía
const isExpanded = ref(false)

// ¿Necesita "Leer más"?
const needsExpansion = computed(() => {
  return props.artist.bio && props.artist.bio.length > 300
})

// Biografía con saltos de línea
const formattedBio = computed(() => {
  if (!props.artist.bio) return ''
  return props.artist.bio.replace(/\n/g, '<br>')
})

// iconos de redes sociales
const socialIconMap = {
  instagram: 'simple-icons:instagram',
  facebook: 'simple-icons:facebook',
  x: 'simple-icons:x',
  twitter: 'simple-icons:x',
  tiktok: 'simple-icons:tiktok',
  spotify: 'simple-icons:spotify',
  youtube: 'simple-icons:youtube',
  apple: 'simple-icons:applemusic',
  amazon: 'simple-icons:amazonmusic',
  deezer: 'simple-icons:deezer',
  soundcloud: 'simple-icons:soundcloud',
  tidal: 'simple-icons:tidal',
}
</script>

<template>
  <section class="relative w-full bg-black py-16 md:py-24 overflow-hidden">
    <!-- Fondo decorativo -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-12 text-center">
        <h2 class="text-4xl md:text-5xl font-black text-white mb-2">
          Sobre {{ artist.name }}
        </h2>
        <div class="h-1 w-16 bg-white mx-auto rounded-full"></div>
      </div>

      <!-- Biografía -->
      <div v-if="artist.bio" class="relative">
        <div
          class="bg-gradient-to-br from-zinc-900 via-zinc-900/50 to-black rounded-2xl ring-1 ring-white/10 p-8 md:p-12 backdrop-blur-sm">
          <!-- Comillas -->
          <div
            class="absolute -top-6 left-8 w-12 h-12 bg-white text-black rounded-full flex items-center justify-center text-2xl font-black">
            "
          </div>

          <!-- Texto -->
          <div class="relative pt-4">
            <p :class="[
              'text-zinc-200 text-base md:text-lg leading-relaxed transition-all duration-500',
              !isExpanded && needsExpansion ? 'line-clamp-4' : ''
            ]" v-html="formattedBio"></p>

            <!-- Fade -->
            <div v-if="!isExpanded && needsExpansion"
              class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-zinc-900 to-transparent pointer-events-none rounded-b-2xl">
            </div>
          </div>

          <!-- Leer más -->
          <div v-if="needsExpansion" class="mt-6 flex justify-center">
            <button @click="isExpanded = !isExpanded"
              class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg transition-all hover:scale-105">
              {{ isExpanded ? 'Leer menos' : 'Leer más' }}
              <Icon icon="mdi:chevron-down" :class="['w-5 h-5 transition-transform', isExpanded && 'rotate-180']" />
            </button>
          </div>
        </div>

        <!-- Info extra -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
          <!-- País -->
          <div v-if="artist.country" class="text-center p-6 bg-zinc-900/50 rounded-xl ring-1 ring-white/10">
            <p class="text-zinc-400 text-sm font-semibold uppercase mb-2">
              País de origen
            </p>
            <p class="text-white text-2xl font-bold">
              {{ artist.country }}
            </p>
          </div>

          <!-- Redes -->
          <div v-if="artist.social_links && Object.keys(artist.social_links).length"
            class="text-center p-6 bg-zinc-900/50 rounded-xl ring-1 ring-white/10">
            <p class="text-zinc-400 text-sm font-semibold uppercase mb-3">
              Sígueme en
            </p>

            <div class="flex justify-center gap-3 flex-wrap">
              <a v-for="(url, platform) in artist.social_links" :key="platform" :href="url" target="_blank"
                rel="noopener noreferrer" :title="platform"
                class="inline-flex items-center justify-center w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full transition-all hover:scale-110">
                <Icon :icon="socialIconMap[platform] || 'mdi:web'" class="w-5 h-5" />
              </a>
            </div>
          </div>

          <!-- Rol -->
          <div class="text-center p-6 bg-zinc-900/50 rounded-xl ring-1 ring-white/10">
            <p class="text-zinc-400 text-sm font-semibold uppercase mb-2">
              En Dilo Records
            </p>
            <p class="text-white text-2xl font-bold">Artista</p>
            <p class="text-zinc-400 text-xs mt-1">desde siempre</p>
          </div>
        </div>
      </div>

      <!-- Sin bio -->
      <div v-else class="text-center p-12 bg-zinc-900/50 rounded-2xl ring-1 ring-white/10">
        <p class="text-zinc-400 text-lg">
          Próximamente más información sobre este artista...
        </p>
      </div>
    </div>
  </section>
</template>
