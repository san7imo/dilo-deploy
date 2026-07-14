<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import MediaBanner from '@/Components/Public/Layout/MediaBanner.vue'
import PlatformLinksModal from '@/Components/Public/Music/PlatformLinksModal.vue'
import PaginationLinks from '@/Components/PaginationLinks.vue'
import SpotifyEmbedPlayer from '@/Components/Public/Music/SpotifyEmbedPlayer.vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
  tracks: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  filterOptions: { type: Object, default: () => ({ artists: [], releases: [] }) },
})

const search = ref(props.filters.search || '')
const selectedArtist = ref(props.filters.artist || '')
const selectedRelease = ref(props.filters.release || '')
const selectedTrack = ref(null)
const platformTrack = ref(null)
const trackList = computed(() => props.tracks?.data || [])
const artistOptions = computed(() => props.filterOptions.artists || [])
const releaseOptions = computed(() => props.filterOptions.releases || [])

const primaryArtist = (track) => {
  return track.artists?.[0] || track.release?.artist || null
}

const trackCover = (track) => {
  return track.optimized_cover_url
    || track.effective_cover_url
    || track.release?.optimized_cover_url
    || track.release?.cover_url
    || '/placeholder.webp'
}

const releaseTypeLabel = (type) => {
  const labels = {
    album: 'Álbum',
    single: 'Single',
    ep: 'EP',
    mixtape: 'Mixtape',
    live: 'Live',
    compilation: 'Compilación',
  }

  return labels[type?.toLowerCase?.()] || type || 'Release'
}

const submitSearch = () => {
  applyFilters()
}

const applyFilters = () => {
  router.get(
    route('public.songs.index'),
    {
      artist: selectedArtist.value || undefined,
      release: selectedRelease.value || undefined,
      search: search.value || undefined,
    },
    {
      preserveState: true,
      replace: true,
    },
  )
}

