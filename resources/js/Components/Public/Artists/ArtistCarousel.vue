<!-- resources/js/Components/Public/Artists/ArtistCarousel.vue -->
<script setup>
import { computed } from 'vue'
import ArtistCard from './ArtistCard.vue'

const props = defineProps({
  artists: { type: Object, required: true }, // paginator de Laravel o array simple
})

const dataList = computed(() => {
  // Soporta paginator { data: [...] } o array
  return Array.isArray(props.artists) ? props.artists : (props.artists.data ?? [])
})
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h2 class="text-white text-2xl md:text-3xl font-bold mb-6">Artistas</h2>

    <div class="relative">
      <div
        class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-thin scrollbar-thumb-zinc-700"
      >
        <div
          v-for="artist in dataList"
          :key="artist.id"
          class="min-w-[240px] snap-start"
        >
          <ArtistCard :artist="artist" />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* opcional si no usas plugin scrollbar */
.scrollbar-thin::-webkit-scrollbar { height: 8px; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 9999px;}
</style>
