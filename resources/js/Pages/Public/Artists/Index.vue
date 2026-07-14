<!-- resources/js/Pages/Public/Artists/Index.vue -->
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BannerFullWidth from '@/Components/Public/Artists/BannerFullWidth.vue'
import ArtistCarousel from '@/Components/Public/Artists/ArtistCarousel.vue'
import ArtistPlaylistCard from '@/Components/Public/Artists/ArtistPlaylistCard.vue'
import PlatformLinksModal from '@/Components/Public/Music/PlatformLinksModal.vue'
import PaginationLinks from '@/Components/PaginationLinks.vue'
import SpotifyEmbedPlayer from '@/Components/Public/Music/SpotifyEmbedPlayer.vue'

// Si tienes un layout público global, descomenta:
import PublicLayout from '@/Layouts/PublicLayout.vue'
 defineOptions({ layout: PublicLayout })

const props = defineProps({
  artists: { type: Object, required: true }, // paginator con data + tracks por artista
  banner: { type: Object, default: () => ({}) },
})

// soporte paginator o array
const artistList = computed(() => Array.isArray(props.artists) ? props.artists : (props.artists.data ?? []))
const selectedTrack = ref(null)
const platformTrack = ref(null)

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
  <Head title="Artistas — Dilo Records" />

  <BannerFullWidth
    :title="banner.title ?? 'Conéctate con el talento de nuestros'"
    :highlight="banner.highlight ?? 'ARTISTAS'"
    :cta="banner.cta ?? 'Mira ahora'"
    :image="banner.image ?? '/Videos/artists-hero.mp4'"
  />

  <ArtistCarousel :artists="artists" />

  <section class="bg-black py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.22em] text-dilo-orange">Catálogo</p>
          <h2 class="mt-2 text-3xl font-black text-white md:text-4xl">Canciones destacadas por artista</h2>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">
            Una selección breve por artista para explorar lanzamientos, portadas y plataformas disponibles.
          </p>
        </div>

        <Link
          :href="route('public.songs.index')"
          class="inline-flex items-center justify-center rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-white transition hover:border-dilo-orange hover:text-dilo-orange"
        >
          Ver todas las canciones
        </Link>
      </div>

      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <ArtistPlaylistCard
          v-for="a in artistList"
          :key="a.id"
          :artist="a"
          @play="playTrack"
          @platforms="openPlatforms"
        />
      </div>

      <PaginationLinks
        v-if="props.artists && props.artists.links"
        :links="props.artists.links"
        :meta="props.artists.meta"
        class="mt-10 justify-center"
      />
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
