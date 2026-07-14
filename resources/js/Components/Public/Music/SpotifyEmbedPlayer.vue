<script setup>
import { Icon } from '@iconify/vue'
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps({
  track: { type: Object, default: null },
})

const emit = defineEmits(['close', 'platforms'])

const embedTarget = ref(null)
const embedController = ref(null)
const useFallbackEmbed = ref(false)

let loadRequestId = 0

const spotifyEntityUrl = (track) => {
  const spotifyUrl = track?.spotify_url || track?.spotify_embed_url

  return spotifyUrl?.replace('open.spotify.com/embed/', 'open.spotify.com/')
}

const fallbackEmbedUrl = (track) => {
  if (!track?.spotify_embed_url) {
    return null
  }

  const separator = track.spotify_embed_url.includes('?') ? '&' : '?'

  return `${track.spotify_embed_url}${separator}autoplay=1`
}

const loadSpotifyIframeApi = () => {
  if (typeof window === 'undefined') {
    return Promise.resolve(null)
  }

  if (window.SpotifyIframeApi) {
    return Promise.resolve(window.SpotifyIframeApi)
  }

  if (window.__spotifyIframeApiPromise) {
    return window.__spotifyIframeApiPromise
  }

  window.__spotifyIframeApiPromise = new Promise((resolve) => {
    window.onSpotifyIframeApiReady = (spotifyIframeApi) => {
      window.SpotifyIframeApi = spotifyIframeApi
      resolve(spotifyIframeApi)
    }

    if (document.querySelector('script[data-spotify-iframe-api]')) {
      return
    }

    const script = document.createElement('script')
    script.src = 'https://open.spotify.com/embed/iframe-api/v1'
    script.async = true
    script.dataset.spotifyIframeApi = 'true'
    script.onerror = () => resolve(null)
    document.body.appendChild(script)
  })

  return window.__spotifyIframeApiPromise
}

const destroyController = () => {
  if (!embedController.value) {
    return
  }

  embedController.value.pause?.()
  embedController.value.destroy?.()
  embedController.value = null
}

const playController = () => {
  window.setTimeout(() => {
    embedController.value?.play?.()
  }, 250)
}

const mountSpotifyEmbed = async (track) => {
  const requestId = ++loadRequestId
  const entityUrl = spotifyEntityUrl(track)

  if (!entityUrl) {
    destroyController()
    return
  }

  await nextTick()

  if (!embedTarget.value) {
    return
  }

  const iframeApi = await loadSpotifyIframeApi()

  if (requestId !== loadRequestId) {
    return
  }

  if (!iframeApi) {
    useFallbackEmbed.value = true
    return
  }

  useFallbackEmbed.value = false

  if (embedController.value) {
    embedController.value.loadEntity(entityUrl)
    playController()
    return
  }

  iframeApi.createController(
    embedTarget.value,
    {
      url: entityUrl,
      width: '100%',
      height: 88,
    },
    (controller) => {
      if (requestId !== loadRequestId) {
        controller.destroy?.()
        return
      }

      embedController.value = controller
      playController()
    },
  )
}

const closePlayer = () => {
  embedController.value?.pause?.()
  emit('close')
}

watch(
  () => props.track,
  (track) => {
    if (!track?.spotify_embed_url) {
      destroyController()
      return
    }

    mountSpotifyEmbed(track)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  destroyController()
})
</script>

<template>
  <transition name="slide-up">
    <aside
      v-if="track?.spotify_embed_url"
      class="fixed inset-x-0 bottom-0 z-[60] border-t border-white/10 bg-black/95 px-3 py-3 shadow-2xl backdrop-blur-md sm:px-6"
      aria-label="Reproductor Spotify"
    >
      <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-center">
        <div class="min-h-[88px] flex-1 overflow-hidden rounded-xl bg-zinc-950 ring-1 ring-white/10 lg:max-w-5xl">
          <iframe
            v-if="useFallbackEmbed"
            :key="fallbackEmbedUrl(track)"
            :src="fallbackEmbedUrl(track)"
            class="h-[88px] w-full"
            frameborder="0"
            allowfullscreen
            allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
            loading="lazy"
            :title="`Spotify Embed: ${track.title}`"
          ></iframe>
          <div
            v-else
            ref="embedTarget"
            :key="track.spotify_embed_url"
            class="spotify-embed-target h-[88px] w-full"
          ></div>
        </div>

        <div class="flex items-center justify-start gap-2">
          <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/10 transition hover:bg-white hover:text-black"
            aria-label="Otras plataformas"
            @click="emit('platforms', track)"
          >
            <Icon icon="mdi:dots-horizontal" class="h-5 w-5" />
          </button>
          <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-black transition hover:bg-dilo-orange"
            aria-label="Cerrar reproductor"
            @click="closePlayer"
          >
            <Icon icon="mdi:close" class="h-5 w-5" />
          </button>
        </div>
      </div>
    </aside>
  </transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.24s ease, opacity 0.24s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.spotify-embed-target :deep(iframe) {
  height: 88px;
  width: 100%;
}
</style>
