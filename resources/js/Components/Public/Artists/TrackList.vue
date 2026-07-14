<!-- resources/js/Components/Public/Artists/TrackList.vue -->
<script setup>
import { Icon } from '@iconify/vue'

const props = defineProps({
  tracks: { type: Array, default: () => [] },
})

const emit = defineEmits(['play', 'platforms'])

const primaryArtistName = (track) => {
  return track.artists?.[0]?.name || track.release?.artist?.name || 'Dilo Records'
}

const platformPreview = (track) => {
  return (track.platforms || []).slice(0, 3)
}
</script>

<template>
  <ul class="space-y-3">
    <li
      v-for="t in tracks"
      :key="t.id"
      class="group flex items-center gap-3 rounded-xl bg-zinc-900/70 p-2.5 ring-1 ring-white/10 transition hover:bg-zinc-900 hover:ring-dilo-orange/40"
    >
      <img
        :src="t.optimized_cover_url || t.effective_cover_url || t.release?.optimized_cover_url || t.release?.cover_url || '/placeholder.webp'"
        :alt="t.title"
        class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-white/10"
        loading="lazy"
      />

      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-white">{{ t.title }}</p>
        <p class="truncate text-xs text-zinc-400">
          {{ t.release?.title || primaryArtistName(t) }}
        </p>
      </div>

      <div class="hidden shrink-0 items-center gap-1 sm:flex">
        <Icon
          v-for="platform in platformPreview(t)"
          :key="platform.key"
          :icon="platform.icon"
          class="h-4 w-4 text-zinc-400 transition group-hover:text-white"
          :title="platform.name"
        />
      </div>

      <button
        v-if="t.spotify_embed_url"
        type="button"
        class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-2.5 text-[0.68rem] font-black uppercase tracking-[0.08em] text-black transition hover:bg-dilo-orange sm:px-3 sm:text-xs"
        aria-label="Reproducir en Spotify"
        @click="emit('play', t)"
      >
        <Icon icon="simple-icons:spotify" class="h-4 w-4" />
        <span class="whitespace-nowrap">Escucha ahora</span>
      </button>

      <button
        v-if="t.platforms?.length"
        type="button"
        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white ring-1 ring-white/10 transition hover:bg-white hover:text-black"
        aria-label="Otras plataformas"
        @click="emit('platforms', t)"
      >
        <Icon icon="mdi:dots-horizontal" class="h-4 w-4" />
      </button>
    </li>
  </ul>
</template>
