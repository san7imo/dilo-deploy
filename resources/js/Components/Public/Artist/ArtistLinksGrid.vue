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

// Iconos SVG mejorados con mejor representación
const icons = {
  user: 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
  headphones: 'M12 1C6.48 1 2 5.48 2 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8c0-3.59 2.91-6.5 6.5-6.5s6.5 2.91 6.5 6.5v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8c0-5.52-4.48-10-10-10zm0 17c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z',
  link: 'M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z',
  briefcase: 'M20 6h-2.15l-2.54-3.09A1.5 1.5 0 0113.3 2h-2.6c-.75 0-1.44.46-1.75 1.15L6.65 6H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 13H4V8h16v11z',
  shopping: 'M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z',
  disc: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 14.5c-2.49 0-4.5-2.01-4.5-4.5S9.51 7.5 12 7.5s4.5 2.01 4.5 4.5-2.01 4.5-4.5 4.5zm0-5.5c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z',
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Grid principal: Video + Links -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Video -->
      <div class="h-80 lg:h-96">
        <ArtistVideo :artist="artist" :youtube-url="artist.presentation_video_url || youtubeUrl" />
      </div>

      <!-- Grid de links -->
      <div class="grid grid-cols-3 gap-4">
        <!-- Card 1: Sígueme -->
        <ArtistLinkCard title="Sígueme" :icon="icons.user" @click="showSocialModal = true" />

        <!-- Card 2: Escucha -->
        <ArtistLinkCard title="Escucha" :icon="icons.headphones" @click="showStreamingModal = true" />


        <!-- Card 3: Discografía -->
        <ArtistLinkCard title="Discografía" :icon="icons.disc" @click="scrollToDiscography" />

        <!-- Card 4: Video -->
        <ArtistLinkCard title="Video" :icon="icons.link" @click="showVideoModal = true" />

        <!-- Card 5: Kit de Prensa -->
        <ArtistLinkCard title="Kit de Prensa" :icon="icons.briefcase" />

        <!-- Card 6: Tienda (deshabilitada) -->
        <ArtistLinkCard title="Tienda" :icon="icons.shopping" :is-disabled="true" />
      </div>
    </div>
  </section>

  <!-- Modales -->
  <SocialNetworksModal :artist="artist" :is-open="showSocialModal" @close="showSocialModal = false" />

  <StreamingModal :artist="artist" :is-open="showStreamingModal" @close="showStreamingModal = false" />

  <VideoModal :artist="artist" :youtube-url="artist.presentation_video_url" :is-open="showVideoModal"
    @close="showVideoModal = false" />
</template>
