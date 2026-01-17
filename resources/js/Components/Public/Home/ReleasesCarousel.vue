<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

type Platform = { key: string; name: string; url: string }
type Item = {
  id: number
  title: string
  slug: string
  cover: string | null
  artist?: { name?: string; slug?: string }
  platforms?: Platform[]
}

const props = defineProps<{ items: Item[] }>()

const scrollContainer = ref<HTMLElement | null>(null)
let interval: any = null

// ---------- Autoscroll ----------
const startAutoScroll = () => {
  stopAutoScroll()
  interval = setInterval(() => {
    if (!scrollContainer.value) return
    scrollContainer.value.scrollBy({ left: 280, behavior: 'smooth' })
    const maxScrollLeft =
      scrollContainer.value.scrollWidth - scrollContainer.value.clientWidth
    if (scrollContainer.value.scrollLeft >= maxScrollLeft - 5) {
      scrollContainer.value.scrollTo({ left: 0, behavior: 'smooth' })
    }
  }, 3000)
}
const stopAutoScroll = () => { if (interval) clearInterval(interval) }

onMounted(() => startAutoScroll())
onUnmounted(() => stopAutoScroll())

const scrollLeft = () => scrollContainer.value?.scrollBy({ left: -300, behavior: 'smooth' })
const scrollRight = () => scrollContainer.value?.scrollBy({ left: 300, behavior: 'smooth' })

// ---------- Hover panel (inline) ----------
const activeId = ref<number | null>(null)
let closeTimer: any = null

function normalizeKey(key: string): string {
  // normaliza variaciones: apple_music -> apple-music, amazon_music -> amazon-music
  return key.replace(/_/g, '-').toLowerCase()
}

function getIconName(key: string): string {
  const k = normalizeKey(key)
  const iconMap: Record<string, string> = {
    'spotify': 'simple-icons:spotify',
    'youtube': 'simple-icons:youtube',
    'apple-music': 'simple-icons:applemusic',
    'deezer': 'simple-icons:deezer',
    'amazon-music': 'simple-icons:amazonmusic',
    'soundcloud': 'simple-icons:soundcloud',
    'tidal': 'simple-icons:tidal',
  }
  return iconMap[k] || 'mdi:music-circle'
}

function getIconColor(key: string): string {
  const k = normalizeKey(key)
  const colorMap: Record<string, string> = {
    'spotify': '#1DB954',
    'youtube': '#FF0000',
    'apple-music': '#FA243C',
    'deezer': '#FF0092',
    'amazon-music': '#00A8E1',
    'soundcloud': '#FF5500',
    'tidal': '#000000',
  }
  return colorMap[k] || '#ffa236'
}

function showPanel(item: Item, e: MouseEvent) {
  if (closeTimer) { clearTimeout(closeTimer); closeTimer = null }
  activeId.value = item.id
}
function scheduleClose() {
  closeTimer = setTimeout(() => { activeId.value = null }, 150)
}
function cancelClose() {
  if (closeTimer) { clearTimeout(closeTimer); closeTimer = null }
}
function togglePanel(item: Item, e: MouseEvent) {
  e.preventDefault()
  if (activeId.value === item.id) activeId.value = null
  else showPanel(item, e)
}

const buildReleaseUrl = (item: Item) => {
  if (typeof route === 'function' && item.slug) {
    return route('public.releases.show', item.slug)
  }
  return window.location.origin + `/releases/${item.slug ?? ''}`
}

const copyReleaseLink = async (item: Item) => {
  const url = buildReleaseUrl(item)
  try {
    if (navigator?.clipboard?.writeText) {
      await navigator.clipboard.writeText(url)
      return
    }
  } catch (_) {}

  const textarea = document.createElement('textarea')
  textarea.value = url
  textarea.setAttribute('readonly', '')
  textarea.style.position = 'absolute'
  textarea.style.left = '-9999px'
  document.body.appendChild(textarea)
  textarea.select()
  document.execCommand('copy')
  document.body.removeChild(textarea)
}
</script>

<template>
  <section class="py-20 bg-black text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold mb-8 text-center">Últimos lanzamientos</h2>

      <div class="relative">
        <!-- Izquierda -->
        <button @click="scrollLeft"
          class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-black/60 hover:bg-black text-white rounded-full p-2 transition"
          aria-label="Anterior">‹</button>

        <!-- Carrusel -->
        <div ref="scrollContainer"
          class="flex gap-6 overflow-x-auto scrollbar-hide snap-x snap-mandatory scroll-smooth px-10"
          @mouseenter="stopAutoScroll" @mouseleave="startAutoScroll">
          <div v-for="item in items" :key="item.id" class="relative flex-none w-52 sm:w-60 snap-start"
            @mouseenter="showPanel(item, $event)" @mouseleave="scheduleClose" @click="togglePanel(item, $event)">
            <!-- Tarjeta -->
            <div
              class="relative group rounded-xl overflow-hidden ring-1 ring-white/10 hover:ring-white/20 transition aspect-square bg-zinc-800">
              <img :src="item.cover || '/placeholder.webp'" :alt="item.title"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                draggable="false" loading="lazy" decoding="async" />
            </div>

            <h3 class="mt-3 text-lg font-semibold text-center">{{ item.title }}</h3>
            <p v-if="item.artist?.name" class="text-sm text-center text-gray-400">
              {{ item.artist.name }}
            </p>

            <!-- Panel de plataformas (overlay) -->
            <div v-if="activeId === item.id"
              class="absolute inset-0 z-20 flex flex-col justify-start gap-3 bg-black/75 backdrop-blur-sm  p-3"
              @mouseenter.stop="cancelClose" @mouseleave="scheduleClose">
              <div class="flex items-center gap-3">
                <img :src="item.cover || '/placeholder.webp'" :alt="item.title"
                  class="w-11 h-11 rounded-lg object-cover ring-2 ring-[#ffa236]/20" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold truncate text-white">{{ item.title }}</p>
                  <p v-if="item.artist?.name" class="text-xs text-gray-400 truncate">
                    {{ item.artist.name }}
                  </p>
                </div>
              </div>

              <div v-if="item.platforms?.length">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2 font-semibold">Escuchar en:</p>
                <div class="grid grid-cols-4 gap-2">
                  <a v-for="p in item.platforms" :key="p.key" :href="p.url" target="_blank" rel="noopener noreferrer"
                    class="group flex items-center justify-center p-2.5 rounded-lg bg-zinc-900/70 hover:bg-[#ffa236]/10 border border-transparent hover:border-[#ffa236]/30 transition-all duration-300"
                    @click.stop :title="p.name" :aria-label="p.name">
                    <Icon :icon="getIconName(p.key)"
                      class="w-7 h-7 opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300"
                      :style="{ color: getIconColor(p.key) }" />
                  </a>
                  <button type="button"
                    class="group flex items-center justify-center p-2.5 rounded-lg bg-zinc-900/70 hover:bg-[#ffa236]/10 border border-transparent hover:border-[#ffa236]/30 transition-all duration-300"
                    @click.stop="copyReleaseLink(item)" aria-label="Copiar enlace" title="Copiar enlace">
                    <Icon icon="mdi:link-variant"
                      class="w-7 h-7 opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300"
                      style="color: #ffa236" />
                  </button>
                </div>
              </div>
              <p v-else class="text-sm text-gray-400 text-center py-4">
                Próximamente enlaces de escucha.
              </p>
            </div>
          </div>
        </div>

        <!-- Derecha -->
        <button @click="scrollRight"
          class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-black/60 hover:bg-black text-white rounded-full p-2 transition"
          aria-label="Siguiente">›</button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
