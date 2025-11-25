<!-- resources/js/Components/Public/Artists/ArtistCard.vue -->
<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  artist: { type: Object, required: true },
  useZiggy: { type: Boolean, default: true },
})

const goTo = (artist) => {
  if (props.useZiggy && route) {
    return route('public.artists.show', artist.slug)
  }
  return `/artistas/${artist.slug}`
}
</script>

<template>
  <Link :href="goTo(artist)" class="group block">
    <div class="bg-zinc-900 rounded-2xl overflow-hidden ring-1 ring-white/5">
      <img :src="artist.image_url" :alt="artist.name" class="w-full h-56 object-cover group-hover:scale-105 transition" />
      <div class="p-4">
        <h3 class="text-white font-semibold tracking-tight">{{ artist.name }}</h3>
        <p class="text-xs text-zinc-400 mt-1">{{ artist.releases_count ?? 0 }} lanzamientos</p>
      </div>
    </div>
  </Link>
</template>
