<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { ref } from 'vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PlatformLinksModal from '@/Components/Public/Music/PlatformLinksModal.vue'
import SpotifyEmbedPlayer from '@/Components/Public/Music/SpotifyEmbedPlayer.vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
  release: { type: Object, required: true },
})

const selectedTrack = ref(null)
const platformTrack = ref(null)

const formatDate = (dateString) => {
  if (!dateString) return 'Fecha por anunciar'

  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const playTrack = (track) => {
  if (track?.spotify_embed_url) {
    selectedTrack.value = track
  }
}

const openPlatforms = (track) => {
  platformTrack.value = track
}
</script>

<template>
  <Head :title="`${release.title} — Dilo Records`" />

  <section class="relative overflow-hidden bg-black pt-32 pb-16">
    <div class="absolute inset-0 opacity-30">
      <img
        :src="release.optimized_cover_url || release.cover_url || '/placeholder.webp'"
        :alt="release.title"
        class="h-full w-full object-cover blur-2xl"
      />
    </div>
    <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/80 to-black"></div>

    <div class="relative mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
      <div class="mx-auto w-full max-w-sm lg:max-w-none">
        <img
          :src="release.optimized_cover_url || release.cover_url || '/placeholder.webp'"
          :alt="release.title"
          class="aspect-square w-full rounded-2xl object-cover shadow-2xl ring-1 ring-white/10"
        />
      </div>

      <div class="flex flex-col justify-end">
        <Link
          :href="route('public.releases.index')"
          class="mb-6 inline-flex w-fit items-center gap-2 text-sm font-semibold text-zinc-300 transition hover:text-dilo-orange"
        >
          <Icon icon="mdi:arrow-left" class="h-4 w-4" />
          Releases
        </Link>

        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-dilo-orange">{{ release.type || 'Release' }}</p>
        <h1 class="mt-3 text-4xl font-black text-white md:text-6xl">{{ release.title }}</h1>
        <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-300">
          <Link
            v-if="release.artist?.slug"
            :href="route('public.artists.show', release.artist.slug)"
            class="font-semibold text-white transition hover:text-dilo-orange"
          >
            {{ release.artist.name }}
          </Link>
          <span v-else>{{ release.artist?.name || 'Dilo Records' }}</span>
          <span class="text-zinc-600">•</span>
          <span>{{ formatDate(release.release_date) }}</span>
          <span class="text-zinc-600">•</span>
          <span>{{ release.tracks?.length || 0 }} canciones</span>
        </div>

        <p v-if="release.description" class="mt-6 max-w-3xl text-base leading-7 text-zinc-300">
          {{ release.description }}
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
          <a
            v-if="release.spotify_url"
            :href="release.spotify_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-dilo-orange px-5 text-sm font-bold text-black transition hover:bg-dilo-orange-light"
          >
            <Icon icon="simple-icons:spotify" class="h-4 w-4" />
            Spotify
          </a>
          <Link
            :href="route('public.songs.index', { release: release.slug })"
            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/10 px-5 text-sm font-bold text-white transition hover:border-dilo-orange hover:text-dilo-orange"
          >
            Ver canciones
          </Link>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-black pb-24">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
      <div class="mb-6 flex items-end justify-between gap-4 border-b border-white/10 pb-4">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-dilo-orange">Tracklist</p>
          <h2 class="mt-1 text-3xl font-black text-white">Canciones del release</h2>
        </div>
      </div>

      <div v-if="release.tracks?.length" class="divide-y divide-white/10 rounded-2xl bg-zinc-950/80 p-4 ring-1 ring-white/10">
        <div
          v-for="track in release.tracks"
          :key="track.id"
          class="flex items-center gap-3 py-4"
        >
          <span class="w-8 shrink-0 text-sm text-zinc-500">{{ track.track_number || '-' }}</span>
          <img
            :src="track.optimized_cover_url || track.effective_cover_url || release.optimized_cover_url || release.cover_url || '/placeholder.webp'"
            :alt="track.title"
            class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-white/10"
            loading="lazy"
          />
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-white">{{ track.title }}</p>
            <p class="text-xs text-zinc-500">{{ track.duration || 'Duración no definida' }}</p>
          </div>
          <button
            v-if="track.spotify_embed_url"
            type="button"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-2.5 text-[0.68rem] font-black uppercase tracking-[0.08em] text-black transition hover:bg-dilo-orange sm:px-3 sm:text-xs"
            aria-label="Reproducir en Spotify"
            @click="playTrack(track)"
          >
            <Icon icon="simple-icons:spotify" class="h-4 w-4" />
            <span class="whitespace-nowrap">Escucha ahora</span>
          </button>
          <button
            v-if="track.platforms?.length"
            type="button"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/10 transition hover:bg-white hover:text-black"
            aria-label="Otras plataformas"
            @click="openPlatforms(track)"
          >
            <Icon icon="mdi:dots-horizontal" class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div v-else class="rounded-2xl bg-zinc-950 px-6 py-12 text-center ring-1 ring-white/10">
        <p class="text-lg font-bold text-white">Este release todavía no tiene canciones públicas asociadas.</p>
      </div>
    </div>
  </section>

  <SpotifyEmbedPlayer
    :track="selectedTrack"
    @close="selectedTrack = null"
    @platforms="openPlatforms"
  />

  <PlatformLinksModal
    :track="platformTrack"
    :is-open="Boolean(platformTrack)"
    @close="platformTrack = null"
  />
</template>
