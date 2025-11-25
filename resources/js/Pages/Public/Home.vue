<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import BannerCarousel from '@/Components/Public/Home/BannerCaroulse.vue'
import ReleasesCarousel from '@/Components/Public/Home/ReleasesCarousel.vue'
import ArtistsCarousel from '@/Components/Public/Home/ArtistsCarousel.vue'
import EventsCarousel from '@/Components/Public/Home/EventsCarousel.vue'

defineProps<{
  banners: any[]
  latestReleases: any[]
  artists: any[]
  events: any[]
  contact: any
}>()
</script>

<template>
  <PublicLayout>
    <!-- Banner principal -->
    <BannerCarousel :items="banners" />
    <!-- Carrusel de últimos lanzamientos -->
    <ReleasesCarousel :items="latestReleases" />
    <!-- Carrusel de artistas -->
    <ArtistsCarousel :items="artists" />
    <!-- Carrusel de eventos -->
    <EventsCarousel :items="events" />


  </PublicLayout>
</template>
