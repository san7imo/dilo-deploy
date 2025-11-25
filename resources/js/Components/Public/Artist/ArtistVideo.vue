<!-- resources/js/Components/Public/Artist/ArtistVideo.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  youtubeUrl: { type: String, default: '' },
})

// Extraer el ID del video de YouTube
const youtubeVideoId = computed(() => {
  if (!props.youtubeUrl) return null
  
  // Soporta: https://www.youtube.com/watch?v=ID o https://youtu.be/ID
  const regex = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/
  const match = props.youtubeUrl.match(regex)
  return match ? match[1] : null
})

// URL del iframe embebido
const embedUrl = computed(() => {
  return youtubeVideoId.value ? `https://www.youtube.com/embed/${youtubeVideoId.value}` : null
})
</script>

<template>
  <div v-if="embedUrl" class="w-full h-full">
    <iframe
      :src="embedUrl"
      class="w-full h-full rounded-2xl"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
      allowfullscreen
    ></iframe>
  </div>
  <div v-else class="w-full h-full flex items-center justify-center bg-zinc-900 rounded-2xl">
    <p class="text-zinc-400">No hay video disponible</p>
  </div>
</template>
