<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import logoBlanco from '@/Assets/Images/Logos/responsive-blanco.webp'


const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ (e: 'close'): void }>()
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
            <li><Link :href="route('public.events.index')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Eventos</Link></li>
            <li><Link :href="route('public.studio')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Estudio</Link></li>
            <li><Link :href="route('public.editorial')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Editorial</Link></li>
            <li><Link :href="route('public.store')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Tienda</Link></li>
            <li><Link :href="route('public.news')" class="block rounded-lg px-4 py-3 hover:bg-white/5 text-white/90" @click="emit('close')">Noticias</Link></li>
          </ul>
        </nav>

        <div class="mt-6 text-sm text-white/50">
          <p>© {{ new Date().getFullYear() }} Dilo Records</p>
        </div>
      </div>
    </aside>
  </transition>
</template>
