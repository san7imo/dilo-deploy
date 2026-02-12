<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  artist: { type: Object, required: true },
})

// iconos de redes sociales
const iconMap = {
  facebook: 'simple-icons:facebook',
  instagram: 'simple-icons:instagram',
  x: 'simple-icons:x',
  tiktok: 'simple-icons:tiktok',
  youtube: 'simple-icons:youtube',
}

// Redes disponibles del artista
const socialLinks = computed(() => {
  if (!props.artist.social_links) return []

  return Object.entries(props.artist.social_links)
    .filter(([_, url]) => url)
    .map(([platform, url]) => ({
      platform,
      url,
      icon: iconMap[platform],
    }))
    .filter(item => item.icon)
})
</script>

<template>
  <section class="relative w-full h-[32rem] md:h-[40rem] overflow-hidden">
    <!-- Imagen de fondo -->
    <img :src="artist.banner_artist_url" :alt="artist.name" class="w-full h-full object-cover" />

    <!-- Overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-black/0 via-black/40 to-black/80"></div>

    <!-- Contenido -->
    <div class="absolute inset-0 flex flex-col items-center justify-end pb-10">
      <h2 class="text-xl md:text-xl font-black text-white mb-3">
        ¡Sígueme!
      </h2>

      <!-- Redes sociales -->
      <div class="flex gap-5">
        <a v-for="item in socialLinks" :key="item.platform" :href="item.url" target="_blank" rel="noopener noreferrer"
          :title="item.platform" class="group flex  items-center justify-center w-6 h-6 rounded-full
                 bg-white-8 hover:bg-white/20 transition-all duration-300
                 hover:scale-110">
          <Icon :icon="item.icon" class="w-7 h-7 text-white group-hover:opacity-90" />
        </a>
      </div>
    </div>
  </section>
</template>
