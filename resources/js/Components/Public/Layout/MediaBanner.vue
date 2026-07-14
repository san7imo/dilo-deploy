<!-- resources/js/Components/Public/Layout/MediaBanner.vue -->
<script setup>
defineProps({
  image: { type: String, required: true },
  imageAlt: { type: String, default: '' },
})

const isVideo = (path) => {
  if (!path) return false

  return /\.(mp4|webm|ogg|mov)$/i.test(path)
}
</script>

<template>
  <section class="w-full overflow-hidden bg-black">
    <video
      v-if="isVideo(image)"
      :src="image"
      autoplay
      muted
      loop
      playsinline
      class="block h-auto w-full"
    />
    <img
      v-else
      :src="image"
      :alt="imageAlt"
      class="block h-auto w-full"
      width="1672"
      height="941"
      fetchpriority="high"
    />
  </section>
</template>
