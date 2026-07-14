<!-- resources/js/Components/Public/Artists/ArtistPlaylistCard.vue -->
<script setup>
import TrackList from './TrackList.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  artist: { type: Object, required: true },
})

const emit = defineEmits(['play', 'platforms'])

const goTo = (artist) => (typeof route === 'function' ? route('public.artists.show', artist.slug) : `/artistas/${artist.slug}`)
const songsUrl = computed(() => {
  if (typeof route === 'function' && route().has('public.songs.index')) {
    return route('public.songs.index', { artist: props.artist.slug })
  }

  return `/canciones?artist=${encodeURIComponent(props.artist.slug)}`
})
</script>

<template>
  <article class="overflow-hidden rounded-2xl bg-zinc-950/80 ring-1 ring-white/10 transition hover:ring-dilo-orange/40">
    <div class="flex flex-col">
      <div class="relative h-56 overflow-hidden bg-zinc-900">
        <img
          :src="artist.playlist_image_url || artist.image_url || artist.main_image_url || '/placeholder.webp'"
          :alt="artist.name"
          class="h-full w-full object-cover transition duration-500 hover:scale-105"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"></div>
        <div class="absolute bottom-4 left-4 right-4">
          <h3 class="truncate text-2xl font-black tracking-tight text-white">{{ artist.name }}</h3>
          <p class="mt-1 text-sm text-zinc-300">
            {{ artist.releases_count ?? 0 }} lanzamientos
          </p>
        </div>
      </div>

      <div class="p-5">
        <div class="mb-4 flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-dilo-orange">Canciones</p>
            <p class="mt-1 text-sm text-zinc-400">
              {{ (artist.tracks?.length || 0) }} destacadas
            </p>
          </div>
          <Link
            :href="goTo(artist)"
            class="rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/10 transition hover:bg-white hover:text-black"
          >
            Ver perfil
          </Link>
        </div>

        <TrackList
          v-if="artist.tracks?.length"
          :tracks="artist.tracks"
          @play="(track) => emit('play', track)"
          @platforms="(track) => emit('platforms', track)"
        />

        <div v-else class="rounded-xl bg-zinc-900/70 px-4 py-6 text-sm text-zinc-400 ring-1 ring-white/10">
          Aun no hay canciones publicadas para este artista.
        </div>

        <Link
          :href="songsUrl"
          class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-dilo-orange px-4 py-3 text-sm font-bold text-black transition hover:bg-dilo-orange-light"
        >
          Ver más canciones
        </Link>
      </div>
    </div>
  </article>
</template>
