<!-- resources/js/Components/Public/Artist/ArtistBanner.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  artist: { type: Object, required: true },
})

// Iconos de redes sociales
const socialIcons = {
  facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
  instagram: 'M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.117.626c-.788.297-1.459.711-2.126 1.378-.667.667-1.079 1.335-1.395 2.126-.296.784-.497 1.663-.558 2.921C.008 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.307.788.723 1.459 1.379 2.126.667.666 1.336 1.079 2.125 1.394.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.059 2.148-.262 2.913-.558.788-.306 1.459-.723 2.126-1.379.666-.667 1.079-1.335 1.395-2.125.296-.765.499-1.636.558-2.913.058-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.059-1.277-.262-2.149-.558-2.913-.306-.789-.723-1.459-1.379-2.126C21.333 1.061 20.666.476 19.875.122c-.765-.296-1.636-.499-2.913-.558C15.667.008 15.26 0 12 0zm0 2.16c3.203 0 3.585.009 4.849.070 1.17.054 1.805.244 2.227.408.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.354 1.057.408 2.227.061 1.264.07 1.646.07 4.849s-.009 3.585-.07 4.849c-.054 1.17-.244 1.805-.408 2.227-.217.562-.477.96-.896 1.382-.42.419-.819.679-1.381.896-.422.164-1.057.354-2.227.408-1.264.061-1.646.07-4.849.07s-3.585-.009-4.849-.07c-1.17-.054-1.805-.244-2.227-.408-.562-.217-.96-.477-1.382-.896-.419-.42-.679-.819-.896-1.381-.164-.422-.354-1.057-.408-2.227-.061-1.264-.07-1.646-.07-4.849s.009-3.585.07-4.849c.054-1.17.244-1.805.408-2.227.217-.562.477-.96.896-1.382.42-.419.819-.679 1.381-.896.422-.164 1.057-.354 2.227-.408 1.264-.061 1.646-.07 4.849-.07Z',
  twitter: 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z',
  tiktok: 'M19.498 3.75H15.5V19.5a1.5 1.5 0 11-3 0V3.75h-3.998a1.5 1.5 0 000 3h3.998V19.5a4.5 4.5 0 109-4.5V6.75h4.5a1.5 1.5 0 000-3z',
}

// URLs de redes sociales del artista
const socialLinks = computed(() => {
  if (!props.artist.social_links) return {}
  return {
    facebook: props.artist.social_links.facebook,
    instagram: props.artist.social_links.instagram,
    twitter: props.artist.social_links.twitter,
    tiktok: props.artist.social_links.tiktok,
  }
})

const getSocialUrl = (platform) => {
  return socialLinks.value[platform]
}
</script>

<template>
  <section class="relative w-full h-[32rem] md:h-[40rem] overflow-hidden">
    <!-- Imagen de fondo -->
    <img
      :src="artist.banner_artist_url"
      :alt="artist.name"
      class="w-full h-full object-cover"
    />
    <!-- Overlay gradiente -->
    <div class="absolute inset-0 bg-gradient-to-b from-black/0 via-black/40 to-black/80"></div>

    <!-- Contenido superpuesto -->
    <div class="absolute inset-0 flex flex-col items-center justify-end pb-8">
      <!-- Texto "¡Sígueme!" -->
      <h2 class="text-4xl md:text-5xl font-black text-white mb-4">¡Sígueme!</h2>

      <!-- Iconos de redes sociales -->
      <div class="flex gap-6">
        <!-- Facebook -->
        <a
          v-if="getSocialUrl('facebook')"
          :href="getSocialUrl('facebook')"
          target="_blank"
          rel="noopener noreferrer"
          class="text-white hover:text-blue-400 transition-colors duration-300"
          title="Facebook"
        >
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path :d="socialIcons.facebook"></path>
          </svg>
        </a>

        <!-- Instagram -->
        <a
          v-if="getSocialUrl('instagram')"
          :href="getSocialUrl('instagram')"
          target="_blank"
          rel="noopener noreferrer"
          class="text-white hover:text-pink-400 transition-colors duration-300"
          title="Instagram"
        >
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path :d="socialIcons.instagram"></path>
          </svg>
        </a>

        <!-- Twitter -->
        <a
          v-if="getSocialUrl('twitter')"
          :href="getSocialUrl('twitter')"
          target="_blank"
          rel="noopener noreferrer"
          class="text-white hover:text-blue-300 transition-colors duration-300"
          title="Twitter"
        >
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path :d="socialIcons.twitter"></path>
          </svg>
        </a>

        <!-- TikTok -->
        <a
          v-if="getSocialUrl('tiktok')"
          :href="getSocialUrl('tiktok')"
          target="_blank"
          rel="noopener noreferrer"
          class="text-white hover:text-gray-300 transition-colors duration-300"
          title="TikTok"
        >
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path :d="socialIcons.tiktok"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>
