<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const props = defineProps<{
  item: {
    id: number
    name: string
    slug: string
    image: string
  }
}>()
</script>

<template>
  <div
    class="relative w-full h-[100dvh] bg-cover bg-center  flex items-end justify-center"
    :style="`background-image: url(${props.item.image})`"
  >
    <div class="absolute inset-0 bg-black/50" />
    <div class="relative z-10 text-center text-white px-4 bottom-3">
      <h2 class="text-xl sm:text-3xl font-bold mb-6 uppercase tracking-wide">
        {{ props.item.name }}
      </h2>
      <Link
        :href="`/artistas/${props.item.slug}`"
        class="inline-flex items-center justify-center h-10 w-10 rounded-full border border-white/70 text-white hover:border-white hover:bg-white/10 transition"
        aria-label="Ver artista"
      >
        <span class="text-lg font-light leading-none">+</span>
      </Link>
    </div>
  </div>
</template>