const clearFilters = () => {
  search.value = ''
  selectedArtist.value = ''
  selectedRelease.value = ''
  router.get(route('public.songs.index'))
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
  <Head title="Canciones — Dilo Records" />

  <MediaBanner
    image="/images/canciones-banner.webp"
    image-alt="Canciones - descubre y reproduce tus temas favoritos"
  />

  <section class="bg-black py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex justify-end">
        <form class="flex w-full gap-2 lg:max-w-md" @submit.prevent="submitSearch">
          <input
            v-model="search"
            type="search"
            placeholder="Buscar canción, artista o release"
            class="min-h-12 flex-1 rounded-xl border-white/10 bg-white/10 text-sm text-white placeholder:text-zinc-500 focus:border-dilo-orange focus:ring-dilo-orange"
          />
          <button
            type="submit"
            class="inline-flex min-h-12 items-center justify-center rounded-xl bg-dilo-orange px-4 text-sm font-bold text-black transition hover:bg-dilo-orange-light"
            aria-label="Buscar"
          >
            <Icon icon="mdi:magnify" class="h-5 w-5" />
          </button>
        </form>
      </div>

      <div class="mt-6 rounded-2xl bg-zinc-950/80 p-4 ring-1 ring-white/10">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
          <label class="block">
            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">Artista</span>
            <select
              v-model="selectedArtist"
              class="min-h-12 w-full rounded-xl border-white/10 bg-zinc-900 text-sm text-white focus:border-dilo-orange focus:ring-dilo-orange"
              @change="applyFilters"
            >
              <option value="">Todos los artistas</option>
              <option v-for="artist in artistOptions" :key="artist.slug" :value="artist.slug">
                {{ artist.name }}
              </option>
            </select>
          </label>

          <label class="block">
            <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">Release</span>
            <select
              v-model="selectedRelease"
              class="min-h-12 w-full rounded-xl border-white/10 bg-zinc-900 text-sm text-white focus:border-dilo-orange focus:ring-dilo-orange"
              @change="applyFilters"
            >
              <option value="">Todos los releases</option>
              <option v-for="release in releaseOptions" :key="release.slug" :value="release.slug">
                {{ release.title }}{{ release.artist_name ? ` — ${release.artist_name}` : '' }}
              </option>
            </select>
          </label>

          <button
            type="button"
            class="inline-flex min-h-12 items-center justify-center rounded-xl bg-dilo-orange px-5 text-sm font-bold text-black transition hover:bg-dilo-orange-light"
            @click="applyFilters"
          >
            Aplicar
          </button>

          <button
            type="button"
            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/10 px-5 text-sm font-bold text-white transition hover:border-dilo-orange hover:text-dilo-orange"
            @click="clearFilters"
          >
            Limpiar
          </button>
        </div>

        <div v-if="filters.artist || filters.release || filters.search" class="mt-4 flex flex-wrap items-center gap-3">
          <span v-if="filters.artist" class="rounded-full bg-white/10 px-3 py-1.5 text-xs text-zinc-300">
            Artista: {{ filters.artist }}
          </span>
          <span v-if="filters.release" class="rounded-full bg-white/10 px-3 py-1.5 text-xs text-zinc-300">
            Release: {{ filters.release }}
          </span>
          <span v-if="filters.search" class="rounded-full bg-white/10 px-3 py-1.5 text-xs text-zinc-300">
            Búsqueda: {{ filters.search }}
          </span>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-black pb-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <ul v-if="trackList.length" class="overflow-hidden rounded-2xl bg-zinc-950/80 ring-1 ring-white/10">
        <li
          v-for="(track, index) in trackList"
          :key="track.id"
          class="grid grid-cols-[auto_1fr] gap-3 border-b border-white/10 px-3 py-3 transition last:border-b-0 hover:bg-white/[0.04] sm:grid-cols-[auto_1fr_auto] sm:items-center sm:px-4"
        >
          <div class="flex items-center gap-3">
            <span class="hidden w-7 text-right text-xs text-zinc-600 sm:block">
              {{ index + 1 }}
            </span>
            <img
              :src="trackCover(track)"
              :alt="track.title"
              class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-white/10 sm:h-14 sm:w-14"
              loading="lazy"
            />
          </div>

          <div class="min-w-0 self-center">
            <p class="truncate text-sm font-bold text-white sm:text-base">{{ track.title }}</p>
            <div class="mt-1 flex min-w-0 items-center gap-1.5 overflow-hidden text-xs text-zinc-400">
              <Link
                v-if="primaryArtist(track)?.slug"
                :href="route('public.artists.show', primaryArtist(track).slug)"
                class="shrink-0 truncate transition hover:text-dilo-orange"
              >
                {{ primaryArtist(track).name }}
              </Link>
              <span v-else class="shrink-0 truncate">{{ primaryArtist(track)?.name || 'Dilo Records' }}</span>

              <span class="shrink-0 text-zinc-600">•</span>

              <Link
                v-if="track.release?.slug"
                :href="route('public.releases.show', track.release.slug)"
                class="min-w-0 truncate transition hover:text-dilo-orange"
              >
                {{ track.release.title }}
              </Link>
              <span v-else class="min-w-0 truncate">{{ track.release?.title || 'Sin release' }}</span>

              <span class="shrink-0 text-zinc-600">•</span>
              <span class="shrink-0">{{ releaseTypeLabel(track.release?.type) }}</span>
              <span class="shrink-0 text-zinc-600">•</span>
              <span class="shrink-0">{{ track.duration || 'Duración no definida' }}</span>
            </div>
          </div>

          <div class="col-span-2 flex items-center justify-end gap-2 sm:col-span-1">
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
        </li>
      </ul>

      <div v-else class="rounded-2xl bg-zinc-950 px-6 py-12 text-center ring-1 ring-white/10">
        <p class="text-lg font-bold text-white">No encontramos canciones para estos filtros.</p>
        <button class="mt-4 text-sm font-semibold text-dilo-orange hover:text-dilo-orange-light" @click="clearFilters">
          Ver catálogo completo
        </button>
      </div>

      <PaginationLinks
        v-if="tracks.links"
        :links="tracks.links"
        :meta="tracks.meta"
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
