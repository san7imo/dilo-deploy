<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import BannerSlide from '@/Components/Public/Home/BannerSlide.vue'

const props = defineProps<{
  items: {
    id: number
    name: string
    slug: string
    image: string
  }[]
}>()

const current = ref(0)
const next = () => (current.value = (current.value + 1) % props.items.length)
const prev = () => (current.value = (current.value - 1 + props.items.length) % props.items.length)

// auto-rotación
let timer: any
onMounted(() => {
  timer = setInterval(next, 6000)
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
  <section class="relative w-full h-[100dvh] overflow-hidden bg-black">
    <!-- Slides -->
    <div
      v-for="(item, i) in props.items"
      :key="item.id"
      class="absolute inset-0 transition-opacity duration-700 ease-in-out"
      :class="i === current ? 'opacity-100 z-10' : 'opacity-0 z-0'"
    >
      <BannerSlide :item="item" />
    </div>

    <!-- Botones laterales -->
    <button
      @click="prev"
      class="absolute left-4 top-1/2 -translate-y-1/2 z-20 h-12 w-12 rounded-full bg-black/40 hover:bg-black/70 flex items-center justify-center transition"
      aria-label="Anterior"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white" class="h-6 w-6">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <button
      @click="next"
      class="absolute right-4 top-1/2 -translate-y-1/2 z-20 h-12 w-12 rounded-full bg-black/40 hover:bg-black/70 flex items-center justify-center transition"
      aria-label="Siguiente"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white" class="h-6 w-6">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </section>
</template>
