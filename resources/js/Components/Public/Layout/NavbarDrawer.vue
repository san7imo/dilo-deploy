<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import logoBlanco from '@/Assets/Images/Logos/responsive-blanco.webp'


const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const lockedItems = ['Estudio', 'Editorial', 'Tienda', 'Noticias']
</script>

<template>
  <!-- Fondo oscuro (click para cerrar) -->
  <transition
    enter-active-class="transition-opacity duration-300"
    leave-active-class="transition-opacity duration-200"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="fixed inset-0 z-40 bg-black/60 backdrop-blur-[2px]"
      @click="emit('close')"
    />
  </transition>

  <!-- Drawer -->
  <transition
    enter-active-class="transform transition duration-300"
    leave-active-class="transform transition duration-200"
    enter-from-class="translate-x-full"
    leave-to-class="translate-x-full"
  >
    <aside
      v-if="open"
      class="fixed right-0 top-0 z-50 h-dvh w-[68vw] sm:w-[18rem] bg-black/60 backdrop-blur-sm border-l border-white/5 shadow-2xl"
      role="dialog"
      aria-modal="true"
    >
      <!-- close button top-right -->
      <button
        @click="emit('close')"
        class="absolute right-4 top-4 rounded-xl h-9 w-9 inline-flex items-center justify-center ring-1 ring-white/10 hover:ring-white/30 z-50"
        aria-label="Cerrar menú"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
          <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.7 2.88 18.29 9.17 12 2.88 5.71 4.29 4.29 10.59 10.6l6.3-6.3z"/>
        </svg>
      </button>

      <div class="h-full flex flex-col items-center justify-center gap-8 px-6">
        <a href="/" class="inline-flex flex-col items-center gap-3">
          <img
            :src="logoBlanco"
            alt="Dilo Records"
            class="h-20 w-auto object-contain"
          />
        </a>

        <nav class="w-full max-w-[14rem] text-center">
          <p class="mb-4 text-xs uppercase tracking-[0.25em] text-white/50">
            Menu Dilo Records
          </p>
          <ul class="space-y-3">
            <li><Link :href="route('public.artists.index')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Artistas</Link></li>
            <li><Link :href="route('public.songs.index')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Canciones</Link></li>
            <li><Link :href="route('public.releases.index')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Lanzamientos</Link></li>
            <li><Link :href="route('public.events.index')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Eventos</Link></li>
            <li v-for="item in lockedItems" :key="item">
              <span
                class="group relative flex cursor-not-allowed items-center justify-center gap-2 rounded-lg px-4 py-3 text-white/45"
                aria-disabled="true"
                tabindex="0"
              >
                <svg class="h-3.5 w-3.5 text-white/35" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 1 1 4 0v2h-4V7Zm3 9.73V18h-2v-1.27a2 2 0 1 1 2 0Z" />
                </svg>
                {{ item }}
                <span
                  class="pointer-events-none absolute left-1/2 top-full z-10 mt-2 -translate-x-1/2 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-black opacity-0 shadow-xl transition group-hover:opacity-100 group-focus:opacity-100"
                >
                  Próximamente
                </span>
              </span>
            </li>
          </ul>
        </nav>

        <div class="mt-6 text-sm text-white/50">
          <p>© {{ new Date().getFullYear() }} Dilo Records</p>
        </div>
      </div>
    </aside>
  </transition>
</template>
