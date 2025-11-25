<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

type ArtistItem = {
  id: number
  name: string
  slug: string
  image: string | null
}

const props = defineProps<{ items: ArtistItem[] }>()

const scrollContainer = ref<HTMLElement | null>(null)
let interval: any = null

const startAuto = () => {
  stopAuto()
  interval = setInterval(() => {
    const el = scrollContainer.value
    if (!el) return
    el.scrollBy({ left: 280, behavior: 'smooth' })
    const max = el.scrollWidth - el.clientWidth
    if (el.scrollLeft >= max - 5) el.scrollTo({ left: 0, behavior: 'smooth' })
  }, 3000)
}
const stopAuto = () => interval && clearInterval(interval)

onMounted(startAuto)
onUnmounted(stopAuto)

const scrollLeft  = () => scrollContainer.value?.scrollBy({ left: -300, behavior: 'smooth' })
const scrollRight = () => scrollContainer.value?.scrollBy({ left:  300, behavior: 'smooth' })

const hrefFor = (slug: string) => `/artistas/${slug}`
</script>

<template>
  <section class="py-20 bg-black text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-3xl font-bold mb-8 text-center">Artistas</h2>

      <div class="relative">
        <!-- Flecha izquierda -->
        <button
          @click="scrollLeft"
          class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-black/60 hover:bg-black text-white rounded-full p-2 transition"
          aria-label="Anterior"
        >‹</button>

        <!-- Carrusel -->
        <div
          ref="scrollContainer"
          class="flex gap-6 overflow-x-auto scrollbar-hide snap-x snap-mandatory scroll-smooth px-10"
          @mouseenter="stopAuto"
          @mouseleave="startAuto"
        >
          <a
            v-for="a in items"
            :key="a.id"
            :href="hrefFor(a.slug)"
            class="relative flex-none w-64 snap-start group overflow-hidden transition"
          >
            <!-- Fondo difuminado para rellenar -->
            <img
              :src="a.image || '/placeholder.webp'"
              :alt="a.name"
              class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-25 pointer-events-none select-none"
              aria-hidden="true"
              draggable="false"
              loading="lazy"
              decoding="async"
            />

            <!-- Card negra sin borde -->
            <div class="relative z-10 h-80 flex flex-col bg-black">
              <!-- Nombre arriba centrado -->
              <div class="px-6 pt-4 text-center">
                <h3 class="text-lg font-semibold truncate">{{ a.name }}</h3>
              </div>

              <!-- Imagen centrada -->
              <div class="flex-1 grid place-items-center px-4">
                <img
                  :src="a.image || '/placeholder.webp'"
                  :alt="a.name"
                  class="max-h-56 max-w-full w-auto h-auto object-contain transition-transform duration-500 group-hover:scale-105"
                  draggable="false"
                  loading="lazy"
                  decoding="async"
                />
              </div>

              <!-- Overlay sutil en hover -->
              <div
                class="pointer-events-none absolute inset-0 bg-white/0 group-hover:bg-white/5 transition-colors duration-300"
              />
              <div
                class="pointer-events-none absolute inset-x-0 bottom-0 translate-y-3 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transition duration-300"
              >
                <p class="mx-auto mb-4 w-fit rounded-full bg-white/10 px-4 py-1 text-sm">
                  Ver más
                </p>
              </div>
            </div>
          </a>
        </div>

        <!-- Flecha derecha -->
        <button
          @click="scrollRight"
          class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-black/60 hover:bg-black text-white rounded-full p-2 transition"
          aria-label="Siguiente"
        >›</button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>