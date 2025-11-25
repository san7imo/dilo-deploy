<!-- resources/js/Components/Public/Events/EventBanner.vue -->
<script setup>
defineProps({
  title: { type: String, default: '' },
  highlight: { type: String, default: '' },
  cta: { type: String, default: '' },
  image: { type: String, default: '' },
})

const isVideo = (path) => {
  if (!path) return false
  return /\.(mp4|webm|ogg|mov)$/i.test(path)
}
</script>

<template>
  <section class="relative w-full min-h-[52vh] md:min-h-[64vh] overflow-hidden">
    <!-- Video o imagen de fondo -->
    <video
      v-if="isVideo(image)"
      :src="image"
      autoplay
      muted
      loop
      playsinline
      class="absolute inset-0 w-full h-full object-cover opacity-70"
    />
    <img
      v-else
      :src="image"
      alt=""
      class="absolute inset-0 w-full h-full object-cover opacity-70"
    />

  </section>
</template>
