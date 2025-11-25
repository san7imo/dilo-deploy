<!-- resources/js/Components/Public/Artists/ArtistPlaylistCard.vue -->
<script setup>
import TrackList from './TrackList.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  artist: { type: Object, required: true },
})

const goTo = (artist) => (route ? route('public.artists.show', artist.slug) : `/artistas/${artist.slug}`)

const emit = defineEmits(['play'])
</script>

<template>
  <article class="bg-zinc-950/60 ring-1 ring-white/5 rounded-3xl overflow-hidden">
    <div class="flex flex-col md:flex-row">
      <div class="md:w-1/3">
        <img :src="artist.playlist_image_url" :alt="artist.name" class="w-full h-56 md:h-full object-cover" />
      </div>

      <div class="md:w-2/3 p-5 md:p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-white text-xl font-bold tracking-tight">{{ artist.name }}</h3>
            <p class="text-sm text-zinc-400">{{ (artist.tracks?.length || 0) }} canciones</p>
          </div>
          <Link :href="goTo(artist)" class="text-xs font-semibold text-black bg-white px-3 py-1.5 rounded-lg hover:opacity-90">
            Ver perfil
          </Link>
        </div>

        <div class="mt-4">
          <TrackList :tracks="artist.tracks ?? []" @play="(t) => emit('play', { artist, track: t })" />
        </div>
      </div>
    </div>
  </article>
</template>
