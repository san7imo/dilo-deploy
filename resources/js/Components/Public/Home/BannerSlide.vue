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
      <h2 class="text-2xl sm:text-4xl font-bold mb-6 uppercase tracking-wide">
        {{ props.item.name }}
      </h2>
      <Link
        :href="`/artistas/${props.item.slug}`"
        class="inline-block bg-white text-black px-3 py-1 rounded-full text-1xl font-semibold hover:bg-gray-100 transition"
      >
        Ver más
      </Link>
    </div>
  </div>
</template>
