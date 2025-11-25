<!-- resources/js/Pages/Public/Artists/Index.vue -->
<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BannerFullWidth from '@/Components/Public/Artists/BannerFullWidth.vue'
import ArtistCarousel from '@/Components/Public/Artists/ArtistCarousel.vue'
import ArtistPlaylistCard from '@/Components/Public/Artists/ArtistPlaylistCard.vue'

// Si tienes un layout público global, descomenta:
import PublicLayout from '@/Layouts/PublicLayout.vue'
 defineOptions({ layout: PublicLayout })

const props = defineProps({
  artists: { type: Object, required: true }, // paginator con data + tracks por artista
  banner: { type: Object, default: () => ({}) },
})

// soporte paginator o array
const artistList = computed(() => Array.isArray(props.artists) ? props.artists : (props.artists.data ?? []))

// Reproductor simple temporal (hasta el reproductor global de la Fase 6)
const audio = typeof Audio !== 'undefined' ? new Audio() : null
const nowPlaying = ref(null)

const handlePlay = ({ artist, track }) => {
  if (!track?.audio_url || !audio) return
  if (nowPlaying.value?.track?.id === track.id) {
    // toggle pausa
    if (!audio.paused) audio.pause()
    else audio.play()
    return
  }
  nowPlaying.value = { artist, track }
  audio.src = track.audio_url
  audio.play().catch(() => {})
}
</script>

<template>
  <Head title="Artistas — Dilo Records" />

  <BannerFullWidth
    :title="banner.title ?? 'Conéctate con el talento de nuestros'"
    :highlight="banner.highlight ?? 'ARTISTAS'"
    :cta="banner.cta ?? 'Mira ahora'"
    :image="banner.image ?? '/Videos/artists-hero.mp4'"
  />

  <ArtistCarousel :artists="artists" />

  <!-- Playlists por artista -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h2 class="text-white text-2xl md:text-3xl font-bold mb-6">Escucha las canciones de nuestros artistas</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <ArtistPlaylistCard
        v-for="a in artistList"
        :key="a.id"
        :artist="a"
        @play="handlePlay"
      />
    </div>

    <!-- Mini barra 'Now Playing' simple (temporal) -->
    <div v-if="nowPlaying" class="mt-8 sticky bottom-4">
      <div class="mx-auto max-w-3xl bg-white text-black rounded-2xl shadow-lg px-4 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
          <p class="text-sm font-semibold truncate">{{ nowPlaying.track.title }}</p>
          <p class="text-xs opacity-70 truncate">por {{ nowPlaying.artist.name }}</p>
        </div>
        <div class="text-xs opacity-70">Reproduciendo…</div>
      </div>
    </div>
  </section>
</template>
