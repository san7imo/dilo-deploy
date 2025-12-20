<!-- resources/js/Components/Public/Artist/ArtistVideo.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  artist: { type: Object, default: () => ({}) },
  youtubeUrl: { type: String, default: '' },
})

// Extraer ID del video
const videoId = computed(() => {
  if (!props.youtubeUrl) return null
  const match = props.youtubeUrl.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/)
  return match ? match[1] : null
})

// URL del embed
const embedUrl = computed(() => {
  return videoId.value
    ? `https://www.youtube.com/embed/${videoId.value}?autoplay=0&rel=0`
    : null
})

// Extraer ID de Vimeo
const vimeoId = computed(() => {
  if (!props.youtubeUrl || !props.youtubeUrl.includes('vimeo')) return null
  const match = props.youtubeUrl.match(/vimeo\.com\/(\d+)/)
  return match ? match[1] : null
})

// URL del embed Vimeo
const vimeoEmbedUrl = computed(() => {
  return vimeoId.value
    ? `https://player.vimeo.com/video/${vimeoId.value}`
    : null
})
</script>

<template>
  <iframe v-if="embedUrl" :src="embedUrl" class="w-full h-full"
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    allowfullscreen></iframe>
  <iframe v-else-if="vimeoEmbedUrl" :src="vimeoEmbedUrl" class="w-full h-full" allow="autoplay; fullscreen"
    allowfullscreen></iframe>
  <div v-else class="w-full h-full bg-black/50 flex items-center justify-center">
    <p class="text-gray-400">No hay video disponible</p>
  </div>
</template>
