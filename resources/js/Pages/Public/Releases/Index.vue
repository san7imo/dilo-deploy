<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import MediaBanner from '@/Components/Public/Layout/MediaBanner.vue'
import PaginationLinks from '@/Components/PaginationLinks.vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
  releases: { type: Object, required: true },
})

const releaseList = () => props.releases?.data || []

const formatDate = (dateString) => {
  if (!dateString) return 'Fecha por anunciar'

  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <Head title="Releases — Dilo Records" />

  <MediaBanner
    image="/images/lanzamientos-banner.webp"
    image-alt="Lanzamientos - descubre los nuevos estrenos de tus artistas favoritos"
  />

  <section class="bg-black py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex justify-end">
        <Link
          :href="route('public.songs.index')"
          class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/10 px-5 text-sm font-bold text-white transition hover:border-dilo-orange hover:text-dilo-orange"
        >
          Ver canciones
        </Link>
      </div>
    </div>
  </section>

  <section class="bg-black pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div v-if="releaseList().length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <article
          v-for="release in releaseList()"
          :key="release.id"
          class="group overflow-hidden rounded-2xl bg-zinc-950/80 ring-1 ring-white/10 transition hover:ring-dilo-orange/50"
        >
          <Link :href="route('public.releases.show', release.slug)" class="block">
            <div class="relative aspect-square overflow-hidden bg-zinc-900">
              <img
                :src="release.optimized_cover_url || release.cover_url || '/placeholder.webp'"
                :alt="release.title"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                loading="lazy"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
              <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between gap-3">
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase text-white ring-1 ring-white/10">
                  {{ release.type || 'release' }}
                </span>
                <span class="text-xs text-zinc-300">{{ release.tracks?.length || 0 }} canciones</span>
              </div>
            </div>

            <div class="p-4">
              <h2 class="truncate text-lg font-black text-white">{{ release.title }}</h2>
              <p class="mt-1 truncate text-sm text-zinc-400">{{ release.artist?.name || 'Dilo Records' }}</p>
              <p class="mt-3 text-xs text-zinc-500">{{ formatDate(release.release_date) }}</p>
            </div>
          </Link>

          <div class="flex items-center gap-2 px-4 pb-4">
            <a
              v-if="release.spotify_url"
              :href="release.spotify_url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-black transition hover:bg-dilo-orange"
              aria-label="Abrir en Spotify"
            >
              <Icon icon="simple-icons:spotify" class="h-4 w-4" />
            </a>
            <Link
              :href="route('public.releases.show', release.slug)"
              class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl bg-white/10 px-4 text-sm font-bold text-white ring-1 ring-white/10 transition hover:bg-white hover:text-black"
            >
              Explorar
            </Link>
          </div>
        </article>
      </div>

      <div v-else class="rounded-2xl bg-zinc-950 px-6 py-12 text-center ring-1 ring-white/10">
        <p class="text-lg font-bold text-white">No hay releases públicos disponibles.</p>
      </div>

      <PaginationLinks
        v-if="releases.links"
        :links="releases.links"
        :meta="releases.meta"
        class="mt-10 justify-center"
      />
    </div>
  </section>
</template>
