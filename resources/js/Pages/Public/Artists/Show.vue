<!-- resources/js/Pages/Public/Artists/Show.vue -->
<script setup>
import { Head } from '@inertiajs/vue3'
import ArtistBanner from '@/Components/Public/Artist/ArtistBanner.vue'
import ArtistLinksGrid from '@/Components/Public/Artist/ArtistLinksGrid.vue'
import ArtistBiography from '@/Components/Public/Artist/ArtistBiography.vue'

// Layout público
import PublicLayout from '@/Layouts/PublicLayout.vue'
defineOptions({ layout: PublicLayout })

const props = defineProps({
  artist: { type: Object, required: true },
})
</script>

<template>
  <Head :title="`${artist.name} — Dilo Records`" />

  <!-- Banner del artista -->
  <ArtistBanner :artist="artist" />

  <!-- Grid de links y video -->
  <ArtistLinksGrid
    :artist="artist"
    :youtube-url="artist.youtube_url"
    :releases="artist.releases"
  />

  <!-- Biografía -->
  <ArtistBiography :artist="artist" />

</template>
