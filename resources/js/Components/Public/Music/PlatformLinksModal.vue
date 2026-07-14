<script setup>
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

const props = defineProps({
  track: { type: Object, default: null },
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const visiblePlatforms = computed(() => {
  const platforms = props.track?.platforms || []

  if (props.track?.spotify_embed_url) {
    return platforms.filter((platform) => platform.key !== 'spotify')
  }

  return platforms
})

const close = () => emit('close')

const handleBackdropClick = (event) => {
  if (event.target === event.currentTarget) close()
}
</script>

<template>
  <transition name="fade">
    <div
      v-if="isOpen && track"
      class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click="handleBackdropClick"
    >
      <div class="relative w-full max-w-md rounded-2xl bg-zinc-950 p-6 shadow-2xl ring-1 ring-white/10">
        <button
          type="button"
          class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white hover:text-black"
          aria-label="Cerrar"
          @click="close"
        >
          <Icon icon="mdi:close" class="h-5 w-5" />
        </button>

        <div class="pr-12">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-dilo-orange">Otras plataformas</p>
          <h2 class="mt-2 truncate text-2xl font-black text-white">{{ track.title }}</h2>
          <p class="mt-1 truncate text-sm text-zinc-400">{{ track.release?.title || track.artists?.[0]?.name }}</p>
        </div>

        <div v-if="visiblePlatforms.length" class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <a
            v-for="platform in visiblePlatforms"
            :key="platform.key"
            :href="platform.url"
            target="_blank"
            rel="noopener noreferrer"
            class="group flex min-h-14 items-center gap-3 rounded-xl bg-zinc-900 px-4 text-white ring-1 ring-white/10 transition hover:bg-white hover:text-black"
          >
            <Icon :icon="platform.icon" class="h-5 w-5 shrink-0 text-dilo-orange transition group-hover:text-black" />
            <span class="truncate text-sm font-bold">{{ platform.name }}</span>
          </a>
        </div>

        <p v-else class="mt-6 rounded-xl bg-zinc-900 px-4 py-6 text-center text-sm text-zinc-400">
          No hay otras plataformas configuradas para esta canción.
        </p>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
