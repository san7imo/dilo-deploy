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
      class="fixed right-0 top-0 z-50 h-dvh w-[88vw] sm:w-[26rem] bg-zinc-950 border-l border-white/10 shadow-2xl"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-center justify-between h-16 px-6 border-b border-white/10">
            <a href="/" class="inline-flex items-center gap-2">
      <img
        :src="logoBlanco"
        alt="Dilo Records"
        class="h-14 w-auto object-contain"
      />
      <span class="sr-only">Inicio</span>
    </a>
        <button
          @click="emit('close')"
          class="rounded-xl h-9 w-9 inline-flex items-center justify-center ring-1 ring-white/10 hover:ring-white/30"
          aria-label="Cerrar menú"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.7 2.88 18.29 9.17 12 2.88 5.71 4.29 4.29 10.59 10.6l6.3-6.3z"/>
          </svg>
        </button>
      </div>

      <nav class="px-6 py-6 space-y-1 text-lg">
        <Link href="/artistas" class="block rounded-lg px-4 py-3 hover:bg-white/5">Artistas</Link>
        <Link href="/eventos" class="block rounded-lg px-4 py-3 hover:bg-white/5">Eventos</Link>        
        <Link href="/tienda" class="block rounded-lg px-4 py-3 hover:bg-white/5">Tienda</Link>
        <Link href="/beats" class="block rounded-lg px-4 py-3 hover:bg-white/5">Beats</Link>
        <Link href="/editorial" class="block rounded-lg px-4 py-3 hover:bg-white/5">Editorial</Link>
        <Link href="/noticias" class="block rounded-lg px-4 py-3 hover:bg-white/5">Noticias</Link>
      </nav>

      <div class="mt-auto p-6 text-sm text-white/50">
        <p>© {{ new Date().getFullYear() }} Dilo Records. All rights reserved.</p>
      </div>
    </aside>
  </transition>
</template>
