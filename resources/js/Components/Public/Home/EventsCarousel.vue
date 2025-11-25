<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

type EventItem = {
  id: number
  title: string
  slug: string
  poster: string | null
}

const props = defineProps<{
  items: EventItem[]
}>()

const scrollEl = ref<HTMLElement | null>(null)
let timer: number | null = null

const start = () => {
  stop()
  timer = window.setInterval(() => {
    const el = scrollEl.value
    if (!el) return
    el.scrollBy({ left: 320, behavior: 'smooth' })
    const max = el.scrollWidth - el.clientWidth
    if (el.scrollLeft >= max - 4) el.scrollTo({ left: 0, behavior: 'smooth' })
  }, 3200)
}
const stop = () => { if (timer) { clearInterval(timer); timer = null } }

onMounted(start)
onUnmounted(stop)

const left  = () => scrollEl.value?.scrollBy({ left: -340, behavior: 'smooth' })
const right = () => scrollEl.value?.scrollBy({ left:  340, behavior: 'smooth' })

const hrefFor = (slug: string) => `/eventos/${slug}`

// compartir nativo si existe
async function share(item: EventItem) {
  const url = location.origin + hrefFor(item.slug)
  if ((navigator as any).share) {
    try { await (navigator as any).share({ title: item.title, url }) } catch {}
  } else {
    await navigator.clipboard.writeText(url)
    // opcional: un pequeño toast… por ahora silencioso
  }
}
</script>

<template>
  <section class="py-20 bg-black text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold mb-8 text-center">Eventos</h2>

      <div class="relative">
        <!-- Flecha izquierda -->
        <button
          @click="left"
          class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-black/70 hover:bg-black text-white rounded-full p-2 transition"
          aria-label="Anterior"
        >‹</button>

        <!-- Pista del carrusel -->
        <div
          ref="scrollEl"
          class="flex gap-8 overflow-x-auto scrollbar-hide snap-x snap-mandatory scroll-smooth px-10"
          @mouseenter="stop"
          @mouseleave="start"
        >
          <article
            v-for="e in items"
            :key="e.id"
            class="flex-none w-[360px] snap-start"
          >
            <div class="rounded-3xl overflow-hidden ring-1 ring-white/10 bg-zinc-900/60 backdrop-blur group">
              <!-- Poster -->
              <a :href="hrefFor(e.slug)" class="block">
                <div class="relative aspect-[16/12] bg-black">
                  <img
                    :src="e.poster || '/placeholder.webp'"
                    :alt="e.title"
                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    loading="lazy"
                    decoding="async"
                    draggable="false"
                  />
                </div>
              </a>

              <!-- Body -->
              <div class="p-5">
                <h3 class="font-semibold text-lg line-clamp-2 min-h-[3.25rem]">
                  {{ e.title }}
                </h3>

                <div class="mt-5 flex items-center justify-between">
                  <!-- Compartir -->
                  <button
                    type="button"
                    @click="share(e)"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-white/10 hover:ring-white/20 hover:bg-white/5 transition"
                    aria-label="Compartir"
                    title="Compartir"
                  >
                    <span class="i-share">↗</span>
                    <span>Compartir</span>
                  </button>

                  <!-- Ver detalle -->
                  <a
                    :href="hrefFor(e.slug)"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm ring-1 ring-white/10 hover:ring-white/20 hover:bg-white/5 transition"
                  >
                    VER DETALLE
                  </a>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Flecha derecha -->
        <button
          @click="right"
          class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-black/70 hover:bg-black text-white rounded-full p-2 transition"
          aria-label="Siguiente"
        >›</button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
/* opcional: si no quieres la transparencia del body de la tarjeta, cambia bg-zinc-900/60 por bg-black arriba */
</style>
