<!-- resources/js/Components/Public/Artist/ArtistLinksGrid.vue -->
<script setup>
import { ref } from 'vue'
import ArtistVideo from './ArtistVideo.vue'
import ArtistLinkCard from './ArtistLinkCard.vue'
import SocialNetworksModal from './SocialNetworksModal.vue'
import StreamingModal from './StreamingModal.vue'
import VideoModal from './VideoModal.vue'

const props = defineProps({
  artist: { type: Object, required: true },
  youtubeUrl: { type: String, default: '' },
})

// Modal states
const showSocialModal = ref(false)
const showStreamingModal = ref(false)
const showVideoModal = ref(false)

// Scroll a la sección de discografía
const scrollToDiscography = () => {
  const discographySection = document.querySelector('#discography-section')
  if (discographySection) {
    discographySection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const icons = {
  follow: 'solar:users-group-two-rounded-bold',
  listen: 'solar:headphones-round-sound-bold',
  discography: 'solar:vinyl-record-bold',
  video: 'solar:play-circle-bold',
  presskit: 'solar:folder-with-files-bold',
  shop: 'solar:shop-2-bold',
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Grid principal: Video + Links -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Video -->
      <div class="h-80 lg:h-[24rem] xl:h-[26rem] lg:col-span-6">
        <ArtistVideo :artist="artist" :youtube-url="artist.presentation_video_url || youtubeUrl" />
      </div>

      <!-- Grid de links -->
      <div class="grid grid-cols-3 gap-3 lg:col-span-6">
        <!-- Card 1: Sígueme -->
        <ArtistLinkCard title="Sígueme" :icon="icons.follow" @click="showSocialModal = true" />

        <!-- Card 2: Escucha -->
        <ArtistLinkCard title="Escucha" :icon="icons.listen" @click="showStreamingModal = true" />


        <!-- Card 3: Discografía -->
        <ArtistLinkCard title="Discografía" :icon="icons.discography" @click="scrollToDiscography" />

        <!-- Card 4: Video -->
        <ArtistLinkCard title="Video" :icon="icons.video" @click="showVideoModal = true" />

        <!-- Card 5: Kit de Prensa -->
        <ArtistLinkCard title="Kit de Prensa" :icon="icons.presskit" />

        <!-- Card 6: Tienda (deshabilitada) -->
        <ArtistLinkCard title="Tienda" :icon="icons.shop" :is-disabled="true" />
      </div>
    </div>
  </section>

  <!-- Modales -->
  <SocialNetworksModal :artist="artist" :is-open="showSocialModal" @close="showSocialModal = false" />

  <StreamingModal :artist="artist" :is-open="showStreamingModal" @close="showStreamingModal = false" />

  <VideoModal :artist="artist" :youtube-url="artist.presentation_video_url" :is-open="showVideoModal"
    @close="showVideoModal = false" />
</template>
